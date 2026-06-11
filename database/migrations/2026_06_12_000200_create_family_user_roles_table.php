<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_user_roles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['member', 'admin', 'owner'])->default('member');
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('family_id');
            $table->index('user_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_user_roles');
    }
};
