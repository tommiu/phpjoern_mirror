<?php

require 'util.php';

/**
 * Exports a syntax tree into two CSV files as
 * described in https://github.com/jexp/batch-import/
 */
function ast_csv_export( $ast, $nodefile, $relfile) {

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
 */
function compute_csv( $ast, $nhandle, $rhandle, $nodecount = 0, $nodeline = 0) : int {

  // if $ast is an AST node, print info and recurse
  if ($ast instanceof ast\Node) {

    $nodetype = ast\get_kind_name( $ast->kind);
    $nodeline = $ast->lineno;

    // TODO when is this useful? check when flags occur
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

  // if it is null, print null
  else if ($ast === null) {
    //return 'null';
    // TODO check when this happens and fwrite
  }

  // otherwise, attempt to convert to string
  // note: this should happen mainly for plain values
  // (AST node children can be either other ast\Node objects or plain values)
  else {
    $nodetype = gettype( $ast);
    $nodecode = (string) $ast;
    fwrite( $nhandle, "$nodecount\t$nodetype\t$nodeline\t$nodecode\n");
  }

  return $nodecount;
}
