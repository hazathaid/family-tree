<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_tree_cache', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('mode', 20);
            $table->unsignedSmallInteger('depth');
            $table->longText('tree_json');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->unique(['member_id', 'mode', 'depth'], 'member_tree_cache_lookup_unique');
            $table->index(['family_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tree_cache');
    }
};
