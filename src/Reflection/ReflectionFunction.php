<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use PhpParser\Node;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\Adapter\Exception\NotImplemented;
use Roave\BetterReflection\Reflection\Exception\FunctionDoesNotExist;
use Roave\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;

use function assert;
use function function_exists;

/** @psalm-immutable */
class ReflectionFunction implements Reflection
{
    use ReflectionFunctionAbstract;

    public const CLOSURE_NAME = '{closure}';

    /**
     * @var bool
     */
    private $isStatic;
    /**
     * @var \Roave\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \Roave\BetterReflection\SourceLocator\Located\LocatedSource
     */
    private $locatedSource;
    /**
     * @var string|null
     */
    private $namespace = null;
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction $node
     */
    private function __construct(Reflector $reflector, $node, LocatedSource $locatedSource, ?string $namespace = null)
    {
        $this->reflector = $reflector;
        $this->locatedSource = $locatedSource;
        $this->namespace = $namespace;
        assert($node instanceof Node\Stmt\Function_ || $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction);
        $name = $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction
            ? self::CLOSURE_NAME
            : $node->name->name;
        assert($name !== '');
        $this->name = $name;
        $this->fillFromNode($node);
        $isClosure = $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction;
        $this->isStatic    = $isClosure && $node->static;
        $this->isClosure   = $isClosure;
        $this->isGenerator = $this->nodeIsOrContainsYield($node);
    }

    /**
     * @deprecated Use Reflector instead.
     *
     * @throws IdentifierNotFound
     */
    public static function createFromName(string $functionName): self
    {
        return (new BetterReflection())->reflector()->reflectFunction($functionName);
    }

    /** @throws IdentifierNotFound */
    public static function createFromClosure(Closure $closure): self
    {
        $configuration = new BetterReflection();

        return (new DefaultReflector(new AggregateSourceLocator([
            $configuration->sourceLocator(),
            new ClosureSourceLocator($closure, $configuration->phpParser()),
        ])))->reflectFunction(self::CLOSURE_NAME);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionFunctionStringCast::toString($this);
    }

    /** @internal
     * @param \PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction $node */
    public static function createFromNode(Reflector $reflector, $node, LocatedSource $locatedSource, ?string $namespace = null): self
    {
        return new self($reflector, $node, $locatedSource, $namespace);
    }

    /**
     * Get the "short" name of the function (e.g. for A\B\foo, this will return
     * "foo").
     *
     * @return non-empty-string
     */
    public function getShortName(): string
    {
        return $this->name;
    }

    /**
     * Check to see if this function has been disabled (by the PHP INI file
     * directive `disable_functions`).
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function, because you cannot disable user-defined functions.
     *
     * @see https://php.net/manual/en/ini.core.php#ini.disable-functions
     *
     * @todo https://github.com/Roave/BetterReflection/issues/14
     */
    public function isDisabled(): bool
    {
        return false;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     */
    public function getClosure(): Closure
    {
        $this->assertIsNoClosure();

        $functionName = $this->getName();

        $this->assertFunctionExist($functionName);

        return static function (...$args) use ($functionName) {
            return $functionName(...$args);
        };
    }

    /**
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     * @param mixed ...$args
     * @return mixed
     */
    public function invoke(...$args)
    {
        return $this->invokeArgs($args);
    }

    /**
     * @param array<mixed> $args
     *
     * @throws NotImplemented
     * @throws FunctionDoesNotExist
     * @return mixed
     */
    public function invokeArgs(array $args = [])
    {
        $this->assertIsNoClosure();

        $functionName = $this->getName();

        $this->assertFunctionExist($functionName);

        return $functionName(...$args);
    }

    /** @throws NotImplemented */
    private function assertIsNoClosure(): void
    {
        if ($this->isClosure()) {
            throw new NotImplemented('Not implemented for closures');
        }
    }

    /** @throws FunctionDoesNotExist */
    private function assertFunctionExist(string $functionName): void
    {
        if (! function_exists($functionName)) {
            throw FunctionDoesNotExist::fromName($functionName);
        }
    }
}
