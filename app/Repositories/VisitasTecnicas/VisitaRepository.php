<?php
namespace App\Repositories\VisitasTecnicas;

use App\Models\RutaTecnica;
use App\Models\Senco360\VisitaEncab;
use App\Models\Senco360\VisitaEstadoHistorico;
use Illuminate\Database\Eloquent\Collection;

class VisitaRepository
{
    /**
     * Listar rutas cerradas asignadas a un técnico
     * Si $codigoTecnico es vacío, retorna todas
     */
    public function listarRutasCerradas(string $codigoTecnico = ''): Collection
    {
        $query = RutaTecnica::where('cerrada', 1)
            ->where('invalido', 0)
            ->with(['visitaEncab', 'visitaEncab.estadoActual', 'visitaEncab.tipoServicio']);

        if (!empty($codigoTecnico)) {
            $query->where('CodTecnico', $codigoTecnico);
        }

        return $query->orderBy('FechaVisita', 'desc')->get();
    }

    /**
     * Obtener una visita encab por ID con todas sus relaciones
     */
    public function findById(int $id, string $codigoTecnico = ''): ?VisitaEncab
    {
        $query = VisitaEncab::with([
            'rutaTecnica',
            'tipoServicio',
            'estadoActual',
            'detalle',
            'detalle.tipoSolucion',
            'detalle.tipoMant',
            'detalle.tiposFalla',
            'detalle.tiposSolucion',
            'detalle.solicitudesPartes',
            'detalle.solicitudesPartes.estado',
            'detalle.fotos',
            'historialEstados.estado',
            'historialEstados.usuario',
            'historialEstados.solicitudParte',
        ])->where('ID', $id);

        if (!empty($codigoTecnico)) {
            $query->whereHas('rutaTecnica', function ($q) use ($codigoTecnico) {
                $q->where('CodTecnico', $codigoTecnico);
            });
        }

        return $query->first();
    }

    /**
     * Verificar si ya existe una visita encab para una ruta
     */
    public function existeVisita(string $idVisita): bool
    {
        return VisitaEncab::where('ID_VISITA', $idVisita)->exists();
    }

    /**
     * Crear encabezado de visita
     */
    public function crear(array $data): VisitaEncab
    {
        return VisitaEncab::create($data);
    }

    /**
     * Actualizar encabezado de visita
     */
    public function actualizar(int $id, array $data): VisitaEncab
    {
        $visita = VisitaEncab::findOrFail($id);
        $visita->update($data);
        return $visita->fresh();
    }

    /**
     * Cambiar estado de una visita y registrar en historial
     */
    public function cambiarEstado(int $id, int $estadoId, string $observacion = ''): VisitaEncab
    {
        $visita = VisitaEncab::findOrFail($id);
        $visita->update(['ID_ESTADO_ACTUAL' => $estadoId]);

        VisitaEstadoHistorico::create([
            'ID_ENC_VISITA' => $id,
            'ID_ESTADO'     => $estadoId,
            'FECHA'         => now(),
            'OBSERVACIONES' => $observacion,
            'ID_USUARIO'    => auth()->id(),
        ]);

        return $visita->fresh(['estadoActual']);
    }
}
