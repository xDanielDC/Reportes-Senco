<?php

namespace App\Http\Controllers\VisitasTecnicas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Senco360Controller extends Controller
{
    /**
     * Buscar herramientas desde la vista vHerramientasMax
     * Campos: Cod Parte, Descripcion Parte, Referencia, Codigo Proveedor
     */
    public function buscarHerramientas(Request $request)
    {
        $busqueda = $request->input('q', '');

        if (strlen($busqueda) < 2) {
            return response()->json([]);
        }

        $resultados = DB::connection('senco360')
            ->table('vHerramientasMax')
            ->select([
                'Cod Parte as codigo',
                'Descripcion Parte as descripcion',
                'Referencia as referencia',
                'Codigo Proveedor as proveedor',
            ])
            ->where(function ($query) use ($busqueda) {
                $query->where('Cod Parte', 'like', "%{$busqueda}%")
                    ->orWhere('Descripcion Parte', 'like', "%{$busqueda}%")
                    ->orWhere('Referencia', 'like', "%{$busqueda}%")
                    ->orWhere('Codigo Proveedor', 'like', "%{$busqueda}%");
            })
            ->orderBy('Descripcion Parte')
            ->limit(50)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Buscar repuestos desde la vista vRepuestosMax
     * Campos: Codigo Repuesto, Descripcion Repuesto, Referencia, Codigo Proveedor, Inventario
     */
    public function buscarRepuestos(Request $request)
    {
        $busqueda = $request->input('q', '');

        if (strlen($busqueda) < 2) {
            return response()->json([]);
        }

        $resultados = DB::connection('senco360')
            ->table('vRepuestosMax')
            ->select([
                'Codigo Repuesto as codigo',
                'Descripcion Repuesto as descripcion',
                'Referencia as referencia',
                'Codigo Proveedor as proveedor',
                'Inventario as inventario',
            ])
            ->where(function ($query) use ($busqueda) {
                $query->where('Codigo Repuesto', 'like', "%{$busqueda}%")
                    ->orWhere('Descripcion Repuesto', 'like', "%{$busqueda}%")
                    ->orWhere('Referencia', 'like', "%{$busqueda}%")
                    ->orWhere('Codigo Proveedor', 'like', "%{$busqueda}%");
            })
            ->orderBy('Descripcion Repuesto')
            ->limit(50)
            ->get();

        return response()->json($resultados);
    }

    /**
     * Obtener descripción de una herramienta por su código
     */
    public function obtenerDescripcionHerramienta(Request $request)
    {
        $codigo = $request->input('codigo', '');
        
        if (empty($codigo)) {
            return response()->json(['descripcion' => null, 'codigo_proveedor' => null]);
        }

        $resultado = DB::connection('senco360')
            ->table('vHerramientasMax')
            ->select('Descripcion Parte as descripcion', 'Codigo Proveedor as codigo_proveedor')
            ->where('Cod Parte', $codigo)
            ->first();

        return response()->json([
            'descripcion' => $resultado?->descripcion,
            'codigo_proveedor' => $resultado?->codigo_proveedor,
        ]);
    }

    /**
     * Obtener descripción de un repuesto por su código
     */
    public function obtenerDescripcionRepuesto(Request $request)
    {
        $codigo = $request->input('codigo', '');
        
        if (empty($codigo)) {
            return response()->json(['descripcion' => null, 'codigo_proveedor' => null, 'inventario' => null]);
        }

        $resultado = DB::connection('senco360')
            ->table('vRepuestosMax')
            ->select('Descripcion Repuesto as descripcion', 'Codigo Proveedor as codigo_proveedor', 'Inventario as inventario')
            ->where('Codigo Repuesto', $codigo)
            ->first();

        return response()->json([
            'descripcion' => $resultado?->descripcion,
            'codigo_proveedor' => $resultado?->codigo_proveedor,
            'inventario' => $resultado?->inventario,
        ]);
    }
}
