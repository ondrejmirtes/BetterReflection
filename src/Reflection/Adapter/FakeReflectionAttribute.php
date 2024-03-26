<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use PhpParser\Node\Expr;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

final class FakeReflectionAttribute
{
    /**
     * @var BetterReflectionAttribute
     */
    private $betterReflectionAttribute;
    public function __construct(BetterReflectionAttribute $betterReflectionAttribute)
    {
        $this->betterReflectionAttribute = $betterReflectionAttribute;
    }

    public function getName(): string
    {
        return $this->betterReflectionAttribute->getName();
    }

    public function getTarget(): int
    {
        return $this->betterReflectionAttribute->getTarget();
    }

    public function isRepeated(): bool
    {
        return $this->betterReflectionAttribute->isRepeated();
    }

    /**
     * @deprecated Use getArgumentsExpressions()
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

    /** @deprecated */
    public function newInstance(): object
    {
        $class = $this->getName();

        return new $class(...$this->getArguments());
    }

    public function __toString(): string
    {
        return $this->betterReflectionAttribute->__toString();
    }
}
