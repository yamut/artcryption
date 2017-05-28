<?php

require_once( ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . 'Artcryption.php' );

$a = new Artcryption( 'cat.jpg', 'cat.jpg.encoded.png', Artcryption::ENCODE, 'storage' . DIRECTORY_SEPARATOR );
$b = new Artcryption( 'cat.jpg.encoded.png', 'cat.jpg.decoded.jpg', Artcryption::DECODE, 'storage' . DIRECTORY_SEPARATOR );
unset( $a, $b );
$a = new Artcryption( 'foo.txt', 'foo.txt.png', Artcryption::ENCODE, 'storage' . DIRECTORY_SEPARATOR );
$b = new Artcryption( 'foo.txt.png', 'foo.txt.decoded', Artcryption::DECODE, 'storage' . DIRECTORY_SEPARATOR );

