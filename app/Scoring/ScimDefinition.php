<?php

namespace App\Scoring;

use App\Enums\ScimVersion;
use RuntimeException;

/**
 * Questionnaire structure (sections, items, allowed scores, labels),
 * loaded from resources/scim/scim-<version>.json.
 */
final class ScimDefinition
{
    /** @var array<string, self> */
    private static array $cache = [];

    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(public readonly ScimVersion $version, private readonly array $data) {}

    public static function for(ScimVersion $version): self
    {
        return self::$cache[$version->value] ??= self::load($version);
    }

    public static function isAvailable(ScimVersion $version): bool
    {
        return isset(self::$cache[$version->value]) || is_file(self::path($version));
    }

    /**
     * Replace a definition at runtime (tests), or reset with null.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function override(ScimVersion $version, ?array $data): void
    {
        if ($data === null) {
            unset(self::$cache[$version->value]);

            return;
        }

        self::$cache[$version->value] = new self($version, $data);
    }

    private static function path(ScimVersion $version): string
    {
        return resource_path('scim/scim-'.strtolower($version->value).'.json');
    }

    private static function load(ScimVersion $version): self
    {
        $path = self::path($version);

        if (! is_file($path)) {
            throw new RuntimeException("SCIM {$version->value} questionnaire is not available yet ({$path}).");
        }

        return new self($version, json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));
    }

    /** @return list<array<string, mixed>> */
    public function sections(): array
    {
        return $this->data['sections'];
    }

    /**
     * @return array<string, list<int>> item key => allowed scores, in questionnaire order
     */
    public function allowedScores(): array
    {
        $out = [];
        foreach ($this->sections() as $section) {
            foreach ($section['items'] as $item) {
                $out[$item['key']] = array_map(fn ($o) => (int) $o['score'], $item['options']);
            }
        }

        return $out;
    }

    /**
     * @return array<string, list<string>> section key => item keys
     */
    public function sectionItems(): array
    {
        $out = [];
        foreach ($this->sections() as $section) {
            $out[$section['key']] = array_map(fn ($i) => $i['key'], $section['items']);
        }

        return $out;
    }

    /**
     * @return array<string, list<int>>|null grade letter => reference vector (questionnaire order)
     */
    public function gradeReferences(): ?array
    {
        return $this->data['grade_reference']['vectors'] ?? null;
    }

    /**
     * @param  array<mixed>  $answers
     * @return array<string, int> validated answers in questionnaire order
     *
     * @throws InvalidScimAnswers
     */
    public function validate(array $answers): array
    {
        $errors = [];
        $clean = [];

        foreach ($this->allowedScores() as $key => $allowed) {
            $value = $answers[$key] ?? null;

            if ($value === null || $value === '') {
                $errors[$key] = 'required';

                continue;
            }

            if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
                $errors[$key] = 'must be an integer';

                continue;
            }

            $value = (int) $value;

            if (! in_array($value, $allowed, true)) {
                $errors[$key] = 'allowed values: '.implode(', ', $allowed);

                continue;
            }

            $clean[$key] = $value;
        }

        foreach (array_keys($answers) as $key) {
            if (! array_key_exists((string) $key, $this->allowedScores())) {
                $errors[(string) $key] = 'unknown item';
            }
        }

        if ($errors !== []) {
            throw new InvalidScimAnswers($errors);
        }

        return $clean;
    }
}
