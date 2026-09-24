<?php

namespace App\Services\DataMigration;

use RuntimeException;

class LegacyImportReportWriter
{
    public function write(string $directory, string $name, array $report): string
    {
        $this->prepareDirectory($directory);
        $path = $directory.DIRECTORY_SEPARATOR.$name.'.json';
        $json = json_encode($this->redact($report), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json.PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('The private import report could not be written.');
        }

        chmod($path, 0600);

        return $path;
    }

    private function prepareDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('The private import directory could not be created.');
        }

        chmod($directory, 0700);
    }

    private function redact(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|email|phone|contact/i', $key) === 1) {
                continue;
            }
            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }
}
