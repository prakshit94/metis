<?php
$files = [
    'app/Modules/Customers/Controllers/CustomerController.php',
    'app/Modules/Orders/Controllers/OrderController.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace(
        "withCount('complaints')",
        "withCount(['complaints', 'complaints as open_complaints_count' => function (\$q) { \$q->whereIn('status', ['open', 'in_progress']); }])",
        $content
    );
    file_put_contents($file, $content);
    echo "Patched $file\n";
}
