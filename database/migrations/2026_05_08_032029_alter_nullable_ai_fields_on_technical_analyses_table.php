<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE technical_analyses MODIFY decision VARCHAR(20) NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY confidence INT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY reason_summary TEXT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY reasons_json JSON NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY ai_response_json JSON NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY agent_model VARCHAR(100) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE technical_analyses MODIFY decision VARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY confidence INT NOT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY reason_summary TEXT NOT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY reasons_json JSON NOT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY ai_response_json JSON NOT NULL");
        DB::statement("ALTER TABLE technical_analyses MODIFY agent_model VARCHAR(100) NOT NULL");
    }
};