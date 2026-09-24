<?php

namespace Tests\Unit\DataMigration;

use App\Services\DataMigration\LegacyImportReportWriter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LegacyImportReportWriterTest extends TestCase
{
    #[Test]
    public function private_reports_exclude_credentials_and_personal_contact_data(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sweq-report-'.bin2hex(random_bytes(4));
        $path = (new LegacyImportReportWriter)->write($directory, 'dry-run', [
            'counts' => ['jobs' => 2],
            'password' => 'do-not-write-this',
            'api_token' => 'do-not-write-this-either',
            'customer_email' => 'customer@example.test',
        ]);
        $contents = file_get_contents($path);

        $this->assertStringContainsString('"jobs": 2', $contents);
        $this->assertStringNotContainsString('do-not-write-this', $contents);
        $this->assertStringNotContainsString('customer@example.test', $contents);
    }
}
