<?php
namespace App\Http\Controllers\VisitasTecnicas;

use App\Http\Controllers\Controller;
use App\Models\Senco360\SolicitudParte;
use App\Models\Senco360\VisitaDetal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SolicitudParteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visitastecnicas.ver');
    }

    private function puedeVerTodasLasVisitas($user): bool
    {
        return $user->hasRole('super-admin')
            || $user->hasPermissionTo('visitastecnicas.ver-todos');
    }

    private function validarAccesoDetalle(VisitaDetal $equipo): void
    {
        $user = auth()->user();

        if ($this->puedeVerTodasLasVisitas($user)) {
            return;
        }

        $codigoTecnico = trim((string) ($user->codigo_vendedor ?? ''));
        abort_if($codigoTecnico === '', 403, 'Tu usuario no tiene un código técnico asignado.');
        abort_if(trim((string) $equipo->encabezado?->rutaTecnica?->CodTecnico) !== $codigoTecnico, 403, 'No tienes acceso a esta visita.');
    }

    private function validarEdicionDetalle(VisitaDetal $equipo): void
    {
        $this->validarAccesoDetalle($equipo);
        abort_if((int) $equipo->encabezado?->ID_ESTADO_ACTUAL !== 1, 403, 'No se pueden modificar repuestos en este estado.');
        abort_if((bool) $equipo->encabezado?->DETALLE_PUBLICADO_COTIZACION, 403, 'El detalle de la visita ya fue enviado a cotización.');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'equipo_id'       => 'required|integer',
            'id_cod_max'      => 'required|string|max:100',
            'cantidad'        => 'required|integer|min:1',
            'observacion'     => 'nullable|string|max:255',
            'resolver_en_campo' => 'nullable|boolean',
        ]);

        $equipo = VisitaDetal::with('encabezado.rutaTecnica')->findOrFail($request->equipo_id);
        $this->validarEdicionDetalle($equipo);

        SolicitudParte::create([
            'ID_DETALLE_VISITA' => $request->equipo_id,
            'ID_COD_MAX_PARTES' => $request->id_cod_max,
            'CANTIDAD'          => $request->cantidad,
            'ID_ESTADO'         => $request->boolean('resolver_en_campo') ? 19 : 13,
            'OBSERVACION'       => $request->observacion,
        ]);

        return back()->with('success', 'Repuesto agregado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'id_estado'  => 'required|integer|in:13,14,15,16,17,18,19,27',
            'observacion'=> 'nullable|string|max:255',
            'cantidad'   => 'nullable|integer|min:1',
            'resolver_en_campo' => 'nullable|boolean',
        ]);

        $parte = SolicitudParte::with('detalle.encabezado.rutaTecnica')->findOrFail($id);
        $this->validarAccesoDetalle($parte->detalle()->firstOrFail());
        $estadoId = $request->has('resolver_en_campo')
            ? ($request->boolean('resolver_en_campo') ? 19 : 13)
            : (int) $request->id_estado;

        $parte->update([
            'ID_ESTADO'  => $estadoId,
            'OBSERVACION'=> $request->observacion ?? $parte->OBSERVACION,
            'CANTIDAD'   => $request->cantidad ?? $parte->CANTIDAD,
        ]);

        return back()->with('success', 'Repuesto actualizado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $parte = SolicitudParte::with('detalle.encabezado.rutaTecnica')->findOrFail($id);
        $this->validarEdicionDetalle($parte->detalle()->firstOrFail());
        $parte->delete();
        return back()->with('success', 'Repuesto eliminado correctamente.');
    }
}
