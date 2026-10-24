<?php

declare(strict_types=1);

namespace Roave\BetterReflection\NodeCompiler;

/** @internal */
class CompiledValue
{
    /**
     * @var mixed
     */
    public $value;
    /**
     * @var string|null
     */
    public $constantName = null;
    /**
     * @param mixed $value
     */
    public function __construct($value, ?string $constantName = null)
    {
        $this->value = $value;
        $this->constantName = $constantName;
    }
}
