<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la columna 'cerrada' ya existe antes de agregarla
        $columnExists = DB::connection('senco360')
            ->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'RT_rutastecnicas' AND COLUMN_NAME = 'cerrada'");
        
        if (empty($columnExists)) {
            // Agregar columnas usando SQL directo para SQL Server con schema dbo
            DB::connection('senco360')->statement(
                "ALTER TABLE [dbo].[RT_rutastecnicas] ADD [cerrada] bit NOT NULL DEFAULT 0, [fecha_cierre] datetime NULL"
            );

            // Cerrar TODAS las rutas existentes al momento de la migración
            // para que no aparezcan como "abiertas" en el índice
            DB::connection('senco360')
                ->table('RT_rutastecnicas')
                ->update([
                    'cerrada' => true,
                    'fecha_cierre' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('senco360')->statement(
            "ALTER TABLE [dbo].[RT_rutastecnicas] DROP COLUMN [cerrada], [fecha_cierre]"
        );
    }
};
