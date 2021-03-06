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
// using AST_ENCAPS_LIST
function perform_query1( $link, $firstname, $lastname) {
  // formulate query
  $query = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='$firstname' AND lastname='$lastname'";
  
  echo "Query 1:", PHP_EOL, $query, PHP_EOL;

  // perform query
  $result = mysql_query($query, $link);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    $message .= 'Whole query: ' . $query . PHP_EOL;
    die( $message);
  }

  return $result;
}

// perform a query with user-supplied input (VULNERABLE!)
// using AST_BINARY_OP
function perform_query2( $link, $firstname, $lastname) {
  // formulate query
  $query2 = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='".$firstname."' AND lastname='".$lastname."'";
  
  echo "Query 2:", PHP_EOL, $query2, PHP_EOL;

  // perform query (let's use Postgres instead... this file won't run, but the AST doesn't mind)
  $result = pg_query($link, $query2);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    $message .= 'Whole query: ' . $query2 . PHP_EOL;
    die( $message);
  }

  return $result;
}

// perform a query with user-supplied input (VULNERABLE!)
// using AST_ASSIGN_OP
function perform_query3( $link, $firstname, $lastname) {
  // formulate query
  $query3 = "SELECT firstname, lastname, age FROM friends WHERE firstname='";
  $query3 .= $firstname;
  $query3 .= "' AND lastname='";
  $query3 .= $lastname;
  $query3 .= "'";
  
  echo "Query 3:", PHP_EOL, $query3, PHP_EOL;

  // perform query (let's use SQlite instead... this file won't run, but the AST doesn't mind)
  $result = sqlite_query($link, $query3);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    $message .= 'Whole query: ' . $query3 . PHP_EOL;
    die( $message);
  }

  return $result;
}

// perform a FIXED query -- no user input (NOT vulnerable!)
function perform_query4($link) {
  // formulate query
  $query4 = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='fred' AND lastname='fox'";
  
  echo "Query 4:", PHP_EOL, $query4, PHP_EOL;

  // perform query
  $result = mysql_query($query4, $link);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    $message .= 'Whole query: ' . $query4 . PHP_EOL;
    die( $message);
  }

  return $result;
}

// perform a query with user-supplied input (VULNERABLE!)
// using AST_ENCAPS_LIST
function perform_query5( $link, $firstname, $lastname) {

  echo "Query 5:", PHP_EOL;

  // perform query
  $result = sqlite_query("SELECT firstname, lastname, age FROM friends 
    WHERE firstname='$firstname' AND lastname='$lastname'", $link);
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    die( $message);
  }

  return $result;
}

// perform a query with user-supplied input (VULNERABLE!)
// using AST_BINARY_OP
function perform_query6( $link, $firstname, $lastname) {

  echo "Query 6:", PHP_EOL;

  // perform query
  $result = sqlite_query($link, "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='".$firstname."' AND lastname='".$lastname."'");
  
  // check result
  if( !$result) {
    $message  = 'Invalid query: ' . mysql_error() . PHP_EOL;
    die( $message);
  }

  return $result;
}



// MAIN
$link = connect( $user, $pwd, $server, $database);

// sample sql injection
// (say the arguments to perform_query() come from some unsanitized user input)
//$result = perform_query( "fred' UNION SELECT firstname, lastname, pwd from friends; #", "fox");

// Using perform_query1()
$result = perform_query1( $link, "fred", "fox");
echo "Result:", PHP_EOL;
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}
echo PHP_EOL;

// Using perform_query2()
$result = perform_query2( $link, "fred", "fox");
echo "Result:", PHP_EOL;
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}
echo PHP_EOL;

// Using perform_query3()
$result = perform_query3( $link, "fred", "fox");
echo "Result:", PHP_EOL;
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}
echo PHP_EOL;

// Using perform_query4()
$result = perform_query4($link);
echo "Result:", PHP_EOL;
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}
echo PHP_EOL;

// Using perform_query5()
$result = perform_query5($link, "fred", "fox");
echo "Result:", PHP_EOL;
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}
echo PHP_EOL;

// Using perform_query6()
$result = perform_query6($link, "fred", "fox");
echo "Result:", PHP_EOL;
while( $row = mysql_fetch_assoc( $result)) {
  echo $row['firstname'], "\t|\t",
    $row['lastname'], "\t|\t",
    $row['age'], PHP_EOL;
}

/* same things, but not enclosed in functions... */
$firstname = 'fred';
$lastname = 'fox';
// perform_query1
$topquery = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='$firstname' AND lastname='$lastname'";
$result = mysql_query($topquery, $link);
// perform_query2
$topquery2 = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='".$firstname."' AND lastname='".$lastname."'";
$result = pg_query($link, $topquery2);
// perform_query3
$topquery3 = "SELECT firstname, lastname, age FROM friends WHERE firstname='";
$topquery3 .= $firstname;
$topquery3 .= "' AND lastname='";
$topquery3 .= $lastname;
$topquery3 .= "'";
$result = sqlite_query($link, $topquery3);
// perform_query4
$topquery4 = "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='fred' AND lastname='fox'";
$result = mysql_query($topquery4, $link);
// perform_query5
$result = sqlite_query("SELECT firstname, lastname, age FROM friends 
    WHERE firstname='$firstname' AND lastname='$lastname'", $link);
// perform_query6
$result = sqlite_query($link, "SELECT firstname, lastname, age FROM friends 
    WHERE firstname='".$firstname."' AND lastname='".$lastname."'");




// free the resources associated with the result set
mysql_free_result( $result);

// close connection
mysql_close( $link);

