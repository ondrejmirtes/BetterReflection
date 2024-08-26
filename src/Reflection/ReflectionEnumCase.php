<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\BetterReflection\NodeCompiler\CompiledValue;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionEnumCaseStringCast;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;
use PHPStan\BetterReflection\Util\GetLastDocComment;

use function assert;
use function is_int;
use function is_string;

/** @psalm-immutable */
class ReflectionEnumCase
{
    /** @var non-empty-string */
    private $name;

    /**
     * @var \PhpParser\Node\Expr|null
     */
    private $value;

    /** @var list<ReflectionAttribute> */
    private $attributes;

    /** @var non-empty-string|null */
    private $docComment;

    /** @var positive-int */
    private $startLine;

    /** @var positive-int */
    private $endLine;

    /** @var positive-int */
    private $startColumn;

    /** @var positive-int */
    private $endColumn;

    /** @psalm-allow-private-mutation
     * @var \PHPStan\BetterReflection\NodeCompiler\CompiledValue|null */
    private $compiledValue = null;
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionEnum
     */
    private $enum;
    private function __construct(Reflector $reflector, EnumCase $node, ReflectionEnum $enum)
    {
        $this->reflector = $reflector;
        $this->enum = $enum;
        $name = $node->name->toString();
        assert($name !== '');
        $this->name = $name;
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
    /** @internal */
    public static function createFromNode(Reflector $reflector, EnumCase $node, ReflectionEnum $enum): self
    {
        return new self($reflector, $node, $enum);
    }

    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @deprecated Use getValueExpression()
     */
    public function getValueExpr(): Node\Expr
    {
        return $this->getValueExpression();
    }

    /**
     * Check ReflectionEnum::isBacked() being true first to avoid throwing exception.
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

    /**
     * @return string|int
     */
    public function getValue()
    {
        $value = $this->getCompiledValue()->value;
        assert(is_string($value) || is_int($value));

        return $value;
    }

    /**
     * Check ReflectionEnum::isBacked() being true first to avoid throwing exception.
     *
     * @throws LogicException
     */
    private function getCompiledValue(): CompiledValue
    {
        if ($this->value === null) {
            throw new LogicException('This enum case does not have a value');
        }

        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke($this->value, new CompilerContext($this->reflector, $this));
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
    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    public function isDeprecated(): bool
    {
        return AnnotationHelper::isDeprecated($this->docComment);
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
