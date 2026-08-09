<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EquipmentFoundationSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $categories = [
            ['CRANE','Grúas Telescópicas',10],
            ['FORKLIFT','Montacargas',20],
            ['TELEHANDLER','Telehandlers',30],
            ['MANLIFT','ManLift',40],
            ['TRANSPORT','Equipos de Transporte',50],
            ['EARTHMOVING','Maquinaria de Terracería',60],
            ['OTHER','Otros Equipos',70],
        ];

        foreach ($categories as [$code,$name,$order]) {
            $existing = $this->db->table('equipment_categories')->where('code', $code)->get()->getRowArray();
            $data = ['code'=>$code,'name'=>$name,'display_order'=>$order,'status'=>1,'modify_user'=>'seeder','modify_date'=>$now];
            if ($existing) {
                $this->db->table('equipment_categories')->where('id', $existing['id'])->update($data);
            } else {
                $data['entry_user'] = 'seeder';
                $data['entry_date'] = $now;
                $this->db->table('equipment_categories')->insert($data);
            }
        }

        $roles = [
            ['OPERATOR','Operador','Opera directamente maquinaria o equipo especializado.'],
            ['DRIVER','Motorista','Conduce el vehículo o unidad de transporte asignada.'],
            ['HELPER','Ayudante','Apoya maniobras, seguridad y operación del equipo.'],
            ['RIGGER','Rigger / Maniobrista','Apoya maniobras de izaje, aparejos y señalización.'],
        ];

        foreach ($roles as [$code,$name,$description]) {
            $existing = $this->db->table('resource_roles')->where('code', $code)->get()->getRowArray();
            $data = ['code'=>$code,'name'=>$name,'description'=>$description,'status'=>1,'modify_user'=>'seeder','modify_date'=>$now];
            if ($existing) {
                $this->db->table('resource_roles')->where('id', $existing['id'])->update($data);
            } else {
                $data['entry_user'] = 'seeder';
                $data['entry_date'] = $now;
                $this->db->table('resource_roles')->insert($data);
            }
        }
    }
}
