<?php

namespace Tests\Unit\Security;

use App\Support\Security\BlindIndex;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BlindIndexTest extends TestCase
{
    private function index(): BlindIndex
    {
        return new BlindIndex(str_repeat('k', 32));
    }

    public function test_same_value_gives_same_index_after_normalization(): void
    {
        $bi = $this->index();

        $this->assertSame(
            $bi->compute('JD1985', 'patients.research_id'),
            $bi->compute('  jd1985 ', 'patients.research_id'),
        );
    }

    public function test_index_does_not_contain_the_value(): void
    {
        $hash = $this->index()->compute('JD1985', 'patients.research_id');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $hash);
        $this->assertStringNotContainsStringIgnoringCase('jd1985', (string) $hash);
    }

    public function test_context_separates_fields(): void
    {
        $bi = $this->index();

        $this->assertNotSame($bi->compute('X1', 'patients.research_id'), $bi->compute('X1', 'patients.alias'));
    }

    public function test_different_keys_give_different_indexes(): void
    {
        $a = (new BlindIndex(str_repeat('a', 32)))->compute('X1', 'f');
        $b = (new BlindIndex(str_repeat('b', 32)))->compute('X1', 'f');

        $this->assertNotSame($a, $b);
    }

    public function test_empty_values_have_no_index(): void
    {
        $this->assertNull($this->index()->compute(null, 'f'));
        $this->assertNull($this->index()->compute('   ', 'f'));
    }

    public function test_short_key_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        new BlindIndex('short');
    }
}
