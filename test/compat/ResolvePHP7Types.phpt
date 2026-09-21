--TEST--
Ability to resolve types in PHP 7
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$source = <<<'EOF'
<?php

function myFunction(int $a, string $b = null): bool
{
}
EOF;

$sourceLocator = new PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator(
    $source,
    (new PHPStan\BetterReflection\BetterReflection())->astLocator()
);

$reflector = new \PHPStan\BetterReflection\Reflector\DefaultReflector($sourceLocator);

$functionInfo = $reflector->reflectFunction('myFunction');

$returnType = $functionInfo->getReturnType();

var_dump([
    'type' => $returnType->__toString(),
]);

array_map(function (\PHPStan\BetterReflection\Reflection\ReflectionParameter $param) {
    $type = $param->getType();

    var_dump([
        'type' => $type->__toString(),
    ]);
}, $functionInfo->getParameters());

?>
--EXPECTF--
array(1) {
  ["type"]=>
  string(4) "bool"
}
array(1) {
  ["type"]=>
  string(3) "int"
}
array(1) {
  ["type"]=>
  string(11) "string|null"
}
