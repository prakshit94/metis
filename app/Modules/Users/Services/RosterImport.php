<?php

namespace App\Modules\Users\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/** Parse roster input only; never creates attendance, leave requests or balances. */
class RosterImport
{
    public array $dates = [];

    public function parse(UploadedFile $file): array
    {
        $this->dates = [];
        ExcelDate::setExcelCalendar(ExcelDate::CALENDAR_WINDOWS_1900);
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            $this->fail('Upload a CSV or XLSX workbook, not a screenshot.');
        }
        $sheet = null;
        if ($extension === 'csv') {
            $handle = fopen($file->getRealPath(), 'r');
            $grid = [];
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    $grid[] = $row;
                    if (count($grid) > 2002 || count($row) > 369) {
                        $this->fail('Maximum 2,000 employee rows and 366 date columns.');
                    }
                }
            } finally {
                fclose($handle);
            }
        } else {
            try {
                $reader = IOFactory::createReader('Xlsx');
                $info = $reader->listWorksheetInfo($file->getRealPath());
                if (count($info) !== 1) {
                    $this->fail('Use a workbook with exactly one roster worksheet.');
                }
                if ($info[0]['totalRows'] > 2002 || $info[0]['totalColumns'] > 369) {
                    $this->fail('Maximum 2,000 employee rows and 366 date columns.');
                }
                $reader->setReadDataOnly(true);
                $sheet = $reader->load($file->getRealPath());
                $grid = $sheet->getActiveSheet()->toArray(null, false, false, false);
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $this->fail('The XLSX workbook could not be read. Save it as a valid XLSX or CSV file.');
            } finally {
                $sheet?->disconnectWorksheets();
            }
        }
        // Spreadsheet exports can retain extra, completely empty columns.
        while ($grid && count($grid[0]) > 3) {
            $last = count($grid[0]) - 1;
            foreach ($grid as $row) {
                if (trim((string) ($row[$last] ?? '')) !== '') {
                    break 2;
                }
            }
            foreach ($grid as &$row) {
                unset($row[$last]);
            }
            unset($row);
        }
        $header = array_map(fn ($v) => trim(str_replace("\xC2\xA0", ' ', (string) $v)), array_shift($grid) ?? []);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $legacy = in_array($header, [['employee_id', 'date', 'day_type'], ['user_id', 'date', 'day_type']], true);
        $dates = [];
        if (! $legacy) {
            if (array_map(fn ($v) => strtolower($v), array_slice($header, 0, 3)) !== ['emp. id', 'advisor name', 'lob'] || count($header) < 4) {
                $this->fail('Expected EMP. ID,Advisor Name,LOB,date columns, or employee_id,date,day_type / user_id,date,day_type.');
            }
            foreach (array_slice($header, 3, null, true) as $column => $value) {
                $date = $this->date($value, true);
                if (! $date) {
                    $label = substr($value, 0, 80);
                    $this->fail('Column '.($column + 1)." has an invalid date header '$label'. Use YYYY-MM-DD, 02-Nov-26, DD/MM/YYYY or DD-MM-YYYY (day first). Excel numeric dates are also supported.");
                }
                if (in_array($date, $dates, true)) {
                    $this->fail('Column '.($column + 1)." repeats the date $date. Each date must appear only once.");
                }
                $dates[$column] = $date;
            }
        }
        $this->dates = array_values($dates);
        $entries = [];
        $errors = [];
        $employeeRows = 0;
        foreach ($grid as $index => $values) {
            $line = $index + 2;
            $values = array_map(fn ($v) => trim((string) $v), $values);
            if (! array_filter($values, fn ($v) => $v !== '')) {
                continue;
            }
            if (! $legacy && $index === 0 && implode('', array_slice($values, 0, 3)) === '') {
                foreach ($dates as $column => $date) {
                    if (strcasecmp($values[$column] ?? '', date('l', strtotime($date))) !== 0) {
                        $errors[] = "Row $line: weekday does not match $date.";
                    }
                }

                continue;
            }
            if (++$employeeRows > 2000) {
                $this->fail('Maximum 2,000 employee rows.');
            }
            if (count($values) !== count($header)) {
                $errors[] = "Row $line: column count does not match the header.";

                continue;
            }
            $cells = $legacy ? [1 => $values[1]] : $dates;
            foreach ($cells as $column => $value) {
                $date = $legacy ? $this->date($value, false, true) : $value;
                $code = $legacy ? $values[2] : $values[$column];
                if (! $legacy && $code === '') {
                    continue;
                } // Blank means leave the stored date untouched.
                $schedule = $this->schedule($code, $legacy);
                if (! $date || ! $schedule) {
                    $errors[] = "Row $line, column ".($legacy ? 3 : $column + 1).": invalid date or roster value '$code'. Use HH:MM-HH:MM, WO, C/OFF or L.";

                    continue;
                }
                $entries[] = ['line' => $line, 'identifier' => $values[0], 'lob' => $legacy ? '' : $values[2], 'identifier_field' => $legacy && $header[0] === 'user_id' ? 'id' : 'employee_id', ...$schedule, 'date' => $date];
                if (count($entries) > 10000) {
                    $this->fail('Maximum 10,000 employee/date entries per import.');
                }
            }
        }
        if ($errors) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 50)]);
        }
        if (! $entries) {
            $this->fail('The file contains no roster entries.');
        }

        return $entries;
    }

    private function date(string $value, bool $excel, bool $isoOnly = false): ?string
    {
        $value = trim(str_replace("\xC2\xA0", ' ', $value));
        if (! $isoOnly) {
            $value = ltrim($value, "'");
        }
        if ($excel && is_numeric($value) && (int) $value == (float) $value && (int) $value > 0 && (int) $value <= 2958465) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        foreach ($isoOnly ? ['Y-m-d'] : ['Y-m-d', 'd-M-y', 'd-M-Y', 'j-M-y', 'j-M-Y', 'd/m/Y', 'j/n/Y', 'j/m/Y', 'd/n/Y', 'd-m-Y', 'j-n-Y', 'j-m-Y', 'd-n-Y', 'd/m/y', 'j/n/y', 'j/m/y', 'd/n/y', 'd-m-y', 'j-n-y', 'j-m-y', 'd-n-y', 'd M Y', 'j M Y', 'd M y', 'j M y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date && (! \DateTimeImmutable::getLastErrors() || \DateTimeImmutable::getLastErrors()['warning_count'] === 0) && (int) $date->format('Y') >= 1000 && strcasecmp($date->format($format), $value) === 0) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function schedule(string $code, bool $legacy): ?array
    {
        $types = $legacy ? ['Working' => 'Working', 'Week Off' => 'Week Off'] : ['WO' => 'Week Off', 'C/OFF' => 'Comp Off', 'L' => 'Planned Leave', 'WORKING' => 'Working', 'WEEK OFF' => 'Week Off'];
        $key = $legacy ? $code : strtoupper($code);
        if (isset($types[$key])) {
            return ['day_type' => $types[$key], 'shift_start' => null, 'shift_end' => null];
        }
        if (! $legacy && preg_match('/^([01]\d|2[0-3]):([0-5]\d)\s*[-–]\s*([01]\d|2[0-3]):([0-5]\d)$/u', $code, $m) && "$m[1]:$m[2]" !== "$m[3]:$m[4]") {
            return ['day_type' => 'Working', 'shift_start' => "$m[1]:$m[2]", 'shift_end' => "$m[3]:$m[4]"];
        }

        return null;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
