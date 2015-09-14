<?php

// settings
$user = 'testuser';
$pwd = 'testpw';
$server = 'localhost';
$database = 'testdb';

function connect( $user, $pwd, $server, $database) {
  // establish connection to server
  $link = mysql_connect($server, $user, $pwd);
  if( !$link)
    die( 'Could not connect: ' . mysql_error() . "\n");
  echo 'Connected successfully', PHP_EOL;

  // connect to database
  if( !mysql_select_db( $database, $link))
    die( "Cannot connect to $database : " . mysql_error() . "\n");

  return $link;
}

// perform a query with user-supplied input (VULNERABLE!)
function perform_query( $firstname, $lastname) {
  // formulate query
  $query = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='$firstname' AND lastname='$lastname'";
  
  echo "Whole query:", PHP_EOL, $query, PHP_EOL;

  // perform query
  $result = mysql_query($query);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    $message .= 'Whole query: ' . $query . PHP_EOL;
    die( $message);
  }

  return $result;
}

// perform a query with user-supplied input (VULNERABLE!)
function perform_otherquery( $firstname) {
  // formulate query
  $otherquery = "SELECT firstname FROM friends 
    WHERE firstname='$firstname'";
  
  echo "Whole query:", PHP_EOL, $otherquery, PHP_EOL;

  // perform query
  $result = mysql_query($otherquery);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    $message .= 'Whole query: ' . $otherquery . PHP_EOL;
    die( $message);
  }

  return $result;
}


// MAIN
$link = connect( $user, $pwd, $server, $database);
// sample sql injection
// (say the arguments to perform_query() come from some unsanitized user input)
$result = perform_query( "fred' UNION SELECT firstname, lastname, pwd from friends; #", "fox");
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}

// free the resources associated with the result set
mysql_free_result( $result);

// close connection
mysql_close( $link);

