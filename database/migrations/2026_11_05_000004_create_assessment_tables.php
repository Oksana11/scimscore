<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete(); // null = Quick Assessment without account
            $table->string('terms_version', 20);
            $table->timestamp('accepted_at');
            // Intentionally no IP / user agent: avoids re-identification of anonymous users.

            $table->index(['user_id', 'terms_version']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('source', 10);                          // quick | account
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('consent_id')->constrained('user_consents')->restrictOnDelete();

            $table->string('scim_version', 3);                     // III | IV
            $table->date('assessed_on');
            $table->boolean('is_first')->default(false);

            // Patient snapshot at the time of assessment (needed for SCI-ARMI and statistics)
            $table->unsignedTinyInteger('gender');
            $table->unsignedTinyInteger('age');
            $table->string('nli', 3)->nullable();
            $table->char('ais', 1)->nullable();
            $table->unsignedTinyInteger('ams')->nullable();

            $table->text('answers');                               // encrypted JSON of item scores
            $table->string('calc_version', 20);                    // version of the scoring algorithm

            // Quick Assessment: access to the result by a secret link (only the hash is stored)
            $table->char('access_token_hash', 64)->nullable()->unique();
            $table->timestamp('access_expires_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'assessed_on']);
            $table->index(['patient_id', 'assessed_on']);
            $table->index(['scim_version', 'assessed_on']);
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('total');
            $table->unsignedTinyInteger('self_care');
            $table->unsignedTinyInteger('respiration_sphincter');
            $table->unsignedTinyInteger('mobility');
            $table->decimal('sciarmi_total', 6, 2)->nullable();
            $table->decimal('sciarmi_self_care', 6, 2)->nullable();
            $table->decimal('sciarmi_respiration_sphincter', 6, 2)->nullable();
            $table->decimal('sciarmi_mobility', 6, 2)->nullable();
            $table->string('grade', 2)->nullable();
            $table->timestamps();

            $table->index('total');
            $table->index('sciarmi_total');
            $table->index('grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('user_consents');
    }
};
