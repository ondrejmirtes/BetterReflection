<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\NodeCompiler\CompiledValue;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClassConstant as ReflectionClassConstantAdapter;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\Deprecated\DeprecatedHelper;
use Roave\BetterReflection\Reflection\StringCast\ReflectionClassConstantStringCast;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Util\CalculateReflectionColumn;
use Roave\BetterReflection\Util\GetLastDocComment;

use function array_map;
use function assert;

/** @psalm-immutable */
class ReflectionClassConstant
{
    /** @var non-empty-string */
    private string $name;

    /** @var int-mask-of<ReflectionClassConstantAdapter::IS_*> */
    private int $modifiers;

    private ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type;

    private Node\Expr $value;

    /** @var non-empty-string|null */
    private string|null $docComment;

    /** @var list<ReflectionAttribute> */
    private array $attributes;

    /** @var positive-int */
    private int $startLine;

    /** @var positive-int */
    private int $endLine;

    /** @var positive-int */
    private int $startColumn;

    /** @var positive-int */
    private int $endColumn;

    private ?ReflectionClass $declaringClass;

    private ?ReflectionClass $implementingClass;

    /** @var non-empty-string */
    private string $declaringClassName;

    /** @var non-empty-string */
    private string $implementingClassName;

    /** @psalm-allow-private-mutation */
    private CompiledValue|null $compiledValue = null;

    private function __construct(
        private Reflector $reflector,
        ClassConst $node,
        int $positionInNode,
        ReflectionClass $declaringClass,
        ReflectionClass $implementingClass,
    ) {
        $this->declaringClass = $declaringClass;
        $this->implementingClass = $implementingClass;
        $this->name      = $node->consts[$positionInNode]->name->name;
        $this->modifiers = $this->computeModifiers($node);
        $this->type      = $this->createType($node);
        $this->value     = $node->consts[$positionInNode]->value;

        $this->docComment = GetLastDocComment::forNode($node);
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);

        $startLine = $node->getStartLine();
        assert($startLine > 0);
        $endLine = $node->getEndLine();
        assert($endLine > 0);

        $this->startLine   = $startLine;
        $this->endLine     = $endLine;
        $this->startColumn = CalculateReflectionColumn::getStartColumn($declaringClass->getLocatedSource()->getSource(), $node);
        $this->endColumn   = CalculateReflectionColumn::getEndColumn($declaringClass->getLocatedSource()->getSource(), $node);

        $this->declaringClassName = $this->declaringClass->getName();
        $this->implementingClassName = $this->implementingClass->getName();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        return [
            'declaringClassName' => $this->declaringClassName,
            'implementingClassName' => $this->implementingClassName,
            'name' => $this->name,
            'modifiers' => $this->modifiers,
            'type' => $this->type !== null ? ['class' => get_class($this->type), 'data' => $this->type->exportToCache()] : null,
            'value' => ExprCacheHelper::export($this->value),
            'docComment' => $this->docComment,
            'attributes' => array_map(
                static fn (ReflectionAttribute $attr) => $attr->exportToCache(),
                $this->attributes,
            ),
            'startLine' => $this->startLine,
            'endLine' => $this->endLine,
            'startColumn' => $this->startColumn,
            'endColumn' => $this->endColumn,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function importFromCache(Reflector $reflector, array $data): self
    {
        $reflection = new CoreReflectionClass(self::class);
        /** @var self $ref */
        $ref = $reflection->newInstanceWithoutConstructor();
        $ref->reflector = $reflector;
        $ref->declaringClassName = $data['declaringClassName'];
        $ref->implementingClassName = $data['implementingClassName'];
        $ref->name = $data['name'];
        $ref->modifiers = $data['modifiers'];

        if ($data['type'] !== null) {
            $typeClass = $data['type']['class'];
            $ref->type = $typeClass::importFromCache($reflector, $data['type']['data'], $ref);
        } else {
            $ref->type = null;
        }

        $ref->value = ExprCacheHelper::import($data['value']);

        $ref->docComment = $data['docComment'];
        $ref->attributes = array_map(
            static fn ($attrData) => ReflectionAttribute::importFromCache($reflector, $attrData, $ref),
            $data['attributes'],
        );
        $ref->startLine = $data['startLine'];
        $ref->endLine = $data['endLine'];
        $ref->startColumn = $data['startColumn'];
        $ref->endColumn = $data['endColumn'];

        return $ref;
    }

    /**
     * Create a reflection of a class's constant by Const Node
     *
     * @internal
     */
    public static function createFromNode(
        Reflector $reflector,
        ClassConst $node,
        int $positionInNode,
        ReflectionClass $declaringClass,
        ReflectionClass $implementingClass,
    ): self {
        return new self(
            $reflector,
            $node,
            $positionInNode,
            $declaringClass,
            $implementingClass,
        );
    }

    /** @internal */
    public function withImplementingClass(ReflectionClass $implementingClass): self
    {
        $clone                    = clone $this;
        $clone->implementingClass = $implementingClass;

        $clone->attributes = array_map(static fn (ReflectionAttribute $attribute): ReflectionAttribute => $attribute->withOwner($clone), $this->attributes);

        $this->compiledValue = null;

        return $clone;
    }

    /**
     * Get the name of the reflection (e.g. if this is a ReflectionClass this
     * will be the class name).
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    private function createType(ClassConst $node): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        $type = $node->type;

        if ($type === null) {
            return null;
        }

        assert($type instanceof Node\Identifier || $type instanceof Node\Name || $type instanceof Node\NullableType || $type instanceof Node\UnionType || $type instanceof Node\IntersectionType);

        return ReflectionType::createFromNode($this->reflector, $this, $type);
    }

    public function getType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        return $this->type;
    }

    public function hasType(): bool
    {
        return $this->type !== null;
    }

    public function getValueExpression(): Node\Expr
    {
        return $this->value;
    }

    /**
     * Returns constant value
     */
    public function getValue(): mixed
    {
        if ($this->compiledValue === null) {
            $this->compiledValue = (new CompileNodeToValue())->__invoke(
                $this->value,
                new CompilerContext($this->reflector, $this),
            );
        }

        return $this->compiledValue->value;
    }

    /**
     * Constant is public
     */
    public function isPublic(): bool
    {
        return (bool) ($this->modifiers & ReflectionClassConstantAdapter::IS_PUBLIC_COMPATIBILITY);
    }

    /**
     * Constant is private
     */
    public function isPrivate(): bool
    {
        // Private constant cannot be final
        return $this->modifiers === ReflectionClassConstantAdapter::IS_PRIVATE_COMPATIBILITY;
    }

    /**
     * Constant is protected
     */
    public function isProtected(): bool
    {
        return (bool) ($this->modifiers & ReflectionClassConstantAdapter::IS_PROTECTED_COMPATIBILITY);
    }

    public function isFinal(): bool
    {
        $final = (bool) ($this->modifiers & ReflectionClassConstantAdapter::IS_FINAL_COMPATIBILITY);
        if ($final) {
            return true;
        }

        if (BetterReflection::$phpVersion >= 80100) {
            return false;
        }

        return $this->getDeclaringClass()->isInterface();
    }

    /**
     * Returns a bitfield of the access modifiers for this constant
     *
     * @return int-mask-of<ReflectionClassConstantAdapter::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    /**
     * Get the line number that this constant starts on.
     *
     * @return positive-int
     */
    public function getStartLine(): int
    {
        return $this->startLine;
    }

    /**
     * Get the line number that this constant ends on.
     *
     * @return positive-int
     */
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

    /**
     * Get the declaring class
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return $this->declaringClass ??= $this->reflector->reflectClass($this->declaringClassName);
    }

    /**
     * Get the class that implemented the method based on trait use.
     */
    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass ??= $this->reflector->reflectClass($this->implementingClassName);
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

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionClassConstantStringCast::toString($this);
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

    /** @return int-mask-of<ReflectionClassConstantAdapter::IS_*> */
    private function computeModifiers(ClassConst $node): int
    {
        $modifiers  = $node->isFinal() ? ReflectionClassConstantAdapter::IS_FINAL_COMPATIBILITY : 0;
        $modifiers += $node->isPrivate() ? ReflectionClassConstantAdapter::IS_PRIVATE_COMPATIBILITY : 0;
        $modifiers += $node->isProtected() ? ReflectionClassConstantAdapter::IS_PROTECTED_COMPATIBILITY : 0;
        $modifiers += $node->isPublic() ? ReflectionClassConstantAdapter::IS_PUBLIC_COMPATIBILITY : 0;

        return $modifiers;
    }
}
