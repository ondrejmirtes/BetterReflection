<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Strategy;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function implode;

/** @internal */
class NodeToReflection implements AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to a Reflection
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Trait_|\PhpParser\Node\Stmt\Enum_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall $node
     * @return \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\ReflectionFunction
     */
    public function __invoke(Reflector $reflector, $node, LocatedSource $locatedSource, ?\PhpParser\Node\Stmt\Namespace_ $namespace, ?int $positionInNode = null)
    {
        /** @psalm-suppress PossiblyNullPropertyFetch, PossiblyNullReference */
        $namespaceName = (($namespace2 = $namespace) ? $namespace2->name : null) !== null ? implode('\\', $namespace->name->getParts()) : null;
        if ($node instanceof Node\Stmt\Enum_) {
            return ReflectionEnum::createFromNode($reflector, $node, $locatedSource, $namespaceName);
        }
        if ($node instanceof Node\Stmt\ClassLike) {
            return ReflectionClass::createFromNode($reflector, $node, $locatedSource, $namespaceName);
        }
        if ($node instanceof Node\Stmt\Const_) {
            return ReflectionConstant::createFromNode($reflector, $node, $locatedSource, $namespaceName, $positionInNode);
        }
        if ($node instanceof Node\Expr\FuncCall) {
            return ReflectionConstant::createFromNode($reflector, $node, $locatedSource);
        }
        return ReflectionFunction::createFromNode($reflector, $node, $locatedSource, $namespaceName);
    }
}
