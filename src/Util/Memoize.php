<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Util;

use Closure;

/**
 * @internal do not touch: you have been warned.
 *
 * @template T
 * @psalm-immutable this context is a pure snapshot of a pure function, therefore transitively pure
 */
final class Memoize
{
    /**
     * @var T
     * @psalm-suppress PropertyNotSetInConstructor
     * @phpstan-ignore property.uninitializedReadonly
     * @readonly
     */
    private $cached;

    /** @var (pure-Closure(): T)|null */
    private $cb;

    /** @param pure-Closure(): T $cb */
    public function __construct(Closure $cb)
    {
        $this->cb = $cb;
    }

    /** @return T */
    public function get()
    {
        if ($this->cb) {
            /** @psalm-suppress InaccessibleProperty */
            /** @phpstan-ignore property.readOnlyAssignNotInConstructor */
            $this->cached = ($this->cb)();
            /** @psalm-suppress InaccessibleProperty */
            $this->cb = null;
        }

        return $this->cached;
    }
}
