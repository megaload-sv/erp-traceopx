<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedCat014UnitMeasurements extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('mh_unit_measurements')) {
            return;
        }

        $units = [
            1 => 'metro',
            2 => 'Yarda',
            6 => 'milímetro',
            9 => 'kilómetro cuadrado',
            10 => 'Hectárea',
            13 => 'metro cuadrado',
            15 => 'Vara cuadrada',
            18 => 'metro cúbico',
            20 => 'Barril',
            22 => 'Galón',
            23 => 'Litro',
            24 => 'Botella',
            26 => 'Mililitro',
            30 => 'Tonelada',
            32 => 'Quintal',
            33 => 'Arroba',
            34 => 'Kilogramo',
            36 => 'Libra',
            37 => 'Onza troy',
            38 => 'Onza',
            39 => 'Gramo',
            40 => 'Miligramo',
            42 => 'Megawatt',
            43 => 'Kilowatt',
            44 => 'Watt',
            45 => 'Megavoltio-amperio',
            46 => 'Kilovoltio-amperio',
            47 => 'Voltio-amperio',
            49 => 'Gigawatt-hora',
            50 => 'Megawatt-hora',
            51 => 'Kilowatt-hora',
            52 => 'Watt-hora',
            53 => 'Kilovoltio',
            54 => 'Voltio',
            55 => 'Millar',
            56 => 'Medio millar',
            57 => 'Ciento',
            58 => 'Docena',
            59 => 'Unidad',
            99 => 'Otra',
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($units as $code => $name) {
            $existing = $this->db->table('mh_unit_measurements')
                ->where('catalog_code', 'CAT-014')
                ->where('code', $code)
                ->get()->getRowArray();

            $data = [
                'name' => $name,
                'description' => 'CAT-014 Unidad de Medida · Ministerio de Hacienda',
                'status' => 1,
                'modify_user' => 'migration',
                'modify_date' => $now,
            ];

            if ($existing === null) {
                $this->db->table('mh_unit_measurements')->insert($data + [
                    'catalog_code' => 'CAT-014',
                    'code' => $code,
                    'entry_user' => 'migration',
                    'entry_date' => $now,
                ]);
            } else {
                $this->db->table('mh_unit_measurements')
                    ->where('id', (int) $existing['id'])
                    ->update($data);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('mh_unit_measurements')) {
            $this->db->table('mh_unit_measurements')->where('catalog_code', 'CAT-014')->delete();
        }
    }
}
