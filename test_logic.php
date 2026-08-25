<?php
$data = ['is_draft' => 0];
$order_is_draft = 0;
$order_status = 'future_order';

$isDraft = isset($data['is_draft']) ? (bool) $data['is_draft'] : $order_is_draft;
$oldStatus = $order_status;
$newStatus = $oldStatus;

if ($oldStatus === 'future_order' && !$isDraft) {
    $newStatus = 'pending';
} elseif ($oldStatus === 'pending' && $isDraft) {
    $newStatus = 'future_order';
}

echo "isDraft: " . var_export($isDraft, true) . "\n";
echo "newStatus: " . $newStatus . "\n";
