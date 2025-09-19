<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\NodeCompiler;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\Util\FileHelper;

/** @internal */
class CompilerContext
{
    private Reflector $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionParameter|\PHPStan\BetterReflection\Reflection\ReflectionConstant
     */
    private $contextReflection;
    /**
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionParameter|\PHPStan\BetterReflection\Reflection\ReflectionConstant $contextReflection
     */
    public function __construct(Reflector $reflector, $contextReflection)
    {
        $this->reflector = $reflector;
        $this->contextReflection = $contextReflection;
    }
    public function getReflector(): Reflector
    {
        return $this->reflector;
    }

    /** @return non-empty-string|null */
    public function getFileName(): ?string
    {
        if ($this->contextReflection instanceof ReflectionConstant) {
            $fileName = $this->contextReflection->getFileName();
            if ($fileName === null) {
                return null;
            }

            return $this->realPath($fileName);
        }

        $fileName = (($nullsafeVariable1 = $this->getClass()) ? $nullsafeVariable1->getFileName() : null) ?? (($nullsafeVariable2 = $this->getFunction()) ? $nullsafeVariable2->getFileName() : null);
        if ($fileName === null) {
            return null;
        }

        return $this->realPath($fileName);
    }

    private function realPath(string $fileName): string
    {
        return FileHelper::normalizePath($fileName, '/');
    }

    public function getNamespace(): ?string
    {
        if ($this->contextReflection instanceof ReflectionConstant) {
            return $this->contextReflection->getNamespaceName();
        }

        // @infection-ignore-all Coalesce: There's no difference
        return (($nullsafeVariable3 = $this->getClass()) ? $nullsafeVariable3->getNamespaceName() : null) ?? (($nullsafeVariable4 = $this->getFunction()) ? $nullsafeVariable4->getNamespaceName() : null);
    }

    public function getClass(): ?\PHPStan\BetterReflection\Reflection\ReflectionClass
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

    /**
     * @return \PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|null
     */
    public function getFunction()
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
