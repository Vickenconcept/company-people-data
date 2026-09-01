<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE api_keys MODIFY COLUMN service ENUM('openai', 'openrouter', 'scraperapi', 'scrapingbee', 'apollo', 'hunter', 'prospeo') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE api_keys MODIFY COLUMN service ENUM('openai', 'openrouter', 'scraperapi', 'apollo', 'hunter', 'prospeo') NOT NULL");
    }
};
