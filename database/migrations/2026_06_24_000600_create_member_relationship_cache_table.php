<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_relationship_cache', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('source_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('target_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('relationship_name')->nullable();
            $table->json('relationship_path');
            $table->boolean('is_connected')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(
                ['family_id', 'source_member_id', 'target_member_id'],
                'member_relationship_cache_lookup_unique'
            );
            $table->index('family_id');
            $table->index('source_member_id');
            $table->index('target_member_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_relationship_cache');
    }
};
