<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRutaTecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'visitas.*.nit' => 'required|string|max:50',
            'visitas.*.nombre_cliente' => 'required|string|max:200',
            'visitas.*.direccion_id' => 'nullable|string|max:50',
            'visitas.*.direccion_completa' => 'required|string|max:500',
            'visitas.*.fecha_visita' => 'required|date|after_or_equal:fecha_inicio|before_or_equal:fecha_fin',
            'visitas.*.tel_contacto' => 'nullable|string|max:50',
            'visitas.*.nom_contacto' => 'nullable|string|max:200',
            'visitas.*.observaciones' => 'nullable|string|max:500',
            'visitas.*.cod_asesor' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'La fecha de inicio es requerida',
            'fecha_fin.required' => 'La fecha fin es requerida',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser mayor o igual a la fecha de inicio',
            'visitas.required' => 'Debe agregar al menos una visita',
            'visitas.min' => 'Debe agregar al menos una visita',
            'visitas.*.cliente_id.required' => 'El cliente es requerido para cada visita',
            'visitas.*.nit.required' => 'El NIT es requerido para cada visita',
            'visitas.*.nombre_cliente.required' => 'El nombre del cliente es requerido',
            'visitas.*.direccion_completa.required' => 'La dirección es requerida',
            'visitas.*.fecha_visita.required' => 'La fecha de visita es requerida',
            'visitas.*.fecha_visita.after_or_equal' => 'La fecha de visita debe estar dentro del rango de la ruta',
            'visitas.*.fecha_visita.before_or_equal' => 'La fecha de visita debe estar dentro del rango de la ruta',
        ];
    }
}
