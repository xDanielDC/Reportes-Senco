<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hacer CodTecnico nullable en SQL Server para permitir capacitaciones de asesores
        DB::connection('sqlsrv')->statement('
            ALTER TABLE RT_rutastecnicas
            ALTER COLUMN CodTecnico VARCHAR(50) NULL
        ');

        // Crear índice para mejorar performance en filtros por CodTecnico
        DB::connection('sqlsrv')->statement('
            IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = \'idx_rt_rutastecnicas_codtecnico\')
            CREATE INDEX idx_rt_rutastecnicas_codtecnico ON RT_rutastecnicas(CodTecnico)
        ');

        // Crear índice para filtros por CodVendedor (para listar capacitaciones del asesor)
        DB::connection('sqlsrv')->statement('
            IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = \'idx_rt_rutastecnicas_codvendedor\')
            CREATE INDEX idx_rt_rutastecnicas_codvendedor ON RT_rutastecnicas(CodVendedor)
        ');
    }

    public function down(): void
    {
        // Revertir: eliminar índices
        DB::connection('sqlsrv')->statement('
            IF EXISTS (SELECT * FROM sys.indexes WHERE name = \'idx_rt_rutastecnicas_codtecnico\')
            DROP INDEX idx_rt_rutastecnicas_codtecnico ON RT_rutastecnicas
        ');

        DB::connection('sqlsrv')->statement('
            IF EXISTS (SELECT * FROM sys.indexes WHERE name = \'idx_rt_rutastecnicas_codvendedor\')
            DROP INDEX idx_rt_rutastecnicas_codvendedor ON RT_rutastecnicas
        ');

        // No revertir el ALTER COLUMN a NOT NULL porque podría causar pérdida de datos
        // En su lugar, dejar documentado que CodTecnico ya permitirá NULLs
    }
};
