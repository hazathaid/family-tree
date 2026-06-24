<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_relationships', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('source_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('target_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->enum('relationship_type', ['father', 'mother', 'child', 'husband', 'wife']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('family_id');
            $table->index('source_member_id');
            $table->index('target_member_id');
            $table->index('relationship_type');
            $table->index(
                ['family_id', 'source_member_id', 'target_member_id', 'relationship_type'],
                'member_relationships_edge_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_relationships');
    }
};
