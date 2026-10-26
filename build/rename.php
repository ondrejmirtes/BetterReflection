#!/usr/bin/env php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', '512M');

use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;
use PhpParser\Node;

class PhpPatcher extends NodeVisitorAbstract
{

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt) {
            $docComment = $node->getDocComment();
            if ($docComment === null) {
                return null;
            }

            $docCommentText = $docComment->getText();
            if (strpos($docCommentText, '\\Roave\\BetterReflection\\') === false) {
                return null;
            }

            $node->setDocComment(new \PhpParser\Comment\Doc(str_replace('\\Roave\\BetterReflection\\', '\\PHPStan\\BetterReflection\\', $docCommentText)));

            return $node;
        }
        if ($node instanceof Node\Scalar\String_) {
            if (strpos($node->value, 'Roave\\BetterReflection\\') === false) {
                return null;
            }

            $attributes = $node->getAttributes();
            unset($attributes['rawValue']);

            return new Node\Scalar\String_(str_replace('Roave\\BetterReflection\\', 'PHPStan\\BetterReflection\\', $node->value), $attributes);
        }
        if (!$node instanceof Node\Name) {
            return null;
        }

        $parts = $node->getParts();
        if (count($parts) < 2) {
            return null;
        }

        if ($parts[0] !== 'Roave' || $parts[1] !== 'BetterReflection') {
            return null;
        }

        if ($node->isFullyQualified()) {
            return Name\FullyQualified::concat('PHPStan\\BetterReflection', $node->slice(2));
        }

        return Name::concat('PHPStan\\BetterReflection', $node->slice(2));
    }

}

(function () {
    $parser = (new \PhpParser\ParserFactory())->createForNewestSupportedVersion();
    $nameResolver = new NodeVisitor\NameResolver(null, [
        'replaceNodes' => false
    ]);

    $printer = new PrettyPrinter\Standard();

    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NodeVisitor\CloningVisitor());
    $traverser->addVisitor($nameResolver);
    $traverser->addVisitor(new PhpPatcher());

    $dirs = new MultipleIterator();
    $dirs->attachIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src'));
    $dirs->attachIterator(new RecursiveDirectoryIterator(__DIR__ . '/../test'));

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../src'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $files = [];
    foreach ($it as $file) {
        $files[] = $file;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../test'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $file) {
        $files[] = $file;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../demo'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $file) {
        $files[] = $file;
    }

    foreach ($files as $file) {
        $fileName = $file->getPathname();
        if (!preg_match('/\.php$/', $fileName) && !preg_match('/\.phpt$/', $fileName)) {
            continue;
        }

        $code = file_get_contents($fileName);
        if ($code === '') {
            continue;
        }
        $origStmts = $parser->parse($code);
        $newCode = $printer->printFormatPreserving(
            $traverser->traverse($origStmts),
            $origStmts,
            $parser->getTokens()
        );

        file_put_contents($fileName, $newCode);
    }

    // Expected outputs outside of PHP code (.phpt EXPECT sections, demo checks, PHPStan baseline)
    $textFiles = [__DIR__ . '/../test/demo/check-demo.sh', __DIR__ . '/../phpstan-baseline.neon'];
    foreach ($files as $file) {
        if (preg_match('/\.phpt$/', $file->getPathname())) {
            $textFiles[] = $file->getPathname();
        }
    }

    foreach ($textFiles as $fileName) {
        $code = file_get_contents($fileName);
        $code = preg_replace_callback(
            '/string\((\d+)\) "Roave\\\\BetterReflection\\\\/',
            static fn (array $matches): string => sprintf('string(%d) "PHPStan\\BetterReflection\\', (int) $matches[1] + strlen('PHPStan') - strlen('Roave')),
            $code
        );
        // \b keeps Roave\BetterReflectionTest and Roave\BetterReflectionBenchmark untouched
        file_put_contents($fileName, preg_replace('/\bRoave(\\\\+BetterReflection)\b/', 'PHPStan$1', $code));
    }
})();
