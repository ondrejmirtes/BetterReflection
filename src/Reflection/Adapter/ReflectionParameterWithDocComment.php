<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

/**
 * ReflectionParameter::getDocComment() exists since PHP 8.6.
 * This class is not downgraded so the native return type stays compatible with the parent.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-immutable
 */
final class ReflectionParameterWithDocComment extends ReflectionParameter
{
    public function getDocComment(): string|false
    {
        return $this->getBetterReflection()->getDocComment() ?? false;
    }
}
