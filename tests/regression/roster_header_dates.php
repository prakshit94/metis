<?php

use App\Modules\Users\Services\RosterImport;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

// Parser-only checks: no database queries or roster writes.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();


function parseHeaderCsv(string $csv): RosterImport
{
    $path = tempnam(sys_get_temp_dir(), 'roster-headers-');
    try {
        file_put_contents($path, $csv);
        $parser = new RosterImport;
        $parser->parse(new UploadedFile($path, 'roster.csv', null, null, true));

        return $parser;
    } finally {
        unlink($path);
    }
}
foreach (['2026-11-02', '02-Nov-26', '2-Nov-2026', '02/11/2026', '2/11/2026', '02-11-2026', '2-11-26', '02 Nov 26', '46328', "'2026-11-02", "\xC2\xA002-Nov-26\xC2\xA0"] as $header) {
    $parser = parseHeaderCsv("EMP. ID,Advisor Name,LOB,$header\n15451,Employee,Sales,08:00-17:00\n");
    if ($parser->dates !== ['2026-11-02']) {
        throw new RuntimeException("Incorrect normalized date for $header");
    }
    echo "PASS: normalized $header\n";
}
$parser = parseHeaderCsv("EMP. ID,Advisor Name,LOB,2026-11-02,,\n15451,Employee,Sales,WO\n");
if ($parser->dates !== ['2026-11-02']) {
    throw new RuntimeException('Trailing blank columns removed nonempty data');
}
echo "PASS: trailing empty columns preserve shorter employee rows\n";
foreach ([
    "EMP. ID,Advisor Name,LOB,31/02/2026\n15451,Employee,Sales,WO\n" => 'invalid date header',
    "EMP. ID,Advisor Name,LOB,2026-11-02,02/11/2026\n15451,Employee,Sales,WO,L\n" => 'repeats the date 2026-11-02',
    "EMP. ID,Advisor Name,LOB,2026-11-02,\n15451,Employee,Sales,WO,L\n" => 'invalid date header',
] as $csv => $expected) {
    try {
        parseHeaderCsv($csv);
        throw new RuntimeException('Invalid header accepted');
    } catch (ValidationException $e) {
        $message = implode(' ', $e->errors()['file']);
        if (! str_contains($message, $expected) || ! str_contains($message, 'Column ')) {
            throw new RuntimeException($message);
        }
        echo "PASS: precise error for $expected\n";
    }
}
