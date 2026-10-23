<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Ast\Parser;

use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\Token;

use function array_key_exists;
use function array_key_first;
use function count;
use function hash;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

/** @internal */
final class MemoizingParser implements Parser
{
    private Parser $wrappedParser;
    /**
     * @var int|null
     */
    private $maxCachedEntries = null;
    /** @var array<string, array{string, Token[]}> indexed by source hash */
    private array $sourceHashToAst = [];

    /** @var Token[] */
    private array $lastTokens = [];

    /** @param int|null $maxCachedEntries maximum number of sources kept in the cache, evicted by LRU; null means unlimited */
    public function __construct(Parser $wrappedParser, ?int $maxCachedEntries = null)
    {
        $this->wrappedParser = $wrappedParser;
        $this->maxCachedEntries = $maxCachedEntries;
    }

    public function parse(string $code, ?\PhpParser\ErrorHandler $errorHandler = null): ?array
    {
        // note: this code is mathematically buggy by default, as we are using a hash to identify
        //       cache entries. The string length is added to further reduce likeliness (although
        //       already imperceptible) of key collisions.
        //       In the "real world", this code will work just fine.
        $hash = sprintf('%s:%d', hash('sha256', $code), strlen($code));

        if (array_key_exists($hash, $this->sourceHashToAst)) {
            [$serializedAst, $tokens] = $this->sourceHashToAst[$hash];

            if ($this->maxCachedEntries !== null) {
                // LRU bookkeeping: re-insert the entry at the end so genuinely cold
                // sources are evicted first, not the ones cached earliest
                unset($this->sourceHashToAst[$hash]);
                $this->sourceHashToAst[$hash] = [$serializedAst, $tokens];
            }

            /** @var Node\Stmt[]|null $ast */
            $ast              = unserialize($serializedAst);
            $this->lastTokens = $tokens;

            return $ast;
        }

        $ast    = $this->wrappedParser->parse($code, $errorHandler);
        $tokens = $this->wrappedParser->getTokens();

        if ($this->maxCachedEntries !== null) {
            while (count($this->sourceHashToAst) >= $this->maxCachedEntries) {
                $oldestKey = array_key_first($this->sourceHashToAst);
                if ($oldestKey === null) {
                    break;
                }

                unset($this->sourceHashToAst[$oldestKey]);
            }
        }

        $this->sourceHashToAst[$hash] = [serialize($ast), $tokens];
        $this->lastTokens             = $tokens;

        return $ast;
    }

    /** @return Token[] */
    public function getTokens(): array
    {
        return $this->lastTokens;
    }
}
