<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->text('email');                          // encrypted
            $table->char('email_bidx', 64)->unique();
            $table->string('name', 100);
            $table->text('totp_secret')->nullable();        // encrypted base32 secret
            $table->timestamp('totp_confirmed_at')->nullable();
            $table->unsignedBigInteger('totp_last_step')->nullable(); // replay protection
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
