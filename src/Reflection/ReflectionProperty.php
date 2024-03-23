<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use Closure;
use Error;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property as PropertyNode;
use ReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use PHPStan\BetterReflection\NodeCompiler\CompiledValue;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use PHPStan\BetterReflection\Reflection\Exception\CodeLocationMissing;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionPropertyStringCast;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;
use PHPStan\BetterReflection\Util\ClassExistenceChecker;
use PHPStan\BetterReflection\Util\Exception\NoNodePosition;
use PHPStan\BetterReflection\Util\GetLastDocComment;

use function array_map;
use function assert;
use function func_num_args;
use function is_object;
use function sprintf;
use function str_contains;

/** @psalm-immutable */
class ReflectionProperty
{
    /** @var non-empty-string */
    private $name;

    /** @var int-mask-of<ReflectionPropertyAdapter::IS_*> */
    private $modifiers;

    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|null
     */
    private $type;

    /**
     * @var \PhpParser\Node\Expr|null
     */
    private $default;

    /** @var non-empty-string|null */
    private $docComment;

    /** @var list<ReflectionAttribute> */
    private $attributes;

    /** @var positive-int|null */
    private $startLine;

    /** @var positive-int|null */
    private $endLine;

    /** @var positive-int|null */
    private $startColumn;

    /** @var positive-int|null */
    private $endColumn;

    /** @psalm-allow-private-mutation
     * @var \PHPStan\BetterReflection\NodeCompiler\CompiledValue|null */
    private $compiledDefaultValue = null;
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass
     */
    private $declaringClass;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass
     */
    private $implementingClass;
    /**
     * @var bool
     */
    private $isPromoted;
    /**
     * @var bool
     */
    private $declaredAtCompileTime;
    private function __construct(Reflector $reflector, PropertyNode $node, Node\Stmt\PropertyProperty $propertyNode, ReflectionClass $declaringClass, ReflectionClass $implementingClass, bool $isPromoted, bool $declaredAtCompileTime)
    {
        $this->reflector = $reflector;
        $this->declaringClass = $declaringClass;
        $this->implementingClass = $implementingClass;
        $this->isPromoted = $isPromoted;
        $this->declaredAtCompileTime = $declaredAtCompileTime;
        $name = $propertyNode->name->name;
        assert($name !== '');
        $this->name       = $name;
        $this->modifiers  = $this->computeModifiers($node);
        $this->type       = $this->createType($node);
        $this->default    = $propertyNode->default;
        $this->docComment = GetLastDocComment::forNode($node);
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);
        $startLine = null;
        if ($node->hasAttribute('startLine')) {
            $startLine = $node->getStartLine();
            assert($startLine > 0);
        }
        $endLine = null;
        if ($node->hasAttribute('endLine')) {
            $endLine = $node->getEndLine();
            assert($endLine > 0);
        }
        $this->startLine = $startLine;
        $this->endLine   = $endLine;
        try {
            $this->startColumn = CalculateReflectionColumn::getStartColumn($declaringClass->getLocatedSource()->getSource(), $node);
        } catch (NoNodePosition $exception) {
            $this->startColumn = null;
        }
        try {
            $this->endColumn = CalculateReflectionColumn::getEndColumn($declaringClass->getLocatedSource()->getSource(), $node);
        } catch (NoNodePosition $exception) {
            $this->endColumn = null;
        }
    }

    /**
     * Create a reflection of a class's property by its name
     *
     * @param non-empty-string $propertyName
     *
     * @throws OutOfBoundsException
     */
    public static function createFromName(string $className, string $propertyName): self
    {
        $property = ReflectionClass::createFromName($className)->getProperty($propertyName);

        if ($property === null) {
            throw new OutOfBoundsException(sprintf('Could not find property: %s', $propertyName));
        }

        return $property;
    }

    /**
     * Create a reflection of an instance's property by its name
     *
     * @param non-empty-string $propertyName
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromInstance(object $instance, string $propertyName): self
    {
        $property = ReflectionClass::createFromInstance($instance)->getProperty($propertyName);

        if ($property === null) {
            throw new OutOfBoundsException(sprintf('Could not find property: %s', $propertyName));
        }

        return $property;
    }

    /** @internal */
    public function withImplementingClass(ReflectionClass $implementingClass): self
    {
        $clone                    = clone $this;
        $clone->implementingClass = $implementingClass;

        if ($clone->type !== null) {
            $clone->type = $clone->type->withOwner($clone);
        }

        $clone->attributes = array_map(static function (ReflectionAttribute $attribute) use ($clone) : ReflectionAttribute {
            return $attribute->withOwner($clone);
        }, $this->attributes);

        $this->compiledDefaultValue = null;

        return $clone;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionPropertyStringCast::toString($this);
    }

    /**
     * @internal
     *
     * @param PropertyNode $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     */
    public static function createFromNode(Reflector $reflector, PropertyNode $node, Node\Stmt\PropertyProperty $propertyProperty, ReflectionClass $declaringClass, ReflectionClass $implementingClass, bool $isPromoted = false, bool $declaredAtCompileTime = true): self
    {
        return new self($reflector, $node, $propertyProperty, $declaringClass, $implementingClass, $isPromoted, $declaredAtCompileTime);
    }

    /**
     * Has the property been declared at compile-time?
     *
     * Note that unless the property is static, this is hard coded to return
     * true, because we are unable to reflect instances of classes, therefore
     * we can be sure that all properties are always declared at compile-time.
     */
    public function isDefault(): bool
    {
        return $this->declaredAtCompileTime;
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int-mask-of<ReflectionPropertyAdapter::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    /**
     * Get the name of the property.
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is the property private?
     */
    public function isPrivate(): bool
    {
        return ($this->modifiers & CoreReflectionProperty::IS_PRIVATE) === CoreReflectionProperty::IS_PRIVATE;
    }

    /**
     * Is the property protected?
     */
    public function isProtected(): bool
    {
        return ($this->modifiers & CoreReflectionProperty::IS_PROTECTED) === CoreReflectionProperty::IS_PROTECTED;
    }

    /**
     * Is the property public?
     */
    public function isPublic(): bool
    {
        return ($this->modifiers & CoreReflectionProperty::IS_PUBLIC) === CoreReflectionProperty::IS_PUBLIC;
    }

    /**
     * Is the property static?
     */
    public function isStatic(): bool
    {
        return ($this->modifiers & CoreReflectionProperty::IS_STATIC) === CoreReflectionProperty::IS_STATIC;
    }

    public function isPromoted(): bool
    {
        return $this->isPromoted;
    }

    public function isInitialized($object = null): bool
    {
        if ($object === null && $this->isStatic()) {
            return ! $this->hasType() || $this->hasDefaultValue();
        }

        try {
            $this->getValue($object);

            return true;

        // @phpstan-ignore-next-line
        } catch (Error $e) {
            if (strpos($e->getMessage(), 'must not be accessed before initialization') !== false) {
                return false;
            }

            throw $e;
        }
    }

    public function isReadOnly(): bool
    {
        return ($this->modifiers & ReflectionPropertyAdapter::IS_READONLY) === ReflectionPropertyAdapter::IS_READONLY
            || $this->getDeclaringClass()->isReadOnly();
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return $this->declaringClass;
    }

    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass;
    }

    /** @return non-empty-string|null */
    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    public function hasDefaultValue(): bool
    {
        return ! $this->hasType() || $this->default !== null;
    }

    /**
     * @deprecated Use getDefaultValueExpression()
     */
    public function getDefaultValueExpr(): ?\PhpParser\Node\Expr
    {
        return $this->getDefaultValueExpression();
    }

    public function getDefaultValueExpression(): ?\PhpParser\Node\Expr
    {
        return $this->default;
    }

    /**
     * Get the default value of the property (as defined before constructor is
     * called, when the property is defined)
     *
     * @deprecated Use getDefaultValueExpr()
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->default === null) {
            return null;
        }

        if ($this->compiledDefaultValue === null) {
            $this->compiledDefaultValue = (new CompileNodeToValue())->__invoke($this->default, new CompilerContext($this->reflector, $this));
        }

        /** @psalm-var scalar|array<scalar>|null $value */
        $value = $this->compiledDefaultValue->value;

        return $value;
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->getDocComment());
    }

    /**
     * Get the line number that this property starts on.
     *
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getStartLine(): int
    {
        if ($this->startLine === null) {
            throw CodeLocationMissing::create();
        }

        return $this->startLine;
    }

    /**
     * Get the line number that this property ends on.
     *
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getEndLine(): int
    {
        if ($this->endLine === null) {
            throw CodeLocationMissing::create();
        }

        return $this->endLine;
    }

    /**
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getStartColumn(): int
    {
        if ($this->startColumn === null) {
            throw CodeLocationMissing::create();
        }

        return $this->startColumn;
    }

    /**
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getEndColumn(): int
    {
        if ($this->endColumn === null) {
            throw CodeLocationMissing::create();
        }

        return $this->endColumn;
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributesByName(string $name): array
    {
        return ReflectionAttributeHelper::filterAttributesByName($this->getAttributes(), $name);
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return ReflectionAttributeHelper::filterAttributesByInstance($this->getAttributes(), $className);
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @return mixed
     */
    public function getValue($object = null)
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);

            $closure = Closure::bind(function (string $implementingClassName, string $propertyName) {
                return $implementingClassName::${$propertyName};
            }, null, $implementingClassName);

            assert($closure instanceof Closure);

            return $closure->__invoke($implementingClassName, $this->getName());
        }

        $instance = $this->assertObject($object);

        $closure = Closure::bind(function (object $instance, string $propertyName) {
            return $instance->{$propertyName};
        }, $instance, $implementingClassName);

        assert($closure instanceof Closure);

        return $closure->__invoke($instance, $this->getName());
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     * @param mixed $object
     * @param mixed $value
     */
    public function setValue($object, $value = null): void
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);

            $closure = Closure::bind(function (string $_implementingClassName, string $_propertyName, $value): void {
                /** @psalm-suppress MixedAssignment */
                $_implementingClassName::${$_propertyName} = $value;
            }, null, $implementingClassName);

            assert($closure instanceof Closure);

            $closure->__invoke($implementingClassName, $this->getName(), func_num_args() === 2 ? $value : $object);

            return;
        }

        $instance = $this->assertObject($object);

        $closure = Closure::bind(function (object $instance, string $propertyName, $value): void {
            $instance->{$propertyName} = $value;
        }, $instance, $implementingClassName);

        assert($closure instanceof Closure);

        $closure->__invoke($instance, $this->getName(), $value);
    }

    /**
     * Does this property allow null?
     */
    public function allowsNull(): bool
    {
        return $this->type === null || $this->type->allowsNull();
    }

    /**
     * @return \PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|null
     */
    private function createType(PropertyNode $node)
    {
        $type = $node->type;

        if ($type === null) {
            return null;
        }

        assert($type instanceof Node\Identifier || $type instanceof Node\Name || $type instanceof Node\NullableType || $type instanceof Node\UnionType || $type instanceof Node\IntersectionType);

        return ReflectionType::createFromNode($this->reflector, $this, $type);
    }

    /**
     * Get the ReflectionType instance representing the type declaration for
     * this property
     *
     * (note: this has nothing to do with DocBlocks).
     * @return \PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Does this property have a type declaration?
     *
     * (note: this has nothing to do with DocBlocks).
     */
    public function hasType(): bool
    {
        return $this->type !== null;
    }

    /**
     * @param class-string $className
     *
     * @throws ClassDoesNotExist
     */
    private function assertClassExist(string $className): void
    {
        if (! ClassExistenceChecker::classExists($className, true) && ! ClassExistenceChecker::traitExists($className, true)) {
            throw new ClassDoesNotExist('Property cannot be retrieved as the class does not exist');
        }
    }

    /**
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     *
     * @psalm-assert object $object
     * @param mixed $object
     */
    private function assertObject($object): object
    {
        if ($object === null) {
            throw NoObjectProvided::create();
        }

        if (! is_object($object)) {
            throw NotAnObject::fromNonObject($object);
        }

        $implementingClassName = $this->getImplementingClass()->getName();

        if (get_class($object) !== $implementingClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($implementingClassName);
        }

        return $object;
    }

    /** @return int-mask-of<ReflectionPropertyAdapter::IS_*> */
    private function computeModifiers(PropertyNode $node): int
    {
        $modifiers  = $node->isReadonly() ? ReflectionPropertyAdapter::IS_READONLY : 0;
        $modifiers += $node->isStatic() ? CoreReflectionProperty::IS_STATIC : 0;
        $modifiers += $node->isPrivate() ? CoreReflectionProperty::IS_PRIVATE : 0;
        $modifiers += $node->isProtected() ? CoreReflectionProperty::IS_PROTECTED : 0;
        $modifiers += $node->isPublic() ? CoreReflectionProperty::IS_PUBLIC : 0;

        return $modifiers;
    }
}
