<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary();          // ISO 3166-1 alpha-2
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('ror_id', 16)->nullable()->unique();   // e.g. "04mhzgx49" (Research Organization Registry)
            $table->string('name', 255);
            $table->char('country_code', 2)->nullable();
            $table->boolean('is_custom')->default(false);         // entered manually, not from ROR
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('merged_into_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->timestamps();

            $table->foreign('country_code')->references('code')->on('countries');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('professions');
        Schema::dropIfExists('countries');
    }
};
