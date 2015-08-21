<?php declare( strict_types = 1);

// report on errors, except notices
error_reporting( E_ALL & ~E_NOTICE);

/**
 * This program looks for PHP files in a given directory and dumps ASTs.
 *
 * @author Malte Skoruppa <skoruppa@cs.uni-saarland.de>
 */

require 'CSVExporter.php';

/** Constant for "complete" mode */
const COMPLETE_MODE = 0;
/** Constant for "functions" mode */
const FUNCTIONS_MODE = 1;

/** Default name of output directory when in "functions" mode */
const OUTPUT_DIR = "phpjoern.astdump";

$path = null; // file/folder to be parsed
$mode = COMPLETE_MODE; // parsing mode
$format = CSVExporter::NEO4J_FORMAT; // format to use for export
$nodefile = CSVExporter::NODE_FILE; // name of node file when in "complete" mode
$relfile = CSVExporter::REL_FILE; // name of relationship file when in "complete" mode
$outdir = OUTPUT_DIR; // name of output directory for ASTs when in "functions" mode
$scriptname = null; // this script's name

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
  global $scriptname;
  $scriptname = array_shift( $argv);

  if( count( $argv) === 0) {
    error_log( '[ERROR] Missing argument.');
    return false;
  }

  // Set the path and remove from command line (last argument)
  global $path;
  $path = (string) array_pop( $argv);

  // Parse options
  $longopts  = ["help", "version", "mode:", "format:", "nodes:", "relationships:", "directory:"];
  $options = getopt( "hvm:f:n:r:d:", $longopts);
  if( $options === FALSE) {
    error_log( '[ERROR] Could not parse command line arguments.');
    return false;
  }

  // Help?
  if( isset( $options['help']) || isset( $options['h'])) {
    print_version();
    echo PHP_EOL;
    print_usage();
    echo PHP_EOL;
    print_help();
    exit( 0);
  }

  // Version?
  if( isset( $options['version']) || isset( $options['v'])) {
    print_version();
    exit( 0);
  }

  // Mode?
  if( isset( $options['mode']) || isset( $options['m'])) {
    global $mode;
    switch( $options['mode'] ?? $options['m']) {
    case "complete":
      $mode = COMPLETE_MODE;
      break;
    case "functions":
      $mode = FUNCTIONS_MODE;
      break;
    default:
      error_log( "[WARNING] Unknown mode '{$options['m']}', using complete mode.");
      $mode = COMPLETE_MODE;
      break;
    }
  }

  // Format?
  if( isset( $options['format']) || isset( $options['f'])) {
    global $format;
    switch( $options['format'] ?? $options['f']) {
    case "jexp":
      $format = CSVExporter::JEXP_FORMAT;
      break;
    case "neo4j":
      $format = CSVExporter::NEO4J_FORMAT;
      break;
    default:
      error_log( "[WARNING] Unknown format '{$options['f']}', using neo4j format.");
      $format = CSVExporter::NEO4J_FORMAT;
      break;
    }
  }

  // Nodes file?
  if( isset( $options['nodes']) || isset( $options['n'])) {
    global $nodefile;
    $nodefile = $options['nodes'] ?? $options['n'];
  }

  // Relationships file?
  if( isset( $options['relationships']) || isset( $options['r'])) {
    global $relfile;
    $relfile = $options['relationships'] ?? $options['r'];
  }

  // Output directory?
  if( isset( $options['directory']) || isset( $options['d'])) {
    global $outdir;
    $outdir = $options['directory'] ?? $options['d'];
  }

  return true;
}

/**
 * Prints a version message.
 */
function print_version() {

  $version = 'UNKNOWN';

  // Note: Only works on Unix :-p
  if( file_exists( ".git/HEAD"))
    if( preg_match( '/^ref: (.+)$/', file_get_contents( ".git/HEAD"), $matches))
      if( file_exists( ".git/{$matches[1]}"))
	$version = substr( file_get_contents( ".git/{$matches[1]}"), 0, 7);

  echo "PHPJoern parser utility, commit {$version}", PHP_EOL;
}

/**
 * Prints a usage message.
 */
function print_usage() {

  global $scriptname;
  echo 'Usage: php '.$scriptname.' [options] <file|folder>', PHP_EOL;
}

/**
 * Prints a help message.
 */
function print_help() {

  echo 'Options:', PHP_EOL;
  echo '  -h, --help                 Display help message', PHP_EOL;
  echo '  -v, --version              Display version information', PHP_EOL;
  echo '  -m, --mode <mode>          Parsing mode: either generate a single AST for a complete project or file', PHP_EOL;
  echo '                             ("complete", default), or one AST per function/method ("functions")', PHP_EOL;
  echo '  -f, --format <format>      Format to use for the CSV files: either "neo4j" (default) or "jexp"', PHP_EOL;
  echo '  -n, --nodes <file>         Output file for nodes when in "complete" mode', PHP_EOL;
  echo '  -r, --relationships <file> Output file for relationships when in "complete" mode', PHP_EOL;
  echo '  -d, --directory <dir>      Output directory for node/relationship files when in "functions" mode,', PHP_EOL;
  echo '                             defaults to "'.OUTPUT_DIR.'"', PHP_EOL;
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
  echo "Parsing file ", $finfo->getPathname(), PHP_EOL;

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
  $dirnode = $top ? $csvexporter->store_dirnode( basename( $path)) : -1;

  $dhandle = opendir( $path);

  // iterate over everything in the current folder
  while( false !== ($filename = readdir( $dhandle))) {
    $finfo = new SplFileInfo( build_path( $path, $filename));

    if( $finfo->isFile() && $finfo->isReadable() && strtolower( $finfo->getExtension()) === 'php')
      $found[] = parse_file( $finfo->getPathname(), $csvexporter);
    else if( $finfo->isDir() && $finfo->isReadable() && $filename !== '.' && $filename !== '..')
      if( -1 !== ($childdir = parse_dir( $finfo->getPathname(), $csvexporter, false)))
	$found[] = $childdir;
  }

  // if the current folder finds itself interesting...
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
 * Parses and generates ASTs for all PHP files buried within a
 * directory, creating a pair of nodes/relationships files for each
 * found function/method while reflecting the directory structure of
 * the directory to parse.
 *
 * @param $path   Path to the directory
 * @param $outdir Path to the output directory for the CSV files
 *                representing the functions/methods
 */
function parse_dir_functions( $path, $outdir) {

  /*
    TODO:
    - traverse directory, similar as in parse_dir()
    - but this time, reflect directory structure by creating
      corresponding directories instead of creating directory/file
      nodes within a CSV file
    - for each found file, call parse_file_functions() with a new
      $outdir, namely, reflecting the original directory structure but
      in $outdir
   */

  error_log( "The 'functions' mode is not yet implemented for directories.");
  error_log( "Fatal error. Aborting.");
  exit( 1);
}

/**
 * Parses and generates ASTs for each function/method of a single file.
 *
 * @param $path   Path to the file
 * @param $outdir Path to the output directory for the CSV files
 *                representing the functions/methods
 */
function parse_file_functions( $path, $outdir) {

  $finfo = new SplFileInfo( $path);
  echo "Parsing file ", $finfo->getPathname(), PHP_EOL;

  try {
    $ast = ast\parse_file( $path);

    // The above may throw a ParseError. We only export to CSV if that
    // didn't happen.
    export_functions( $ast, $outdir, $finfo->getBasename());
    //echo ast_dump( $ast), PHP_EOL;
  }
  catch( ParseError $e) {
    error_log( "[ERROR] In $path: ".$e->getMessage());
  }
}

/**
 * Traverses an AST, and exports each function node it finds into a
 * different pair of node/relationship files in $outdir. For each
 * function, the corresponding pair of files will be saved in $outdir
 * and they will be named
 * {nodes,rels}_$filename{_class_$classname_method || _function}_$functionname.csv
 *
 * @param $ast       The AST to traverse
 * @param $outdir    Path to the output directory for the CSV files
 * @param $filename  The filename of the file that the AST was built from
 * @param $nodecount As we create running node indices throughout all
 *                   created node files, this variable keeps track of the
 *                   next index to create.
 * @param $classname When recursing in this function, and the current
 *                   traversal is below a class node, the name of that class
 *
 * @return The possibly nodecount after the recursive call.
 */
function export_functions( $ast, $outdir, $filename, $nodecount = 0, $classname = null) : int {

  if( $ast instanceof ast\Node) {

    $nodetype = ast\get_kind_name( $ast->kind);

    // TODO do something about closures too? i.e., AST_CLOSURE nodes
    if( $nodetype === "AST_FUNC_DECL" || $nodetype === "AST_METHOD") {

      if( $nodetype === "AST_FUNC_DECL") {
	$nodefile = build_path( $outdir, "nodes_".$filename."_function_".$ast->name.".csv");
	$relfile = build_path( $outdir, "rels_".$filename."_function_".$ast->name.".csv");
      }
      elseif( $nodetype === "AST_METHOD") {
	$nodefile = build_path( $outdir, "nodes_".$filename."_class_".$classname."_method_".$ast->name.".csv");
	$relfile = build_path( $outdir, "rels_".$filename."_class_".$classname."_method_".$ast->name.".csv");
      }

      $csvexporter = null;
      try {
	global $format;
	$csvexporter = new CSVExporter( $format, $nodefile, $relfile, $nodecount);
	$csvexporter->export( $ast);
	$nodecount = $csvexporter->getNodeCount();
      }
      catch( IOError $e) {
	error_log( "[ERROR] ".$e->getMessage());
	exit( 1);
      }
    }

    // For other types of nodes, we just don't do anything at all,
    // except for recursing and continuing our search.  Such nodes
    // include, for example, AST_STMT_LIST which are normally at the
    // very root of an AST parsed from a file, but also, for example,
    // any top-level statements under it.
    else {
      // if we're looking at a class, save the name for recursing
      if( $nodetype === "AST_CLASS")
	$classname = $ast->name;

      foreach( $ast->children as $i => $child) {
	$nodecount = export_functions( $child, $outdir, $filename, $nodecount, $classname);
      }
    }
  }
  // For non-nodes (i.e., not instances of ast\Node), we don't do anything.
  // Such nodes include children of non-function/method nodes, for example,
  // the name of a global variable within a top-level variable declaration/assignment,
  // represented as a string.

  return $nodecount;
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
 * Main script
 */
if( parse_arguments() === false) {
  print_usage();
  echo PHP_EOL;
  print_help();
  exit( 1);
}

// Check that source exists and is readable
if( !file_exists( $path) || !is_readable( $path)) {
  error_log( '[ERROR] The given path does not exist or cannot be read.');
  exit( 1);
}

if( $mode === FUNCTIONS_MODE) {
  // create $outdir
  if( !file_exists( $outdir)) {
    if( !mkdir( $outdir, 0755, true)) {
      error_log( '[ERROR] Could not create output directory '.$outdir.".");
      exit( 1);
    }
  }
  else {
    error_log( '[WARNING] Directory '.$outdir.' already exists.');
  }
}

// Determine whether source is a file or a directory
if( is_file( $path)) {
  // functions mode
  if( $mode === FUNCTIONS_MODE) {
    parse_file_functions( $path, $outdir);
  }
  // complete mode
  else {
    $csvexporter = null;
    try {
      $csvexporter = new CSVExporter( $format, $nodefile, $relfile);
    }
    catch( IOError $e) {
      error_log( "[ERROR] ".$e->getMessage());
      exit( 1);
    }
    parse_file( $path, $csvexporter);
  }
}
elseif( is_dir( $path)) {
  // functions mode
  if( $mode === FUNCTIONS_MODE) {
    parse_dir_functions( $path, $outdir);
  }
  // complete mode
  else {
    $csvexporter = null;
    try {
      $csvexporter = new CSVExporter( $format, $nodefile, $relfile);
    }
    catch( IOError $e) {
      error_log( "[ERROR] ".$e->getMessage());
      exit( 1);
    }
    parse_dir( $path, $csvexporter);
  }
}
else {
  error_log( '[ERROR] The given path is neither a regular file nor a directory.');
  exit( 1);
}

echo "Done.", PHP_EOL;
