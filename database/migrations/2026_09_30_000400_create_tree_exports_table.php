<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_exports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('root_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('mode', 20);
            $table->unsignedTinyInteger('depth');
            $table->string('layout', 20);
            $table->string('format', 10);
            $table->string('paper_size', 5)->default('A4');
            $table->string('status', 20)->default('pending');
            $table->string('path')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tree_exports');
    }
};
