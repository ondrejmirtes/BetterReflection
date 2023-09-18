<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use Closure;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\IgnoreMethodForCodeCoverage;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionType as CoreReflectionType;
use ReturnTypeWillChange;
use PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use PHPStan\BetterReflection\Reflection\Exception\CodeLocationMissing;
use PHPStan\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use PHPStan\BetterReflection\Util\FileHelper;
use Throwable;
use ValueError;

use function array_map;
use function sprintf;

/** @psalm-suppress PropertyNotSetInConstructor */
#[IgnoreMethodForCodeCoverage(ReflectionMethod::class, 'setAccessible')]
final class ReflectionMethod extends CoreReflectionMethod
{
    /**
     * @var BetterReflectionMethod
     */
    private $betterReflectionMethod;
    public function __construct(BetterReflectionMethod $betterReflectionMethod)
    {
        $this->betterReflectionMethod = $betterReflectionMethod;
        unset($this->name);
        unset($this->class);
    }

    public function __toString(): string
    {
        return $this->betterReflectionMethod->__toString();
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionMethod->inNamespace();
    }

    public function isClosure(): bool
    {
        return $this->betterReflectionMethod->isClosure();
    }

    public function isDeprecated(): bool
    {
        return $this->betterReflectionMethod->isDeprecated();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionMethod->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionMethod->isUserDefined();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getClosureThis()
    {
        throw new NotImplemented('Not implemented');
    }

    public function getClosureScopeClass(): ?CoreReflectionClass
    {
        throw new NotImplemented('Not implemented');
    }

    public function getClosureCalledClass(): ?CoreReflectionClass
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionMethod->getDocComment() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStartLine()
    {
        try {
            return $this->betterReflectionMethod->getStartLine();
        } catch (CodeLocationMissing $exception) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getEndLine()
    {
        try {
            return $this->betterReflectionMethod->getEndLine();
        } catch (CodeLocationMissing $exception) {
            return false;
        }
    }

    /** @psalm-suppress ImplementedReturnTypeMismatch */
    public function getExtension(): ?CoreReflectionExtension
    {
        throw new NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getExtensionName()
    {
        return $this->betterReflectionMethod->getExtensionName() ?? false;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionMethod->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    public function getName(): string
    {
        return $this->betterReflectionMethod->getName();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionMethod->getNamespaceName() ?? '';
    }

    public function getNumberOfParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfParameters();
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this->betterReflectionMethod->getNumberOfRequiredParameters();
    }

    /** @return list<ReflectionParameter> */
    public function getParameters(): array
    {
        return array_map(static function (BetterReflectionParameter $parameter) : ReflectionParameter {
            return new ReflectionParameter($parameter);
        }, $this->betterReflectionMethod->getParameters());
    }

    public function hasReturnType(): bool
    {
        return $this->betterReflectionMethod->hasReturnType();
    }

    /** @return ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null */
    public function getReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getReturnType());
    }

    public function getShortName(): string
    {
        return $this->betterReflectionMethod->getShortName();
    }

    /** @return array<string, scalar> */
    public function getStaticVariables(): array
    {
        throw new NotImplemented('Not implemented');
    }

    public function returnsReference(): bool
    {
        return $this->betterReflectionMethod->returnsReference();
    }

    public function isGenerator(): bool
    {
        return $this->betterReflectionMethod->isGenerator();
    }

    public function isVariadic(): bool
    {
        return $this->betterReflectionMethod->isVariadic();
    }

    public function isPublic(): bool
    {
        return $this->betterReflectionMethod->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->betterReflectionMethod->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->betterReflectionMethod->isProtected();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionMethod->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionMethod->isFinal();
    }

    public function isStatic(): bool
    {
        return $this->betterReflectionMethod->isStatic();
    }

    public function isConstructor(): bool
    {
        return $this->betterReflectionMethod->isConstructor();
    }

    public function isDestructor(): bool
    {
        return $this->betterReflectionMethod->isDestructor();
    }

    /**
     * {@inheritDoc}
     */
    public function getClosure($object = null): Closure
    {
        try {
            return $this->betterReflectionMethod->getClosure($object);
        } catch (NoObjectProvided $e) {
            throw new ValueError($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionMethod->getModifiers();
    }

    /**
     * @param object $object
     * @param mixed  $arg
     * @param mixed  ...$args
     *
     * @return mixed
     *
     * @throws CoreReflectionException
     */
    #[ReturnTypeWillChange]
    public function invoke($object = null, $arg = null, ...$args)
    {
        try {
            return $this->betterReflectionMethod->invoke($object, $arg, ...$args);
        } catch (NoObjectProvided $exception) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param object  $object
     * @param mixed[] $args
     *
     * @return mixed
     *
     * @throws CoreReflectionException
     */
    #[ReturnTypeWillChange]
    public function invokeArgs($object = null, array $args = [])
    {
        try {
            return $this->betterReflectionMethod->invokeArgs($object, $args);
        } catch (NoObjectProvided $exception) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionMethod->getImplementingClass());
    }

    public function getPrototype(): ReflectionMethod
    {
        return new self($this->betterReflectionMethod->getPrototype());
    }

    public function hasPrototype(): bool
    {
        try {
            $this->betterReflectionMethod->getPrototype();

            return true;
        } catch (MethodPrototypeNotFound $exception) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     * @codeCoverageIgnore
     * @infection-ignore-all
     */
    public function setAccessible($accessible): void
    {
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags !== 0) {
            $attributes = $this->betterReflectionMethod->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionMethod->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionMethod->getAttributes();
        }

        return array_map(static function (BetterReflectionAttribute $betterReflectionAttribute) {
            return ReflectionAttributeFactory::create($betterReflectionAttribute);
        }, $attributes);
    }

    public function hasTentativeReturnType(): bool
    {
        return $this->betterReflectionMethod->hasTentativeReturnType();
    }

    /** @return ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null */
    public function getTentativeReturnType(): ?CoreReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionMethod->getTentativeReturnType());
    }

    /** @return mixed[] */
    public function getClosureUsedVariables(): array
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionMethod->getName();
        }

        if ($name === 'class') {
            return $this->betterReflectionMethod->getImplementingClass()->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }

    public function getBetterReflection(): BetterReflectionMethod
    {
        return $this->betterReflectionMethod;
    }
}
