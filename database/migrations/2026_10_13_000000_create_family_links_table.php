<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('linked_family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('linked_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->enum('link_type', ['marriage'])->default('marriage');
            $table->foreignId('children_home_family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->enum('status', ['pending', 'active', 'revoked', 'expired'])->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_family')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_linked_family')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('family_id');
            $table->index('linked_family_id');
            $table->index('member_id');
            $table->index('linked_member_id');
            $table->index('status');
            $table->index(['member_id', 'status']);
            $table->index(['linked_member_id', 'status']);
            $table->index(['family_id', 'status']);
            $table->index(['linked_family_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_links');
    }
};
