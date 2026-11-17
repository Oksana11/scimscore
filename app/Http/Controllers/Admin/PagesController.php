<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PagesController extends AdminController
{
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::query()->orderBy('sort')->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['is_published' => true, 'sort' => 50])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = new Page;
        $page->fill($this->validated($request, $page) + ['updated_by_admin_id' => $this->admin()->id])->save();
        $this->log('admin.page_created', $page, ['slug' => $page->slug]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page);
        $versionChanged = $page->slug === Page::TERMS && ($data['version'] ?? null) !== $page->version;

        $page->fill($data + ['updated_by_admin_id' => $this->admin()->id])->save();
        $this->log('admin.page_updated', $page, ['slug' => $page->slug, 'terms_version_changed' => $versionChanged]);

        return back()->with('status', $versionChanged
            ? 'Saved. Terms version changed — users will be asked to accept the new terms at their next sign-in.'
            : 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($page->is_system, 403, 'This page cannot be deleted.');

        $this->log('admin.page_deleted', $page, ['slug' => $page->slug]);
        $page->delete();

        return redirect()->route('admin.pages')->with('status', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Page $page): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:100000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
        if (! $page->is_system) {
            $rules['slug'] = [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->ignore($page->id),
                Rule::notIn($this->reservedSlugs()),
            ];
        }
        if ($page->slug === Page::TERMS) {
            $rules['version'] = ['required', 'string', 'max:32'];
        }

        $data = $request->validate($rules, ['slug.not_in' => 'This address is used by the site. Choose another one.']);

        return $data + [
            'sort' => (int) ($data['sort'] ?? 0),
            'show_in_header' => $request->boolean('show_in_header'),
            'show_in_footer' => $request->boolean('show_in_footer'),
            'is_published' => $page->is_system ? true : $request->boolean('is_published'),
        ];
    }

    /**
     * First URL segments already taken by application routes (login, patients, admin, …).
     *
     * @return list<string>
     */
    private function reservedSlugs(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => explode('/', $r->uri())[0])
            ->reject(fn ($s) => $s === '' || str_starts_with($s, '{'))
            ->merge(['build', 'storage', 'favicon', 'robots', 'index'])
            ->unique()->values()->all();
    }
}
