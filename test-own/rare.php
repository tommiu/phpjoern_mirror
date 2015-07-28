<?php

/* Testing some rather rare constructions */

// AST_UNPACK
// https://wiki.php.net/rfc/argument_unpacking
test(...[1, 2, 3]);

// AST_COALESCE
// https://wiki.php.net/rfc/isset_ternary
$a ?? 'was not set';

// AST_YIELD_FROM
// https://wiki.php.net/rfc/generator-delegation
yield from foo();

// AST_TRAIT_PRECEDENCE
// https://wiki.php.net/rfc/horizontalreuse
class T {
  use A, B {
    B::U insteadof A;
  }
}

// AST_GROUP_USE
// https://wiki.php.net/rfc/group_use_declarations
use Foo\Bar\{ A, B };
