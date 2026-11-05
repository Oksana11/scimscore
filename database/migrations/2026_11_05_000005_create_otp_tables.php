<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One-time codes. Neither the email nor the code is stored in clear text.
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->id();
            $table->string('purpose', 24);
            $table->char('email_bidx', 64);
            $table->char('code_hash', 64);                 // HMAC of the code
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['email_bidx', 'purpose', 'created_at']);
            $table->index('expires_at');
        });

        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('new_email');                     // encrypted
            $table->char('new_email_bidx', 64);
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
        Schema::dropIfExists('otp_requests');
    }
};
