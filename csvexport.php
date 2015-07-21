<?php

require 'util.php';

/**
 * Exports a syntax tree into two CSV files as
 * described in https://github.com/jexp/batch-import/
 */
function ast_csv_export( $ast, $nodefile = "nodes.csv", $relfile = "rels.csv") {

  $nhandle = fopen( $nodefile, "w");
  $rhandle = fopen( $relfile, "w");

  store_node( $nhandle, "index:int", "type", "lineno:int", "code", "flags:string_array");
  store_rel( $rhandle, "start", "end", "type");

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

    $nodeflags = "";
    if( ast\kind_uses_flags( $ast->kind)) {
      $nodeflags = csv_format_flags( $ast->kind, $ast->flags);
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

    store_node( $nhandle, $nodecount, $nodetype, $nodeline, "", $nodeflags);

    $startnode = $nodecount;
    foreach( $ast->children as $i => $child) {
      $nodecount++;
      store_rel( $rhandle, $startnode, $nodecount, "PARENT_OF");
      $nodecount = compute_csv( $child, $nhandle, $rhandle, $nodecount, $nodeline);
    }
  }

  // if $ast is not an AST node, it should be a plain value
  // see http://php.net/manual/en/language.types.intro.php

  // (2) if it is a plain value and more precisely a string, put quotes around the content
  else if( is_string( $ast)) {

    $nodetype = gettype( $ast); // should be string
    store_node( $nhandle, $nodecount, $nodetype, $nodeline, "\"$ast\"");

    // TODO what if we consider a string that contains newlines and/or tabs and/or quotes?
    // probably screws up our nodes.csv file... (ask Fabian how he dealt with this in the past)
  }

  // (3) If it a plain value and more precisely null, there's no corresponding code per se, so we just print the type.
  // The thing is that this branch is NOT relevant for statements such as, e.g.,
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
    store_node( $nhandle, $nodecount, $nodetype, $nodeline);
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
    store_node( $nhandle, $nodecount, $nodetype, $nodeline, $nodecode);
  }

  return $nodecount;
}

/*
 * Helper function to write a node to a CSV file
 *
 * @param nhandle Handle for the node file
 * @param index   The node index (mandatory)
 * @param type    The node type (mandatory)
 * @param lineno  The node's line number (mandatory)
 * @param code    The node code (optional)
 * @param flags   The node's flags (optional)
 */
function store_node( $nhandle, $index, $type, $lineno, $code = "", $flags = "") {

  fwrite( $nhandle, "$index\t$type\t$lineno\t$code\t$flags\n");
}

/*
 * Helper function to write a relationship to a CSV file
 *
 * @param rhandle Handle for the relationship file
 * @param start   The starting node's index
 * @param end     The ending node's index
 * @param type    The relationship's type
 */
function store_rel( $rhandle, $start, $end, $type) {

  fwrite( $rhandle, "$start\t$end\t$type\n");
}

/*
 * Slight modification of format_flags() from php-ast's util.php
 *
 * Given a kind (e.g., AST_NAME or AST_METHOD) and a set of flags
 * (say, NAME_NOT_FQ or MODIFIER_PUBLIC | MODIFIER_STATIC), both of
 * which are represented as an integer, return the named list of flags
 * as a comma-separated list.
 *
 * Some flags are exclusive (say, NAME_FQ and NAME_NOT_FQ, for the
 * AST_NAME kind), while others are combinable (say, MODIFIER_PUBLIC
 * and MODIFIER_STATIC, for the AST_METHOD kind). Each kind has at
 * most one exclusive flag, but may have more than one combinable
 * flag. Additionally, no kind uses both exclusive and combinable
 * flags, i.e., the set of kinds using exclusive flags and the set of
 * kinds using cominable flags are disjunct.
 *
 * More information on flags can be found by looking at the source
 * code of the function get_flag_info() in util.php
 *
 * @param kind  An AST node type, represented as an integer
 * @param flags Flags pertaining to the current AST node, represented
 *              as an integer
 *
 * @return A comma-separated list of named flags.
 */
function csv_format_flags( int $kind, int $flags) : string {

    list( $exclusive, $combinable) = get_flag_info();
    if( isset( $exclusive[$kind])) {
      $flagInfo = $exclusive[$kind];
      if( isset( $flagInfo[$flags])) {
	return $flagInfo[$flags];
      }
    }

    else if( isset( $combinable[$kind])) {
      $flagInfo = $combinable[$kind];
      $names = [];
      foreach( $flagInfo as $flag => $name) {
	if( $flags & $flag) {
	  $names[] = $name;
	}
      }
      if( !empty($names)) {
	return implode(",", $names);
      }
    }

    // If the given $kind does not use either exclusive or combinable
    // flags, or if it does, but the given $flags did not yield any
    // flags for the given $kind, we arrive here. Probably, this means
    // that the given $flags was 0, otherwise this method was called
    // with weird parameters.
    // TODO Watch out whether this ever happens!
    if( $flags === 0)
      return "";
    else
      return "[ERROR] Unexpected flags for kind: kind=$kind and flags=$flags";
}
