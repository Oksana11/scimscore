<?php

namespace Tests\Unit;

use App\Support\Security\AuditCategories;
use PHPUnit\Framework\TestCase;

class AuditCategoriesTest extends TestCase
{
    public function test_actions_are_grouped(): void
    {
        $this->assertSame('views', AuditCategories::of('patient.viewed'));
        $this->assertSame('views', AuditCategories::of('admin.view_users'));
        $this->assertSame('security', AuditCategories::of('access.denied'));
        $this->assertSame('security', AuditCategories::of('auth.otp_failed'));
        $this->assertSame('security', AuditCategories::of('admin.mfa_failed'));
        $this->assertSame('exports', AuditCategories::of('export.xlsx'));
        $this->assertSame('admin', AuditCategories::of('admin.page_updated'));
        $this->assertSame('data', AuditCategories::of('patient.created'));
        $this->assertSame('data', AuditCategories::of('support.message_received'));
    }
}
