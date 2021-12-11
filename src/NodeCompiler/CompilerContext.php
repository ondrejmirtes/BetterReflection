<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\Util\FileHelper;

/** @internal */
class CompilerContext
{
    public function __construct(
        private Reflector $reflector,
        private ReflectionClass|ReflectionProperty|ReflectionClassConstant|ReflectionEnumCase|ReflectionMethod|ReflectionFunction|ReflectionParameter|ReflectionConstant $contextReflection,
    ) {
    }

    public function getReflector(): Reflector
    {
        return $this->reflector;
    }

    /** @return non-empty-string|null */
    public function getFileName(): string|null
    {
        if ($this->contextReflection instanceof ReflectionConstant) {
            $fileName = $this->contextReflection->getFileName();
            if ($fileName === null) {
                return null;
            }

            return $this->realPath($fileName);
        }

        $fileName = $this->getClass()?->getFileName() ?? $this->getFunction()?->getFileName();
        if ($fileName === null) {
            return null;
        }

        return $this->realPath($fileName);
    }

    private function realPath(string $fileName): string
    {
        return FileHelper::normalizePath($fileName, '/');
    }

    public function getNamespace(): string|null
    {
        if ($this->contextReflection instanceof ReflectionConstant) {
            return $this->contextReflection->getNamespaceName();
        }

        // @infection-ignore-all Coalesce: There's no difference
        return $this->getClass()?->getNamespaceName() ?? $this->getFunction()?->getNamespaceName();
    }

    public function getClass(): ReflectionClass|null
    {
        if ($this->contextReflection instanceof ReflectionClass) {
            return $this->contextReflection;
        }

        if ($this->contextReflection instanceof ReflectionFunction) {
            return null;
        }

        if ($this->contextReflection instanceof ReflectionConstant) {
            return null;
        }

        if ($this->contextReflection instanceof ReflectionClassConstant) {
            return $this->contextReflection->getDeclaringClass();
        }

        if ($this->contextReflection instanceof ReflectionEnumCase) {
            return $this->contextReflection->getDeclaringClass();
        }

        return $this->contextReflection->getImplementingClass();
    }

    public function getFunction(): ReflectionMethod|ReflectionFunction|null
    {
        if ($this->contextReflection instanceof ReflectionMethod) {
            return $this->contextReflection;
        }

        if ($this->contextReflection instanceof ReflectionFunction) {
            return $this->contextReflection;
        }

        if ($this->contextReflection instanceof ReflectionParameter) {
            return $this->contextReflection->getDeclaringFunction();
        }

        return null;
    }
}
