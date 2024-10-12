<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Util;

use function array_pop;
use function assert;
use function explode;
use function implode;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strpos;
use function substr;
use function trim;

use const DIRECTORY_SEPARATOR;

class FileHelper
{
    /**
     * @param non-empty-string $path
     *
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function normalizeWindowsPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        assert($path !== '');

        return $path;
    }

    /**
     * @param non-empty-string $originalPath
     *
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function normalizePath(string $originalPath, string $directorySeparator = DIRECTORY_SEPARATOR): string
    {
        $isLocalPath = $originalPath !== '' && $originalPath[0] === '/';

        $matches = null;
        if (! $isLocalPath) {
            if (! preg_match('~^([a-z]+)\\:\\/\\/(.+)~', $originalPath, $matches)) {
                $matches = null;
            }
        }

        if ($matches !== null) {
            [, $scheme, $path] = $matches;
        } else {
            $scheme = null;
            $path   = $originalPath;
        }

        $path = str_replace(['\\', '//', '///', '////'], '/', $path);

        $pathRoot  = strpos($path, '/') === 0 ? $directorySeparator : '';
        $pathParts = explode('/', trim($path, '/'));

        $normalizedPathParts = [];
        foreach ($pathParts as $pathPart) {
            if ($pathPart === '.') {
                continue;
            }

            if ($pathPart === '..') {
                $removedPart = array_pop($normalizedPathParts);
                assert(is_string($removedPart));
                if ($scheme === 'phar' && substr($removedPart, -5) === '.phar') {
                    $scheme = null;
                }
            } else {
                $normalizedPathParts[] = $pathPart;
            }
        }

        return ($scheme !== null ? $scheme . '://' : '') . $pathRoot . implode($directorySeparator, $normalizedPathParts);
    }

    public static function normalizeSystemPath(string $originalPath): string
    {
        $path = self::normalizeWindowsPath($originalPath);
        preg_match('~^([a-z]+)\\:\\/\\/(.+)~', $path, $matches);
        $scheme = null;
        if ($matches !== []) {
            [, $scheme, $path] = $matches;
        }

        // @infection-ignore-all Identical Needed only on Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            // @infection-ignore-all UnwrapStrReplace Needed only on Windows
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        assert($path !== '');

        return ($scheme !== null ? sprintf('%s://', $scheme) : '') . $path;
    }
}
