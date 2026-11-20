<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();                   // used in URLs instead of id
            $table->string('system_id', 32)->nullable()->unique(); // SCIM-2026-000184
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained()->restrictOnDelete(); // owner's institution at creation

            // Identifying codes: encrypted + blind index for exact search / duplicate checks
            $table->text('research_id');
            $table->char('research_id_bidx', 64);
            $table->text('alias');
            $table->char('alias_bidx', 64);

            $table->unsignedTinyInteger('gender');
            $table->unsignedSmallInteger('birth_year');

            // Clinical data at the first SCIM assessment (optional)
            $table->string('first_nli', 3)->nullable();
            $table->char('first_ais', 1)->nullable();
            $table->unsignedTinyInteger('first_ams')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'research_id_bidx']);
            $table->index(['institution_id', 'research_id_bidx']);
            $table->index(['user_id', 'alias_bidx']);
            $table->index(['institution_id', 'gender', 'birth_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
