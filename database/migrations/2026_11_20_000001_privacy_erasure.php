<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account deletion keeps research data but removes everything that identifies people.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('anonymized_at')->nullable()->after('deactivated_at');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->text('research_id')->nullable()->change();
            $table->char('research_id_bidx', 64)->nullable()->change();
            $table->text('alias')->nullable()->change();
            $table->char('alias_bidx', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('anonymized_at');
        });
    }
};
