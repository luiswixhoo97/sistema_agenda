<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\ReglaAnticipo;
use App\Models\ReglaAnticipoMonto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentasTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            // =====================================================
            // CATEGORÍA DE PRODUCTOS
            // =====================================================
            $categoria = CategoriaProducto::firstOrCreate(
                ['nombre' => 'Productos de Belleza'],
                [
                    'descripcion' => 'Productos para el cuidado personal',
                    'active' => true,
                ]
            );

            // =====================================================
            // PRODUCTOS
            // =====================================================
            $producto1 = Producto::firstOrCreate(
                ['codigo' => 'PROD001'],
                [
                    'nombre' => 'Shampoo Reparador',
                    'categoria_id' => $categoria->id,
                    'precio' => 250.00,
                    'costo' => 150.00,
                    'inventario_actual' => 50,
                    'inventario_minimo' => 10,
                    'active' => true,
                ]
            );

            $producto2 = Producto::firstOrCreate(
                ['codigo' => 'PROD002'],
                [
                    'nombre' => 'Crema Hidratante',
                    'categoria_id' => $categoria->id,
                    'precio' => 350.00,
                    'costo' => 200.00,
                    'inventario_actual' => 30,
                    'inventario_minimo' => 10,
                    'active' => true,
                ]
            );

            // =====================================================
            // CLIENTE (usar el primero disponible o crear uno)
            // =====================================================
            $cliente = Cliente::first();
            if (!$cliente) {
                $cliente = Cliente::create([
                    'nombre' => 'Cliente Prueba Ventas',
                    'telefono' => '+525599999999',
                    'email' => 'cliente.ventas@test.com',
                    'active' => true,
                ]);
            }

            // =====================================================
            // MÉTODO DE PAGO
            // =====================================================
            $metodoPago = MetodoPago::firstOrCreate(
                ['codigo' => 'EFECTIVO'],
                [
                    'nombre' => 'Efectivo',
                    'es_efectivo' => true,
                    'activo' => true,
                    'orden' => 1,
                ]
            );

            // =====================================================
            // REGLA DE ANTICIPO (tipo monto)
            // =====================================================
            $reglaAnticipo = ReglaAnticipo::firstOrCreate(
                [
                    'nombre' => 'Anticipo ventas mayores a $500',
                    'tipo_regla' => 'monto',
                ],
                [
                    'descripcion' => 'Ventas de $500 o más requieren 30% de anticipo',
                    'tipo_calculo' => 'porcentaje',
                    'valor_calculo' => 30.00, // 30%
                    'prioridad' => 1,
                    'activo' => true,
                ]
            );

            // Crear relación con regla de monto
            if (!$reglaAnticipo->reglaMonto) {
                ReglaAnticipoMonto::create([
                    'regla_anticipo_id' => $reglaAnticipo->id,
                    'monto_minimo' => 500.00,
                    'monto_maximo' => null,
                ]);
            }

            // =====================================================
            // MOVIMIENTOS DE INVENTARIO INICIALES (entradas)
            // =====================================================
            $adminUser = User::where('email', 'admin@beautyspa.com')->first();
            $userId = $adminUser ? $adminUser->id : null;

            // Entrada manual para Producto 1
            MovimientoInventario::firstOrCreate(
                [
                    'producto_id' => $producto1->id,
                    'tipo' => MovimientoInventario::TIPO_ENTRADA_MANUAL,
                    'cantidad' => 50,
                    'referencia_id' => null,
                ],
                [
                    'motivo' => 'Inventario inicial',
                    'user_id' => $userId,
                    'notas' => 'Carga inicial de inventario desde seeder',
                    'created_at' => now()->subDays(5),
                ]
            );

            // Entrada manual para Producto 2
            MovimientoInventario::firstOrCreate(
                [
                    'producto_id' => $producto2->id,
                    'tipo' => MovimientoInventario::TIPO_ENTRADA_MANUAL,
                    'cantidad' => 30,
                    'referencia_id' => null,
                ],
                [
                    'motivo' => 'Inventario inicial',
                    'user_id' => $userId,
                    'notas' => 'Carga inicial de inventario desde seeder',
                    'created_at' => now()->subDays(5),
                ]
            );

            // =====================================================
            // VENTA
            // =====================================================
            // Calcular totales
            $subtotal = $producto1->precio + $producto2->precio; // 250 + 350 = 600
            $total = $subtotal; // Sin descuentos ni impuestos
            $montoAnticipoRequerido = $total * 0.30; // 30% = 180

            // Buscar venta existente con las notas del seeder para evitar duplicados
            $venta = Venta::where('notas', 'Venta de prueba creada desde seeder')
                ->where('cliente_id', $cliente->id)
                ->first();

            if (!$venta) {
                $venta = Venta::create([
                    'cliente_id' => $cliente->id,
                    'fecha_venta' => now()->subDay(),
                    'subtotal' => $subtotal,
                    'descuento_general' => 0,
                    'impuesto_total' => 0,
                    'total' => $total,
                    'total_pagado' => 0,
                    'saldo_pendiente' => $total,
                    'estado' => Venta::ESTADO_PENDIENTE_PAGO,
                    'requiere_anticipo' => true,
                    'monto_anticipo_requerido' => $montoAnticipoRequerido,
                    'monto_anticipo_pagado' => 0,
                    'notas' => 'Venta de prueba creada desde seeder',
                ]);
            }

            // =====================================================
            // DETALLES DE VENTA
            // =====================================================
            // Si la venta ya existe, verificar si tiene detalles
            if ($venta->detalles->count() === 0) {
                // Detalle Producto 1
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'tipo' => VentaDetalle::TIPO_PRODUCTO,
                    'producto_id' => $producto1->id,
                    'cantidad' => 1,
                    'precio_unitario' => $producto1->precio,
                    'descuento' => 0,
                    'impuesto' => 0,
                    'subtotal_linea' => $producto1->precio,
                ]);

                // Detalle Producto 2
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'tipo' => VentaDetalle::TIPO_PRODUCTO,
                    'producto_id' => $producto2->id,
                    'cantidad' => 1,
                    'precio_unitario' => $producto2->precio,
                    'descuento' => 0,
                    'impuesto' => 0,
                    'subtotal_linea' => $producto2->precio,
                ]);
            }

            // =====================================================
            // MOVIMIENTOS DE INVENTARIO POR VENTA (salidas)
            // =====================================================
            // Salida por venta para Producto 1
            MovimientoInventario::firstOrCreate(
                [
                    'producto_id' => $producto1->id,
                    'tipo' => MovimientoInventario::TIPO_VENTA,
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'venta',
                ],
                [
                    'cantidad' => 1,
                    'motivo' => 'Venta #' . $venta->id,
                    'user_id' => $userId,
                    'notas' => 'Venta de prueba',
                    'created_at' => $venta->fecha_venta,
                ]
            );

            // Salida por venta para Producto 2
            MovimientoInventario::firstOrCreate(
                [
                    'producto_id' => $producto2->id,
                    'tipo' => MovimientoInventario::TIPO_VENTA,
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'venta',
                ],
                [
                    'cantidad' => 1,
                    'motivo' => 'Venta #' . $venta->id,
                    'user_id' => $userId,
                    'notas' => 'Venta de prueba',
                    'created_at' => $venta->fecha_venta,
                ]
            );

            // Actualizar inventario actual (reducir por venta)
            // Nota: En producción esto se hace automáticamente al crear la venta
            // Aquí lo hacemos manualmente para el seeder
            if ($producto1->inventario_actual == 50) {
                $producto1->inventario_actual = 49;
                $producto1->save();
            }
            if ($producto2->inventario_actual == 30) {
                $producto2->inventario_actual = 29;
                $producto2->save();
            }

            // =====================================================
            // PAGO (anticipo)
            // =====================================================
            if ($venta->pagos->count() === 0) {
                $pago = VentaPago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago_id' => $metodoPago->id,
                    'monto' => $montoAnticipoRequerido,
                    'monto_recibido' => $montoAnticipoRequerido,
                    'cambio' => 0,
                    'estado_pago' => VentaPago::ESTADO_APROBADO,
                    'notas' => 'Pago de anticipo de prueba',
                    'user_id' => $userId,
                ]);

                // Actualizar saldo de la venta
                $venta->actualizarSaldo();
                
                // Actualizar monto de anticipo pagado
                $venta->monto_anticipo_pagado = $montoAnticipoRequerido;
                $venta->save();
            }

            DB::commit();

            $this->command->info('✅ Seeder de ventas ejecutado correctamente!');
            $this->command->info('');
            $this->command->info('📦 Datos creados:');
            $this->command->info('   - Categoría: ' . $categoria->nombre);
            $this->command->info('   - Productos: ' . $producto1->codigo . ', ' . $producto2->codigo);
            $this->command->info('   - Cliente: ' . $cliente->nombre);
            $this->command->info('   - Método de pago: ' . $metodoPago->nombre);
            $this->command->info('   - Regla de anticipo: ' . $reglaAnticipo->nombre);
            $this->command->info('   - Venta ID: ' . $venta->id . ' (Total: $' . number_format($venta->total, 2) . ')');
            $this->command->info('   - Pago registrado: $' . number_format($montoAnticipoRequerido, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error al ejecutar seeder: ' . $e->getMessage());
            throw $e;
        }
    }
}
