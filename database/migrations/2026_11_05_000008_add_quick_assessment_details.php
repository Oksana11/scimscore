<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Quick Assessment: the assessing professional (no name, no email) — for statistics
            $table->foreignId('caregiver_profession_id')->nullable()->after('consent_id')->constrained('professions')->nullOnDelete();
            $table->char('caregiver_country_code', 2)->nullable()->after('caregiver_profession_id');
            $table->foreignId('caregiver_institution_id')->nullable()->after('caregiver_country_code')->constrained('institutions')->nullOnDelete();
            $table->string('caregiver_title', 100)->nullable()->after('caregiver_institution_id');
            $table->string('caregiver_organization', 255)->nullable()->after('caregiver_title');
            $table->string('caregiver_department', 255)->nullable()->after('caregiver_organization');

            // Quick Assessment: the caregiver's own patient code (encrypted) and etiology
            $table->text('patient_code')->nullable()->after('caregiver_department');
            $table->string('etiology', 32)->nullable()->after('ams');

            $table->foreign('caregiver_country_code')->references('code')->on('countries');
            $table->index('caregiver_country_code');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['caregiver_country_code']);
            $table->dropIndex(['caregiver_country_code']);
            $table->dropConstrainedForeignId('caregiver_institution_id');
            $table->dropConstrainedForeignId('caregiver_profession_id');
            $table->dropColumn(['caregiver_country_code', 'caregiver_title', 'caregiver_organization', 'caregiver_department', 'patient_code', 'etiology']);
        });
    }
};
