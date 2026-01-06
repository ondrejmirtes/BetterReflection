<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\EnumCase;
use ReflectionClass as CoreReflectionClass;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\NodeCompiler\CompiledValue;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\Deprecated\DeprecatedHelper;
use Roave\BetterReflection\Reflection\StringCast\ReflectionEnumCaseStringCast;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\GetLastDocComment;

use function assert;
use function is_int;
use function is_string;

/** @psalm-immutable */
class ReflectionEnumCase
{
    /** @var non-empty-string */
    private string $name;

    private Node\Expr|null $value;

    /** @var list<ReflectionAttribute> */
    private array $attributes;

    /** @var non-empty-string|null */
    private string|null $docComment;

    /** @var positive-int */
    private int $startLine;

    /** @var positive-int */
    private int $endLine;

    /** @var positive-int */
    private int $startColumn;

    /** @var positive-int */
    private int $endColumn;

    /** @psalm-allow-private-mutation */
    private CompiledValue|null $compiledValue = null;

    private function __construct(
        private Reflector $reflector,
        EnumCase $node,
        private ReflectionEnum $enum,
    ) {
        $this->name = $node->name->toString();

        $this->value      = $node->expr;
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);
        $this->docComment = GetLastDocComment::forNode($node);

        $startLine = $node->getStartLine();
        assert($startLine > 0);
        $endLine = $node->getEndLine();
        assert($endLine > 0);

        $this->startLine   = $startLine;
        $this->endLine     = $endLine;
        $this->startColumn = CalculateReflectionColumn::getStartColumn($this->enum->getLocatedSource()->getSource(), $node);
        $this->endColumn   = CalculateReflectionColumn::getEndColumn($this->enum->getLocatedSource()->getSource(), $node);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        $br = new BetterReflection();

        return [
            'name' => $this->name,
            'value' => $this->value !== null ? $br->printer()->prettyPrintExpr($this->value) : null,
            'attributes' => array_map(
                static fn (ReflectionAttribute $attr) => $attr->exportToCache(),
                $this->attributes,
            ),
            'docComment' => $this->docComment,
            'startLine' => $this->startLine,
            'endLine' => $this->endLine,
            'startColumn' => $this->startColumn,
            'endColumn' => $this->endColumn,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function importFromCache(Reflector $reflector, array $data, ReflectionEnum $enum): self
    {
        $reflection = new CoreReflectionClass(self::class);
        /** @var self $ref */
        $ref = $reflection->newInstanceWithoutConstructor();
        $ref->reflector = $reflector;
        $ref->enum = $enum;
        $ref->name = $data['name'];

        if ($data['value'] !== null) {
            $br = new BetterReflection();
            $ref->value = $br->phpParser()->parse('<?php ' . $data['value'] . ';')[0]->expr;
        } else {
            $ref->value = null;
        }

        $ref->attributes = array_map(
            static fn ($attrData) => ReflectionAttribute::importFromCache($reflector, $attrData, $ref),
            $data['attributes'],
        );
        $ref->docComment = $data['docComment'];
        $ref->startLine = $data['startLine'];
        $ref->endLine = $data['endLine'];
        $ref->startColumn = $data['startColumn'];
        $ref->endColumn = $data['endColumn'];

        return $ref;
    }

    /** @internal */
    public static function createFromNode(
        Reflector $reflector,
        EnumCase $node,
        ReflectionEnum $enum,
    ): self {
        return new self($reflector, $node, $enum);
    }

    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * While ReflectionEnum::isBacked() is a sufficient check when working with valid PHP code,
     * with an invalid enum declaration we can still encounter a back enum case with a missing value.
     */
    public function hasValueExpression(): bool
    {
        return $this->value !== null;
    }

    /**
     * Check self::hasValueExpression() being true first to avoid throwing exception.
     *
     * @throws LogicException
     */
    public function getValueExpression(): Node\Expr
    {
        if ($this->value === null) {
            throw new LogicException('This enum case does not have a value');
        }

        return $this->value;
    }

    public function getValue(): string|int
    {
        $value = $this->getCompiledValue()->value;
        assert(is_string($value) || is_int($value));

        return $value;
    }

    /**
     * Check self::hasValueExpression() being true first to avoid throwing exception.
     *
     * @throws LogicException
     */
    private function getCompiledValue(): CompiledValue
    {
        if ($this->value === null) {
            throw new LogicException('This enum case does not have a value');
        }

        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke(
                $this->value,
                new CompilerContext($this->reflector, $this),
            );
        }

        return $this->compiledValue;
    }

    /** @return positive-int */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /** @return positive-int */
    public function getEndLine(): int
    {
        return $this->endLine;
    }

    /** @return positive-int */
    public function getStartColumn(): int
    {
        return $this->startColumn;
    }

    /** @return positive-int */
    public function getEndColumn(): int
    {
        return $this->endColumn;
    }

    public function getDeclaringEnum(): ReflectionEnum
    {
        return $this->enum;
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return $this->enum;
    }

    /** @return non-empty-string|null */
    public function getDocComment(): string|null
    {
        return $this->docComment;
    }

    public function isDeprecated(): bool
    {
        return DeprecatedHelper::isDeprecated($this);
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** @return list<ReflectionAttribute> */
    public function getAttributesByName(string $name): array
    {
        return ReflectionAttributeHelper::filterAttributesByName($this->getAttributes(), $name);
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return ReflectionAttributeHelper::filterAttributesByInstance($this->getAttributes(), $className);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionEnumCaseStringCast::toString($this);
    }
}
