<?php
$files = glob("app/Services/*.php");
$files = array_merge($files, glob("app/Services/*/*.php"));

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Find all occurrences of DB::transaction
    $offset = 0;
    while (($pos = strpos($content, 'DB::transaction(', $offset)) !== false) {
        // Find the matching closing parenthesis for DB::transaction(
        $openCount = 0;
        $closePos = -1;
        for ($i = $pos + 16; $i < strlen($content); $i++) {
            if ($content[$i] === '(') $openCount++;
            if ($content[$i] === ')') {
                if ($openCount === 0) {
                    $closePos = $i;
                    break;
                }
                $openCount--;
            }
        }
        
        if ($closePos !== -1) {
            // Check if it already has a retry count like , 3)
            $beforeClose = substr($content, $closePos - 5, 5);
            if (!preg_match('/,\s*\d+\s*$/', $beforeClose)) {
                $content = substr_replace($content, ', 3)', $closePos, 1);
            }
        }
        $offset = $pos + 16;
    }
    file_put_contents($file, $content);
}
echo "Done";
