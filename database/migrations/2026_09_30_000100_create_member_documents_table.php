<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->date('document_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'created_at']);
            $table->index(['family_member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_documents');
    }
};
