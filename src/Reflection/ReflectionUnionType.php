<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;
use function implode;
use function sprintf;

/** @psalm-immutable */
class ReflectionUnionType extends ReflectionType
{
    /** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */
    private array $types;

    /** @internal */
    public function __construct(
        Reflector $reflector,
        ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty|ReflectionClassConstant $owner,
        UnionType $type,
    ) {
        /** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> $types */
        $types = array_map(static function (Identifier|Name|IntersectionType $type) use ($reflector, $owner): ReflectionNamedType|ReflectionIntersectionType {
            $type = ReflectionType::createFromNode($reflector, $owner, $type);
            assert($type instanceof ReflectionNamedType || $type instanceof ReflectionIntersectionType);

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
                static fn (ReflectionNamedType|ReflectionIntersectionType $type) => [
                    'class' => get_class($type),
                    'data' => $type->exportToCache(),
                ],
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
            static function (array $typeData) use ($reflector, $owner): ReflectionNamedType|ReflectionIntersectionType {
                $typeClass = $typeData['class'];
                return $typeClass::importFromCache($reflector, $typeData['data'], $owner);
            },
            $data['types'],
        );

        return $ref;
    }

    /** @internal */
    public function withOwner(ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty|ReflectionClassConstant $owner): static
    {
        $clone = clone $this;

        $clone->types = array_map(static fn (ReflectionNamedType|ReflectionIntersectionType $type): ReflectionNamedType|ReflectionIntersectionType => $type->withOwner($owner), $clone->types);

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
