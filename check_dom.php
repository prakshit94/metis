<?php
libxml_use_internal_errors(true);
$content = file_get_contents('resources/views/users/index.blade.php');
$content = preg_replace('/@(?:extends|section|endsection|push|endpush)[^\n]*/', '', $content);
$doc = new DOMDocument();
$doc->loadHTML('<?xml encoding="UTF-8">' . $content);
foreach (libxml_get_errors() as $error) {
    if (strpos($error->message, 'Tag') !== false) {
        echo "Line " . $error->line . ": " . $error->message;
    }
}
libxml_clear_errors();
