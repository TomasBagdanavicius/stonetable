<?php

/**
 * Advanced PHP syntax highlighting test.
 *
 * This is a sample PHP syntax file. It is not valid! The mere purpose of this
 * file is to have various PHP language syntax features in one place. Primarily,
 * this will be used to tokenize the contents, or test advanced PHP syntax
 * highlighting features.
 */

declare(strict_types=1);

declare(encoding='ISO-8859-1'):

enddeclare;

namespace Vendor\Foo\Bar;

__NAMESPACE__;
__LINE__;
__FILE__;
__DIR__;

use Vendor\Foo\Bar;
use \ArrayIterator;
use Foo;
use Namespace\Foo\Bar\Namespace;
use \; // T_NAMESPACE_SEPARATOR
use Vendor\Package\{ClassA as A, ClassB, ClassC as C};
use function My\Full\functionName as func;
use const Another\Vendor\CONSTANT_D;

$var1 = "hello";
$hello = "World";
echo $$var1;

$var2 = ((((1 + 2))));

const CONST_2 = ME;

enum Suit: string
{
    case Hearts = 'H';
    case Diamonds = 'D';
    case Clubs = 'C';
    case Spades = 'S';
}

interface Interface1 {

}

trait Trait1 {

    use Trait0;
    use HelloWorld { sayHello as private myPrivateHello; }

    public function method1(): void {

        echo __TRAIT__;
    }
}

readonly class ParentClass {

    public const CONST_VALUE = 1;
}

#[MyAttribute]
#[\MyExample\MyAttribute]
#[MyAttribute(1234)]
#[MyAttribute(value: 1234)]
#[MyAttribute(MyAttribute::VALUE)]
#[MyAttribute(array("key" => "value"))]
#[MyAttribute(100 + 200)]
abstract final class AbstractTest extends ParentClass implements Interface1, Interface2 {

    public const CONST_1 = "constant";

    var $prop1;
    public string $prop2;
    private static $prop3 = [];
    private Class1|int|array|Class2|Class3 $prop4 = [];

    use Trait0;
    use Vendor\CustomTrait;
    use A, B {
        B::smallTalk insteadof A;
        A::bigTalk insteadof B;
    }

    public function __construct(
        public readonly int $int = new Integer('2'),
    ) {

        __CLASS__;

        echo parent::CONST_VALUE . "\n";

        parent::__construct();
    }

    final protected function method1(): false|array {

        __METHOD__;

        return [];
    }

    private function method2( string $prop1, bool|object $prop2 ): void {


    }

    public static function method2(): ClassA {

        return new ClassA;
    }
}

User::method2();

$util->setLogger(new class {
    public function log($msg)
    {
        echo $msg;
    }
});

/**
 * A test function.
 *
 * @return string A test string.
 */
function &test(...$args): string|array|ParentClass|\Closure {

    global $a, $b;

    $GLOBALS['b'] = $GLOBALS['a'] + $GLOBALS['b'];

    __FUNCTION__;

    return "Hello, world!";
}

$var3 =& $var2;

$var4 = 1.12;

$var4 = "Hello $var1";

$great = 'fantastic';
echo "This is {$great}";

$juice = "apple";
echo "He drank some juice made of ${juice}s.";

$a = ['one'];
echo "$a[0]";

include('foo/bar.php');
include_once('foo/baz.php');

require 'foo/bar.php';
require_once 'foo/baz.php';

if ($a > $b)
    echo "a is bigger than b";

if ($a > $b) {
    echo "a is greater than b";
} elseif ($c < $d) {
    echo "c is less than d";
} else {
    echo "a is NOT greater than b";
}

if ($a == 5):
    echo "a equals 5";
    echo "...";
elseif ($a == 6):
    echo "a equals 6";
    echo "!!!";
else:
    echo "a is neither 5 nor 6";
endif;

/* For */

for ($i = 1; $i <= 10; $i++) {
    echo $i;
}

for ($i = 1; ; $i++):
    if ($i > 10) {
        break;
    }
    echo $i;
endfor;

$i = 1;
for (; ; ) {
    if ($i > 10) {
        break;
    }
    echo $i;
    $i++;
}

/* Foreach */

$arr = array(1, 2, 3, 4);
foreach ($arr as &$value) {
    $value = $value * 2;
}

foreach ($arr as $key => $value):
    echo "{$key} => {$value} ";
    print_r($arr);
endforeach;

foreach ($arr as $key => $value) {
    if (!($key % 2)) { // skip even members
        continue;
    }
    do_something_odd($value);
}

/* While */

$i = 1;
while ($i <= 10) {
    echo $i++;
}

$i = 1;
while ($i <= 10):
    echo $i++;
endwhile;

/* Operators */

$c = ($a == $b);
$c = ($a != $b);
$c = ($a <> $b);
$c = ($a === $b);
$c = ($a !== $b);
$c = ($a < $b);
$c = ($a > $b);
$c = ($a <= $b);
$c = ($a >= $b);
$c = ($a <=> $b);
$c = ($a xor $b);

$c += $b;
$c -= $b;
$c %= $b;
$c *= $b;
$c /= $b;
$c .= $b;
$c |= $b;
$c ^= $b;
$c <<= $b;
$c >>= $b;
$c ??= $b;
$c ** $b;
$c **= $b;

$a = (false && foo());
$b = (true || foo());
$c = (false and foo());
$d = (true or foo());

/* Bitwise Operators */

($a & $b);
($a | $b);
($a ^ $b);
($a << $b);
($a >> $b);
~ $a;

/* Null Coalescing Operator */

$action = $_POST['action'] ?? 'default';
echo $foo ?? $bar ?? $baz ?? $qux;

/* Print */

print "print does not require parentheses.";
print("hello");

exit;
exit();
exit(0);

die();
die("Status");

switch ($val) {
    case 0:
        echo "lorem";
        break;
    case "foo":
        echo "ipsum";
        break;
    case FUNCTION:
        echo "dolor";
        break;
    case "$foo":
        echo "sit";
        break;
}

switch ($i):
    case 0:
        echo "i equals 0";
        break;
    default
        echo "Not found";
        break;
endswitch;

$i = 0;
do {
    echo $i;
} while ($i > 0);

/* Exceptions */

throw new Vendor\Foo\Exception(
    "Lorem $var1 dolor sit amet..."
);

throw new CustomException("Error");

try {
    echo inverse(5) . "\n";
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} catch (Vendor\Foo\Bar\Baz|\Throwable|Exception1 $e) {
    echo "Caught another exception";
} finally {
    echo "First finally.\n";
}

/* Yield */

function &gen_reference() {
    $value = 3;
    while ($value > 0) {
        yield $value;
    }
}

foreach (gen_reference() as &$number) {
    echo (--$number).'... ';
}

function inner() {
    yield 1; // key 0
    yield 2; // key 1
    yield 3; // key 2
}
function gen() {
    yield 0; // key 0
    yield from inner(); // keys 0-2
    yield 4; // key 1
}

/* Type Operators */

class MyClass {}

class NotMyClass {}

$a = new MyClass;

var_dump($a instanceof MyClass);
var_dump(!($a instanceof NotMyClass));

/* Clone */

$obj = new MyCloneable();

$obj->object1 = new SubObject();
$obj?->object2 = new SubObject();

$obj2 = clone $obj;

/* Match */

$food = 'cake';

$return_value = match ($food) {
    'apple' => 'This food is an apple',
    'bar' => 'This food is a bar',
    'cake' => 'This food is a cake',
};

var_dump($return_value);

/* Go To */

goto a;
echo 'Foo';

a:
echo 'Bar';

/* Arrow Function */

$y = 1;

$fn1 = fn($x) => $x + $y;
// equivalent to using $y by value:
$fn2 = function ($x) use ($y) {
    return $x + $y;
};

var_export($fn1(3));

/* Casting */

$bar = (bool) $foo;
$bar = ( int )$foo;
$bar = (bool) $foo;
$bar = ( float )$foo;
$bar = (string) $foo;
$bar = ( array )$foo;
($bar = (object) $foo);
$bar = ( unset )$foo;
$binary = (binary) $string;

/* Other Functions */

isset($foo);
unset($foo);
empty($foo);
list($drink, $color, $power) = $info;
eval("\$str = \"$str\";");

/* Callable */

function callMe(callable $callback) {
    $callback();
}

$myClosure = function() {
    echo "Hello from closure!\n";
};

callMe($myClosure);

/* Heredoc */

echo <<<END
      a
     b
    c
    $var1
\n
END;

/* Misc */

$fp = fopen(__FILE__, 'r');
fseek($fp, __COMPILER_HALT_OFFSET__);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Title</title>
</head>
<body>
    <?= $var1 ?>
</body>
</html>
<?php

__halt_compiler();
