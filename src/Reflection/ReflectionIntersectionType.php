<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;
use function implode;

/** @psalm-immutable */
class ReflectionIntersectionType extends ReflectionType
{
    /** @var non-empty-list<ReflectionNamedType> */
    private array $types;

    /** @internal
     * @param \Roave\BetterReflection\Reflection\ReflectionParameter|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionEnum|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionClassConstant $owner */
    public function __construct(Reflector $reflector, $owner, IntersectionType $type)
    {
        /** @var non-empty-list<ReflectionNamedType> $types */
        $types = array_map(static function ($type) use ($reflector, $owner): ReflectionNamedType {
            $type = ReflectionType::createFromNode($reflector, $owner, $type);
            assert($type instanceof ReflectionNamedType);

            return $type;
        }, $type->types);
        $this->types = $types;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        return [
            'types' => array_map(
                static fn (ReflectionNamedType $type) => $type->exportToCache(),
                $this->types,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty|ReflectionClassConstant $owner
     */
    public static function importFromCache(Reflector $reflector, array $data, $owner): self
    {
        $reflection = new CoreReflectionClass(self::class);
        /** @var self $ref */
        $ref = $reflection->newInstanceWithoutConstructor();
        $ref->types = array_map(
            static fn (array $typeData) => ReflectionNamedType::importFromCache($reflector, $typeData, $owner),
            $data['types'],
        );

        return $ref;
    }

    /** @internal
     * @param \Roave\BetterReflection\Reflection\ReflectionParameter|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionEnum|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionClassConstant $owner
     * @return static */
    public function withOwner($owner)
    {
        $clone = clone $this;

        $clone->types = array_map(static fn (ReflectionNamedType $type): ReflectionNamedType => $type->withOwner($owner), $clone->types);

        return $clone;
    }

    /** @return non-empty-list<ReflectionNamedType> */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return false
     */
    public function allowsNull(): bool
    {
        return false;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        // @infection-ignore-all UnwrapArrayMap: It works without array_map() as well but this is less magical
        return implode('&', array_map(static fn (ReflectionNamedType $type): string => $type->__toString(), $this->types));
    }
}
