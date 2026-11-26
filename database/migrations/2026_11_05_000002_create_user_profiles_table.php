<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('profession_id')->constrained()->restrictOnDelete();
            $table->char('country_code', 2);
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->string('professional_title', 100)->nullable();
            $table->string('organization', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->timestamps();

            $table->foreign('country_code')->references('code')->on('countries');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
