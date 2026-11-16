<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin override of an email's editable texts. Defaults live in App\Support\Mail\EmailTemplates.
 */
class EmailTemplate extends Model
{
    protected $fillable = ['key', 'subject', 'heading', 'body', 'updated_by_admin_id'];
}
