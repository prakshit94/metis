<?php
$files = [];
exec('find app -name "*.php" | grep -i "Models"', $files);

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Skip if not a class extending Model or Authenticatable or Pivot or something
    // Skip if already implements Auditable
    if (strpos($content, 'implements Auditable') !== false || strpos($content, 'OwenIt\Auditing\Contracts\Auditable') !== false) {
        continue;
    }

    if (preg_match('/class\s+(\w+)\s+extends\s+(Model|Authenticatable|Pivot|User|AuthenticatableUser)/i', $content)) {
        echo "Updating: $file\n";
        
        // Add use statements
        $useStatements = "use OwenIt\Auditing\Contracts\Auditable;\nuse OwenIt\Auditing\Auditable as AuditableTrait;";
        
        // Find last use statement or namespace to inject new use statements
        if (preg_match('/^use\s+[\w\\\\]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            // Find the end of all use statements
            $lastUsePos = strrpos($content, 'use ', 0);
            $lastUseEnd = strpos($content, ';', $lastUsePos) + 1;
            $content = substr_replace($content, "\n" . $useStatements . "\n", $lastUseEnd, 0);
        } else {
            // Inject after namespace
            $content = preg_replace('/(namespace\s+[\w\\\\]+;)/i', "$1\n\n" . $useStatements, $content);
        }

        // Add implements Auditable
        if (preg_match('/(class\s+\w+\s+extends\s+[\w\\\\]+)(\s+implements\s+[\w\\\,\s]+)?/i', $content, $matches)) {
            if (isset($matches[2]) && trim($matches[2]) !== '') {
                $content = str_replace($matches[0], $matches[0] . ', Auditable', $content);
            } else {
                $content = str_replace($matches[0], $matches[0] . ' implements Auditable', $content);
            }
        }

        // Add use AuditableTrait; inside class
        $content = preg_replace('/(class\s+\w+\s+extends\s+[\w\\\\]+(?:\s+implements\s+[\w\\\,\s]+)?\s*\{)/i', "$1\n    use AuditableTrait;", $content);

        file_put_contents($file, $content);
    }
}
echo "Done\n";
