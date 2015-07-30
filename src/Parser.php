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
 *
 * @return The node index of the exported file node, or -1 if there
 *         was an error.
 */
function parse_file( $path, $csvexporter) : int {

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
    $fnode = -1;
    error_log( "[ERROR] In $path: ".$e->getMessage());
  }

  return $fnode;
}

/**
 * Parses and generates ASTs for all PHP files buried within a
 * directory.
 *
 * @param $path        Path to the directory
 * @param $csvexporter A CSV exporter instance to use for exporting
 *                     the ASTs of all parsed files.
 * @param $top         Boolean indicating whether this call
 *                     corresponds to the top-level call of the
 *                     function. We wouldn't need this if I didn't
 *                     insist on the root directory of a project
 *                     getting node index 0. But, I do insist.
 *
 * @return If the directory corresponding to the function call finds
 *         itself interesting, it stores a directory node for itself
 *         and this function returns the index of that
 *         node. Otherwise, returns -1. A directory finds itself
 *         interesting if it contains PHP files, or if one of its
 *         child directories finds itself interesting. -- As a special
 *         case, the root directory of a project (corresponding to the
 *         top-level call) always finds itself interesting and always
 *         stores a directory node for itself.
 */
function parse_dir( $path, $csvexporter, $top = true) : int {

  // save any interesting directory/file indices in the current folder
  $found = [];
  // if the current folder finds itself interesting, we will create a
  // directory node for it and return its index
  if( $top)
    $dirnode = $csvexporter->store_dirnode( basename( $path));
  else
    $dirnode = -1;

  $dhandle = opendir( $path);

  while( false !== ($filename = readdir( $dhandle))) {
    $finfo = new SplFileInfo( build_path( $path, $filename));

    if( $finfo->isFile() && $finfo->isReadable() && strtolower( $finfo->getExtension()) === 'php')
      $found[] = parse_file( $finfo->getPathname(), $csvexporter);
    else if( $finfo->isDir() && $finfo->isReadable() && $filename !== '.' && $filename !== '..')
      if( -1 !== ($childdir = parse_dir( $finfo->getPathname(), $csvexporter, false)))
	$found[] = $childdir;
  }

  if( !empty( $found)) {
    if( !$top)
      $dirnode = $csvexporter->store_dirnode( basename( $path));
    foreach( $found as $i => $nodeindex)
      $csvexporter->store_rel( $dirnode, $nodeindex, "DIRECTORY_OF");
  }

  closedir( $dhandle);

  return $dirnode;
}

/**
 * Builds a file path with the appropriate directory separator.
 *
 * @param ...$segments Unlimited number of path segments.
 *
 * @return The file path built from the path segments.
 */
function build_path( ...$segments) {

  return join( DIRECTORY_SEPARATOR, $segments);
}

/*
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
*/

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

echo "Done.", PHP_EOL;
