<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RutaTecnica;

class CerrarRutasTecnicas extends Command
{
    protected $signature = 'rutas:cerrar';
    protected $description = 'Cierra todas las rutas técnicas abiertas (corre viernes 2pm)';

    public function handle()
    {
        RutaTecnica::cerrarRutasVencidas();
        $this->info('Rutas cerradas correctamente.');
    }
}