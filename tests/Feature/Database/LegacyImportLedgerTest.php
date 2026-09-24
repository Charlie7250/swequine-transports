<?php

namespace Tests\Feature\Database;

use App\Models\LegacyImportRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyImportLedgerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function legacy_import_runs_store_safety_and_reconciliation_evidence(): void
    {
        $this->assertTrue(Schema::hasColumns('legacy_import_runs', [
            'source_path',
            'source_checksum',
            'source_schema_fingerprint',
            'target_identity',
            'target_schema_fingerprint',
            'importer_version',
            'defaults_version',
            'phase',
            'status',
            'is_dry_run',
            'was_maintenance_active',
            'source_snapshot_path',
            'source_snapshot_checksum',
            'backup_manifest_path',
            'backup_checksum',
            'report_path',
            'counts',
            'identifier_mappings',
            'mapping_checksum',
            'verification_results',
            'failure_message',
            'started_at',
            'committed_at',
            'verified_at',
        ]));
    }

    #[Test]
    public function legacy_import_run_evidence_is_cast_to_structured_values(): void
    {
        $run = LegacyImportRun::query()->create([
            'source_path' => 'private/source.sqlite',
            'source_checksum' => str_repeat('a', 64),
            'source_schema_fingerprint' => str_repeat('b', 64),
            'target_identity' => ['database' => 'sweq_transports'],
            'target_schema_fingerprint' => str_repeat('c', 64),
            'importer_version' => '1',
            'defaults_version' => '1',
            'phase' => 'preflight',
            'status' => 'planned',
            'is_dry_run' => true,
            'counts' => ['jobs' => 2],
            'started_at' => now(),
        ]);

        $this->assertSame(['database' => 'sweq_transports'], $run->target_identity);
        $this->assertSame(['jobs' => 2], $run->counts);
        $this->assertTrue($run->is_dry_run);
    }
}
