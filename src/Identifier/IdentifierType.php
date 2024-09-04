<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Identifier;

use InvalidArgumentException;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;

use function array_key_exists;
use function sprintf;

class IdentifierType
{
    public const IDENTIFIER_CLASS    = ReflectionClass::class;
    public const IDENTIFIER_FUNCTION = ReflectionFunction::class;
    public const IDENTIFIER_CONSTANT = ReflectionConstant::class;

    private const VALID_TYPES = [
        self::IDENTIFIER_CLASS    => null,
        self::IDENTIFIER_FUNCTION => null,
        self::IDENTIFIER_CONSTANT => null,
    ];

    /**
     * @var string
     */
    private $name;

    public function __construct(string $type = self::IDENTIFIER_CLASS)
    {
        if (! array_key_exists($type, self::VALID_TYPES)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid identifier type', $type));
        }

        $this->name = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isClass(): bool
    {
        return $this->name === self::IDENTIFIER_CLASS;
    }

    public function isFunction(): bool
    {
        return $this->name === self::IDENTIFIER_FUNCTION;
    }

    public function isConstant(): bool
    {
        return $this->name === self::IDENTIFIER_CONSTANT;
    }
}
