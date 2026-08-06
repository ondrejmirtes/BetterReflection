<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\NodeCompiler;

use Attribute;
use Closure;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;

use function array_map;
use function assert;
use function class_exists;
use function constant;
use function defined;
use function dirname;
use function explode;
use function in_array;
use function is_file;
use function sprintf;

use const PHP_VERSION_ID;

/** @internal */
class CompileNodeToValue
{
    /**
     * @var mixed[]
     */
    private const TRUE_FALSE_NULL = ['true', 'false', 'null'];

    /**
     * Compile an expression from a node into a value.
     *
     * @param Node\Stmt\Expression|Node\Expr $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     *
     * @throws Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, CompilerContext $context): CompiledValue
    {
        if ($node instanceof Node\Stmt\Expression) {
            return $this($node->expr, $context);
        }

        $constantName = null;

        if (
            $node instanceof Node\Expr\ConstFetch
            && ! in_array($node->name->toLowerString(), self::TRUE_FALSE_NULL, true)
        ) {
            $constantName = $this->resolveConstantName($node, $context);
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            $constantName = $this->resolveClassConstantName($node, $context);
        }

        $constExprEvaluator = new ConstExprEvaluator(function (Node\Expr $node) use ($context, $constantName) {
            if ($node instanceof Node\Expr\ConstFetch) {
                return $this->getConstantValue($node, $constantName, $context);
            }

            if ($node instanceof Node\Expr\ClassConstFetch) {
                return $this->getClassConstantValue($node, $constantName, $context);
            }

            if ($node instanceof Node\Expr\New_) {
                return $this->compileNew($node, $context);
            }

            if ($node instanceof Node\Scalar\MagicConst\Dir) {
                return $this->compileDirConstant($context, $node);
            }

            if ($node instanceof Node\Scalar\MagicConst\File) {
                return $this->compileFileConstant($context, $node);
            }

            if ($node instanceof Node\Scalar\MagicConst\Class_) {
                return $this->compileClassConstant($context);
            }

            if ($node instanceof Node\Scalar\MagicConst\Line) {
                return $node->getStartLine();
            }

            if ($node instanceof Node\Scalar\MagicConst\Namespace_) {
                return $context->getNamespace() ?? '';
            }

            if ($node instanceof Node\Scalar\MagicConst\Property) {
                $method = $context->getFunction();

                if ($method !== null && $method instanceof ReflectionMethod && $method->isHook()) {
                    return $method->getHookProperty()->getName();
                }

                return '';
            }

            if ($node instanceof Node\Scalar\MagicConst\Method) {
                $class    = $context->getClass();
                $function = $context->getFunction();

                if ($class !== null && $function !== null) {
                    return sprintf('%s::%s', $class->getName(), $function->getName());
                }

                if ($function !== null) {
                    return $function->getName();
                }

                return '';
            }

            if ($node instanceof Node\Scalar\MagicConst\Function_) {
                return (($nullsafeVariable1 = $context->getFunction()) ? $nullsafeVariable1->getName() : null) ?? '';
            }

            if ($node instanceof Node\Scalar\MagicConst\Trait_) {
                $class = $context->getClass();

                if ($class !== null && $class->isTrait()) {
                    return $class->getName();
                }

                return '';
            }

            if (
                $node instanceof Node\Expr\FuncCall
                && $node->name instanceof Node\Name
                && $node->name->toLowerString() === 'constant'
                && $node->args[0] instanceof Node\Arg
                && $node->args[0]->value instanceof Node\Scalar\String_
                && defined($node->args[0]->value->value)
            ) {
                return constant($node->args[0]->value->value);
            }

            if (
                $node instanceof Node\Expr\PropertyFetch
                && $node->var instanceof Node\Expr\ClassConstFetch
            ) {
                return $this->getEnumPropertyValue($node, $context);
            }

            if ($node instanceof Node\Expr\Cast\Int_) {
                /** @phpstan-ignore cast.int */
                return (int) $this($node->expr, $context)->value;
            }

            if ($node instanceof Node\Expr\Cast\Double) {
                /** @phpstan-ignore cast.double */
                return (float) $this($node->expr, $context)->value;
            }

            if ($node instanceof Node\Expr\Cast\Bool_) {
                return (bool) $this($node->expr, $context)->value;
            }

            if ($node instanceof Node\Expr\Cast\String_) {
                /** @phpstan-ignore cast.string */
                return (string) $this($node->expr, $context)->value;
            }

            if ($node instanceof Node\Expr\Cast\Array_) {
                return (array) $this($node->expr, $context)->value;
            }

            if ($node instanceof Node\Expr\Cast\Object_) {
                return (object) $this($node->expr, $context)->value;
            }

            if ($node instanceof Node\Expr\Closure) {
                return $this->compileClosureDeclaration($node, $context);
            }

            if (
                ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\StaticCall)
                && $node->isFirstClassCallable()
                && PHP_VERSION_ID >= 80100 // the syntax cannot be eval'd on older runtimes
            ) {
                return $this->compileClosureDeclaration($node, $context);
            }

            throw Exception\UnableToCompileNode::forUnRecognizedExpressionInContext($node, $context);
        });

        /** @psalm-var mixed $value */
        $value = $constExprEvaluator->evaluateDirectly($node);

        return new CompiledValue($value, $constantName);
    }

    /**
     * @return mixed
     */
    private function getEnumPropertyValue(Node\Expr\PropertyFetch $node, CompilerContext $context)
    {
        assert($node->var instanceof Node\Expr\ClassConstFetch);
        assert($node->var->class instanceof Node\Name);

        $className = $this->resolveClassName($node->var->class->toString(), $context);
        $class     = $context->getReflector()->reflectClass($className);

        if (! $class instanceof ReflectionEnum) {
            throw Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }

        assert($node->var->name instanceof Node\Identifier);

        $caseName = $node->var->name->name;

        $case = $class->getCase($caseName);

        if ($case === null) {
            throw Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }

        assert($node->name instanceof Node\Identifier);

        switch ($node->name->toString()) {
            case 'value':
                return $case->getValue();
            case 'name':
                return $case->getName();
            default:
                throw Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }
    }

    /**
     * Compile a closure or a first-class callable declared in a constant expression (PHP 8.5+)
     * into a real Closure, like native reflection does.
     *
     * The language guarantees such closures are static and capture no variables,
     * so evaluating the declaration itself executes no user code.
     *
     * @param Node\Expr\Closure|Node\Expr\FuncCall|Node\Expr\StaticCall $node
     */
    private function compileClosureDeclaration(Node\Expr $node, CompilerContext $context): Closure
    {
        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name) {
            $calleeClassName = $this->resolveClassName($node->class->toString(), $context);

            if (! class_exists($calleeClassName)) {
                throw Exception\UnableToCompileNode::becauseOfClassCannotBeLoaded($context, $node, $calleeClassName);
            }

            $node        = clone $node;
            $node->class = new Node\Name\FullyQualified($calleeClassName);
        }

        $code = (new PrettyPrinter())->prettyPrintExpr($node);

        $namespace = $context->getNamespace();

        // The namespace wrapper keeps the fallback to global scope for unqualified function calls
        $code = $namespace !== null && $namespace !== ''
            ? sprintf('namespace %s; return %s;', $namespace, $code)
            : sprintf('return %s;', $code);

        try {
            $closure = eval($code);
        } catch (\Throwable $e) {
            // e.g. a syntax not supported by the current runtime
            throw Exception\UnableToCompileNode::forUnRecognizedExpressionInContext($node, $context);
        }

        assert($closure instanceof Closure);

        $contextClass     = $context->getClass();
        $contextClassName = $contextClass !== null ? $contextClass->getName() : null;

        // Bind the class scope like native reflection does
        if ($contextClassName !== null && class_exists($contextClassName)) {
            $boundClosure = Closure::bind($closure, null, $contextClassName);

            return $boundClosure ?? $closure;
        }

        return $closure;
    }

    private function resolveConstantName(Node\Expr\ConstFetch $constNode, CompilerContext $context): string
    {
        $constantName = $constNode->name->toString();
        $namespace    = $context->getNamespace() ?? '';

        if ($constNode->name->isUnqualified()) {
            $namespacedConstantName = sprintf('%s\\%s', $namespace, $constantName);

            if ($this->constantExists($namespacedConstantName, $context)) {
                return $namespacedConstantName;
            }
        }

        if ($this->constantExists($constantName, $context)) {
            return $constantName;
        }

        throw Exception\UnableToCompileNode::becauseOfNotFoundConstantReference($context, $constNode, $constantName);
    }

    private function constantExists(string $constantName, CompilerContext $context): bool
    {
        if (defined($constantName)) {
            return true;
        }

        try {
            $context->getReflector()->reflectConstant($constantName);

            return true;
        } catch (IdentifierNotFound $exception) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    private function getConstantValue(Node\Expr\ConstFetch $node, ?string $constantName, CompilerContext $context)
    {
        // It's not resolved when constant value is expression
        // @infection-ignore-all Assignment, AssignCoalesce: There's no difference, ??= is just optimization
        $constantName ??= $this->resolveConstantName($node, $context);

        if (defined($constantName)) {
            return constant($constantName);
        }

        return $context->getReflector()->reflectConstant($constantName)->getValue();
    }

    private function resolveClassConstantName(Node\Expr\ClassConstFetch $node, CompilerContext $context): string
    {
        assert($node->name instanceof Node\Identifier);
        $constantName = $node->name->name;
        assert($node->class instanceof Node\Name);
        $className = $node->class->toString();

        return sprintf('%s::%s', $this->resolveClassName($className, $context), $constantName);
    }

    /**
     * @return mixed
     */
    private function getClassConstantValue(Node\Expr\ClassConstFetch $node, ?string $classConstantName, CompilerContext $context)
    {
        // It's not resolved when constant value is expression
        // @infection-ignore-all Assignment, AssignCoalesce: There's no difference, ??= is just optimization
        $classConstantName ??= $this->resolveClassConstantName($node, $context);

        [$className, $constantName] = explode('::', $classConstantName);
        assert($constantName !== '');

        if ($constantName === 'class') {
            return $className;
        }

        $classContext    = $context->getClass();
        $classReflection = $classContext !== null && $classContext->getName() === $className ? $classContext : $context->getReflector()->reflectClass($className);
        if ($classReflection instanceof ReflectionEnum) {
            if ($classReflection->hasCase($constantName)) {
                return constant(sprintf('%s::%s', $className, $constantName));
            }
        }

        if ($classReflection instanceof ReflectionEnum) {
            if ($classReflection->hasCase($constantName)) {
                throw Exception\UnableToCompileNode::becauseOfValueIsEnum($context, $classReflection, $node);
            }
        }

        $reflectionConstant = $classReflection->getConstant($constantName);

        if (! $reflectionConstant instanceof ReflectionClassConstant) {
            if ($classReflection->getName() === Attribute::class && $constantName === 'TARGET_CONSTANT') {
                return 1 << 16;
            }
            throw Exception\UnableToCompileNode::becauseOfNotFoundClassConstantReference($context, $classReflection, $node);
        }

        return $reflectionConstant->getValue();
    }

    private function compileNew(Node\Expr\New_ $node, CompilerContext $context): object
    {
        assert($node->class instanceof Node\Name);

        /** @psalm-var class-string $className */
        $className = $node->class->toString();

        if (! class_exists($className)) {
            throw Exception\UnableToCompileNode::becauseOfClassCannotBeLoaded($context, $node, $className);
        }

        $arguments = [];
        foreach ($node->args as $argNo => $arg) {
            $arguments[(($argName = $arg->name) ? $argName->toString() : null) ?? $argNo] = $this($arg->value, $context)->value;
        }

        return new $className(...$arguments);
    }

    /**
     * Compile a __DIR__ node
     */
    private function compileDirConstant(CompilerContext $context, Node\Scalar\MagicConst\Dir $node): string
    {
        $fileName = $context->getFileName();

        if ($fileName === null) {
            throw Exception\UnableToCompileNode::becauseOfMissingFileName($context, $node);
        }

        if (! is_file($fileName)) {
            throw Exception\UnableToCompileNode::becauseOfNonexistentFile($context, $fileName);
        }

        return dirname($fileName);
    }

    /**
     * Compile a __FILE__ node
     */
    private function compileFileConstant(CompilerContext $context, Node\Scalar\MagicConst\File $node): string
    {
        $fileName = $context->getFileName();

        if ($fileName === null) {
            throw Exception\UnableToCompileNode::becauseOfMissingFileName($context, $node);
        }

        if (! is_file($fileName)) {
            throw Exception\UnableToCompileNode::becauseOfNonexistentFile($context, $fileName);
        }

        return $fileName;
    }

    /**
     * Compiles magic constant __CLASS__
     */
    private function compileClassConstant(CompilerContext $context): string
    {
        return (($nullsafeVariable2 = $context->getClass()) ? $nullsafeVariable2->getName() : null) ?? '';
    }

    private function resolveClassName(string $className, CompilerContext $context): string
    {
        if ($className !== 'self' && $className !== 'static' && $className !== 'parent') {
            return $className;
        }

        $classContext = $context->getClass();
        assert($classContext !== null);

        if ($className !== 'parent') {
            return $classContext->getName();
        }

        $parentClass = $classContext->getParentClass();
        assert($parentClass instanceof ReflectionClass);

        return $parentClass->getName();
    }
}
