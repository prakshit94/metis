<?php
$files = [];
exec('find app -name "*.php" | grep -i "Models"', $files);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Remove the badly placed lines
    $content = str_replace("use OwenIt\Auditing\Contracts\Auditable;\nuse OwenIt\Auditing\Auditable as AuditableTrait;\n", "", $content);
    $content = str_replace("use OwenIt\Auditing\Contracts\Auditable;\nuse OwenIt\Auditing\Auditable as AuditableTrait;", "", $content);
    
    // Wait, the original script might have added it multiple times if run multiple times? No, it skipped if present.
    // Let's re-inject properly after namespace if it implements Auditable
    if (strpos($content, 'implements Auditable') !== false) {
        // Ensure imports are at the top, after namespace
        if (strpos($content, 'use OwenIt\Auditing\Contracts\Auditable;') === false) {
            $content = preg_replace('/(namespace\s+[\w\\\\]+;)/i', "$1\n\nuse OwenIt\Auditing\Contracts\Auditable;\nuse OwenIt\Auditing\Auditable as AuditableTrait;", $content);
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    }
}
echo "Done\n";
