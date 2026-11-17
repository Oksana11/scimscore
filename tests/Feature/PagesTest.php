<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Page;
use App\Support\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): void
    {
        $admin = Admin::query()->create(['email' => 'boss@scim.org', 'name' => 'Boss']);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();
        $this->actingAs($admin, 'admin');
    }

    public function test_seeded_pages_and_menus(): void
    {
        $this->get('/about')->assertOk()->assertSee('Loewenstein Rehabilitation Medical Center');
        $this->get('/terms')->assertOk()->assertSee('Version '.config('scim.terms_version'));
        $this->get('/')->assertOk()->assertSee('href="'.url('/about').'"', false);
        $this->get('/no-such-page')->assertNotFound();
        $this->get('/up')->assertOk();
    }

    public function test_markdown_is_rendered_without_raw_html_or_unsafe_links(): void
    {
        $page = Page::query()->create(['slug' => 'demo', 'title' => 'Demo', 'is_published' => true,
            'body' => "## Hi\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(1)) [good](https://example.com)"]);

        $res = $this->get('/demo')->assertOk();
        $res->assertSee('<h2>Hi</h2>', false);
        $res->assertDontSee('<script>alert(1)</script>', false);
        $res->assertDontSee('javascript:', false);
        $res->assertSee('href="https://example.com"', false);
    }

    public function test_admin_creates_edits_and_deletes_page(): void
    {
        $this->admin();

        $this->post('/admin/pages', ['title' => 'FAQ', 'slug' => 'faq', 'body' => 'Questions', 'show_in_footer' => '1', 'is_published' => '1', 'sort' => 5])
            ->assertRedirect();
        $page = Page::query()->where('slug', 'faq')->sole();
        $this->get('/faq')->assertOk()->assertSee('Questions');
        $this->get('/terms')->assertSee('href="'.url('/faq').'"', false);

        $this->put("/admin/pages/{$page->id}", ['title' => 'FAQ', 'slug' => 'faq', 'body' => 'Updated'])->assertRedirect();
        $this->get('/faq')->assertNotFound(); // unpublished: checkbox not sent

        $this->delete("/admin/pages/{$page->id}")->assertRedirect('/admin/pages');
        $this->assertNull(Page::query()->where('slug', 'faq')->first());
    }

    public function test_reserved_addresses_and_system_pages_are_protected(): void
    {
        $this->admin();

        $this->post('/admin/pages', ['title' => 'X', 'slug' => 'login', 'body' => 'x'])->assertSessionHasErrors('slug');
        $this->post('/admin/pages', ['title' => 'X', 'slug' => 'admin', 'body' => 'x'])->assertSessionHasErrors('slug');
        $this->post('/admin/pages', ['title' => 'X', 'slug' => 'Bad Slug', 'body' => 'x'])->assertSessionHasErrors('slug');

        $terms = Page::query()->where('slug', 'terms')->sole();
        $this->delete("/admin/pages/{$terms->id}")->assertForbidden();

        $this->put("/admin/pages/{$terms->id}", ['title' => 'Terms of Use', 'body' => 'New terms', 'version' => '2026-12'])->assertRedirect();
        $this->assertSame('2026-12', Page::termsVersion());
        $this->assertTrue($terms->fresh()->is_published);
        $this->get('/terms')->assertSee('New terms')->assertSee('Version 2026-12');
    }

    public function test_guests_cannot_manage_pages(): void
    {
        $this->get('/admin/pages')->assertRedirect('/admin/login');
        $this->post('/admin/pages', ['title' => 'X', 'slug' => 'x', 'body' => 'x'])->assertRedirect('/admin/login');
    }
}
