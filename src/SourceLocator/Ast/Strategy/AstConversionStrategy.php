<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Strategy;

use PhpParser\Node;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/** @internal */
interface AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to something (concrete implementation decides)
     * @param \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Interface_|\PhpParser\Node\Stmt\Trait_|\PhpParser\Node\Stmt\Enum_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall $node
     * @return \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\ReflectionFunction
     */
    public function __invoke(Reflector $reflector, $node, LocatedSource $locatedSource, ?\PhpParser\Node\Stmt\Namespace_ $namespace, ?int $positionInNode = null);
}
