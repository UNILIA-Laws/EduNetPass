<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Reads a .csv / .txt / .xlsx upload into a list of associative rows.
 */
class StudentFileReader
{
    private const COLUMNS = ['givenName', 'sn', 'uid', 'mail', 'userPassword', 'Reg'];
    private const REQUIRED = ['givenName', 'sn', 'uid', 'mail'];

    /** @return list<array<string,string>> */
    public function read(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $raw = $ext === 'xlsx' ? $this->readXlsx($file->getRealPath()) : $this->readCsv($file->getRealPath());

        if (count($raw) < 2) {
            throw new InvalidArgumentException('The file has no data rows.');
        }

        // Map headers (case-insensitive) to canonical column names
        $lookup = [];
        foreach (self::COLUMNS as $c) {
            $lookup[strtolower($c)] = $c;
        }

        $headers = [];
        foreach (array_shift($raw) as $i => $h) {
            $h = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)));
            if (isset($lookup[$h])) {
                $headers[$i] = $lookup[$h];
            }
        }

        $missing = array_diff(self::REQUIRED, $headers);
        if ($missing) {
            throw new InvalidArgumentException(
                'Missing required column(s): ' . implode(', ', $missing) .
                '. Expected headers: ' . implode(', ', self::COLUMNS) . '.'
            );
        }

        $rows = [];
        foreach ($raw as $line) {
            $row = [];
            foreach ($headers as $i => $name) {
                $row[$name] = trim((string) ($line[$i] ?? ''));
            }
            if (implode('', $row) === '') {
                continue; // blank line
            }
            $rows[] = $row;
        }

        $max = (int) config('ldap.max_rows_per_upload');
        if (count($rows) > $max) {
            throw new InvalidArgumentException("Too many rows (" . count($rows) . "). Maximum per upload is {$max}.");
        }

        return $rows;
    }

    private function readCsv(string $path): array
    {
        $out = [];
        $h = fopen($path, 'r');
        while (($line = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
            $out[] = $line;
        }
        fclose($h);
        return $out;
    }

    private function readXlsx(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new InvalidArgumentException(
                'Excel support is not installed. Run "composer require phpoffice/phpspreadsheet" or upload a CSV.'
            );
        }

        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
    }
}
