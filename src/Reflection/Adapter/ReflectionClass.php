<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use ReturnTypeWillChange;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\ClassExistenceChecker;
use Roave\BetterReflection\Util\FileHelper;
use ValueError;

use function array_combine;
use function array_map;
use function array_values;
use function constant;
use function func_num_args;
use function sprintf;
use function strtolower;

/**
 * @template-extends CoreReflectionClass<object>
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ReflectionClass extends CoreReflectionClass
{
    public const IS_READONLY = 65536;

    public function __construct(private BetterReflectionClass|BetterReflectionEnum $betterReflectionClass)
    {
        unset($this->name);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionClass->__toString();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionClass->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }

    /**
     * @psalm-mutation-free
     * @return class-string
     */
    public function getName(): string
    {
        return $this->betterReflectionClass->getName();
    }

    /** @psalm-mutation-free */
    public function isAnonymous(): bool
    {
        return $this->betterReflectionClass->isAnonymous();
    }

    /** @psalm-mutation-free */
    public function isInternal(): bool
    {
        return $this->betterReflectionClass->isInternal();
    }

    /** @psalm-mutation-free */
    public function isUserDefined(): bool
    {
        return $this->betterReflectionClass->isUserDefined();
    }

    /** @psalm-mutation-free */
    public function isInstantiable(): bool
    {
        return $this->betterReflectionClass->isInstantiable();
    }

    /** @psalm-mutation-free */
    public function isCloneable(): bool
    {
        return $this->betterReflectionClass->isCloneable();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionClass->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionClass->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionClass->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionClass->getDocComment() ?? false;
    }

    /**
     * @psalm-mutation-free
     * @return ReflectionMethod|null
     */
    public function getConstructor(): CoreReflectionMethod|null
    {
        $constructor = $this->betterReflectionClass->getConstructor();

        if ($constructor === null) {
            return null;
        }

        return new ReflectionMethod($constructor);
    }

    /**
     * {@inheritDoc}
     */
    public function hasMethod($name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionClass->hasMethod($name);
    }

    /**
     * @param string $name
     * @return ReflectionMethod
     */
    public function getMethod($name): CoreReflectionMethod
    {
        $method = $name !== '' ? $this->betterReflectionClass->getMethod($name) : null;

        if ($method === null) {
            throw new CoreReflectionException(sprintf('Method %s::%s() does not exist', $this->betterReflectionClass->getName(), $name));
        }

        return new ReflectionMethod($method);
    }

    /**
     * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
     * @return ReflectionMethod[]
     */
    public function getMethods($filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(
            static fn (BetterReflectionMethod $method): ReflectionMethod => new ReflectionMethod($method),
            $this->betterReflectionClass->getMethods($filter ?? 0),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name): bool
    {
        if ($name === '') {
            return false;
        }

        return $this->betterReflectionClass->hasProperty($name);
    }

    /**
     * @param string $name
     * @return ReflectionProperty
     */
    public function getProperty($name): \ReflectionProperty
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionClass->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionClass->getName(), $name));
        }

        return new ReflectionProperty($betterReflectionProperty);
    }

    /**
     * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
     * @return ReflectionProperty[]
     */
    public function getProperties($filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(
            static fn (BetterReflectionProperty $property): ReflectionProperty => new ReflectionProperty($property),
            $this->betterReflectionClass->getProperties($filter ?? 0),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function hasConstant($name): bool
    {
        if ($name === '') {
            return false;
        }

        if ($this->betterReflectionClass instanceof BetterReflectionEnum && $this->betterReflectionClass->hasCase($name)) {
            return true;
        }

        return $this->betterReflectionClass->hasConstant($name);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, mixed>
     *
     * @psalm-mutation-free
     */
    public function getConstants(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(
            fn (BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed => $this->getConstantValue($betterConstantOrEnumCase),
            $this->filterBetterReflectionClassConstants($filter),
        );
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getConstant($name)
    {
        if ($name === '') {
            return false;
        }

        if ($this->betterReflectionClass instanceof BetterReflectionEnum) {
            $enumCase = $this->betterReflectionClass->getCase($name);
            if ($enumCase !== null) {
                return $this->getConstantValue($enumCase);
            }
        }

        $betterReflectionConstant = $this->betterReflectionClass->getConstant($name);
        if ($betterReflectionConstant === null) {
            return false;
        }

        return $betterReflectionConstant->getValue();
    }

    /** @psalm-pure */
    private function getConstantValue(BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed
    {
        if ($betterConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            return constant(sprintf('%s::%s', $betterConstantOrEnumCase->getDeclaringClass()->getName(), $betterConstantOrEnumCase->getName()));
        }

        return $betterConstantOrEnumCase->getValue();
    }

    /**
     * @param string $name
     * @return ReflectionClassConstant|false
     */
    #[ReturnTypeWillChange]
    public function getReflectionConstant($name)
    {
        if ($name === '') {
            return false;
        }

        if ($this->betterReflectionClass instanceof BetterReflectionEnum) {
            $enumCase = $this->betterReflectionClass->getCase($name);
            if ($enumCase !== null) {
                return new ReflectionClassConstant($enumCase);
            }
        }

        $betterReflectionConstant = $this->betterReflectionClass->getConstant($name);
        if ($betterReflectionConstant === null) {
            return false;
        }

        return new ReflectionClassConstant($betterReflectionConstant);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return list<ReflectionClassConstant>
     *
     * @psalm-mutation-free
     */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnum): ReflectionClassConstant => new ReflectionClassConstant($betterConstantOrEnum),
            $this->filterBetterReflectionClassConstants($filter),
        ));
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, BetterReflectionClassConstant|BetterReflectionEnumCase>
     *
     * @psalm-mutation-free
     */
    private function filterBetterReflectionClassConstants(int|null $filter): array
    {
        $reflectionConstants = $this->betterReflectionClass->getConstants($filter ?? 0);

        if (
            $this->betterReflectionClass instanceof BetterReflectionEnum
            && (
                $filter === null
                || $filter & ReflectionClassConstant::IS_PUBLIC
            )
        ) {
            $reflectionConstants += $this->betterReflectionClass->getCases();
        }

        return $reflectionConstants;
    }

    /**
     * @psalm-mutation-free
     * @return array<class-string, self>
     */
    public function getInterfaces(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(
            static fn (BetterReflectionClass $interface): self => new self($interface),
            $this->betterReflectionClass->getInterfaces(),
        );
    }

    /**
     * @return list<class-string>
     *
     * @psalm-mutation-free
     */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionClass->getInterfaceNames();
    }

    /** @psalm-mutation-free */
    public function isInterface(): bool
    {
        return $this->betterReflectionClass->isInterface();
    }

    /**
     * @psalm-mutation-free
     * @return array<trait-string, self>
     */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionClass->getTraits();

        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn (BetterReflectionClass $trait): string => $trait->getName(), $traits);

        /** @psalm-suppress ImpureFunctionCall */
        return array_combine(
            $traitNames,
            array_map(static fn (BetterReflectionClass $trait): self => new self($trait), $traits),
        );
    }

    /**
     * @return list<trait-string>
     *
     * @psalm-mutation-free
     */
    public function getTraitNames(): array
    {
        return $this->betterReflectionClass->getTraitNames();
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     *
     * @psalm-mutation-free
     */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionClass->getTraitAliases();
    }

    /** @psalm-mutation-free */
    public function isTrait(): bool
    {
        return $this->betterReflectionClass->isTrait();
    }

    /** @psalm-mutation-free */
    public function isAbstract(): bool
    {
        return $this->betterReflectionClass->isAbstract();
    }

    /** @psalm-mutation-free */
    public function isFinal(): bool
    {
        return $this->betterReflectionClass->isFinal();
    }

    /** @psalm-mutation-free */
    public function isReadOnly(): bool
    {
        return $this->betterReflectionClass->isReadOnly();
    }

    /** @psalm-mutation-free */
    public function getModifiers(): int
    {
        return $this->betterReflectionClass->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object): bool
    {
        return $this->betterReflectionClass->isInstance($object);
    }

    /**
     * @return object
     */
    #[ReturnTypeWillChange]
    public function newInstance(mixed ...$args)
    {
        ClassExistenceChecker::classExists($this->getName(), true);
        $reflection = new CoreReflectionClass($this->getName());

        return $reflection->newInstance(...$args);
    }

    public function newInstanceWithoutConstructor(): object
    {
        ClassExistenceChecker::classExists($this->getName(), true);
        $reflection = new CoreReflectionClass($this->getName());

        return $reflection->newInstanceWithoutConstructor();
    }

    public function newInstanceArgs(array $args = null): object
    {
        ClassExistenceChecker::classExists($this->getName(), true);
        $reflection = new CoreReflectionClass($this->getName());

        return $reflection->newInstanceArgs($args);
    }

    /**
     * @return self|false
     */
    #[ReturnTypeWillChange]
    public function getParentClass()
    {
        $parentClass = $this->betterReflectionClass->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new self($parentClass);
    }

    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class): bool
    {
        $realParentClassNames = $this->betterReflectionClass->getParentClassNames();

        $parentClassNames = array_combine(array_map(static fn (string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);

        $className           = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);

        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;

        if ($this->betterReflectionClass->isSubclassOf($realParentClassName)) {
            return true;
        }

        return $this->implementsInterface($className);
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionClass->getStaticProperties();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStaticPropertyValue($name, $default = null)
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionClass->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            if (func_num_args() === 2) {
                return $default;
            }

            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionClass->getName(), $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionClass->getName(), $name));
        }

        return $property->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue($name, $value): void
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionClass->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionClass->getName(), $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionClass->getName(), $name));
        }

        $property->setValue($value);
    }

    /**
     * @return array<non-empty-string, mixed>
     *
     * @psalm-mutation-free
     */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionClass->getDefaultProperties();
    }

    /** @psalm-mutation-free */
    public function isIterateable(): bool
    {
        return $this->betterReflectionClass->isIterateable();
    }

    /** @psalm-mutation-free */
    public function isIterable(): bool
    {
        return $this->isIterateable();
    }

    /**
     * @param \ReflectionClass|string $interface
     */
    public function implementsInterface($interface): bool
    {
        $realInterfaceNames = $this->betterReflectionClass->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static fn (string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);

        $interfaceName           = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedInterfaceName = strtolower($interfaceName);

        $realInterfaceName = $interfaceNames[$lowercasedInterfaceName] ?? $interfaceName;

        return $this->betterReflectionClass->implementsInterface($realInterfaceName);
    }

    /** @psalm-mutation-free */
    public function getExtension(): CoreReflectionExtension|null
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getExtensionName()
    {
        return $this->betterReflectionClass->getExtensionName() ?? false;
    }

    /** @psalm-mutation-free */
    public function inNamespace(): bool
    {
        return $this->betterReflectionClass->inNamespace();
    }

    /** @psalm-mutation-free */
    public function getNamespaceName(): string
    {
        return $this->betterReflectionClass->getNamespaceName() ?? '';
    }

    /** @psalm-mutation-free */
    public function getShortName(): string
    {
        return $this->betterReflectionClass->getShortName();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(string|null $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags !== 0) {
            $attributes = $this->betterReflectionClass->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionClass->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionClass->getAttributes();
        }

        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute|FakeReflectionAttribute => ReflectionAttributeFactory::create($betterReflectionAttribute), $attributes);
    }

    /** @psalm-mutation-free */
    public function isEnum(): bool
    {
        return $this->betterReflectionClass->isEnum();
    }
}
