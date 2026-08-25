<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$request = new \Illuminate\Http\Request();
$request->merge(['is_draft' => 0]);
$validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
    'is_draft' => 'nullable|boolean',
]);
var_dump($validator->validated());
