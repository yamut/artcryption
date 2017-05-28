<?php
require_once( ".." . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
use Imagine\Gd\Imagine, Imagine\Image\Box, Imagine\Image\Palette\RGB, Imagine\Image\ImageInterface, Imagine\Image\Point;

ini_set( 'memory_limit', -1 );
ini_set( 'max_execution_time', -1 );

class Artcryption {

	const DECODE = 0;
	const ENCODE = 1;
	const DECODE_ARG = ' --decode ';
	/**
	 * This filename needs to be stripped of anything dangerous before coming in
	 * Only alphanumeric chars, _, and . allowed
	 * @var string
	 */
	protected $inFileName;
	protected $outFileName;
	protected $inBuffer;
	protected $outBuffer;
	protected $outTempFile;
	/**
	 * @var SplFileObject
	 */
	protected $inFileHandle;

	/**
	 * @var Imagine
	 */
	protected $inImageHandle;

	/**
	 * @var SplFileObject
	 */
	protected $outFileHandle;

	/**
	 * @var Imagine
	 */
	protected $outImageHandle;

	/**
	 * @var ImageInterface
	 */
	protected $image;

	/**
	 * @var array
	 */
	protected $masterMap = [];

	/**
	 * @var array
	 */
	protected $customMap = [];

	/**
	 * @var bool
	 */
	protected $applyCustomMap = false;

	/**
	 * @var string
	 */
	protected $storageLocation;

	/**
	 * @var int
	 */
	protected $sideLength;

	/**
	 * @var int
	 */
	protected $direction;

	public function __construct( string $inFileName, string $outFileName, int $direction = self::ENCODE, string $storageLocation = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR ) {
		$this->inFileName      = $inFileName;
		$this->outFileName     = $outFileName;
		$this->storageLocation = $storageLocation;
		$this->direction       = $direction;
		switch ( $this->direction ) {
			case self::ENCODE:
				$this->executeEncodeFile( $this->inFileName );
				break;
			case self::DECODE:
				$this->executeDecodeFile();
				break;
		}
	}

	/**
	 * @param string $fileName
	 * @param string $mode
	 * @param string $isBinary - should not be needed in this context
	 *
	 * @return SplFileObject
	 */
	protected function openFile( string $fileName, string $mode = 'r', $isBinary = '' ): SplFileObject {
		return new SplFileObject( $fileName, $mode . $isBinary );
	}

	/**
	 * Get the size of the file in the handle
	 *
	 * @param SplFileObject $handle
	 *
	 * @return int
	 */
	protected function getFileSize( SplFileObject $handle ): int {
		return $handle->getSize();
	}

	/**
	 * return the size of one side of the square
	 *
	 * @param int $fileSize
	 * @param int $basis - number of data points to be used per pixel
	 *
	 * @return int
	 */
	protected function calculateSideLength( int $fileSize, int $basis = 3 ): int {
		return ceil( sqrt( ceil( $fileSize / $basis ) ) );
	}


	public function executeEncodeFile( $filename ) {
		//Final checks
		if ( !file_exists( $this->storageLocation . $filename ) || !is_readable( $this->storageLocation . $filename ) ) {
			throw new Exception( "File does not exist or is not readable." );
		}

		$new_filename       = $this->externalBase64File( $filename );
		$this->inFileHandle = $this->openFile( $new_filename );
		$this->sideLength   = $this->calculateSideLength( $this->getFileSize( $this->inFileHandle ) );

		$this->outImageHandle = new Imagine();
		$this->image          = $this->outImageHandle->create( new Box( $this->sideLength, $this->sideLength ) );
		$this->executePixelLoop( $this->sideLength );
		$this->image->save( $this->storageLocation . $this->outFileName );

		//As expected, converting to webp drops the file size
		//Interestingly, converting back to png does not lose any color data but does compress the file further
		//Even more interestingly, this reduces the file size of the encoded file to less than the original file (Very few test cases to support this)
		//Requires the cwebp and dwebp commands to be available on linux. In ubuntu, apt-get install webp will get what you need
		//Without lossless, the jpg problem persists
		// It appears this reduction in size may be due to it converting from 32 bit color to 24 bit color

		//@TODO: would like to know how it is possible to take a binary file, represent it as 8/6th of its bytes, and end up with a smaller size. Image compression magic?

		//On further research this is a library 'bug?' that causes it. It always saves the alpha channel for a png, even if you don't use it
		//and there is no way to disable that. Thinking the below solution, while messy, will work in the short term.

		//If possible it may be worth storing a bit in alpha, but this library forces the alpha to be an int representation of a percentage
		//between 0 and 100. Based on the PNG specification I think it may actually be stored in a full byte which would enable this and further decrease
		//the end file size, by 1/4th the size of the output of the base64 output(4 per px rather than 3 per px)

		//`cwebp -lossless -q 100 $this->storageLocation$this->outFileName -o $this->storageLocation$this->outFileName.webp`;
		//`dwebp $this->storageLocation$this->outFileName.webp -o $this->storageLocation$this->outFileName.webp.png`;
	}

	protected function executePixelLoop( $size ) {
		for ( $h = 0; $h < $size; $h++ ) {
			for ( $w = 0; $w < $size; $w++ ) {
				if ( $this->direction === self::ENCODE ) {
					$to_pick     = 3;
					$color_array = [];
					//@TODO: determine whether to use alpha, if so refactor this
					for ( $i = 0; $i < $to_pick; $i++ ) {
						$color_array[ $i ] = ( !$this->inFileHandle->eof() ) ? ord( $this->inFileHandle->fread( 1 ) ) : 0;
					}
					$color = new \Imagine\Image\Palette\Color\RGB( new RGB(), $color_array, 100 );
					//$color->color( $color_array );
					$this->image->draw()->dot( new Point( $h, $w ), $color );
				} else if ( $this->direction === self::DECODE ) {

					/**
					 * @var $color \Imagine\Image\Palette\Color\RGB
					 */
					$color = $this->image->getColorAt( new Point( $h, $w ) );
					$red   = $color->getRed();
					$green = $color->getGreen();
					$blue  = $color->getBlue();
					//Test for termination
					if ( $red === 0 ) {
						return;
					} else if ( $green === 0 ) {
						$this->outBuffer .= chr( $red );
						return;
					} else if ( $blue === 0 ) {
						$this->outBuffer .= chr( $red ) . chr( $green );
						return;
					} else {
						$this->outBuffer .= chr( $red ) . chr( $green ) . chr( $blue );
					}
				} else {
					throw new Exception( "No valid direction given for loop in " . __CLASS__ . "::" . __METHOD__ . " on line " . __LINE__ );
				}
			}
		}
	}

	protected function executeDecodeFile() {
		if ( !file_exists( $this->storageLocation . $this->inFileName ) || !is_readable( $this->storageLocation . $this->inFileName ) ) {
			throw new Exception( "Unable to access or read file in " . __CLASS__ . "::" . __METHOD__ . " on line " . __LINE__ );
		}
		$this->inImageHandle = new Imagine();
		$this->outTempFile   = uniqid( "", true );
		$this->outFileHandle = $this->openFile( $this->storageLocation . $this->outTempFile, 'w' );
		$this->image         = $this->inImageHandle->open( $this->storageLocation . $this->inFileName );
		$this->executePixelLoop( $this->image->getSize()->getHeight() );

		$this->outFileHandle->fwrite( $this->outBuffer );
		$this->externalBase64File( $this->outTempFile, self::DECODE_ARG );
	}

	protected function generateMasterMap() {
		$this->masterMap = array_merge( range( 'a', 'z' ), range( 'A', 'Z' ), array_walk( range( 0, 9 ), function ( &$value ) {
			$value = strval( $value );
		} ) );
	}

	/**
	 * @param $name - can contain alphanumeric chars, _, and .
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function validateFileName( $name ): bool {
		$match = preg_match( '/[^A-Za-z0-9_\.-]+/', $name );
		if ( $match === false ) {
			throw new Exception( "Error processing regular expression. Input filename is '$name'" );
		}
		return !(bool)$match;
	}

	/**
	 * @param        $filename
	 * @param string $direction empty or self::DECODE_ARG
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function externalBase64File( $filename, $direction = '' ) {
		if ( !$this->validateFileName( $filename ) ) {
			throw new Exception( "Filename is invalid. Name is '$filename'" );
		}
		$inFilename = $this->storageLocation . $filename;
		$nowrap     = '';
		if ( $this->direction === self::ENCODE ) {
			$outFilename = $this->storageLocation . $filename . '.64';
			$nowrap      = "-w 0";
		} else {
			$outFilename = $this->storageLocation . $this->outFileName;
		}
		echo "base64 $nowrap $direction $inFilename > $outFilename\n";

		`base64 $nowrap $direction $inFilename > $outFilename`;
		return $outFilename;
	}


}
/**
 * As execpted large files are a huge failure.
 * @TODO: improve large file processing. Initial thoughts, chunk images? Would need sequencing to work
 * @TODO: look into using the bare php api instead of imagine, could save the conversion step to get to 24bit?
 *
 */