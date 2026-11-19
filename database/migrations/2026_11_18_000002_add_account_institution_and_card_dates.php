<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table): void {
            $table->string('institution')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->unsignedTinyInteger('closing_day')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table): void {
            $table->dropColumn(['institution', 'due_day', 'closing_day']);
        });
    }
};
