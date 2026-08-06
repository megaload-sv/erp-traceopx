<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class QuotationFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUnits();
        $this->seedPaymentTerms();
    }

    private function seedUnits(): void
    {
        $units = [
            ['code' => 'UNIT', 'name' => 'Unidad', 'symbol' => 'Und.'],
            ['code' => 'HOUR', 'name' => 'Hora', 'symbol' => 'Hrs.'],
            ['code' => 'DAY', 'name' => 'Día', 'symbol' => 'Días'],
            ['code' => 'WEEK', 'name' => 'Semana', 'symbol' => 'Sem.'],
            ['code' => 'MONTH', 'name' => 'Mes', 'symbol' => 'Meses'],
            ['code' => 'TRIP', 'name' => 'Viaje', 'symbol' => 'Viajes'],
            ['code' => 'FREIGHT', 'name' => 'Flete', 'symbol' => 'Fletes'],
            ['code' => 'SERVICE', 'name' => 'Servicio', 'symbol' => 'Servicio'],
            ['code' => 'GLOBAL', 'name' => 'Suma global', 'symbol' => 'Global'],
        ];

        foreach ($units as $unit) {
            $this->upsert('commercial_units', 'code', $unit + ['status' => 1]);
        }
    }

    private function seedPaymentTerms(): void
    {
        $terms = [
            [1, 'MENSUAL ANTICIPADO'], [2, 'SEMANAL ANTICIPADO'], [3, 'SEMANAL VENCIDO'],
            [4, 'FLETE 100% ANTICIPADO Y 50% ANTICIPADO PRIMER MES'], [5, '50% ANTICIPADO'],
            [6, '100% ANTICIPADO'], [7, 'CONTADO'], [8, '08 DIAS CREDITO'], [9, '15 DIAS CREDITO'],
            [10, '30 DIAS CREDITO'], [11, '45 DIAS CREDITO'], [12, '60 DIAS CREDITO'],
            [13, '90 DIAS CREDITO'], [14, 'TRANSFERENCIA BANCARIA'], [15, 'DEPOSITO A CUENTA'],
            [16, '50% ANTICIPADO Y 50% AL FINALIZAR'], [17, '50% ANTICIPADO Y 50% 15 DIAS CREDITO'],
            [18, '50% ANTICIPADO Y 50% 30 DIAS CREDITO'], [19, 'CHEQUE'], [20, 'OTRA FORMA DE PAGO'],
            [21, '20% ANTICIPADO'], [22, '30% AL INICIAR TRANSITO'], [23, '50% CONTRA ENTREGA'],
            [24, '50% ANTICIPADO Y 50% AL ENTREGAR EL PRIMERO TANQUE; 20% ANTES DE ENTREGAR'],
            [25, '50% ANTICIPADO Y 50% AL ENTREGAR EL PRIMERO TANQUE; 20% ANTES DE ENTREGAR'],
            [26, '50% ANTICIPADO Y 50% AL ENTREGAR EL PRIMERO TANQUE'], [27, '20% ANTES DE ENTREGAR'],
            [28, 'A CONVENIR'], [29, '50% ANTICIPADO 30% AL CARGAR 20% ANTES DE DESCARGAR'],
            [30, '20% ANTES DE ENTREGAR; A CONVENIR; 50% ANTICIPADO; 30% AL CARGAR; 20% ANTES DE DESCARGAR'],
            [31, '80% ANTICIPADO; 20% CONTRA ENTREGA'], [32, '50% ANTICIPADO Y 50% EN FRONTERA'],
            [33, '50% ANTICIPADO Y 50% CONTRA ENTREGA'],
            [34, '30% ANTICIPADO, 30% EN FRONTERA Y 40% CONTRA ENTREGA'],
            [35, '30% ANTICIPADO Y 70% AL FINALIZAR'], [36, '30% ANTICIPADO Y 70% PARCIAL SEMANA VENCIDO'],
        ];

        foreach ($terms as [$legacyId, $name]) {
            $advance = $this->advancePercentage($name);
            $this->upsert('payment_terms', 'code', [
                'code' => 'PT-' . str_pad((string) $legacyId, 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'description' => null,
                'term_type' => $this->termType($name),
                'requires_advance' => $advance > 0 ? 1 : 0,
                'minimum_advance_percentage' => $advance,
                'coordination_release_rule' => $advance > 0 ? 'confirm_advance' : 'no_block',
                'status' => 1,
            ]);
        }
    }

    private function upsert(string $table, string $key, array $data): void
    {
        $existing = $this->db->table($table)->where($key, $data[$key])->get()->getRowArray();
        $audit = ['modify_user' => 'QuotationFoundationSeeder', 'modify_date' => date('Y-m-d H:i:s')];

        if ($existing !== null) {
            $this->db->table($table)->where('id', $existing['id'])->update($data + $audit);
            return;
        }

        $this->db->table($table)->insert($data + [
            'entry_user' => 'QuotationFoundationSeeder',
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function advancePercentage(string $name): float
    {
        if (preg_match('/(100|80|50|30|20)%\s+ANTICIPADO/u', $name, $matches) === 1) {
            return (float) $matches[1];
        }

        return str_contains($name, 'ANTICIPADO') ? 100.0 : 0.0;
    }

    private function termType(string $name): string
    {
        if (str_contains($name, 'CREDITO')) {
            return 'credit';
        }
        if (substr_count($name, '%') > 1) {
            return 'milestone';
        }
        if (str_contains($name, '%')) {
            return 'percentage';
        }

        return 'simple';
    }
}
