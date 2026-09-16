<?php
$file = 'app/Modules/Orders/Controllers/OrderController.php';
$content = file_get_contents($file);

// We need to move the filter block into a protected function and call it from index and bulkExport
// This is somewhat complex via string replacement. Let's do it using regex.
