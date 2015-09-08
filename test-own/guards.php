<?php

if( 2 == 3) {
  echo "there is a glitch in the matrix!", PHP_EOL;
}

$i = 0;
while( $i <= 10) {
  echo "i: ", $i, PHP_EOL;
  $i++;
}

for( $j = 1; $j <= 8; $j++) {
  echo "j: ", $j, PHP_EOL;
}

foreach( [1,2,3] as $k) {
  echo "k: ", $k, PHP_EOL;
}
