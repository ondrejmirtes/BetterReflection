<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Attribute;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;

/** @psalm-immutable */
class ReflectionAttribute
{
    /** @var class-string */
    private string $name;

    /** @var array<int|string, Node\Expr> */
    private array $arguments;

    /** @internal */
    public function __construct(
        private Reflector $reflector,
        Node\Attribute $node,
        private ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner,
        private bool $isRepeated,
    ) {
        /** @var class-string $name */
        $name = $node->name->toString();

        $this->name = $name;

        $arguments = [];
        foreach ($node->args as $argNo => $arg) {
            $arguments[$arg->name?->toString() ?? $argNo] = $arg->value;
        }

        $this->arguments = $arguments;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        $br = new BetterReflection();

        return [
            'name' => $this->name,
            'isRepeated' => $this->isRepeated,
            'arguments' => array_map(
                static fn (Expr $expr) => $br->printer()->prettyPrintExpr($expr),
                $this->arguments,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner
     */
    public static function importFromCache(Reflector $reflector, array $data, $owner): self
    {
        $reflection = new CoreReflectionClass(self::class);
        /** @var self $ref */
        $ref = $reflection->newInstanceWithoutConstructor();
        $ref->reflector = $reflector;

        $ref->owner = $owner;
        $ref->name = $data['name'];
        $ref->isRepeated = $data['isRepeated'];

        $br = new BetterReflection();
        $ref->arguments = array_map(
            static fn (string $exprCode) => $br->phpParser()->parse('<?php ' . $exprCode . ';')[0]->expr,
            $data['arguments'],
        );

        return $ref;
    }

    /** @internal */
    public function withOwner(ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner): self
    {
        $clone        = clone $this;
        $clone->owner = $owner;

        return $clone;
    }

    /** @return class-string */
    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): ReflectionClass
    {
        return $this->reflector->reflectClass($this->getName());
    }

    /** @return array<int|string, Node\Expr> */
    public function getArgumentsExpressions(): array
    {
        return $this->arguments;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        return array_map(static fn (Node\Expr $value): mixed => $compiler->__invoke($value, $context)->value, $this->arguments);
    }

    /** @return int-mask-of<Attribute::TARGET_*>|ReflectionAttributeAdapter::TARGET_CONSTANT_COMPATIBILITY */
    public function getTarget(): int
    {
        return match (true) {
            $this->owner instanceof ReflectionClass => Attribute::TARGET_CLASS,
            $this->owner instanceof ReflectionFunction => Attribute::TARGET_FUNCTION,
            $this->owner instanceof ReflectionConstant => ReflectionAttributeAdapter::TARGET_CONSTANT_COMPATIBILITY,
            $this->owner instanceof ReflectionMethod => Attribute::TARGET_METHOD,
            $this->owner instanceof ReflectionProperty => Attribute::TARGET_PROPERTY,
            $this->owner instanceof ReflectionClassConstant => Attribute::TARGET_CLASS_CONSTANT,
            $this->owner instanceof ReflectionEnumCase => Attribute::TARGET_CLASS_CONSTANT,
            // @infection-ignore-all InstanceOf_: There's no other option
            $this->owner instanceof ReflectionParameter => Attribute::TARGET_PARAMETER,
            default => throw new LogicException('unknown owner'), // @phpstan-ignore-line
        };
    }

    public function isRepeated(): bool
    {
        return $this->isRepeated;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionAttributeStringCast::toString($this);
    }
}
