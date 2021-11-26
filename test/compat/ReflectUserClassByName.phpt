--TEST--
Reflecting a user-defined class by name
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Roave\BetterReflection\Reflection\ReflectionClass;

$classInfo = ReflectionClass::createFromName(ReflectionClass::class);

var_dump($classInfo->getName());
var_dump($classInfo->isInternal());
?>
--EXPECT--
string(51) "PHPStan\BetterReflection\Reflection\ReflectionClass"
bool(false)
