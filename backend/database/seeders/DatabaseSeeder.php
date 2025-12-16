<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Empleado;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Servicio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =====================================================
        // ROLES
        // =====================================================
        $roleAdmin = Role::firstOrCreate(['nombre' => 'admin']);
        $roleEmpleado = Role::firstOrCreate(['nombre' => 'empleado']);
        $roleCliente = Role::firstOrCreate(['nombre' => 'cliente']);

        // =====================================================
        // USUARIO ADMIN
        // =====================================================
        $adminUser = User::create([
            'role_id' => $roleAdmin->id,
            'nombre' => 'Administrador',
            'email' => 'admin@beautyspa.com',
            'password' => Hash::make('admin123'),
            'telefono' => '5500000000',
            'active' => true,
        ]);

        // =====================================================
        // EMPLEADOS
        // =====================================================
        $empleado1User = User::create([
            'role_id' => $roleEmpleado->id,
            'nombre' => 'Ana López',
            'email' => 'ana@beautyspa.com',
            'password' => Hash::make('empleado123'),
            'telefono' => '5511111111',
            'active' => true,
        ]);

        $empleado1 = Empleado::create([
            'user_id' => $empleado1User->id,
            'bio' => 'Especialista en corte y coloración con 5 años de experiencia',
            'especialidades' => 'Corte, Tinte, Peinado',
            'active' => true,
        ]);

        $empleado2User = User::create([
            'role_id' => $roleEmpleado->id,
            'nombre' => 'Carmen Ruiz',
            'email' => 'carmen@beautyspa.com',
            'password' => Hash::make('empleado123'),
            'telefono' => '5522222222',
            'active' => true,
        ]);

        $empleado2 = Empleado::create([
            'user_id' => $empleado2User->id,
            'bio' => 'Experta en uñas y tratamientos faciales',
            'especialidades' => 'Manicure, Pedicure, Facial',
            'active' => true,
        ]);

        // =====================================================
        // CLIENTES DE PRUEBA
        // =====================================================
        Cliente::create([
            'nombre' => 'María García',
            'telefono' => '+525512345678',
            'email' => 'maria@email.com',
        ]);

        Cliente::create([
            'nombre' => 'Laura Martínez',
            'telefono' => '+525598765432',
            'email' => 'laura@email.com',
        ]);

        // =====================================================
        // CATEGORÍAS
        // =====================================================
        $catCabello = Categoria::create(['nombre' => 'Cabello', 'descripcion' => 'Servicios de corte, tinte y peinado', 'active' => true]);
        $catUnas = Categoria::create(['nombre' => 'Uñas', 'descripcion' => 'Manicure y pedicure', 'active' => true]);
        $catFacial = Categoria::create(['nombre' => 'Facial', 'descripcion' => 'Tratamientos faciales', 'active' => true]);
        $catMasajes = Categoria::create(['nombre' => 'Masajes', 'descripcion' => 'Masajes relajantes y terapéuticos', 'active' => true]);

        // =====================================================
        // SERVICIOS
        // =====================================================
        $servCorte = Servicio::create([
            'categoria_id' => $catCabello->id,
            'nombre' => 'Corte de cabello',
            'descripcion' => 'Corte moderno personalizado',
            'precio' => 150.00,
            'duracion' => 30,
            'active' => true,
        ]);

        $servTinte = Servicio::create([
            'categoria_id' => $catCabello->id,
            'nombre' => 'Tinte completo',
            'descripcion' => 'Coloración completa del cabello',
            'precio' => 350.00,
            'duracion' => 90,
            'active' => true,
        ]);

        $servPeinado = Servicio::create([
            'categoria_id' => $catCabello->id,
            'nombre' => 'Peinado',
            'descripcion' => 'Peinado para eventos especiales',
            'precio' => 250.00,
            'duracion' => 45,
            'active' => true,
        ]);

        $servManicure = Servicio::create([
            'categoria_id' => $catUnas->id,
            'nombre' => 'Manicure tradicional',
            'descripcion' => 'Manicure con esmalte tradicional',
            'precio' => 200.00,
            'duracion' => 45,
            'active' => true,
        ]);

        $servPedicure = Servicio::create([
            'categoria_id' => $catUnas->id,
            'nombre' => 'Pedicure spa',
            'descripcion' => 'Pedicure completo con tratamiento spa',
            'precio' => 300.00,
            'duracion' => 60,
            'active' => true,
        ]);

        $servFacial = Servicio::create([
            'categoria_id' => $catFacial->id,
            'nombre' => 'Limpieza facial profunda',
            'descripcion' => 'Limpieza facial con extracción',
            'precio' => 450.00,
            'duracion' => 60,
            'active' => true,
        ]);

        $servMasaje = Servicio::create([
            'categoria_id' => $catMasajes->id,
            'nombre' => 'Masaje relajante',
            'descripcion' => 'Masaje de cuerpo completo',
            'precio' => 500.00,
            'duracion' => 60,
            'active' => true,
        ]);

        // =====================================================
        // ASIGNAR SERVICIOS A EMPLEADOS
        // =====================================================
        $empleado1->servicios()->attach([$servCorte->id, $servTinte->id, $servPeinado->id]);
        $empleado2->servicios()->attach([$servManicure->id, $servPedicure->id, $servFacial->id]);

        // =====================================================
        // HORARIOS DE EMPLEADOS (Lunes a Sábado)
        // =====================================================
        foreach ([1, 2, 3, 4, 5, 6] as $dia) { // 1=Lunes, 6=Sábado
            $empleado1->horarios()->create([
                'dia_semana' => $dia,
                'hora_inicio' => '09:00:00',
                'hora_fin' => '18:00:00',
            ]);

            $empleado2->horarios()->create([
                'dia_semana' => $dia,
                'hora_inicio' => '10:00:00',
                'hora_fin' => '19:00:00',
            ]);
        }

        // =====================================================
        // DATOS DE PRUEBA PARA VENTAS
        // =====================================================
        $this->call(VentasTestSeeder::class);

        $this->command->info('✅ Base de datos sembrada correctamente!');
        $this->command->info('');
        $this->command->info('📋 CREDENCIALES DE PRUEBA:');
        $this->command->info('');
        $this->command->info('🔐 ADMIN:');
        $this->command->info('   Email: admin@beautyspa.com');
        $this->command->info('   Password: admin123');
        $this->command->info('');
        $this->command->info('👩‍💼 EMPLEADO:');
        $this->command->info('   Email: ana@beautyspa.com');
        $this->command->info('   Password: empleado123');
        $this->command->info('');
        $this->command->info('📱 CLIENTE (OTP):');
        $this->command->info('   Teléfono: 5512345678');
        $this->command->info('   (El código OTP se muestra en los logs del servidor)');
    }
}
