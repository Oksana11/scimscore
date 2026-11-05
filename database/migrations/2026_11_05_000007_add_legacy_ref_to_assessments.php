<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // sha256 of the source record id (e.g. "drupal:123"): makes the import idempotent.
            $table->char('legacy_ref', 64)->nullable()->unique()->after('calc_version');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropUnique(['legacy_ref']);
            $table->dropColumn('legacy_ref');
        });
    }
};
