<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('family_branch_id')->constrained('users')->nullOnDelete();
            $table->unique(['family_id', 'user_id']);
        });

        Schema::create('member_account_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_member_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_account_invitations');

        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropUnique(['family_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
