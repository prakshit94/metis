<?php

use App\Services\TwoFactorService;

require __DIR__.'/bootstrap.php';

$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$result = app(TwoFactorService::class)->verifyLogin($input['challenge'], $input['code'], $input['channel'], requestFor([]));
echo json_encode(['verified' => $result !== null]);
