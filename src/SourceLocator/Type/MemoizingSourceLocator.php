<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;

use function array_key_exists;
use function array_key_first;
use function count;
use function spl_object_id;
use function sprintf;

final class MemoizingSourceLocator implements SourceLocator
{
    /** @var array<string, Reflection|null> indexed by reflector key and identifier cache key */
    private array $cacheByIdentifierKeyAndOid = [];

    /** @var array<string, list<Reflection>> indexed by reflector key and identifier type cache key */
    private array $cacheByIdentifierTypeKeyAndOid = [];

    /** @param int|null $maxCachedEntries maximum number of entries kept in each cache, evicted by LRU; null means unlimited */
    public function __construct(
        private SourceLocator $wrappedSourceLocator,
        private int|null $maxCachedEntries = null,
    ) {
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): Reflection|null
    {
        $cacheKey = sprintf('%s_%s', $this->reflectorCacheKey($reflector), $this->identifierToCacheKey($identifier));

        if (array_key_exists($cacheKey, $this->cacheByIdentifierKeyAndOid)) {
            $reflection = $this->cacheByIdentifierKeyAndOid[$cacheKey];

            if ($this->maxCachedEntries !== null) {
                // LRU bookkeeping: re-insert the entry at the end so genuinely cold
                // entries are evicted first, not the ones cached earliest
                unset($this->cacheByIdentifierKeyAndOid[$cacheKey]);
                $this->cacheByIdentifierKeyAndOid[$cacheKey] = $reflection;
            }

            return $reflection;
        }

        $this->evictLeastRecentlyUsed($this->cacheByIdentifierKeyAndOid);

        return $this->cacheByIdentifierKeyAndOid[$cacheKey]
            = $this->wrappedSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /** @return list<Reflection> */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        $cacheKey = sprintf('%s_%s', $this->reflectorCacheKey($reflector), $this->identifierTypeToCacheKey($identifierType));

        if (array_key_exists($cacheKey, $this->cacheByIdentifierTypeKeyAndOid)) {
            $reflections = $this->cacheByIdentifierTypeKeyAndOid[$cacheKey];

            if ($this->maxCachedEntries !== null) {
                unset($this->cacheByIdentifierTypeKeyAndOid[$cacheKey]);
                $this->cacheByIdentifierTypeKeyAndOid[$cacheKey] = $reflections;
            }

            return $reflections;
        }

        $this->evictLeastRecentlyUsed($this->cacheByIdentifierTypeKeyAndOid);

        return $this->cacheByIdentifierTypeKeyAndOid[$cacheKey]
            = $this->wrappedSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }

    /** @param array<string, Reflection|list<Reflection>|null> $cache */
    private function evictLeastRecentlyUsed(array &$cache): void
    {
        if ($this->maxCachedEntries === null) {
            return;
        }

        while (count($cache) >= $this->maxCachedEntries) {
            $oldestKey = array_key_first($cache);
            if ($oldestKey === null) {
                break;
            }

            unset($cache[$oldestKey]);
        }
    }

    private function reflectorCacheKey(Reflector $reflector): string
    {
        return sprintf('type:%s#oid:%d', $reflector::class, spl_object_id($reflector));
    }

    private function identifierToCacheKey(Identifier $identifier): string
    {
        return sprintf(
            '%s#name:%s',
            $this->identifierTypeToCacheKey($identifier->getType()),
            $identifier->getName(),
        );
    }

    private function identifierTypeToCacheKey(IdentifierType $identifierType): string
    {
        return sprintf('type:%s', $identifierType->getName());
    }
}
