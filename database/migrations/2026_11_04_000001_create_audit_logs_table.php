<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('occurred_at', 26);             // "Y-m-d H:i:s.u", stored as text so the hashed value never changes
            $table->string('actor_type', 20);              // guest | user | admin | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 64);
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->text('context')->nullable();           // canonical JSON, text (not JSON type) to keep exact bytes
            $table->char('prev_hash', 64);
            $table->char('hash', 64)->unique();

            $table->index(['actor_type', 'actor_id']);
            $table->index('action');
            $table->index('occurred_at');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
