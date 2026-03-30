<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            'unidades',
            'afectacion_tipos',
            'proveedores',
            'clientes',
            'documento_tipos',
            'productos',
            'ventas',
            'comprobante_tipos',
            'cobranza_tipos',
            'users',
            'roles',
            'pago_formas',
            'pago_medios',
            'operacion_tipos',
            'sunat',
            'empresa',
            'lineas',
            'compras',
            'prestamos',
            'formulaciones',
            'preparadas',
            'cotizaciones',
            'caja_pagos',
            'nucleos',
            'nucleo_preparadas',
            'venta_provisionales',
            'compra_provisionales',
            'venta_entregas',
            'gastos',
            'empleados',
            'planilla_adelantos',
            'planilla_prestamos',
            'planilla_pagos'
        ];
        $actions = ['list', 'create', 'edit', 'delete'];
        $permissions = [];

        // Crear permisos con formato tabla_accion (ej. ventas_list)
        foreach ($tables as $table) {
            foreach ($actions as $action) {
                $permName = "{$table}_{$action}";
                $permissions[] = Permission::firstOrCreate(['name' => $permName]);
            }
        }
        
        $permissionsReport = [
            'dashboard_estadisticas',
            'dashboard_productos',
            'ventas_report',
            'compras_report',
            'formulaciones_report',
            'preparadas_report',
            'prestamos_report',
            'nucleos_report',
            'caja_pagos_report',
            'kardex_report',
            'stock_report',
            'cuenta_corriente_report',
            'caja_report',
            'rentabilidad_report',
            'super_admin',
            'planilla_report'
        ];

        foreach ($permissionsReport as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $vendedorRole = Role::firstOrCreate(['name' => 'vendedor']);
        $operadorRole = Role::firstOrCreate(['name' => 'operador']);

        // Asignar todos los permisos al rol admin
        $adminRole->syncPermissions($permissions);
        $adminRole->givePermissionTo($permissionsReport);

        // Permisos solo de ventas para vendedor
        $vendedorPerms = [
            'ventas_list',
            'ventas_create',
            'ventas_edit',
            'ventas_delete',
        ];
        $vendedorRole->syncPermissions($vendedorPerms);

        // Permisos para operador
        $operadorPerms = collect($permissions)
            ->filter(fn ($perm) =>
                str_ends_with($perm->name, '_list') ||
                str_ends_with($perm->name, '_create')
            )
            ->pluck('name')
            ->toArray();
        $operadorRole->syncPermissions($operadorPerms);

        // Crear usuario administrador
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@prueba.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin+25'), // Cambiar por una segura en producción
            ]
        );

        $adminUser->assignRole($adminRole);
    }
}
