<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('technical_analyses')) {
            Schema::table('technical_analyses', function (Blueprint $table) {
                if (!Schema::hasColumn('technical_analyses', 'reason_summary')) {
                    $table->text('reason_summary')
                        ->nullable()
                        ->after('confidence');
                }

                if (!Schema::hasColumn('technical_analyses', 'reasons_json')) {
                    $table->json('reasons_json')
                        ->nullable()
                        ->after('reason_summary');
                }
            });
        }

        if (Schema::hasTable('trade_signals')) {
            Schema::table('trade_signals', function (Blueprint $table) {
                if (!Schema::hasColumn('trade_signals', 'reason_summary')) {
                    $table->text('reason_summary')
                        ->nullable()
                        ->after('confidence');
                }

                if (!Schema::hasColumn('trade_signals', 'reasons_json')) {
                    $table->json('reasons_json')
                        ->nullable()
                        ->after('reason_summary');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('technical_analyses')) {
            Schema::table('technical_analyses', function (Blueprint $table) {
                if (Schema::hasColumn('technical_analyses', 'reasons_json')) {
                    $table->dropColumn('reasons_json');
                }

                if (Schema::hasColumn('technical_analyses', 'reason_summary')) {
                    $table->dropColumn('reason_summary');
                }
            });
        }

        if (Schema::hasTable('trade_signals')) {
            Schema::table('trade_signals', function (Blueprint $table) {
                if (Schema::hasColumn('trade_signals', 'reasons_json')) {
                    $table->dropColumn('reasons_json');
                }

                if (Schema::hasColumn('trade_signals', 'reason_summary')) {
                    $table->dropColumn('reason_summary');
                }
            });
        }
    }
};