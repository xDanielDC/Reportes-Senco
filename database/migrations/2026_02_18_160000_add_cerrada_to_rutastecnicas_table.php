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
        Schema::connection('senco360')->table('RT_rutastecnicas', function (Blueprint $table) {
            $table->boolean('cerrada')->default(false)->after('Observaciones');
            $table->timestamp('fecha_cierre')->nullable()->after('cerrada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('senco360')->table('RT_rutastecnicas', function (Blueprint $table) {
            $table->dropColumn(['cerrada', 'fecha_cierre']);
        });
    }
};
