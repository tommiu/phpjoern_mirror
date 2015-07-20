<?php

// testing the different primitive types
// see http://php.net/manual/en/language.types.object.php

$b=true; // bool
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "b"
        1: AST_CONST
            0: AST_NAME
                flags: NAME_NOT_FQ (1)
                0: "TRUE"
*/

$i=42; // int
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "i"
        1: 42
*/

$f=3.14; // float
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "f"
        1: 3.14
*/

$s="hello"; // string
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "s"
        1: "hello"
*/

$a=["foo","bar"]; // array
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "a"
        1: AST_ARRAY
            0: AST_ARRAY_ELEM
                flags: 0
                0: "foo"
                1: null
            1: AST_ARRAY_ELEM
                flags: 0
                0: "bar"
                1: null
*/

$o=new stdClass(); // object
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "o"
        1: AST_NEW
            0: AST_NAME
                flags: NAME_NOT_FQ (1)
                0: "stdClass"
            1: AST_ARG_LIST
*/

$r=aspell_new(); // resource
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "r"
        1: AST_CALL
            0: AST_NAME
                flags: NAME_NOT_FQ (1)
                0: "aspell_new"
            1: AST_ARG_LIST
*/

$n=NULL; // null
/*
AST_STMT_LIST
    0: AST_ASSIGN
        0: AST_VAR
            0: "n"
        1: AST_CONST
            0: AST_NAME
                flags: NAME_NOT_FQ (1)
                0: "NULL"
*/

