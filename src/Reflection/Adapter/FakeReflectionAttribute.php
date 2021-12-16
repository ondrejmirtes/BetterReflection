<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

final class FakeReflectionAttribute
{
    public function __construct(private BetterReflectionAttribute $betterReflectionAttribute)
    {
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
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        return $this->betterReflectionAttribute->getArguments();
    }

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
