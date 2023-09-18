<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\BetterReflection\Util\FindReflectionOnLine;

use const PHP_VERSION_ID;

final class BetterReflection
{
    /**
     * @var int
     */
    public static $phpVersion = PHP_VERSION_ID;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\SourceLocator|null
     */
    private static $sharedSourceLocator = null;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\SourceLocator|null
     */
    private $sourceLocator = null;

    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector|null
     */
    private static $sharedReflector = null;

    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector|null
     */
    private $reflector = null;

    /**
     * @var \PhpParser\Parser|null
     */
    private static $sharedPhpParser = null;

    /**
     * @var \PhpParser\Parser|null
     */
    private $phpParser = null;

    /**
     * @var AstLocator|null
     */
    private $astLocator = null;

    /**
     * @var \PHPStan\BetterReflection\Util\FindReflectionOnLine|null
     */
    private $findReflectionOnLine = null;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber|null
     */
    private $sourceStubber = null;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber|null
     */
    private static $sharedSourceStubber = null;

    public static function populate(int $phpVersion, SourceLocator $sourceLocator, Reflector $classReflector, Parser $phpParser, SourceStubber $sourceStubber): void
    {
        self::$phpVersion          = $phpVersion;
        self::$sharedSourceLocator = $sourceLocator;
        self::$sharedReflector     = $classReflector;
        self::$sharedPhpParser     = $phpParser;
        self::$sharedSourceStubber = $sourceStubber;
    }

    public function __construct()
    {
        $this->sourceLocator = self::$sharedSourceLocator;
        $this->reflector     = self::$sharedReflector;
        $this->phpParser     = self::$sharedPhpParser;
        $this->sourceStubber = self::$sharedSourceStubber;
    }

    public function sourceLocator(): SourceLocator
    {
        $astLocator    = $this->astLocator();
        $sourceStubber = $this->sourceStubber();

        return $this->sourceLocator
            ?? $this->sourceLocator = new MemoizingSourceLocator(new AggregateSourceLocator([
                new PhpInternalSourceLocator($astLocator, $sourceStubber),
                new EvaledCodeSourceLocator($astLocator, $sourceStubber),
                new AutoloadSourceLocator($astLocator, $this->phpParser()),
            ]));
    }

    public function reflector(): Reflector
    {
        return $this->reflector
            ?? $this->reflector = new DefaultReflector($this->sourceLocator());
    }

    public function phpParser(): Parser
    {
        return $this->phpParser
            ?? $this->phpParser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7, new Emulative([
                'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
            ]));
    }

    public function astLocator(): AstLocator
    {
        return $this->astLocator
            ?? $this->astLocator = new AstLocator($this->phpParser());
    }

    public function findReflectionsOnLine(): FindReflectionOnLine
    {
        return $this->findReflectionOnLine
            ?? $this->findReflectionOnLine = new FindReflectionOnLine($this->sourceLocator(), $this->astLocator());
    }

    public function sourceStubber(): SourceStubber
    {
        return $this->sourceStubber
            ?? $this->sourceStubber = new AggregateSourceStubber(new PhpStormStubsSourceStubber($this->phpParser(), self::$phpVersion), new ReflectionSourceStubber());
    }
}
