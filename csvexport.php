<?php

require 'util.php';

/**
 * Exports a syntax tree into two CSV files as
 * described in https://github.com/jexp/batch-import/
 */
function ast_csv_export( $ast, $nodefile = "nodes.csv", $relfile = "rels.csv") {

  $nhandle = fopen( $nodefile, "w");
  $rhandle = fopen( $relfile, "w");

  fwrite( $nhandle, "index\ttype\tlineno\tcode\n");
  fwrite( $rhandle, "start\tend\ttype\n");

  compute_csv( $ast, $nhandle, $rhandle);

  fclose( $nhandle);
  fclose( $rhandle);
}

/**
 * Helper function for ast_csv_export; the main work is done here.
 *
 * @param $nodecount keeps track of node indices. Mostly for internal
 * use, since the function calls itself recursively.
 *
 * @param $nodeline indicates the nodeline of the parent node. This is
 * necessary when $ast is a plain value, since we cannot get back from
 * a plain value to the parent node to learn the line number.
 *
 * @return The increased node count, i.e., $nodecount plus the number of children
 *         and children's children etc. found in $ast
 */
function compute_csv( $ast, $nhandle, $rhandle, $nodecount = 0, $nodeline = 0) : int {

  // (1) if $ast is an AST node, print info and recurse
  // an instance of ast\Node declares:
  // $kind (integral value, name can be retrieved using ast\get_kind_name())
  // $flags (TODO)
  // $lineno (starting line number)
  // $children (array of child nodes)
  if ($ast instanceof ast\Node) {

    $nodetype = ast\get_kind_name( $ast->kind);
    $nodeline = $ast->lineno;

    // TODO check when flags occur
    // TODO also we will probably need another column 'flags' or so in our CSV file.
    // For this and other reasons, we should also check how the importer behaves if some property
    // is empty, i.e., two tabs follow each other immediately. Does the importer behave correctly,
    // or will it consider the two tabs as one tab and thereby shift everything too far to the left?
    // If the latter is the case, we should probably introduce a function write_csv_line or so,
    // that we use instead of fwrite and that performs the fwrite itself, except that it additionally
    // checks for empty values and replaces them with -- what -- probably a space or so?
    if( ast\kind_uses_flags( $ast->kind)) {
      $nodeflags = format_flags( $ast->kind, $ast->flags);
    }

    // TODO when do we need endLineno, name and docComment? probably only for ast\Node\Decl
    if( isset( $ast->endLineno)) {
      $nodeendline = $ast->endLineno;
    }
    if( isset( $ast->name)) {
      $nodename = $ast->name;
    }
    if( isset( $ast->docComment)) {
      $nodedoccomment = $ast->docComment;
    }

    fwrite( $nhandle, "$nodecount\t$nodetype\t$nodeline\n");

    $nodeindex = $nodecount;
    foreach( $ast->children as $i => $child) {
      $nodecount++;
      fwrite( $rhandle, "$nodeindex\t$nodecount\tPARENT_OF\n");
      $nodecount = compute_csv( $child, $nhandle, $rhandle, $nodecount, $nodeline);
    }
  }

  // if $ast is not an AST node, it should be a plain value
  // see http://php.net/manual/en/language.types.intro.php

  // (2) if it is a plain value and more precisely a string, put quotes around the content
  else if( is_string( $ast)) {

    $nodetype = gettype( $ast); // should be string
    fwrite( $nhandle, "$nodecount\t$nodetype\t$nodeline\t\"$ast\"\n");

    // TODO what if we consider a string that contains newlines and/or tabs and/or quotes?
    // probably screws up our nodes.csv file... (ask Fabian how he dealt with this in the past)
  }

  // (3) If it a plain value and more precisely null, there's no corresponding code per se, so we just print the type.
  // The thing is that this branch is *not* relevant for statements such as, e.g.,
  // $n = NULL;
  // Indeed, in this case, NULL would be parsed as an AST_CONST with appropriate children (see test-own/assignments.php)
  // Rather, this branch will be taken for example for arrays, e.g.,
  // $a=["foo","bar"];
  // In this case the array is parsed as an AST_ARRAY with AST_ARRAY_ELEM children, and the AST_ARRAY_ELEM children each
  // have two children that are plain values: the array element's value (first child), and the array element's key (second value)
  // Now if the key is undefined, the second child will be null, and for that child the following branch will be taken.
  // So far, this is the only case I found where this branch is taken; there may be others...
  else if( $ast === null) {

    $nodetype = gettype( $ast); // should be NULL
    fwrite( $nhandle, "$nodecount\t$nodetype\t$nodeline\t\n");
  }

  // (4) if it is a plain value but not a string and not null, cast to string and store the result as $nodecode
  // Note: I expected at first that such values may be booleans, integers, floats/doubles, arrays, objects, resources, or the null value.
  // However, testing this on test-own/assignments.php, I found that this branch is only taken for integers and floats/doubles;
  // * for booleans and the null value, AST_CONST is used;
  // * for arrays, AST_ARRAY is used;
  // * for objects and resources, which can only be instantiated via the
  //   new operator or function calls, the corresponding statement is
  //   decomposed as an AST, i.e., we get AST_NEW or AST_CALL nodes with appropriate children.
  // Thus, so far I have only seen this branch taken for integers and floats/doubles.
  // We print these similarly as strings, but without quotes.
  else {

    $nodetype = gettype( $ast);
    $nodecode = (string) $ast;
    fwrite( $nhandle, "$nodecount\t$nodetype\t$nodeline\t$nodecode\n");
  }

  return $nodecount;
}
