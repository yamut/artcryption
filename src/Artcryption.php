<?php
//echo ".." . $_SERVER[ 'DOCUMENT_ROOT' ] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once( ".." . $_SERVER[ 'DOCUMENT_ROOT' ] . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php' );
use Imagine\Gd\Imagine, Imagine\Image\Box, Imagine\Image\Palette\RGB, Imagine\Image\ImageInterface, Imagine\Image\Point;

class Artcryption {
	const DIRECTION = [ 'ENCODE' => 0, 'DECODE' => 1 ];
	protected $inFileName;
	protected $outFileName;
	protected $inFileHandle;
	protected $outFileHandle;
	protected $inBuffer;
	protected $outBuffer;
	protected $width = 0;
	protected $height = 0;
	protected $imageHandle;
	protected $fileSize = 0;
	protected $image;
	protected $direction;

	/**
	 * Artcryption constructor.
	 * @param string $inFileName
	 * @param string $outFileName
	 * @param int $direction
	 * @throws Exception
	 */
	public function __construct( string $inFileName, string $outFileName, int $direction = 0 ) {
		$this->setInFileName( $inFileName );
		$this->setOutFileName( $outFileName );
		if ( in_array( $direction, self::DIRECTION ) ) {
			$this->direction = $direction;
		} else {
			throw new Exception( "Invalid direction: " . $direction );
		}
		if ( file_exists( $inFileName ) ) {
			$this->fileSize = filesize( $inFileName );
		} else {
			$this->fileSize = 0;
		}
		$this->calculateXY();
		if ( $direction == self::DIRECTION[ 'ENCODE' ] ) {
			$this->inFileHandle = new SplFileObject( $this->inFileName, 'rb' );
			$this->outFileName = $outFileName;
			$this->createImageHandle();
			$this->executeEncode();
			$this->saveImage();
		} else if ( $direction == self::DIRECTION[ 'DECODE' ] ) {
			$this->inFileHandle = new Imagine();
			$this->image = $this->inFileHandle->open( $this->inFileName );
			$this->outFileHandle = new SplFileObject( $this->outFileName, 'w' );
			$size = $this->image->getSize();
			$this->image->usePalette( new RGB() );
			$this->width = $size->getWidth();
			$this->height = $size->getHeight();
			$this->executeDecode();
		}
	}

	/**
	 * Get the next byte from the input file if there is one
	 * @return bool|string
	 */
	protected function getNextByte() {
		if ( !$this->inFileHandle->eof() ) {
			$return = $this->inFileHandle->fread( 1 );
			echo bin2hex( $return ) . "\n";
			return $return;
		}
		return false;
	}

	protected function putNextByte( $byte ) {

	}

	/**
	 * Is the file at the end
	 * @return bool
	 */
	protected function isEof(): bool {
		return $this->inFileHandle->eof();
	}

	/**
	 * @return mixed
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * @param mixed $width
	 */
	public function setWidth( $width ) {
		$this->width = $width;
	}

	/**
	 * @return mixed
	 */
	public function getHeight() {
		return $this->height;
	}

	/**
	 * @param mixed $height
	 */
	public function setHeight( $height ) {
		$this->height = $height;
	}

	/**
	 * @return mixed
	 */
	public function getInFileName() {
		return $this->inFileName;
	}

	/**
	 * @param mixed $inFileName
	 */
	public function setInFileName( $inFileName ) {
		$this->inFileName = $inFileName;
	}

	/**
	 * @return mixed
	 */
	public function getOutFileName() {
		return $this->outFileName;
	}

	/**
	 * @param mixed $outFileName
	 */
	public function setOutFileName( $outFileName ) {
		$this->outFileName = $outFileName;
	}

	/**
	 * @throws Exception
	 */
	protected function createImageHandle() {
		if ( $this->width == 0 || $this->height == 0 ) {
			throw new Exception( "Image size is not valid: X: " . $this->width . " Y:" . $this->height );
		}
		$this->imageHandle = new Imagine();
		$this->image = $this->imageHandle->create( new Box( $this->width, $this->height ) );

	}

	protected function executeDecode() {
		$nibbles = [ 0 => null, 1 => null ];
		$alpha_packet = null;
		$time_to_write_alpha = false;
		for ( $h = 0; $h < $this->height; $h++ ) {
			for ( $w = 0; $w < $this->width; $w++ ) {
				/**
				 * @var $pixel \Imagine\Image\Palette\Color\RGB
				 */
				$pixel = $this->image->getColorAt( new Point( $w, $h ) );
				//$alpha = $pixel->getAlpha();

				$red = $pixel->getRed();
				$green = $pixel->getGreen();
				$blue = $pixel->getBlue();
				//die( var_dump( [ 'red' => $red, 'green' => $green, 'blue' => $blue ] ) );
				/*if ( $nibbles[ 0 ] == null && $nibbles[ 1 ] == null ) {
					$nibbles[ 0 ] = dechex( 50 - $alpha );
				} else if ( $nibbles[ 0 ] != null && $nibbles[ 1 ] == null ) {
					$nibbles[ 1 ] = dechex( 50 - $alpha );
					$alpha_packet = $nibbles[ 0 ] . $nibbles[ 1 ];
					$time_to_write_alpha = !$time_to_write_alpha;
					$nibbles = [ 0 => null, 1 => null ];
				}
				if ( $alpha_packet != null ) {
					//var_dump( $alpha_packet );
					//echo dechex( $alpha_packet ) . "\n";
				}*/
				//echo dechex( $red ) . "\n";
				//echo dechex( $green ) . "\n";
				//echo dechex( $blue ) . "\n";
				//var_dump(dechex($red),dechex($green),dechex($blue));
				$red = hex2bin( str_pad( dechex( $red ), 2, '0', STR_PAD_LEFT ) );
				$green = hex2bin( str_pad( dechex( $green ), 2, '0', STR_PAD_LEFT ) );
				$blue = hex2bin( str_pad( dechex( $blue ), 2, '0', STR_PAD_LEFT ) );

				if ( !$time_to_write_alpha || true ) {
					$this->outBuffer .= $red . $green . $blue;
				} else {
					//need to first write the nibbles, then continue
					$joined_byte = hex2bin( $alpha_packet );
					$alpha_packet = null;
					$this->outBuffer .= $joined_byte . $red . $green . $blue;
					$time_to_write_alpha = !$time_to_write_alpha;
				}
			}
		}
		$this->outFileHandle->fwrite( $this->outBuffer );
	}

	protected function executeEncode() {
		$nibbles = [ 0 => null, 1 => null ];
		for ( $h = 0; $h < $this->height; $h++ ) {
			for ( $w = 0; $w < $this->width; $w++ ) {
				//draw one pixel
				$bytes = [];
				for ( $b = 0; $b < 3; $b++ ) {
					if ( !$this->isEof() ) {
						$bytes[ $b ] = $this->getNextByte();
					} else {
						$bytes[ $b ] = 0;
					}
				}
				/*if ( !$this->isEof() ) {
					if ( $nibbles[ 0 ] == null && $nibbles[ 1 ] == null ) {
						//get two new nibbles
						$_byte = $this->getNextByte();
						$_byte = bin2hex( $_byte );
						//var_dump( $_byte );
						$nibbles = [ 0 => substr( $_byte, 0, 1 ), 1 => substr( $_byte, -1 ) ];
						var_dump( $nibbles );
						$alpha = $nibbles[ 0 ];
						$nibbles[ 0 ] = null;
					} else if ( $nibbles[ 0 ] == null ) {
						$alpha = $nibbles[ 1 ];
						$nibbles[ 1 ] = null;
					}
				} else {
					//adjust this for some scenarios
					$alpha = 0;
				}*/
				$this->image->draw()->dot( new Point( $w, $h ), $this->encodeToColor( $bytes[ 0 ], $bytes[ 1 ], $bytes[ 2 ], 100 ) );
			}
		}

	}

	protected function saveImage() {
		$this->image->save( $this->outFileName );
	}

	/**
	 * Create a color based on input bytes
	 * @param $byte1
	 * @param $byte2
	 * @param $byte3
	 * @param $byte4 - not a byte, actually a nibble, 0-15
	 * @return \Imagine\Image\Palette\Color\ColorInterface|mixed
	 */
	protected function encodeToColor( $byte1, $byte2, $byte3, $byte4 ) {
		//$byte4 is in fact a nibble
		$byte1 = hexdec( bin2hex( $byte1 ) );
		$byte2 = hexdec( bin2hex( $byte2 ) );
		$byte3 = hexdec( bin2hex( $byte3 ) );
		//echo "Hex: " . $byte4 . ", Dec: " . hexdec( $byte4 ) . "\n";
		$byte4 = 100;//50 - hexdec( $byte4 );
		//var_dump( $byte4 );

		$palette = new RGB();
		return $palette->color( [ $byte1, $byte2, $byte3 ], $byte4 );
	}

	protected function decodeFromColor( $color ) {

	}

	/**
	 * Create the stop pixel
	 * this may not be necessary if I can find a way to determine that input has stopped on the image
	 * @return \Imagine\Image\Palette\Color\ColorInterface
	 */
	protected function createStopColor() {
		$palette = new RGB();
		return $palette->color( [ 0, 0, 0 ], 100 );
	}

	protected function calculateXY() {
		if ( $this->fileSize == 0 ) {
			return;
		}
		//filesize+1, because if filesize/4%2==0 then there is no space for the terminator pixel
		//TODO-determine if I could drop the terminator pixel if the value of that equation is modulo 0
		$this->width = $this->height = ceil( sqrt( ( $this->fileSize + 1 ) / 3 ) );
	}

	protected function base64File(){
		$this->inBuffer='';
	}
}

/*
 * Notes:
 * If I run out of bytes mid color then I could set the alpha to something to say that it ran out of data, however;
 * in that case I'm thinking that if alpha==99(because 100=terminator bit) then if bit 1/2/3==0, ignore. That would
 * mean I could have up to two partially complete pixels.
 * This also causes an issue if the last byte is 0, because then it would be ignored.
 * In this case maybe an alpha value=98 means only consider byte1, not matter what it is
 * Could expand on this
 * if 97
 * 	byte1
 * if 98
 * 	byte1 and 2
 * if 99
 * 	all non zero bytes
 *
 * TODO-revisit this logic
 */