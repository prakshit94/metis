<?php

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$parser = (new ParserFactory)->createForHostVersion();
$finder = new NodeFinder;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'));
$count = 0;
foreach ($files as $file) {
    if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/.')) {
        continue;
    }
    $nodes = $parser->parse(file_get_contents($file->getPathname()));
    foreach ($finder->findInstanceOf($nodes, ClassMethod::class) as $method) {
        if ($method->name->toString() === 'middleware') {
            continue;
        }
        foreach ($finder->findInstanceOf($method->stmts ?? [], New_::class) as $new) {
            if ($new->class instanceof Name && $new->class->getLast() === 'Middleware') {
                throw new RuntimeException('Unexpected middleware object in '.$file->getPathname().':'.$new->getStartLine());
            }
        }
        $count++;
    }
}
echo "PASS: {$count} application methods contain no misplaced middleware objects\n";
