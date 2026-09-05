<?php
$user1 = \App\Modules\Users\Models\User::where('email', 'rj.agent@metis.test')->first();
$user2 = \App\Modules\Users\Models\User::where('email', 'agent.one@example.com')->first();

if (!$user1) echo "User rj.agent@metis.test not found\n";
if (!$user2) echo "User agent.one@example.com not found\n";

if ($user1 && $user2) {
    echo "--- rj.agent@metis.test ---\n";
    echo "Direct Permissions: " . implode(', ', $user1->getDirectPermissions()->pluck('name')->toArray()) . "\n";
    echo "Roles: " . implode(', ', $user1->roles->pluck('name')->toArray()) . "\n";
    echo "All Permissions: " . implode(', ', $user1->getAllPermissions()->pluck('name')->toArray()) . "\n";

    echo "\n--- agent.one@example.com ---\n";
    echo "Direct Permissions: " . implode(', ', $user2->getDirectPermissions()->pluck('name')->toArray()) . "\n";
    echo "Roles: " . implode(', ', $user2->roles->pluck('name')->toArray()) . "\n";
    echo "All Permissions: " . implode(', ', $user2->getAllPermissions()->pluck('name')->toArray()) . "\n";
}
