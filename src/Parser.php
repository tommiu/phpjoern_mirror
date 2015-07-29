<?php

/**
 * This program looks for PHP files in a given directory and dumps ASTs.
 *
 * @author Malte Skoruppa <skoruppa@cs.uni-saarland.de>
 */

require 'CSVExporter.php'; // for ast_csv_export()
// require 'util.php'; // for ast_dump()

$path = NULL; // file/folder to be parsed
$mode = CSVExporter::NEO4J_MODE; // mode to use for export

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
      error_log( '[ERROR] Please enable register_argc_argv in your php.ini.');
    }
    else {
      error_log( '[ERROR] No $argv array available.');
    }
    echo PHP_EOL;
    return false;
  }

  // Remove the script name (first argument)
  array_shift( $argv);

  if( count( $argv) === 0) {
    error_log( '[ERROR] Missing argument.');
    return false;
  }

  // Set the path and remove from command line (last argument)
  global $path;
  $path = (string) array_pop( $argv);

  // Now see if a mode has been set
  global $mode;
  $options = getopt( "m:");
  if( $options === FALSE)
    error_log( '[ERROR] Could not parse command line arguments.');
  else if( isset( $options['m'])) {
    switch( $options['m']) {
    case "jexp":
      $mode = CSVExporter::JEXP_MODE;
      break;
    case "neo4j":
      $mode = CSVExporter::NEO4J_MODE;
      break;
    default:
      error_log( "[WARNING] Unknown mode '{$options['m']}', using neo4j mode.");
      $mode = CSVExporter::NEO4J_MODE;
      break;
    }
  }
 
  return true;
}

/**
 * Prints a help message.
 */
function print_help() {

  // TODO read script name and version string from somewhere...
  echo 'php-joern parser utility 0.0.1', PHP_EOL, PHP_EOL;
  echo 'Usage: ./parser [-m <neo4j|jexp>] <file|folder>', PHP_EOL;
}

/**
 * Parses and generates an AST for a single file.
 *
 * @param $path        Path to the file
 * @param $cvsexporter A CSV exporter instance to use for exporting
 *                     the AST of the parsed file.
 */
function parse_file( $path, $csvexporter) {

  $finfo = new SplFileInfo( $path);
  echo "Parsing file ", $finfo->getFilename(), PHP_EOL;

  try {
    $ast = ast\parse_file( $path);

    // The above may throw a ParseError. We only export to CSV if that
    // didn't happen.
    $fnode = $csvexporter->store_filenode( $finfo->getFilename());
    $astroot = $csvexporter->export( $ast);
    $csvexporter->store_rel( $fnode, $astroot, "FILE_OF");
    //echo ast_dump( $ast), PHP_EOL;
  }
  catch( ParseError $e) {
    error_log( "[ERROR] In $path: ".$e->getMessage());
  }
}

/**
 * Parses and generates ASTs for all PHP files buried within a directory.
 *
 * @param $path        Path to the directory
 * @param $cvsexporter A CSV exporter instance to use for exporting
 *                     the ASTs of all parsed files.
 */
function parse_dir( $path, $csvexporter) {

  $di = new RecursiveDirectoryIterator( $path);
  $ii = new RecursiveIteratorIterator( $di);
  $ri = new RegexIterator( $ii, '/^.+\.php$/i');

  foreach( $ri as $path => $file) {

    if( $file->isFile() && $file->isReadable()) {

      // TODO: parse_file() will store File nodes and ASTs, great!
      // However, we should *somehow* also reflect the directory
      // structure, i.e., create Directory nodes here, but only for
      // such directories which (recursively) contain .php files, and
      // we do not want to create any Directory node twice. This will
      // need a little bit of careful thinking.
      parse_file( $path, $csvexporter);
    }
  }
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
  error_log( '[ERROR] The given path does not exist or cannot be read.');
  exit( 1);
}

// Determine whether source is a file or a directory
if( is_file( $path)) {
  $csvexporter = new CSVExporter( $mode);
  parse_file( $path, $csvexporter);
  $csvexporter->__destruct();
}
elseif( is_dir( $path)) {
  $csvexporter = new CSVExporter( $mode);
  parse_dir( $path, $csvexporter);
  $csvexporter->__destruct();
}
else {
  error_log( '[ERROR] The given path is neither a regular file nor a directory.');
  exit( 1);
}
