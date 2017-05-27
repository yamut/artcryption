<?php

require_once( ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . 'Artcryption.php' );

$a = new Artcryption( 'cat . jpg', 'cat . jpg . encoded . png', Artcryption::ENCODE, '' );
$b = new Artcryption( 'cat . jpg . encoded . png', 'cat . jpg . decoded . jpg', Artcryption::DECODE, '' );

$a = new Artcryption( 'foo . txt', 'foo . txt . png', Artcryption::ENCODE, '' );
$b = new Artcryption( 'foo . txt . png', 'foo . txt . decoded', Artcryption::DECODE, '' );

