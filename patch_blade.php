<?php
$file = 'resources/views/villages/index.blade.php';
$content = file_get_contents($file);

$search = <<<TXT
@push('scripts')
<!-- Loaded via main.js or separate scripts -->
@endpush
TXT;

$replace = <<<TXT
@push('scripts')
<!-- Loaded via main.js or separate scripts -->
<script>
    window.backendSyncing = @json(\Illuminate\Support\Facades\Cache::has('syncing_indiapost_pincodes'));
    window.backendSyncQuery = @json(\Illuminate\Support\Facades\Cache::get('syncing_indiapost_pincodes_query', 'ALL'));
</script>
@endpush
TXT;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Patched Blade successfully\n";
