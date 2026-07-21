<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_albums', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['family_id', 'created_at']);
        });

        Schema::create('member_photos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_album_id')->nullable()->constrained('photo_albums')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('path');
            $table->string('thumbnail_path');
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->text('caption')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['family_id', 'created_at']);
            $table->index(['photo_album_id', 'created_at']);
        });

        Schema::create('member_photo_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_photo_id')->constrained('member_photos')->cascadeOnDelete();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['member_photo_id', 'family_member_id']);
            $table->index('family_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_photo_tags');
        Schema::dropIfExists('member_photos');
        Schema::dropIfExists('photo_albums');
    }
};
