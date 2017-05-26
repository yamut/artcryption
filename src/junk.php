<?php

$foo = "a";

$b = ord( $foo );

var_dump( $b );

$c = str_pad( decbin( ord($foo) ), 8, 0, STR_PAD_LEFT );

$n1 = substr( $c, 0, 4 );
$n2 = substr( $c, -4 );

var_dump( $n1, $n2 );

var_dump( $c );

$p = chr( bindec( $c ) );

var_dump( $p );