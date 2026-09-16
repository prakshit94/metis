<?php
$file = 'app/Imports/OrdersImport.php';
$content = file_get_contents($file);

$content = str_replace("Village::where('name', \$villageName)", "Village::where('village_name', \$villageName)", $content);
$content = str_replace("'village_name' => \$village->name,", "'village_name' => \$village->village_name,", $content);
$content = str_replace("'district' => \$village->district,", "'district' => \$village->district_name,", $content);
$content = str_replace("'state' => \$village->state,", "'state' => \$village->state_name,", $content);
$content = str_replace("Warehouse::where('state', \$village->state)", "Warehouse::where('state', \$village->state_name)", $content);

file_put_contents($file, $content);
echo "Patched Village columns\n";
