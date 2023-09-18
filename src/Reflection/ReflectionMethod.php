<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use Closure;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
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
    use ReflectionFunctionAbstract;

    /** @var int-mask-of<ReflectionMethodAdapter::IS_*> */
    private $modifiers;
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Located\LocatedSource
     */
    private $locatedSource;
    /**
     * @var string|null
     */
    private $namespace;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass
     */
    private $declaringClass;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass
     */
    private $implementingClass;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass
     */
    private $currentClass;
    /**
     * @var non-empty-string|null
     */
    private $aliasName;
    /** @param non-empty-string|null $aliasName
     * @param MethodNode|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction $node */
    private function __construct(Reflector $reflector, $node, LocatedSource $locatedSource, ?string $namespace, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ?string $aliasName)
    {
        $this->reflector = $reflector;
        $this->locatedSource = $locatedSource;
        $this->namespace = $namespace;
        $this->declaringClass = $declaringClass;
        $this->implementingClass = $implementingClass;
        $this->currentClass = $currentClass;
        $this->aliasName = $aliasName;
        assert($node instanceof MethodNode);
        $name = $node->name->name;
        assert($name !== '');
        $this->name      = $name;
        $this->modifiers = $this->computeModifiers($node);
        $this->fillFromNode($node);
    }
    /**
     * @internal
     *
     * @param non-empty-string|null $aliasName
     */
    public static function createFromNode(Reflector $reflector, MethodNode $node, LocatedSource $locatedSource, ?string $namespace, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ?string $aliasName = null): self
    {
        return new self($reflector, $node, $locatedSource, $namespace, $declaringClass, $implementingClass, $currentClass, $aliasName);
    }

    /**
     * Create a reflection of a method by it's name using a named class
     *
     * @param non-empty-string $methodName
     *
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromName(string $className, string $methodName): self
    {
        $method = ReflectionClass::createFromName($className)->getMethod($methodName);

        if ($method === null) {
            throw new OutOfBoundsException(sprintf('Could not find method: %s', $methodName));
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

        $clone->parameters = array_map(static function (ReflectionParameter $parameter) use ($clone) : ReflectionParameter {
            return $parameter->withFunction($clone);
        }, $this->parameters);

        $clone->attributes = array_map(static function (ReflectionAttribute $attribute) use ($clone) : ReflectionAttribute {
            return $attribute->withOwner($clone);
        }, $this->attributes);

        return $clone;
    }

    /** @internal */
    public function withCurrentClass(ReflectionClass $currentClass): self
    {
        $clone               = clone $this;
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
            $prototype = ($getMethod = $currentClass->getMethod($this->getName())) ? $getMethod->findPrototype() : null;

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

        throw new Exception\MethodPrototypeNotFound(sprintf('Method %s::%s does not have a prototype', $this->getDeclaringClass()->getName(), $this->getName()));
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

    /** @return int-mask-of<ReflectionMethodAdapter::IS_*> */
    private function computeModifiers(MethodNode $node): int
    {
        $modifiers  = $node->isStatic() ? CoreReflectionMethod::IS_STATIC : 0;
        $modifiers += $node->isPublic() ? CoreReflectionMethod::IS_PUBLIC : 0;
        $modifiers += $node->isProtected() ? CoreReflectionMethod::IS_PROTECTED : 0;
        $modifiers += $node->isPrivate() ? CoreReflectionMethod::IS_PRIVATE : 0;
        $modifiers += $node->isAbstract() ? CoreReflectionMethod::IS_ABSTRACT : 0;
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
        return ($this->modifiers & CoreReflectionMethod::IS_ABSTRACT) === CoreReflectionMethod::IS_ABSTRACT
            || $this->declaringClass->isInterface();
    }

    /**
     * Is the method final.
     */
    public function isFinal(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_FINAL) === CoreReflectionMethod::IS_FINAL;
    }

    /**
     * Is the method private visibility.
     */
    public function isPrivate(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_PRIVATE) === CoreReflectionMethod::IS_PRIVATE;
    }

    /**
     * Is the method protected visibility.
     */
    public function isProtected(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_PROTECTED) === CoreReflectionMethod::IS_PROTECTED;
    }

    /**
     * Is the method public visibility.
     */
    public function isPublic(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_PUBLIC) === CoreReflectionMethod::IS_PUBLIC;
    }

    /**
     * Is the method static.
     */
    public function isStatic(): bool
    {
        return ($this->modifiers & CoreReflectionMethod::IS_STATIC) === CoreReflectionMethod::IS_STATIC;
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
        return $this->declaringClass;
    }

    /**
     * Get the class that implemented the method based on trait use.
     */
    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass;
    }

    /**
     * Get the current reflected class.
     *
     * @internal
     */
    public function getCurrentClass(): ReflectionClass
    {
        return $this->currentClass;
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     */
    public function getClosure($object = null): Closure
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return function (...$args) {
                return $this->callStaticMethod($args);
            };
        }

        $instance = $this->assertObject($object);

        return function (...$args) use ($instance) {
            return $this->callObjectMethod($instance, $args);
        };
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @param mixed ...$args
     * @return mixed
     */
    public function invoke($object = null, ...$args)
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
    public function invokeArgs($object = null, array $args = [])
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
        $closure = Closure::bind(function (string $implementingClassName, string $_methodName, array $methodArgs) {
            return $implementingClassName::{$_methodName}(...$methodArgs);
        }, null, $implementingClassName);

        assert($closure instanceof Closure);

        return $closure->__invoke($implementingClassName, $this->getName(), $args);
    }

    /** @param array<mixed> $args
     * @return mixed */
    private function callObjectMethod(object $object, array $args)
    {
        /** @psalm-suppress MixedMethodCall */
        $closure = Closure::bind(function (object $object, string $methodName, array $methodArgs) {
            return $object->{$methodName}(...$methodArgs);
        }, $object, $this->getImplementingClass()->getName());

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
    private function assertObject($object): object
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
