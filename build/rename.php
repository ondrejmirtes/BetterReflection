#!/usr/bin/env php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('memory_limit', '512M');

use PhpParser\Lexer;
use PhpParser\Node\Name;
use PhpParser\Parser;
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
        if (!$node instanceof Node\Name) {
            return null;
        }

        $parts = $node->parts;
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
    $lexer = new Lexer\Emulative([
        'usedAttributes' => [
            'comments',
            'startLine', 'endLine',
            'startTokenPos', 'endTokenPos',
        ],
    ]);
    $parser = new Parser\Php7($lexer, [
        'useIdentifierNodes' => true,
        'useConsistentVariableNodes' => true,
        'useExpressionStatements' => true,
        'useNopStatements' => false,
    ]);
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
        $origStmts = $parser->parse($code);
        $newCode = $printer->printFormatPreserving(
            $traverser->traverse($origStmts),
            $origStmts,
            $lexer->getTokens()
        );

        file_put_contents($fileName, $newCode);
    }
})();
