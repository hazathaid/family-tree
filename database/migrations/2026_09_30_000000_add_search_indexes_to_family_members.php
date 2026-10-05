<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->index(['family_id', 'full_name'], 'family_members_family_full_name_index');
            $table->index(['family_id', 'is_alive'], 'family_members_family_is_alive_index');
            $table->index(['family_id', 'birth_place'], 'family_members_family_birth_place_index');
        });

        Schema::table('member_relationships', function (Blueprint $table): void {
            $table->index(['family_id', 'relationship_type'], 'member_relationships_family_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropIndex('family_members_family_full_name_index');
            $table->dropIndex('family_members_family_is_alive_index');
            $table->dropIndex('family_members_family_birth_place_index');
        });

        Schema::table('member_relationships', function (Blueprint $table): void {
            $table->dropIndex('member_relationships_family_type_index');
        });
    }
};
