<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use Attribute;
use OutOfBoundsException;
use PhpParser\Node\Expr;
use ReflectionAttribute as CoreReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

use function sprintf;

/** @template-extends CoreReflectionAttribute<object> */
final class ReflectionAttribute extends CoreReflectionAttribute
{
    private BetterReflectionAttribute $betterReflectionAttribute;
    public const TARGET_CONSTANT_COMPATIBILITY = 64;

    public function __construct(BetterReflectionAttribute $betterReflectionAttribute)
    {
        $this->betterReflectionAttribute = $betterReflectionAttribute;
        unset($this->name);
    }

    public function getBetterReflection(): BetterReflectionAttribute
    {
        return $this->betterReflectionAttribute;
    }

    /** @psalm-mutation-free */
    public function getName(): string
    {
        return $this->betterReflectionAttribute->getName();
    }

    /**
     * @return int-mask-of<Attribute::TARGET_*>|self::TARGET_CONSTANT_COMPATIBILITY
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

    /**
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        return $this->betterReflectionAttribute->getArguments();
    }

    /** @return array<int|string, Expr> */
    public function getArgumentsExpressions(): array
    {
        return $this->betterReflectionAttribute->getArgumentsExpressions();
    }

    public function newInstance(): object
    {
        $class = $this->getName();

        return new $class(...$this->getArguments());
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionAttribute->__toString();
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionAttribute->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
