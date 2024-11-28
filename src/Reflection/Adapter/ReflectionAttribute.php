<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Attribute;
use OutOfBoundsException;
use ReflectionAttribute as CoreReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

use function sprintf;

/** @template-extends CoreReflectionAttribute<object> */
final class ReflectionAttribute extends CoreReflectionAttribute
{
    public function __construct(private BetterReflectionAttribute $betterReflectionAttribute)
    {
        unset($this->name);
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionAttribute->getName();
    }

    /**
     * @return int-mask-of<Attribute::TARGET_*>
     *
     * @psalm-mutation-free
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function getTarget(): int
    {
        return $this->betterReflectionAttribute->getTarget();
    }

    /** @psalm-mutation-free */
    public function isRepeated(): bool
    {
        return $this->betterReflectionAttribute->isRepeated();
    }

    /** @return array<int|string, mixed> */
    public function getArguments(): array
    {
        return $this->betterReflectionAttribute->getArguments();
    }

    /** @return never */
    public function newInstance(): object
    {
        throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionAttribute->__toString();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionAttribute->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
