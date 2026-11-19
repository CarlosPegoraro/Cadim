<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_series', function (Blueprint $table): void {
            $table->unsignedTinyInteger('recurrence_day')->nullable()->after('recurrence');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_series', function (Blueprint $table): void {
            $table->dropColumn('recurrence_day');
        });
    }
};
