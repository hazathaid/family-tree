<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_view_grants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('anchor_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('grantee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invited_email')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->boolean('can_view_living')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('family_id');
            $table->index('anchor_member_id');
            $table->index('grantee_user_id');
            $table->index('invited_email');
            $table->index('expires_at');
            $table->index(['family_id', 'grantee_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_view_grants');
    }
};
