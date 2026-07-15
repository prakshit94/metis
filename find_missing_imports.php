<?php
require 'vendor/autoload.php';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/Modules'));
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$missing = [];

foreach ($regex as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    
    // get namespace
    preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
    $namespace = $nsMatch[1] ?? '';
    
    // get all uses
    preg_match_all('/use\s+([^;]+);/', $content, $useMatches);
    $uses = [];
    foreach ($useMatches[1] as $use) {
        $parts = explode('\\', $use);
        $alias = end($parts);
        if (strpos($use, ' as ') !== false) {
            $alias = trim(explode(' as ', $use)[1]);
            $use = trim(explode(' as ', $use)[0]);
        }
        $uses[$alias] = $use;
    }
    
    // find all ::class
    preg_match_all('/([A-Za-z0-9_]+)::class/', $content, $classMatches);
    $classes = array_unique($classMatches[1]);
    
    foreach ($classes as $class) {
        // ignore same namespace or built-in or imported
        if ($class === 'self' || $class === 'static' || $class === 'parent') continue;
        if (isset($uses[$class])) continue;
        
        // it must be in the same namespace. let's check if it exists in the same namespace
        $fullClass = $namespace . '\\' . $class;
        if (!class_exists($fullClass) && !interface_exists($fullClass) && !trait_exists($fullClass)) {
            // Check if it's in global namespace? 
            if (!class_exists($class)) {
                $missing[] = "$path: Missing import for $class (assumed $fullClass)";
            }
        }
    }
}
echo implode("\n", $missing) . "\n";
