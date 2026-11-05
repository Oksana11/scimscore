<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\Security\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class AdminController extends Controller
{
    public function __construct(protected readonly AuditLogger $audit) {}

    protected function admin(): Admin
    {
        /** @var Admin */
        return Auth::guard('admin')->user();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(string $action, ?Model $subject = null, array $context = []): void
    {
        $this->audit->log($action, $subject, $context, actorType: 'admin', actorId: $this->admin()->id);
    }
}
