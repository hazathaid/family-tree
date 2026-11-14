<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_relationships', function (Blueprint $table): void {
            $table->string('end_reason', 20)->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('member_relationships', function (Blueprint $table): void {
            $table->dropColumn('end_reason');
        });
    }
};
