<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EmployeeSkillsSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['code'=>'CRANE_OPERATOR','name'=>'Operador de grúa','requires_certification'=>1],
            ['code'=>'DRIVER','name'=>'Motorista','requires_certification'=>1],
            ['code'=>'FORKLIFT_OPERATOR','name'=>'Operador de montacargas','requires_certification'=>1],
            ['code'=>'TELEHANDLER_OPERATOR','name'=>'Operador de telehandler','requires_certification'=>1],
            ['code'=>'MANLIFT_OPERATOR','name'=>'Operador de ManLift','requires_certification'=>1],
            ['code'=>'RIGGER','name'=>'Rigger / Maniobrista','requires_certification'=>0],
            ['code'=>'HELPER','name'=>'Ayudante operativo','requires_certification'=>0],
            ['code'=>'MISSION_LEADER','name'=>'Responsable de misión','requires_certification'=>0],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $existing = $this->db->table('employee_skills')->where('code', $row['code'])->get()->getRowArray();
            $data = $row + ['status'=>1,'modify_user'=>'seeder','modify_date'=>$now];
            if ($existing) {
                $this->db->table('employee_skills')->where('id', $existing['id'])->update($data);
            } else {
                $data['entry_user'] = 'seeder';
                $data['entry_date'] = $now;
                $this->db->table('employee_skills')->insert($data);
            }
        }
    }
}
