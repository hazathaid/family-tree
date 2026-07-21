<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('type')->default('general')->after('event_id')->index();
            $table->json('data')->nullable()->after('body');
            $table->timestamp('read_at')->nullable()->after('is_read');
        });

        Schema::create('push_device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['android', 'ios']);
            $table->string('token', 512)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_device_tokens');

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropColumn(['type', 'data', 'read_at']);
        });
    }
};
