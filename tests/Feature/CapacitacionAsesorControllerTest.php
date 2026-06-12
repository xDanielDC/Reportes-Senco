<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RutaTecnica;
use App\Models\Senco360\VisitaEncab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CapacitacionAsesorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor1;
    protected User $asesor2;
    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles y permisos
        Role::create(['name' => 'asesor']);
        Role::create(['name' => 'técnico']);
        Permission::create(['name' => 'rutas-tecnicas.crear']);

        // Crear usuarios
        $this->asesor1 = User::factory()->create([
            'codigo_vendedor' => 'ASESOR01',
            'name' => 'Asesor 1',
        ]);
        $this->asesor1->assignRole('asesor');
        $this->asesor1->givePermissionTo('rutas-tecnicas.crear');

        $this->asesor2 = User::factory()->create([
            'codigo_vendedor' => 'ASESOR02',
            'name' => 'Asesor 2',
        ]);
        $this->asesor2->assignRole('asesor');
        $this->asesor2->givePermissionTo('rutas-tecnicas.crear');

        $this->tecnico = User::factory()->create([
            'codigo_vendedor' => 'TEC001',
            'name' => 'Técnico 1',
        ]);
        $this->tecnico->assignRole('técnico');
    }

    /**
     * Test: Asesor puede ver sus capacitaciones
     */
    public function test_asesor_puede_ver_sus_capacitaciones()
    {
        // Crear 2 capacitaciones para asesor1
        RutaTecnica::create([
            'NumeroRuta' => 'RTA001',
            'CodVendedor' => 'ASESOR01',
            'CodTecnico' => 'ASESOR01', // Asesor se asigna a sí mismo
            'NombreCliente' => 'Cliente 1',
            'Nit' => '123456789',
            'DireccionCompleta' => 'Calle 1',
            'FechaVisita' => now()->addDay(),
        ]);

        $this->actingAs($this->asesor1)
            ->get(route('visitastecnicas.capacitaciones.index'))
            ->assertStatus(200)
            ->assertSee('Mis Capacitaciones');
    }

    /**
     * Test: Asesor NO puede ver capacitaciones de otro asesor
     */
    public function test_asesor_no_puede_ver_capacitaciones_de_otro_asesor()
    {
        // Crear capacitación de asesor2
        $ruta = RutaTecnica::create([
            'NumeroRuta' => 'RTA002',
            'CodVendedor' => 'ASESOR02',
            'CodTecnico' => 'ASESOR02',
            'NombreCliente' => 'Cliente 2',
            'Nit' => '987654321',
            'DireccionCompleta' => 'Calle 2',
            'FechaVisita' => now()->addDay(),
        ]);

        $this->actingAs($this->asesor1)
            ->get(route('visitastecnicas.capacitaciones.show', $ruta->IdVisita))
            ->assertStatus(404);
    }

    /**
     * Test: Usuario sin permiso no puede acceder
     */
    public function test_usuario_sin_permiso_no_puede_acceder()
    {
        $usuarioSinPermiso = User::factory()->create();

        $this->actingAs($usuarioSinPermiso)
            ->get(route('visitastecnicas.capacitaciones.index'))
            ->assertStatus(403);
    }

    /**
     * Test: Asesor sin código_vendedor no puede acceder
     */
    public function test_asesor_sin_codigo_vendedor_no_puede_acceder()
    {
        $asesorSinCodigo = User::factory()->create(['codigo_vendedor' => null]);
        $asesorSinCodigo->assignRole('asesor');
        $asesorSinCodigo->givePermissionTo('rutas-tecnicas.crear');

        $this->actingAs($asesorSinCodigo)
            ->get(route('visitastecnicas.capacitaciones.index'))
            ->assertStatus(403);
    }

    /**
     * Test: Técnico NO ve capacitaciones en módulo de asesor
     */
    public function test_tecnico_no_accede_a_modulo_capacitaciones()
    {
        $this->tecnico->givePermissionTo('rutas-tecnicas.crear');

        $this->actingAs($this->tecnico)
            ->get(route('visitastecnicas.capacitaciones.index'))
            ->assertStatus(403); // Sin código_vendedor = NULL o sin permiso
    }

    /**
     * Test: Asesor puede editar su capacitación en estado REPROGRAMADO
     */
    public function test_asesor_puede_editar_capacitacion_reprogramada()
    {
        // Crear ruta
        $ruta = RutaTecnica::create([
            'NumeroRuta' => 'RTA003',
            'CodVendedor' => 'ASESOR01',
            'CodTecnico' => 'ASESOR01',
            'NombreCliente' => 'Cliente 3',
            'Nit' => '111111111',
            'DireccionCompleta' => 'Calle 3',
            'FechaVisita' => now()->addDay(),
        ]);

        $this->actingAs($this->asesor1)
            ->get(route('visitastecnicas.capacitaciones.edit', $ruta->IdVisita))
            ->assertStatus(200);
    }

    /**
     * Test: Asesor puede finalizar capacitación
     */
    public function test_asesor_puede_finalizar_capacitacion()
    {
        // Crear ruta y visita
        $ruta = RutaTecnica::create([
            'NumeroRuta' => 'RTA004',
            'CodVendedor' => 'ASESOR01',
            'CodTecnico' => 'ASESOR01',
            'NombreCliente' => 'Cliente 4',
            'Nit' => '222222222',
            'DireccionCompleta' => 'Calle 4',
            'FechaVisita' => now()->addDay(),
        ]);

        $visita = VisitaEncab::create([
            'ID_VISITA' => $ruta->IdVisita,
            'ID_ESTADO_ACTUAL' => 1, // EN PROCESO
            'CORREO' => 'cliente@test.com',
        ]);

        $this->actingAs($this->asesor1)
            ->post(route('visitastecnicas.capacitaciones.finalizar', $ruta->IdVisita), [
                'observaciones_cierre' => 'Capacitación completada',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Asesor puede eliminar capacitación en estado PENDIENTE
     */
    public function test_asesor_puede_eliminar_capacitacion_pendiente()
    {
        $ruta = RutaTecnica::create([
            'NumeroRuta' => 'RTA005',
            'CodVendedor' => 'ASESOR01',
            'CodTecnico' => 'ASESOR01',
            'NombreCliente' => 'Cliente 5',
            'Nit' => '333333333',
            'DireccionCompleta' => 'Calle 5',
            'FechaVisita' => now()->addDay(),
        ]);

        $visita = VisitaEncab::create([
            'ID_VISITA' => $ruta->IdVisita,
            'ID_ESTADO_ACTUAL' => 0, // PENDIENTE
        ]);

        $this->actingAs($this->asesor1)
            ->delete(route('visitastecnicas.capacitaciones.destroy', $ruta->IdVisita))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('RT_rutastecnicas', ['IdVisita' => $ruta->IdVisita]);
    }

    /**
     * Test: Asesor NO puede eliminar capacitación completada
     */
    public function test_asesor_no_puede_eliminar_capacitacion_completada()
    {
        $ruta = RutaTecnica::create([
            'NumeroRuta' => 'RTA006',
            'CodVendedor' => 'ASESOR01',
            'CodTecnico' => 'ASESOR01',
            'NombreCliente' => 'Cliente 6',
            'Nit' => '444444444',
            'DireccionCompleta' => 'Calle 6',
            'FechaVisita' => now()->addDay(),
        ]);

        $visita = VisitaEncab::create([
            'ID_VISITA' => $ruta->IdVisita,
            'ID_ESTADO_ACTUAL' => 2, // COMPLETADO
        ]);

        $this->actingAs($this->asesor1)
            ->delete(route('visitastecnicas.capacitaciones.destroy', $ruta->IdVisita))
            ->assertStatus(403);
    }
}
