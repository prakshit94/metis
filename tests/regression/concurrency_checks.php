<?php

use Illuminate\Support\Facades\DB;

// Included after fixture rollback, with the disposable schema still available.
$parallel = function (string $mode, array $payloads) use ($testDatabase): array {
    $barrier = sys_get_temp_dir().'/metis-regression-'.bin2hex(random_bytes(8));
    $workers = [];
    $environment = array_merge(getenv(), ['METIS_EXISTING_TEST_DB' => $testDatabase]);
    try {
        foreach ($payloads as $payload) {
            $payload['barrier'] = $barrier;
            $process = proc_open([PHP_BINARY, __DIR__.'/concurrency_worker.php', $mode, json_encode($payload)],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
            if (! is_resource($process)) {
                throw new RuntimeException('Cannot start concurrent test worker');
            }
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $deadline = microtime(true) + 10;
        while (count(glob($barrier.'.ready.*')) < count($workers) && microtime(true) < $deadline) {
            usleep(10000);
        }
        file_put_contents($barrier.'.go', 'go');
        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            if (proc_close($process) !== 0) {
                throw new RuntimeException('Concurrent worker failed: '.$error.' '.$output);
            }
            try {
                $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('Non-JSON worker output for '.$mode.': '.substr($output, 0, 3000).' '.$error, previous: $exception);
            }
        }

        return $results;
    } finally {
        foreach (glob($barrier.'.*') as $file) {
            unlink($file);
        }
    }
};
$values = array_column($parallel('sequence', array_fill(0, 6, [])), 'result');
check(count(array_unique($values)) === 6 && max($values) === 6, 'concurrent sequence allocation stays unique');
DB::table('india_post_barcode_ranges')->insert(['office_id' => 'default', 'prefix' => 'EA', 'start_sequence' => 10000000, 'current_sequence' => 10000000, 'end_sequence' => 10000010, 'status' => 'active']);
$barcodes = array_column($parallel('awb', [[], []]), 'result');
check(count(array_unique($barcodes)) === 2 && DB::table('india_post_awb_trackings')->count() === 2, 'concurrent AWBs are uniquely committed without a remote booking call');
$uid = DB::table('users')->insertGetId(['name' => 'Concurrent User', 'email' => 'concurrent@example.invalid', 'password' => 'unused']);
$party = DB::table('parties')->insertGetId(['type' => 'customer', 'wallet_balance' => 100]);
$wh = DB::table('warehouses')->insertGetId(['name' => 'Concurrent warehouse']);
$product = DB::table('products')->insertGetId(['name' => 'Concurrent product', 'sku' => 'CONCURRENT', 'slug' => 'concurrent']);
$order = DB::table('orders')->insertGetId(['order_no' => 'CONCURRENT-QC', 'type' => 'sale', 'party_id' => $party, 'warehouse_id' => $wh, 'order_date' => now(), 'status' => 'return_requested', 'created_by' => $uid]);
DB::table('order_items')->insert(['order_id' => $order, 'product_id' => $product, 'quantity' => 1, 'unit_price' => 10, 'total_amount' => 10]);
$returnId = DB::table('order_returns')->insertGetId(['order_id' => $order, 'return_no' => 'CONCURRENT-RETURN', 'status' => 'approved']);
$itemId = DB::table('order_return_items')->insertGetId(['order_return_id' => $returnId, 'product_id' => $product, 'requested_qty' => 1]);
$qc = $parallel('qc', array_fill(0, 2, ['user' => $uid, 'return' => $returnId, 'item' => $itemId]));
check(count(array_filter($qc, fn ($r) => $r['ok'])) === 1 && (float) DB::table('stocks')->where('product_id', $product)->sum('quantity') === 1.0, 'concurrent stale QC requests restock exactly once');
$wallet = $parallel('wallet', array_map(fn ($number) => ['user' => $uid, 'party' => $party, 'warehouse' => $wh, 'product' => $product, 'number' => $number], [1, 2]));
check(count(array_filter($wallet, fn ($r) => $r['ok'])) === 2 && (float) DB::table('parties')->where('id', $party)->value('wallet_balance') === 0.0
    && (float) DB::table('orders')->where('order_no', 'like', 'WALLET-%')->sum('wallet_amount_used') === 100.0, 'concurrent orders cannot redeem the same wallet funds twice');
