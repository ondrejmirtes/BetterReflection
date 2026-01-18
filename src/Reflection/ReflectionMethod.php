<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use Closure;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod as ReflectionMethodAdapter;
use PHPStan\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionMethodStringCast;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\Util\ClassExistenceChecker;

use function array_map;
use function assert;
use function sprintf;
use function strtolower;

/** @psalm-immutable */
class ReflectionMethod
{
    private Reflector $reflector;
    private LocatedSource $locatedSource;
    /**
     * @var non-empty-string|null
     */
    private $namespace;
    /**
     * @var non-empty-string|null
     */
    private $aliasName;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionProperty|null
     */
    private $hookProperty = null;
    use ReflectionFunctionAbstract;

    /** @var int-mask-of<ReflectionMethodAdapter::IS_*> */
    private int $modifiers;

    private ?ReflectionClass $declaringClass;

    private ?ReflectionClass $implementingClass;

    private ?ReflectionClass $currentClass;

    /** @var non-empty-string */
    private string $declaringClassName;

    /** @var non-empty-string */
    private string $implementingClassName;

    /** @var non-empty-string */
    private string $currentClassName;

    /**
     * @param non-empty-string      $name
     * @param non-empty-string|null $aliasName
     * @param non-empty-string|null $namespace
     * @param MethodNode|\PhpParser\Node\PropertyHook|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction $node
     */
    private function __construct(Reflector $reflector, $node, LocatedSource $locatedSource, string $name, ?string $namespace, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ?string $aliasName, ?\PHPStan\BetterReflection\Reflection\ReflectionProperty $hookProperty = null)
    {
        $this->reflector = $reflector;
        $this->locatedSource = $locatedSource;
        $this->namespace = $namespace;
        $this->aliasName = $aliasName;
        $this->hookProperty = $hookProperty;
        $this->declaringClass = $declaringClass;
        $this->implementingClass = $implementingClass;
        $this->currentClass = $currentClass;
        assert($node instanceof MethodNode || $node instanceof Node\PropertyHook);
        $this->name      = $name;
        $this->modifiers = $this->computeModifiers($node);
        $this->fillFromNode($node);
        $this->declaringClassName = $this->declaringClass->getName();
        $this->implementingClassName = $this->implementingClass->getName();
        $this->currentClassName = $this->currentClass->getName();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        return array_merge($this->exportFunctionAbstractToCache(), [
            'modifiers' => $this->modifiers,
            'namespace' => $this->namespace,
            'declaringClassName' => $this->declaringClassName,
            'implementingClassName' => $this->implementingClassName,
            'currentClassName' => $this->currentClassName,
            'aliasName' => $this->aliasName,
        ]);
    }

    public static function importFromCache(Reflector $reflector, array $data, LocatedSource $locatedSource, ?ReflectionProperty $hookProperty): self
    {
        $reflection = new CoreReflectionClass(self::class);
        /** @var self $ref */
        $ref = $reflection->newInstanceWithoutConstructor();
        $ref->reflector = $reflector;
        $ref->locatedSource = $locatedSource;
        $ref->namespace = $data['namespace'];
        $ref->modifiers = $data['modifiers'];
        $ref->declaringClassName = $data['declaringClassName'];
        $ref->implementingClassName = $data['implementingClassName'];
        $ref->currentClassName = $data['currentClassName'];
        $ref->aliasName = $data['aliasName'];
        $ref->hookProperty = $hookProperty;

        self::importFunctionAbstractFromCache($ref, $reflector, $data);

        return $ref;
    }

    /**
     * @internal
     *
     * @param non-empty-string|null $aliasName
     * @param non-empty-string|null $namespace
     */
    public static function createFromMethodNode(Reflector $reflector, MethodNode $node, LocatedSource $locatedSource, ?string $namespace, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ?string $aliasName = null): self
    {
        return new self(
            $reflector,
            $node,
            $locatedSource,
            $node->name->name,
            $namespace,
            $declaringClass,
            $implementingClass,
            $currentClass,
            $aliasName,
        );
    }

    /**
     * @internal
     *
     * @param non-empty-string $name
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\NullableType|\PhpParser\Node\UnionType|\PhpParser\Node\IntersectionType|null $type
     */
    public static function createFromPropertyHook(Reflector $reflector, Node\PropertyHook $node, LocatedSource $locatedSource, string $name, $type, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ReflectionProperty $hookProperty): self
    {
        $method = new self(
            $reflector,
            $node,
            $locatedSource,
            $name,
            null,
            $declaringClass,
            $implementingClass,
            $currentClass,
            null,
            $hookProperty,
        );
        if ($node->name->name === 'set') {
            $method->returnType = ReflectionType::createFromNode($reflector, $method, new Node\Identifier('void'));

            if ($method->parameters === []) {
                $parameter = ReflectionParameter::createFromNode(
                    $reflector,
                    new Node\Param(new Node\Expr\Variable('value'), null, $type),
                    $method,
                    0,
                    false,
                );

                $method->parameters['value'] = $parameter;
            }
        } elseif ($node->name->name === 'get') {
            $method->returnType = $type !== null
                ? ReflectionType::createFromNode($reflector, $method, $type)
                : null;
        }
        return $method;
    }

    /**
     * Create a reflection of a method by it's name using an instance
     *
     * @param non-empty-string $methodName
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromInstance(object $instance, string $methodName): self
    {
        $method = ReflectionClass::createFromInstance($instance)->getMethod($methodName);

        if ($method === null) {
            throw new OutOfBoundsException(sprintf('Could not find method: %s', $methodName));
        }

        return $method;
    }

    /**
     * @internal
     *
     * @param non-empty-string|null                      $aliasName
     * @param int-mask-of<ReflectionMethodAdapter::IS_*> $modifiers
     */
    public function withImplementingClass(ReflectionClass $implementingClass, ?string $aliasName, int $modifiers): self
    {
        $clone = clone $this;

        $clone->aliasName         = $aliasName;
        $clone->modifiers         = $modifiers;
        $clone->implementingClass = $implementingClass;
        $clone->currentClass      = $implementingClass;

        if ($clone->returnType !== null) {
            $clone->returnType = $clone->returnType->withOwner($clone);
        }

        $clone->parameters = array_map(static fn (ReflectionParameter $parameter): ReflectionParameter => $parameter->withFunction($clone), $this->parameters);

        $clone->attributes = array_map(static fn (ReflectionAttribute $attribute): ReflectionAttribute => $attribute->withOwner($clone), $this->attributes);

        return $clone;
    }

    /** @internal */
    public function withCurrentClass(ReflectionClass $currentClass): self
    {
        $clone = clone $this;
        /** @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor */
        $clone->currentClass = $currentClass;

        if ($clone->returnType !== null) {
            $clone->returnType = $clone->returnType->withOwner($clone);
        }

        // We don't need to clone parameters and attributes

        return $clone;
    }

    /** @return non-empty-string */
    public function getShortName(): string
    {
        if ($this->aliasName !== null) {
            return $this->aliasName;
        }

        return $this->name;
    }

    /** @return non-empty-string|null */
    public function getAliasName(): ?string
    {
        return $this->aliasName;
    }

    /**
     * Find the prototype for this method, if it exists. If it does not exist
     * it will throw a MethodPrototypeNotFound exception.
     *
     * @throws Exception\MethodPrototypeNotFound
     */
    public function getPrototype(): self
    {
        $currentClass = $this->getImplementingClass();

        foreach ($currentClass->getImmediateInterfaces() as $interface) {
            $interfaceMethod = $interface->getMethod($this->getName());

            if ($interfaceMethod !== null) {
                return $interfaceMethod;
            }
        }

        $currentClass = $currentClass->getParentClass();

        if ($currentClass !== null) {
            $prototype = ($nullsafeVariable1 = $currentClass->getMethod($this->getName())) ? $nullsafeVariable1->findPrototype() : null;

            if (
                $prototype !== null
                && (
                    ! $this->isConstructor()
                    || $prototype->isAbstract()
                )
            ) {
                return $prototype;
            }
        }

        throw new Exception\MethodPrototypeNotFound(sprintf(
            'Method %s::%s does not have a prototype',
            $this->getDeclaringClass()->getName(),
            $this->getName(),
        ));
    }

    private function findPrototype(): ?self
    {
        if ($this->isAbstract()) {
            return $this;
        }

        if ($this->isPrivate()) {
            return null;
        }

        try {
            return $this->getPrototype();
        } catch (Exception\MethodPrototypeNotFound $exception) {
            return $this;
        }
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int-mask-of<ReflectionMethodAdapter::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    /** @return int-mask-of<ReflectionMethodAdapter::IS_*>
     * @param MethodNode|\PhpParser\Node\PropertyHook $node */
    private function computeModifiers($node): int
    {
        $modifiers = 0;

        if ($node instanceof MethodNode) {
            $modifiers += $node->isStatic() ? CoreReflectionMethod::IS_STATIC : 0;
            $modifiers += $node->isPublic() ? CoreReflectionMethod::IS_PUBLIC : 0;
            $modifiers += $node->isProtected() ? CoreReflectionMethod::IS_PROTECTED : 0;
            $modifiers += $node->isPrivate() ? CoreReflectionMethod::IS_PRIVATE : 0;
            $modifiers += $node->isAbstract() ? CoreReflectionMethod::IS_ABSTRACT : 0;
        }

        $modifiers += $node->isFinal() ? CoreReflectionMethod::IS_FINAL : 0;

        return $modifiers;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionMethodStringCast::toString($this);
    }

    public function inNamespace(): bool
    {
        return false;
    }

    public function getNamespaceName(): ?string
    {
        return null;
    }

    public function isClosure(): bool
    {
        return false;
    }

    /**
     * Is the method abstract.
     */
    public function isAbstract(): bool
    {
        return (bool) ($this->modifiers & CoreReflectionMethod::IS_ABSTRACT)
            || $this->getDeclaringClass()->isInterface();
    }

    /**
     * Is the method final.
     */
    public function isFinal(): bool
    {
        return (bool) ($this->modifiers & CoreReflectionMethod::IS_FINAL);
    }

    /**
     * Is the method private visibility.
     */
    public function isPrivate(): bool
    {
        return (bool) ($this->modifiers & CoreReflectionMethod::IS_PRIVATE);
    }

    /**
     * Is the method protected visibility.
     */
    public function isProtected(): bool
    {
        return (bool) ($this->modifiers & CoreReflectionMethod::IS_PROTECTED);
    }

    /**
     * Is the method public visibility.
     */
    public function isPublic(): bool
    {
        return (bool) ($this->modifiers & CoreReflectionMethod::IS_PUBLIC);
    }

    /**
     * Is the method static.
     */
    public function isStatic(): bool
    {
        return (bool) ($this->modifiers & CoreReflectionMethod::IS_STATIC);
    }

    /**
     * Is the method a constructor.
     */
    public function isConstructor(): bool
    {
        if (strtolower($this->getName()) === '__construct') {
            return true;
        }

        $declaringClass = $this->getDeclaringClass();
        if ($declaringClass->inNamespace()) {
            return false;
        }

        return strtolower($this->getName()) === strtolower($declaringClass->getShortName());
    }

    /**
     * Is the method a destructor.
     */
    public function isDestructor(): bool
    {
        return strtolower($this->getName()) === '__destruct';
    }

    /**
     * Get the class that declares this method.
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return $this->declaringClass ??= $this->reflector->reflectClass($this->declaringClassName);
    }

    /**
     * Get the class that implemented the method based on trait use.
     */
    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass ??= $this->reflector->reflectClass($this->implementingClassName);
    }

    /**
     * Get the current reflected class.
     *
     * @internal
     */
    public function getCurrentClass(): ReflectionClass
    {
        return $this->currentClass ??= $this->reflector->reflectClass($this->currentClassName);
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    public function getClosure(?object $object = null): Closure
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return fn (...$args) => $this->callStaticMethod($args);
        }

        $instance = $this->assertObject($object);

        return fn (...$args) => $this->callObjectMethod($instance, $args);
    }

    /** @psalm-assert-if-true !null $this->getHookProperty() */
    public function isHook(): bool
    {
        return $this->hookProperty !== null;
    }

    public function getHookProperty(): ?\PHPStan\BetterReflection\Reflection\ReflectionProperty
    {
        return $this->hookProperty;
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @param mixed ...$args
     * @return mixed
     */
    public function invoke(?object $object = null, ...$args)
    {
        return $this->invokeArgs($object, $args);
    }

    /**
     * @param array<mixed> $args
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @return mixed
     */
    public function invokeArgs(?object $object = null, array $args = [])
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);

            return $this->callStaticMethod($args);
        }

        return $this->callObjectMethod($this->assertObject($object), $args);
    }

    /** @param array<mixed> $args
     * @return mixed */
    private function callStaticMethod(array $args)
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        /** @psalm-suppress InvalidStringClass */
        $closure = Closure::bind(fn (string $implementingClassName, string $_methodName, array $methodArgs) => $implementingClassName::{$_methodName}(...$methodArgs), null, $implementingClassName);

        /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($closure instanceof Closure);

        return $closure->__invoke($implementingClassName, $this->getName(), $args);
    }

    /** @param array<mixed> $args
     * @return mixed */
    private function callObjectMethod(object $object, array $args)
    {
        /** @psalm-suppress MixedMethodCall */
        $closure = Closure::bind(fn (object $object, string $methodName, array $methodArgs) => $object->{$methodName}(...$methodArgs), $object, $this->getImplementingClass()->getName());

        /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($closure instanceof Closure);

        return $closure->__invoke($object, $this->getName(), $args);
    }

    /** @throws ClassDoesNotExist */
    private function assertClassExist(string $className): void
    {
        if (! ClassExistenceChecker::classExists($className, true) && ! ClassExistenceChecker::traitExists($className, true)) {
            throw new ClassDoesNotExist(sprintf('Method of class %s cannot be used as the class does not exist', $className));
        }
    }

    /**
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    private function assertObject(?object $object): object
    {
        if ($object === null) {
            throw NoObjectProvided::create();
        }

        $implementingClassName = $this->getImplementingClass()->getName();

        if (get_class($object) !== $implementingClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($implementingClassName);
        }

        return $object;
    }
}
