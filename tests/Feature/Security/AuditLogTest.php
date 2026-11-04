<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Support\Security\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function writeThree(): void
    {
        $logger = app(AuditLogger::class);
        $logger->log('test.one');
        $logger->log('test.two', context: ['b' => 2, 'a' => 1]);
        $logger->log('test.three');
    }

    public function test_records_are_chained_and_verify_passes(): void
    {
        $this->writeThree();

        $rows = AuditLog::query()->orderBy('id')->get();

        $this->assertCount(3, $rows);
        $this->assertSame(AuditLogger::GENESIS, $rows[0]->prev_hash);
        $this->assertSame($rows[0]->hash, $rows[1]->prev_hash);
        $this->assertSame($rows[1]->hash, $rows[2]->prev_hash);

        $this->artisan('audit:verify')->assertSuccessful();
    }

    public function test_modified_record_is_detected(): void
    {
        $this->writeThree();

        DB::table('audit_logs')->where('action', 'test.two')->update(['action' => 'test.forged']);

        $this->artisan('audit:verify')->assertFailed();
    }

    public function test_deleted_record_is_detected(): void
    {
        $this->writeThree();

        DB::table('audit_logs')->where('action', 'test.two')->delete();

        $this->artisan('audit:verify')->assertFailed();
    }

    public function test_model_refuses_update_and_delete(): void
    {
        $log = app(AuditLogger::class)->log('test.immutable');

        try {
            $log->update(['action' => 'changed']);
            $this->fail('Update was allowed');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_secrets_are_redacted_from_context(): void
    {
        $log = app(AuditLogger::class)->log('test.redact', context: [
            'email_domain' => 'clinic.org',
            'code' => '123456',
            'nested' => ['token' => 'abc', 'ok' => 'yes'],
        ]);

        $this->assertStringNotContainsString('123456', (string) $log->context);
        $this->assertStringNotContainsString('abc', (string) $log->context);
        $this->assertStringContainsString('clinic.org', (string) $log->context);
        $this->assertStringContainsString('yes', (string) $log->context);
    }
}
