<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('family_branch_id')->nullable()->constrained('family_branches')->nullOnDelete();
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->boolean('is_alive')->default(true);
            $table->date('death_date')->nullable();
            $table->string('death_place')->nullable();
            $table->text('biography')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('profile_photo_thumbnail')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('family_id');
            $table->index('family_branch_id');
            $table->index('full_name');
            $table->index('is_alive');
            $table->index('birth_date');
            $table->index('death_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
