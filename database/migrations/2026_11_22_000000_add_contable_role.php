<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rol Contable: acceso a la parte financiera (ventas, impuestos, cuentas por pagar,
 * gastos) sin poder vender, tocar inventario ni administrar usuarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('roles')->where('name', 'contable')->exists()) {
            DB::table('roles')->insert([
                'name' => 'contable',
                'description' => 'Contable / Finanzas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'contable')->value('id');
        if ($roleId) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
