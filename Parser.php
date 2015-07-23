<?php

/**
 * This program looks for PHP files in a given directory and dumps ASTs into a
 * directory .php-joern/
 *
 * @author Malte Skoruppa <skoruppa@cs.uni-saarland.de>
 */

require 'csvexport.php'; // for ast_csv_export()
// require 'util.php'; // for ast_dump()

$path = NULL; // file/folder to be parsed
// $outdir = '.php-joern'; // output folder for analysis
$nodefile = 'nodes.csv';
$relfile = 'rels.csv';

/**
 * Parses the cli arguments.
 *
 * @return Boolean that indicates whether the given arguments are
 *         fine.
 */
function parse_arguments() {

  global $argv;
  
  if( !isset( $argv)) {
    if( false === (boolean) ini_get( 'register_argc_argv')) {
      echo 'Error: Please enable register_argc_argv in your php.ini.';
    }
    else {
      echo 'Error: No $argv array available.';
    }
    echo PHP_EOL;
    return false;
  }

  // Remove the script name
  array_shift( $argv);
  
  if( count( $argv) !== 1) {
    echo 'Error: Please specify exactly one path to be parsed.', PHP_EOL;
    return false;
  }

  global $path;
  $path = (string) array_pop( $argv);
  
  return true;
}

/**
 * Prints a help message.
 */
function print_help() {

  // TODO read version string from somewhere...
  echo 'php-joern parser utility 0.0.1', PHP_EOL, PHP_EOL;
  echo 'Usage: php Parser.php <file|folder>', PHP_EOL;
}

/**
 * Searches for all files with extension .php in a given folder and
 * returns their paths in an array.
 *
 * @param path Folder to search.
 *
 * @return Array of paths corresponding to the .php files within the
 *         given folder.
 */
function find_php_files( $path) {

  $sources = [];

  $di = new RecursiveDirectoryIterator( $path);
  foreach( new RecursiveIteratorIterator( $di) as $filename => $file) {
    if( $file->isFile() && $file->isReadable() && false !== strpos( $filename, '.php', strlen($filename)-strlen('.php')))
      $sources[] = $filename;
  }

  return $sources;
}

/*
 * Main script
 */
if( parse_arguments() === false) {
  print_help();
  exit( 1);
}

// Check that source exists and is readable
if( !file_exists( $path) || !is_readable( $path)) {

  echo 'Error: The given path does not exist or cannot be read.', PHP_EOL;
  exit( 1);
}

// Determine whether source is a file or a directory
if( is_file( $path)) {
  if( false !== strrpos( $path, DIRECTORY_SEPARATOR)) {
    $prefix = substr( $path, 0, strrpos( $path, DIRECTORY_SEPARATOR) + 1);
  }
  else {
    $prefix = '';
  }
  $sources = [$path];
}
elseif( is_dir( $path)) {
  $prefix = $path;
  // let's boldly assume that strlen(DIRECTORY_SEPARATOR) is 1 :p
  if( substr( $prefix, -1) !== DIRECTORY_SEPARATOR)
    $prefix .= DIRECTORY_SEPARATOR;
  $sources = find_php_files( $path);
}
else {
  echo 'Error: The given path is neither a regular file nor a directory.', PHP_EOL;
  exit( 1);
}

// let's save all the errors for now -- use stderr later
$ERRORS == '';

// not needed any longer -- we save everything to only two files
/*
if( mkdir( $outdir, 0755)) {
  echo "Creating folder $outdir", PHP_EOL;
}
else {
  echo "Warning: Folder $outdir already exists, files may get overwritten.", PHP_EOL;
}
*/

$csvexporter = new CSVExporter( $nodefile, $relfile);

foreach( $sources as $source) {

  $ast = null; // important: empty this at iteration start (in case an exception is thrown by ast\parse_file)

  $filepath = substr( $source, strlen( $prefix));
  echo 'Parsing file ', $filepath, PHP_EOL;

  try {
    $ast = ast\parse_file( $source);
  }
  catch( Error $e) {
    $ERRORS .= "In $source: ".$e->getMessage()."\n";
  }

  // mkdir( $outdir.DIRECTORY_SEPARATOR.$filepath, 0755, true);

  $csvexporter->export( $ast);
  //echo ast_dump( $ast), PHP_EOL;
  //file_put_contents( $outdir.DIRECTORY_SEPARATOR.$filepath.DIRECTORY_SEPARATOR.'ast.dump', ast_dump( $ast, AST_DUMP_LINENOS));
  //file_put_contents( $outdir.DIRECTORY_SEPARATOR.$filepath.DIRECTORY_SEPARATOR.'ast.dump', var_dump( $ast));
}

//$csvexporter->__destruct();

//if( !is_empty( $ERRORS)) {
  echo "Errors: \n";
  echo $ERRORS;
//}
