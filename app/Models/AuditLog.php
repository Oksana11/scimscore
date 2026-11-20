<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only audit record. Rows are chained with HMAC (see AuditLogger),
 * so editing or deleting any row is detected by `php artisan audit:verify`.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log records are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit log records cannot be deleted.'));
    }
}
