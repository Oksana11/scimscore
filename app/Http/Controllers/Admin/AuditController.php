<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Support\Security\AuditCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class AuditController extends AdminController
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'action' => ['nullable', 'string', 'max:64'],
            'actor_type' => ['nullable', 'in:guest,user,admin,system'],
            'actor_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(AuditCategories::LABELS))],
        ]);
        $category = $f['category'] ?? AuditCategories::DEFAULT;

        $logs = AuditCategories::apply(AuditLog::query(), $category)
            ->when($f['action'] ?? null, fn ($q, $v) => $q->where('action', 'like', addcslashes($v, '%_\\').'%'))
            ->when($f['actor_type'] ?? null, fn ($q, $v) => $q->where('actor_type', $v))
            ->when($f['actor_id'] ?? null, fn ($q, $v) => $q->where('actor_id', $v))
            ->latest('id')
            ->paginate(100)
            ->withQueryString();

        $this->log('admin.view_audit', context: ['filters' => array_keys(array_filter($f, fn ($v) => $v !== null && $v !== ''))]);

        return view('admin.audit', ['logs' => $logs, 'filters' => $f, 'category' => $category]);
    }

    public function verify(): RedirectResponse
    {
        $code = Artisan::call('audit:verify');
        $output = trim(Artisan::output());
        $this->log('admin.audit_verified', context: ['ok' => $code === 0]);

        return back()->with($code === 0 ? 'status' : 'audit_error', $output);
    }
}
