<?php
namespace App\Http\Controllers\VisitasTecnicas;

use App\Http\Controllers\Controller;
use App\Models\Senco360\VisitaEncab;
use App\Models\Senco360\VisitaDetal;
use App\Models\Senco360\VisitaFoto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FotoController extends Controller
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

    private function puedeGestionarFoto(VisitaDetal $equipo, string $tipo): bool
    {
        $this->validarAccesoDetalle($equipo);

        $visita = $equipo->encabezado;
        if (!$visita) {
            return false;
        }

        $estado = (int) $visita->ID_ESTADO_ACTUAL;
        $detallePublicado = (bool) $visita->DETALLE_PUBLICADO_COTIZACION;

        if ($tipo === 'FIRMA') {
            return in_array($estado, [1, 2, 6], true);
        }

        if (!$detallePublicado) {
            return $estado === 1;
        }

        return $tipo === 'DESPUES' && in_array($estado, [1, 6], true);
    }

    public function store(Request $request): JsonResponse
    {
        \Log::info('FotoController::store llamado', [
            'method' => $request->method(),
            'path' => $request->path(),
            'body' => $request->all(),
            'files' => $request->files->keys(),
        ]);

        $validated = $request->validate([
            'equipo_id' => 'nullable|numeric',
            'visita_id' => 'nullable|numeric',
            'tipo'      => 'required|in:ANTES,DESPUES,FIRMA',
            'fotos'     => 'required|array|min:1|max:10',
            'fotos.*'   => 'required|image|max:5120', // 5MB por foto
        ]);

        \Log::info('Validación pasada');

        abort_if(!$request->equipo_id && !$request->visita_id, 422, 'Debe indicar un equipo o una visita.');

        if ($request->equipo_id) {
            \Log::info('Buscando equipo', ['equipo_id' => $request->equipo_id]);
            $equipo = VisitaDetal::with('encabezado.rutaTecnica')->findOrFail($request->equipo_id);
            \Log::info('Equipo encontrado', ['equipo_id' => $equipo->ID]);
        } else {
            \Log::info('Buscando visita', ['visita_id' => $request->visita_id]);
            $visita = VisitaEncab::with(['detalle.encabezado.rutaTecnica'])->findOrFail($request->visita_id);
            $equipo = $visita->detalle->first();
            abort_if(!$equipo, 422, 'La visita no tiene equipos para asociar la evidencia.');
            \Log::info('Equipo de visita encontrado', ['equipo_id' => $equipo->ID]);
        }

        \Log::info('Verificando permisos de foto', [
            'estado_actual' => $equipo->encabezado?->ID_ESTADO_ACTUAL,
            'detalle_publicado' => $equipo->encabezado?->DETALLE_PUBLICADO_COTIZACION,
            'tipo' => $request->tipo,
        ]);
        abort_if(!$this->puedeGestionarFoto($equipo, $request->tipo), 403, 'No se pueden agregar fotos de este tipo en el estado actual de la visita.');


        $guardadas = [];
        foreach ($request->file('fotos') as $foto) {
            \Log::info('Procesando foto', ['nombre' => $foto->getClientOriginalName()]);
            $ruta = $foto->store(
                'visitas/equipos/' . $equipo->ID,
                'public'
            );
            \Log::info('Foto almacenada en:', ['ruta' => $ruta]);
            $guardadas[] = VisitaFoto::create([
                'ID_DETALLE_VISITA' => $equipo->ID,
                'TIPO'              => $request->tipo,
                'RUTA_FOTO'         => $ruta,
            ]);
        }

        \Log::info('Fotos guardadas exitosamente', ['cantidad' => count($guardadas)]);

        return response()->json([
            'success' => true,
            'fotos'   => collect($guardadas)->map(fn($f) => [
                'id'   => $f->ID,
                'url'  => asset('storage/' . $f->RUTA_FOTO),
                'tipo' => $f->TIPO,
            ]),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $foto = VisitaFoto::with('detalle.encabezado.rutaTecnica')->findOrFail($id);
        abort_if(!$foto->detalle || !$this->puedeGestionarFoto($foto->detalle, $foto->TIPO), 403, 'No se pueden eliminar fotos de este tipo en el estado actual de la visita.');
        \Illuminate\Support\Facades\Storage::disk('public')->delete($foto->RUTA_FOTO);
        $foto->delete();

        return response()->json(['success' => true]);
    }
}
