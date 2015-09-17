<?php

/*
 Testing some rather rare constructions (which are described in
 https://github.com/nikic/php-ast/issues/12, but I have not seen in
 pratice yet)
*/

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


/*
 Other way around: not described in
 https://github.com/nikic/php-ast/issues/12, but seen in pratice.
*/

// AST_CLOSURE_USES
function($foo) use ($bar, $buzz) {};

class blah {
  // AST_PROP_DECL
  static $buzz, $hui;
  const bla = 2, yay = 3;

  // AST_TRAIT_ADAPTATIONS
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

}

// AST_CONST_DECL
const ha = 1, ho = 2;

// AST_ARRAY
[1,2];

// AST_IF
if(true){}
elseif(false){}
elseif(false&&true) {}
elseif(true&&false) {}
elseif(true||true) {}
else {}

// AST_USE
use this, that;

// ...and all *_LIST nodes which have a variable number of children
