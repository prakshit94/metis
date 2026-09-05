<?php
$user1 = \App\Modules\Users\Models\User::where('email', 'rj.agent@metis.test')->first();
$user2 = \App\Modules\Users\Models\User::where('email', 'agent.one@example.com')->first();

function dump_user($user) {
    if (!$user) return;
    
    // Simulate TeamContextMiddleware
    if ($teamId = $user->lob_team_id) {
        setPermissionsTeamId($teamId);
    } else {
        setPermissionsTeamId(null);
    }
    
    echo "--- {$user->email} ---\n";
    echo "Direct Permissions: " . implode(', ', $user->getDirectPermissions()->pluck('name')->toArray()) . "\n";
    echo "Roles: " . implode(', ', $user->roles->pluck('name')->toArray()) . "\n";
    echo "All Permissions: " . implode(', ', $user->getAllPermissions()->pluck('name')->toArray()) . "\n";
}

dump_user($user1);
dump_user($user2);
