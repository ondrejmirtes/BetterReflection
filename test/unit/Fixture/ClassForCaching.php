<?php

namespace Roave\BetterReflectionTest;

interface TimestampsInterface
{
    public \DateTimeImmutable $createdAt { get; }
}

trait Timestamps
{
    public private(set) \DateTimeImmutable $createdAt {
        get {
            return $this->createdAt ??= new \DateTimeImmutable();
        }
    }
}

#[Attr(1, 2, 3)]
class Example implements TimestampsInterface
{
    use Timestamps;

    public const int FOO_C = 2;

    public function doFoo(int $param): void {
        echo $this->createdAt;
    }
}
