<?php
$file = 'resources/views/teams/index.blade.php';
$content = file_get_contents($file);

$target = "                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                
                this.teams = data.data;";

$replacement = "                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                
                if (!res.ok) {
                    const errorMsg = data?.message || data?.error || 'Failed to load teams.';
                    if (res.status === 403 || errorMsg.toLowerCase().includes('authoriz') || errorMsg.toLowerCase().includes('forbidden')) {
                        window.location.href = '/';
                        return;
                    }
                    throw new Error(errorMsg);
                }
                
                this.teams = data.data;";

$content = str_replace($target, $replacement, $content);

// Also fix colspan="6" to colspan="7"
$content = str_replace('<td colspan="6" class="text-center py-5">', '<td colspan="7" class="text-center py-5">', $content);
$content = str_replace('<td colspan="6" class="text-center py-5 text-muted">', '<td colspan="7" class="text-center py-5 text-muted">', $content);

file_put_contents($file, $content);
echo "Patched loadTeams in teams view.\n";
