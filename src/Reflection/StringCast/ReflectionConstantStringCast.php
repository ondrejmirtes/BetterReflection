<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\ReflectionConstant;

use function assert;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Implementation of ReflectionConstant::__toString()
 *
 * @internal
 */
final class ReflectionConstantStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionConstant $constantReflection): string
    {
        /** @psalm-var scalar|array<scalar> $value */
        $value = $constantReflection->getValue();

        if (is_object($value)) {
            $valueAsString = 'Object';
        } elseif (is_array($value)) {
            $valueAsString = 'Array';
        } else {
            $valueAsString = (string) $value;
        }

        return sprintf(
            'Constant [ <%s> %s %s ] {%s %s }',
            self::sourceToString($constantReflection),
            gettype($value),
            $constantReflection->getName(),
            self::fileAndLinesToString($constantReflection),
            $valueAsString,
        );
    }

    /** @psalm-pure */
    private static function sourceToString(ReflectionConstant $constantReflection): string
    {
        if ($constantReflection->isUserDefined()) {
            return 'user';
        }

        $extensionName = $constantReflection->getExtensionName();
        assert(is_string($extensionName));

        return sprintf('internal:%s', $extensionName);
    }

    /** @psalm-pure */
    private static function fileAndLinesToString(ReflectionConstant $constantReflection): string
    {
        if ($constantReflection->isInternal()) {
            return '';
        }

        $fileName = $constantReflection->getFileName();
        if ($fileName === null) {
            return '';
        }

        return sprintf("\n  @@ %s %d - %d\n", $fileName, $constantReflection->getStartLine(), $constantReflection->getEndLine());
    }
}
