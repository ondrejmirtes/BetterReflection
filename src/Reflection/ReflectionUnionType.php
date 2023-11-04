<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;
use function implode;
use function sprintf;

/** @psalm-immutable */
class ReflectionUnionType extends ReflectionType
{
    /** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */
    private $types;

    /** @internal
     * @param \Roave\BetterReflection\Reflection\ReflectionParameter|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionEnum|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionClassConstant $owner */
    public function __construct(Reflector $reflector, $owner, UnionType $type)
    {
        /** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> $types */
        $types = array_map(static function ($type) use ($reflector, $owner) {
            $type = ReflectionType::createFromNode($reflector, $owner, $type);
            assert($type instanceof ReflectionNamedType || $type instanceof ReflectionIntersectionType);

            return $type;
        }, $type->types);
        $this->types = $types;
    }

    /** @internal
     * @param \Roave\BetterReflection\Reflection\ReflectionParameter|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionEnum|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionClassConstant $owner
     * @return $this */
    public function withOwner($owner)
    {
        $clone = clone $this;

        foreach ($clone->types as $typeNo => $innerType) {
            $clone->types[$typeNo] = $innerType->withOwner($owner);
        }

        return $clone;
    }

    /** @return non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function allowsNull(): bool
    {
        foreach ($this->types as $type) {
            if ($type->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return implode('|', array_map(static function (ReflectionType $type): string {
            if ($type instanceof ReflectionIntersectionType) {
                return sprintf('(%s)', $type->__toString());
            }

            return $type->__toString();
        }, $this->types));
    }
}
