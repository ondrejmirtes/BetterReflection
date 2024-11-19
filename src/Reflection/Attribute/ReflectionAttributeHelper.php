<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Attribute;

use PhpParser\Node;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;
use PHPStan\BetterReflection\Reflector\Reflector;

use function array_filter;
use function array_values;
use function count;

/** @internal */
class ReflectionAttributeHelper
{
    /**
     * @param Node\AttributeGroup[] $attrGroups
     *
     * @return list<ReflectionAttribute>
     *
     * @psalm-pure
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter $reflection
     */
    public static function createAttributes(Reflector $reflector, $reflection, array $attrGroups): array
    {
        $repeated = [];
        foreach ($attrGroups as $attributesGroupNode) {
            foreach ($attributesGroupNode->attrs as $attributeNode) {
                $repeated[$attributeNode->name->toLowerString()][] = $attributeNode;
            }
        }
        $attributes = [];
        foreach ($attrGroups as $attributesGroupNode) {
            foreach ($attributesGroupNode->attrs as $attributeNode) {
                $attributes[] = new ReflectionAttribute($reflector, $attributeNode, $reflection, count($repeated[$attributeNode->name->toLowerString()]) > 1);
            }
        }
        return $attributes;
    }

    /**
     * @param list<ReflectionAttribute> $attributes
     *
     * @return list<ReflectionAttribute>
     *
     * @psalm-pure
     */
    public static function filterAttributesByName(array $attributes, string $name): array
    {
        return array_values(array_filter($attributes, static function (ReflectionAttribute $attribute) use ($name) : bool {
            return $attribute->getName() === $name;
        }));
    }

    /**
     * @param list<ReflectionAttribute> $attributes
     * @param class-string              $className
     *
     * @return list<ReflectionAttribute>
     *
     * @psalm-pure
     */
    public static function filterAttributesByInstance(array $attributes, string $className): array
    {
        return array_values(array_filter($attributes, static function (ReflectionAttribute $attribute) use ($className): bool {
            $class = $attribute->getClass();

            return $class->getName() === $className || $class->isSubclassOf($className) || $class->implementsInterface($className);
        }));
    }
}
