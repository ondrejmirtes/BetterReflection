<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionObject as CoreReflectionObject;
use ReturnTypeWillChange;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Roave\BetterReflection\Util\FileHelper;
use ValueError;

use function array_combine;
use function array_map;
use function array_values;
use function func_num_args;
use function sprintf;
use function strtolower;

use const PHP_VERSION_ID;

/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionObject extends CoreReflectionObject
{
    public function __construct(private BetterReflectionObject $betterReflectionObject)
    {
        unset($this->name);
    }

    public function __toString(): string
    {
        return $this->betterReflectionObject->__toString();
    }

    public function getName(): string
    {
        return $this->betterReflectionObject->getName();
    }

    public function isInternal(): bool
    {
        return $this->betterReflectionObject->isInternal();
    }

    public function isUserDefined(): bool
    {
        return $this->betterReflectionObject->isUserDefined();
    }

    public function isInstantiable(): bool
    {
        return $this->betterReflectionObject->isInstantiable();
    }

    public function isCloneable(): bool
    {
        return $this->betterReflectionObject->isCloneable();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionObject->getFileName();

        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : false;
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionObject->getStartLine();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionObject->getEndLine();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionObject->getDocComment() ?? false;
    }

    public function getConstructor(): ReflectionMethod|null
    {
        $constructor = $this->betterReflectionObject->getConstructor();

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

        return $this->betterReflectionObject->hasMethod($this->getMethodRealName($name));
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod($name): \ReflectionMethod
    {
        $method = $name !== '' ? $this->betterReflectionObject->getMethod($this->getMethodRealName($name)) : null;

        if ($method === null) {
            throw new CoreReflectionException(sprintf('Method %s::%s() does not exist', $this->betterReflectionObject->getName(), $name));
        }

        return new ReflectionMethod($method);
    }

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    private function getMethodRealName(string $name): string
    {
        $realMethodNames = array_map(static fn (BetterReflectionMethod $method): string => $method->getName(), $this->betterReflectionObject->getMethods());

        $methodNames = array_combine(array_map(static fn (string $methodName): string => strtolower($methodName), $realMethodNames), $realMethodNames);

        $lowercasedName = strtolower($name);

        return $methodNames[$lowercasedName] ?? $name;
    }

    /**
     * {@inheritDoc}
     * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
     */
    public function getMethods($filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionMethod $method): ReflectionMethod => new ReflectionMethod($method),
            $this->betterReflectionObject->getMethods($filter ?? 0),
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

        return $this->betterReflectionObject->hasProperty($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name): \ReflectionProperty
    {
        $property = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;

        if ($property === null) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }

        return new ReflectionProperty($property);
    }

    /**
     * {@inheritDoc}
     * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
     */
    public function getProperties($filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionProperty $property): ReflectionProperty => new ReflectionProperty($property),
            $this->betterReflectionObject->getProperties($filter ?? 0),
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

        return $this->betterReflectionObject->hasConstant($name);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<string, mixed>
     */
    public function getConstants(int|null $filter = null): array
    {
        return array_map(
            static fn (BetterReflectionClassConstant $betterConstant): mixed => $betterConstant->getValue(),
            $this->betterReflectionObject->getConstants($filter ?? 0),
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

        $betterReflectionConstant = $this->betterReflectionObject->getConstant($name);
        if ($betterReflectionConstant === null) {
            return false;
        }

        return $betterReflectionConstant->getValue();
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function getReflectionConstant($name)
    {
        if ($name === '') {
            return false;
        }

        $betterReflectionConstant = $this->betterReflectionObject->getConstant($name);

        if ($betterReflectionConstant === null) {
            return false;
        }

        return new ReflectionClassConstant($betterReflectionConstant);
    }

    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return list<ReflectionClassConstant>
     */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(
            static fn (BetterReflectionClassConstant $betterConstant): ReflectionClassConstant => new ReflectionClassConstant($betterConstant),
            $this->betterReflectionObject->getConstants($filter ?? 0),
        ));
    }

    /** @return array<class-string, CoreReflectionClass> */
    public function getInterfaces(): array
    {
        return array_map(
            static fn (BetterReflectionClass $interface): ReflectionClass => new ReflectionClass($interface),
            $this->betterReflectionObject->getInterfaces(),
        );
    }

    /** @return list<class-string> */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionObject->getInterfaceNames();
    }

    public function isInterface(): bool
    {
        return $this->betterReflectionObject->isInterface();
    }

    /** @return array<trait-string, ReflectionClass> */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionObject->getTraits();

        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn (BetterReflectionClass $trait): string => $trait->getName(), $traits);

        return array_combine(
            $traitNames,
            array_map(static fn (BetterReflectionClass $trait): ReflectionClass => new ReflectionClass($trait), $traits),
        );
    }

    /** @return list<trait-string> */
    public function getTraitNames(): array
    {
        return $this->betterReflectionObject->getTraitNames();
    }

    /** @return array<string, string> */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionObject->getTraitAliases();
    }

    public function isTrait(): bool
    {
        return $this->betterReflectionObject->isTrait();
    }

    public function isAbstract(): bool
    {
        return $this->betterReflectionObject->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->betterReflectionObject->isFinal();
    }

    public function isReadOnly(): bool
    {
        return $this->betterReflectionObject->isReadOnly();
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionObject->getModifiers();
    }

    /**
     * {@inheritDoc}
     */
    public function isInstance($object): bool
    {
        return $this->betterReflectionObject->isInstance($object);
    }

    /**
     * @param mixed $arg
     * @param mixed ...$args
     *
     * @return object
     */
    #[ReturnTypeWillChange]
    public function newInstance($arg = null, ...$args)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function newInstanceWithoutConstructor()
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function newInstanceArgs(?array $args = null)
    {
        throw new Exception\NotImplemented('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getParentClass()
    {
        $parentClass = $this->betterReflectionObject->getParentClass();

        if ($parentClass === null) {
            return false;
        }

        return new ReflectionClass($parentClass);
    }

    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class): bool
    {
        $realParentClassNames = $this->betterReflectionObject->getParentClassNames();

        $parentClassNames = array_combine(array_map(static fn (string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);

        $className           = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);

        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;

        return $this->betterReflectionObject->isSubclassOf($realParentClassName);
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionObject->getStaticProperties();
    }

    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getStaticPropertyValue($name, $default = null)
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            if (func_num_args() === 2) {
                return $default;
            }

            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }

        return $property->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue($name, $value): void
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;

        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionObject->getName(), $name));
        }

        $property = new ReflectionProperty($betterReflectionProperty);

        if (! $property->isStatic()) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionObject->getName(), $name));
        }

        $property->setValue($value);
    }

    /** @return array<string, scalar|array<scalar>|null> */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionObject->getDefaultProperties();
    }

    public function isIterateable(): bool
    {
        return $this->betterReflectionObject->isIterateable();
    }

    public function isIterable(): bool
    {
        return $this->isIterateable();
    }

    /**
     * @param \ReflectionClass|string $interface
     */
    public function implementsInterface($interface): bool
    {
        $realInterfaceNames = $this->betterReflectionObject->getInterfaceNames();

        $interfaceNames = array_combine(array_map(static fn (string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);

        $interfaceName           = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedInterfaceName = strtolower($interfaceName);

        $realInterfaceName = $interfaceNames[$lowercasedInterfaceName] ?? $interfaceName;

        return $this->betterReflectionObject->implementsInterface($realInterfaceName);
    }

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
        return $this->betterReflectionObject->getExtensionName() ?? false;
    }

    public function inNamespace(): bool
    {
        return $this->betterReflectionObject->inNamespace();
    }

    public function getNamespaceName(): string
    {
        return $this->betterReflectionObject->getNamespaceName() ?? '';
    }

    public function getShortName(): string
    {
        return $this->betterReflectionObject->getShortName();
    }

    public function isAnonymous(): bool
    {
        return $this->betterReflectionObject->isAnonymous();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(string|null $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80012) {
            return [];
        }

        if ($name !== null && $flags & ReflectionAttribute::IS_INSTANCEOF) {
            $attributes = $this->betterReflectionObject->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionObject->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionObject->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function isEnum(): bool
    {
        return $this->betterReflectionObject->isEnum();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionObject->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
