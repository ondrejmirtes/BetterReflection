<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class AttributesResetter
{

    public static function resetClass(ReflectionClass $class): void
    {
        self::resetAttributes($class->getAttributes());
        if ($class instanceof ReflectionEnum && $class->isBacked()) {
            foreach ($class->getCases() as $case) {
                self::resetNodeAttributes($case->getValueExpression());
            }
        }

        $rmDeclaringClass = new CoreReflectionProperty(ReflectionMethod::class, 'declaringClass');
        $rmDeclaringClass->setAccessible(true);
        $rmImplementingClass = new CoreReflectionProperty(ReflectionMethod::class, 'implementingClass');
        $rmImplementingClass->setAccessible(true);
        $rmCurrentClass = new CoreReflectionProperty(ReflectionMethod::class, 'currentClass');
        $rmCurrentClass->setAccessible(true);
        foreach ($class->getImmediateMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                self::resetParameter($parameter);
            }


            $rmDeclaringClass->setValue($method, null);
            $rmImplementingClass->setValue($method, null);
            $rmCurrentClass->setValue($method, null);
        }

        $rpDeclaringClass = new CoreReflectionProperty(ReflectionProperty::class, 'declaringClass');
        $rpDeclaringClass->setAccessible(true);
        $rpImplementingClass = new CoreReflectionProperty(ReflectionProperty::class, 'implementingClass');
        $rpImplementingClass->setAccessible(true);
        foreach ($class->getImmediateProperties() as $property) {
            if ($property->getDefaultValueExpression() === null) {
                continue;
            }
            self::resetNodeAttributes($property->getDefaultValueExpression());
            $rmDeclaringClass->setValue($property, null);
            $rmImplementingClass->setValue($property, null);
        }

        $rcDeclaringClass = new CoreReflectionProperty(ReflectionClassConstant::class, 'declaringClass');
        $rcDeclaringClass->setAccessible(true);
        $rcImplementingClass = new CoreReflectionProperty(ReflectionClassConstant::class, 'implementingClass');
        $rcImplementingClass->setAccessible(true);
        foreach ($class->getImmediateConstants() as $constant) {
            self::resetNodeAttributes($constant->getValueExpression());
            $rcDeclaringClass->setValue($constant, null);
            $rcImplementingClass->setValue($constant, null);
        }
    }

    /**
     * @param array<ReflectionAttribute> $attributes
     */
    public static function resetAttributes(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            foreach ($attribute->getArgumentsExpressions() as $expr) {
                self::resetNodeAttributes($expr);
            }
        }
    }

    public static function resetParameter(ReflectionParameter $parameter): void
    {
        if ($parameter->getDefaultValueExpression() === null) {
            return;
        }

        self::resetNodeAttributes($parameter->getDefaultValueExpression());
    }

    public static function resetNodeAttributes(Node $node): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class () extends NodeVisitorAbstract {
            public function enterNode(Node $node)
            {
                foreach (['startLine', 'endLine', 'startTokenPos', 'startFilePos', 'endTokenPos', 'endFilePos'] as $attribute) {
                    $node->setAttribute($attribute, null);
                }
            }
        });

        $traverser->traverse([$node]);
    }

}
