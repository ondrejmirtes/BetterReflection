<?php

namespace Roave\BetterReflectionTest\Fixture;

class AsymetricVisibilityClass
{
    public public(set) string $publicPublicSet = 'string';
    public protected(set) string $publicProtectedSet = 'string';
    public private(set) string $publicPrivateSet = 'string';
    protected protected(set) int $protectedProtectedSet = 123;
    protected private(set) int $protectedPrivateSet = 123;
    private private(set) bool $privatePrivateSet = true;

    public function __construct(
        public public(set) string $promotedPublicPublicSet,
        public protected(set) string $promotedPublicProtectedSet,
        public private(set) string $promotedPublicPrivateSet,
        protected protected(set) int $promotedProtectedProtectedSet,
        protected private(set) int $promotedProtectedPrivateSet,
        private private(set) bool $promotedPrivatePrivateSet,
    )
    {
    }
}

