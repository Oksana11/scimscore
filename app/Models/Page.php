<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

/**
 * Admin-editable content page (About, Terms of Use, …).
 * Security: Markdown only — raw HTML is stripped and javascript:/data: links are dropped.
 */
#[Fillable(['slug', 'title', 'body', 'version', 'show_in_header', 'show_in_footer', 'sort', 'is_published', 'updated_by_admin_id'])]
class Page extends Model
{
    public const TERMS = 'terms';

    private const MENU_CACHE = 'pages.menu';

    protected function casts(): array
    {
        return [
            'show_in_header' => 'boolean',
            'show_in_footer' => 'boolean',
            'is_published' => 'boolean',
            'is_system' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::MENU_CACHE);
        static::saved($flush);
        static::deleted($flush);
    }

    public function html(): HtmlString
    {
        return new HtmlString(Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]));
    }

    public function url(): string
    {
        return $this->slug === self::TERMS ? route('terms') : route('page', $this->slug);
    }

    /**
     * Published pages for a menu ('header' | 'footer'), cached.
     *
     * @return Collection<int, array{title: string, url: string}>
     */
    public static function menu(string $where): Collection
    {
        try {
            $all = Cache::rememberForever(self::MENU_CACHE, fn () => static::query()
                ->where('is_published', true)
                ->orderBy('sort')->orderBy('title')
                ->get(['slug', 'title', 'show_in_header', 'show_in_footer'])
                ->map(fn (Page $p) => ['title' => $p->title, 'url' => $p->url(), 'header' => $p->show_in_header, 'footer' => $p->show_in_footer])
                ->all());
        } catch (Throwable) {
            return collect(); // e.g. before migrations ran
        }

        return collect($all)->filter(fn ($p) => $p[$where])->map(fn ($p) => ['title' => $p['title'], 'url' => $p['url']])->values();
    }

    /**
     * Current Terms of Use version (users must accept it again when it changes).
     */
    public static function termsVersion(): string
    {
        try {
            $v = static::query()->where('slug', self::TERMS)->value('version');
        } catch (Throwable) {
            $v = null;
        }

        return filled($v) ? (string) $v : (string) config('scim.terms_version');
    }
}
