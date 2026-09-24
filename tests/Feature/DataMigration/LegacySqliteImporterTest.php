<?php

namespace Tests\Feature\DataMigration;

use App\Models\Customer;
use App\Models\Job;
use App\Models\RateSetting;
use App\Models\User;
use App\Services\DataMigration\LegacySqliteImporter;
use App\Services\DataMigration\LegacySqliteImportPlanner;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use UnexpectedValueException;

class LegacySqliteImporterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_legacy_sqlite_importer_is_available(): void
    {
        $this->assertTrue(class_exists(LegacySqliteImporter::class));
    }

    #[Test]
    public function imported_identifiers_and_relationships_are_reconciled_without_changing_the_operations_user(): void
    {
        $password = Hash::make('password');
        $user = User::query()->create([
            'name' => 'Existing Operator',
            'email' => 'ops@sweq.local',
            'password' => $password,
            'can_manage_quote_exceptions' => true,
        ]);
        Customer::query()->create(['name' => 'Existing Customer']);
        $plan = (new LegacySqliteImportPlanner)->plan($this->createLegacySource());

        $result = (new LegacySqliteImporter)->import($plan);

        $importedJob = Job::query()->where('customer_id', $result['mappings']['customers']['1'])->firstOrFail();
        $this->assertSame($user->id, $result['mappings']['users']['1']);
        $this->assertSame($password, $user->fresh()->getRawOriginal('password'));
        $this->assertTrue($user->fresh()->can_manage_quote_exceptions);
        $this->assertNotSame(1, $result['mappings']['customers']['1']);
        $this->assertSame($result['mappings']['job_revisions']['1'], $importedJob->current_working_revision_id);
        $this->assertDatabaseHas('route_legs', [
            'job_revision_id' => $result['mappings']['job_revisions']['1'],
            'sequence' => 1,
        ]);
        $this->assertDatabaseHas('fuel_price_sources', [
            'key' => 'manual_texaco_entry',
            'display_name' => 'Texaco (manual entry)',
        ]);
        $this->assertDatabaseHas('rate_settings', [
            'name' => 'Initial internal rates [legacy-1]',
            'one_horse_multiplier' => '1.000000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => false,
        ]);
        $this->assertFalse(RateSetting::query()->where('name', 'Initial internal rates [legacy-1]')->firstOrFail()->is_active);
    }

    #[Test]
    public function a_conflict_rolls_back_every_business_record_write(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        DB::statement("CREATE TRIGGER reject_imported_route BEFORE INSERT ON route_legs WHEN NEW.sequence = 1 BEGIN SELECT RAISE(ABORT, 'route rejected'); END");
        $plan = (new LegacySqliteImportPlanner)->plan($this->createLegacySource());

        try {
            (new LegacySqliteImporter)->import($plan);
            $this->fail('The import did not reject the duplicate route sequence.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('weekly_fuel_prices', 0);
        $this->assertDatabaseCount('fuel_price_sources', 0);
        $this->assertDatabaseCount('rate_settings', 0);
    }

    #[Test]
    public function preflight_detects_conflicts_without_writing_business_records(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        $plan = (new LegacySqliteImportPlanner)->plan($this->createLegacySource());

        (new LegacySqliteImporter)->preflight($plan);

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('weekly_fuel_prices', 0);
        $this->assertDatabaseCount('fuel_price_sources', 0);
        $this->assertDatabaseCount('rate_settings', 0);
    }

    #[Test]
    public function ambiguous_normalised_operations_users_are_rejected(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        User::factory()->create(['email' => ' OPS@SWEQ.LOCAL ']);
        $plan = (new LegacySqliteImportPlanner)->plan($this->createLegacySource());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Multiple PostgreSQL users match legacy user 1.');

        (new LegacySqliteImporter)->preflight($plan);
    }

    #[Test]
    public function duplicate_target_weekly_fuel_rows_are_rejected_before_import(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        DB::table('weekly_fuel_prices')->insert([
            [
                'week_commencing' => '2026-08-10',
                'source' => 'manual_texaco_entry',
                'price_per_litre_inc_vat' => '1.5300',
                'is_active' => true,
            ],
            [
                'week_commencing' => '2026-08-10',
                'source' => 'manual_texaco_entry',
                'price_per_litre_inc_vat' => '1.5300',
                'is_active' => false,
            ],
        ]);
        $plan = (new LegacySqliteImportPlanner)->plan($this->createLegacySource());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Multiple PostgreSQL weekly fuel rows match the legacy fuel identity.');

        (new LegacySqliteImporter)->preflight($plan);
    }

    private function createLegacySource(?string $mutation = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sweq-import-source-');
        $pdo = new PDO('sqlite:'.$path);
        $pdo->exec(file_get_contents(base_path('tests/Fixtures/legacy-import.sql')));
        if ($mutation !== null) {
            $pdo->exec($mutation);
        }

        return $path;
    }
}
