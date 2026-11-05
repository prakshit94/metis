<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;

function navigationCheck(bool $ok, string $message): void {
    if (! $ok) throw new RuntimeException($message);
    echo 'PASS: '.$message.PHP_EOL;
}
$header = file_get_contents(resource_path('views/components/header.blade.php'));
$sidebar = file_get_contents(resource_path('views/components/sidebar.blade.php'));
$start = strpos($header, '                    items: [');
$end = strpos($header, '                    selectedIndex: -1,', $start);
$items = substr($header, $start, $end - $start);
preg_match_all('/href="{{ route\(\'([^\']+)\'\)/', $sidebar, $matches);
$sidebarRoutes = array_unique($matches[1]);
foreach ($sidebarRoutes as $name) {
    navigationCheck(str_contains($items, "route('".$name."')"), 'search contains sidebar destination '.$name);
}
navigationCheck(str_contains($items, "path: '/docs/api'"), 'API documentation remains searchable');

// Override only authorization for isolated template rendering; no database or business records needed.
auth()->setUser(new User);
Gate::swap(new Illuminate\Auth\Access\Gate(app(), fn () => auth()->user()));
$allowed = [];
Gate::before(function ($user, $ability) use (&$allowed) { return in_array($ability, $allowed, true); });
$cases = [
    'customer-view' => 'Customers',
    'settings-view' => 'Customer Settings',
    'permission-view' => 'Roles & Permissions',
    'role-view' => 'Roles & Permissions',
    'messages-view' => 'Messages',
    'calendar-view' => 'Calendar',
    'forms-view' => 'Forms',
    'audit-log-view' => 'Audit Logs',
    'help-view' => 'Help & Support',
];
$allowed = [];
$baseline = Blade::render($items);
navigationCheck(str_contains($baseline, "name: 'My Security'"), 'personal security matches the always-visible sidebar entry');
navigationCheck(! str_contains($baseline, "name: 'API Documentation'"), 'API docs respect the sidebar workspace visibility gate');
foreach ($cases as $permission => $label) {
    $allowed = [$permission];
    $rendered = Blade::render($items);
    navigationCheck(str_contains($rendered, "name: '".$label."'"), $permission.' exposes its permitted search destination');
    navigationCheck(! str_contains($baseline, "name: '".$label."'"), $label.' stays hidden without its permission');
    navigationCheck(! str_contains($rendered, "name: 'Orders'"), $permission.' does not expose unrelated order navigation');
}
