<?php


$test1_in  = $argv[ 1 ];
$test1_out = $argv[ 2 ];
$test2_in  = $argv[ 3 ];
$test2_out = $argv[ 4 ];

require_once( ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . 'Artcryption.php' );

$a = new Artcryption( $test1_in, $test1_in . '.encoded.png', Artcryption::ENCODE, 'storage' . DIRECTORY_SEPARATOR );
$b = new Artcryption( $test1_in . '.encoded.png', $test1_out, Artcryption::DECODE, 'storage' . DIRECTORY_SEPARATOR );
unset( $a, $b );
$a = new Artcryption( $test2_in, $test2_in . '.png', Artcryption::ENCODE, 'storage' . DIRECTORY_SEPARATOR );
$b = new Artcryption( $test2_in . '.png', $test2_out, Artcryption::DECODE, 'storage' . DIRECTORY_SEPARATOR );

