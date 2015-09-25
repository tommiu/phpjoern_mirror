<?php

// dangerous if allow_url_include=1 in php.ini
include 'http://localhost/~malte/pwned.php.txt';

// dangerous if allow_url_include=1 in php.ini
include 'https://localhost/~malte/pwned.php.txt';

// dangerous if allow_url_include=1 in php.ini
include 'HtTpS://localhost/~malte/pwned.php.txt';

// or we could even include some (possibly poisoned) variable
$loc = 'http://localhost/~malte/pwned.php.txt';
include $loc;

// this is fine
include 'some_local_file.php';

// this may not be
eval($something);

// this should be
eval('constant string');
