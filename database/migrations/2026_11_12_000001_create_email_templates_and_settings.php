<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Site-wide values editable in the admin panel (e.g. support email). No personal data.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Admin overrides of the editable parts of system emails. Plain text only:
        // rendered escaped, placeholders replaced by fixed values — never compiled as Blade.
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('subject', 150);
            $table->string('heading', 150);
            $table->text('body');
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('settings');
    }
};
