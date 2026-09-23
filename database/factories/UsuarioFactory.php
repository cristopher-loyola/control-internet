<?php

namespace Database\Factories;

use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero_servicio' => fake()->unique()->numberBetween(1000, 9999),
            'nombre_cliente' => fake()->name(),
            'domicilio' => 'Domicilio de prueba',
            'estado_id' => fn () => Estado::firstOrCreate(['nombre' => 'Activado'])->id,
            'estatus_servicio_id' => fn () => EstatusServicio::firstOrCreate(['nombre' => 'Pendiente de pago'])->id,
            'servicio_id' => fn () => Servicio::firstOrCreate(['nombre' => 'Internet'])->id,
        ];
    }
}
