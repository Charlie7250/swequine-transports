<?php

namespace Tests\Unit\DataMigration;

use App\Services\DataMigration\LegacySqliteImportPlanner;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class LegacySqliteImportPlannerTest extends TestCase
{
    #[Test]
    public function the_legacy_import_planner_is_available(): void
    {
        $this->assertTrue(class_exists(LegacySqliteImportPlanner::class));
    }

    #[Test]
    public function legacy_pricing_is_transformed_without_changing_business_values(): void
    {
        $source = $this->createLegacySource();

        $plan = (new LegacySqliteImportPlanner)->plan($source);
        $rate = $plan['rows']['rate_settings'][0];

        $this->assertSame('Initial internal rates [legacy-1]', $rate['name']);
        $this->assertSame('1.000000', $rate['one_horse_multiplier']);
        $this->assertSame('1.150000', $rate['two_horse_multiplier']);
        $this->assertSame('0.750000', $rate['shared_load_percentage']);
        $this->assertFalse($rate['is_active']);
        $this->assertSame('30.00', $rate['loading_practice_within_15_miles_price']);
        $this->assertSame('410.00', $rate['loading_practice_livery_fortnight_rate']);
        $this->assertSame('ops@sweq.local', $plan['rows']['users'][0]['normalised_email']);
    }

    #[Test]
    public function unknown_legacy_tables_are_rejected(): void
    {
        $source = $this->createLegacySource('CREATE TABLE horses (id INTEGER PRIMARY KEY);');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported legacy table: horses');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function malformed_calculation_json_is_rejected(): void
    {
        $source = $this->createLegacySource("UPDATE job_revisions SET calculation_explanation = '{bad';");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Malformed calculation JSON for job revision 1.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function invalid_job_statuses_are_rejected(): void
    {
        $source = $this->createLegacySource("UPDATE jobs SET status = 'unknown';");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid job status for job 1.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function unresolved_customer_relationships_are_rejected(): void
    {
        $source = $this->createLegacySource('UPDATE jobs SET customer_id = 99;');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unresolved customer 99 for job 1.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function non_ascii_legacy_email_is_rejected(): void
    {
        $source = $this->createLegacySource("UPDATE users SET email = 'opérator@sweq.local';");

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Legacy user 1 has a non-ASCII email address.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function unexpected_legacy_fuel_values_are_rejected(): void
    {
        $source = $this->createLegacySource('UPDATE weekly_fuel_prices SET price_per_litre_inc_vat = 1.6000;');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The legacy weekly fuel row does not match the approved invariant.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function unresolved_job_revision_pointers_are_rejected(): void
    {
        $source = $this->createLegacySource('UPDATE jobs SET current_working_revision_id = 99;');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unresolved current_working_revision_id for job 1.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function duplicate_route_sequences_are_rejected_before_import(): void
    {
        $source = $this->createLegacySource('INSERT INTO route_legs SELECT 2, job_revision_id, sequence, label, start_postcode, end_postcode, miles, manual_miles, rate_type, rate_per_mile, amount, created_at, updated_at FROM route_legs WHERE id = 1;');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Duplicate route sequence 1 for job revision 1.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function unexpected_historical_two_horse_pricing_is_rejected(): void
    {
        $source = $this->createLegacySource('UPDATE rate_settings SET two_horse_multiplier = 1.20;');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The legacy historical rate must use a 1.15 two-horse multiplier.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    #[Test]
    public function missing_historical_two_horse_pricing_is_rejected(): void
    {
        $source = $this->createLegacySource('UPDATE rate_settings SET two_horse_multiplier = NULL;');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The legacy historical rate must use a 1.15 two-horse multiplier.');

        (new LegacySqliteImportPlanner)->plan($source);
    }

    private function createLegacySource(?string $mutation = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sweq-legacy-');
        $pdo = new PDO('sqlite:'.$path);
        $sql = file_get_contents(dirname(__DIR__, 2).'/Fixtures/legacy-import.sql');
        $pdo->exec($sql);

        if ($mutation !== null) {
            $pdo->exec($mutation);
        }

        return $path;
    }
}
