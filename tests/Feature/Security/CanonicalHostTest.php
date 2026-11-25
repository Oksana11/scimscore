<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class CanonicalHostTest extends TestCase
{
    public function test_www_redirects_to_the_canonical_host_keeping_the_path(): void
    {
        config(['app.url' => 'https://scimscore.com']);

        $this->get('http://www.scimscore.com/terms?x=1')
            ->assertStatus(301)
            ->assertRedirect('https://scimscore.com/terms?x=1');
    }

    public function test_other_hosts_are_untouched(): void
    {
        config(['app.url' => 'https://scimscore.com']);
        $this->withoutVite();

        $this->get('http://scimscore.com/up')->assertOk();
    }
}
