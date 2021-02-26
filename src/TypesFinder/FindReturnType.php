<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\BetterReflection\Reflection\ReflectionFunctionAbstract;
use PHPStan\BetterReflection\TypesFinder\PhpDocumentor\NamespaceNodeToReflectionTypeContext;
use function explode;

class FindReturnType
{
    /** @var ResolveTypes */
    private $resolveTypes;

    /** @var DocBlockFactory */
    private $docBlockFactory;

    /** @var NamespaceNodeToReflectionTypeContext */
    private $makeContext;

    public function __construct()
    {
        $this->resolveTypes    = new ResolveTypes();
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->makeContext     = new NamespaceNodeToReflectionTypeContext();
    }

    /**
     * Given a function, attempt to find the return type.
     *
     * @return Type[]
     */
    public function __invoke(ReflectionFunctionAbstract $function, ?Namespace_ $namespace) : array
    {
        $docComment = $function->getDocComment();

        if ($docComment === '') {
            return [];
        }

        $context = $this->makeContext->__invoke($namespace);

        $returnTags = $this
            ->docBlockFactory
            ->create($docComment, $context)
            ->getTagsByName('return');

        foreach ($returnTags as $returnTag) {
            if (! $returnTag instanceof Return_) {
                continue;
            }

            return $this->resolveTypes->__invoke(explode('|', (string) $returnTag->getType()), $context);
        }

        return [];
    }
}
