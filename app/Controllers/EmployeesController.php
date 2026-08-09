<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class EmployeesController extends BaseController
{
    public function index(): string
    {
        return view('employees/index', [
            'title' => 'Personal operativo',
            'employees' => (new EmployeeModel())->workspaceList(),
        ]);
    }

    public function create(): string
    {
        return view('employees/form', $this->formData(null));
    }

    public function edit(int $id): string
    {
        $employee = (new EmployeeModel())->find($id);
        if ($employee === null) throw new RuntimeException('Colaborador no encontrado.');
        return view('employees/form', $this->formData($employee));
    }

    public function store(): RedirectResponse { return $this->persist(null); }
    public function update(int $id): RedirectResponse { return $this->persist($id); }

    private function persist(?int $id): RedirectResponse
    {
        $db = db_connect(); $model = new EmployeeModel(); $db->transBegin();
        try {
            $data = [
                'employee_code'=>strtoupper(trim((string)$this->request->getPost('employee_code'))),
                'name'=>trim((string)$this->request->getPost('name')),
                'email'=>$this->nullable('email'),'phone'=>$this->nullable('phone'),
                'employment_status'=>(string)($this->request->getPost('employment_status') ?: 'active'),
                'availability_status'=>(string)($this->request->getPost('availability_status') ?: 'available'),
                'notes'=>$this->nullable('notes'),'status'=>1,
            ];
            if ($data['employee_code']==='' || $data['name']==='') throw new RuntimeException('Código y nombre son obligatorios.');
            $duplicate=$model->where('employee_code',$data['employee_code']); if($id) $duplicate->where('id !=',$id);
            if($duplicate->first()) throw new RuntimeException('Ya existe un colaborador con ese código.');
            if($id===null){$data['uuid']=$this->uuidV4();$id=(int)$model->insert($data,true);} else {$model->update($id,$data);}

            $db->table('employee_skill_assignments')->where('employee_id',$id)->delete();
            $levels=(array)$this->request->getPost('proficiency_level');
            $certifications=(array)$this->request->getPost('certification_number');
            $validFrom=(array)$this->request->getPost('valid_from');
            $validUntil=(array)$this->request->getPost('valid_until');
            $skillNotes=(array)$this->request->getPost('skill_notes');

            foreach((array)$this->request->getPost('skill_id') as $skillId){
                $skillId=(int)$skillId; if($skillId<=0) continue;
                $level=trim((string)($levels[$skillId] ?? ''));
                $db->table('employee_skill_assignments')->insert([
                    'employee_id'=>$id,'skill_id'=>$skillId,
                    'proficiency_level'=>$level !== '' ? $level : null,
                    'certification_number'=>trim((string)($certifications[$skillId] ?? '')) ?: null,
                    'valid_from'=>trim((string)($validFrom[$skillId] ?? '')) ?: null,
                    'valid_until'=>trim((string)($validUntil[$skillId] ?? '')) ?: null,
                    'notes'=>trim((string)($skillNotes[$skillId] ?? '')) ?: null,
                    'status'=>1,'entry_user'=>(string)(session('auth_user_email') ?: 'system'),'entry_date'=>date('Y-m-d H:i:s'),
                ]);
            }
            $db->transCommit();
            return redirect()->to(route_to('employees.edit',$id))->with('success','Colaborador y capacidades guardados correctamente.');
        } catch(Throwable $e){$db->transRollback(); return redirect()->back()->withInput()->with('error',$e->getMessage());}
    }

    private function formData(?array $employee): array
    {
        $db=db_connect(); $assigned=[];
        if($employee){foreach($db->table('employee_skill_assignments')->where('employee_id',$employee['id'])->get()->getResultArray() as $r)$assigned[(int)$r['skill_id']]=$r;}
        return ['title'=>$employee?'Editar colaborador':'Nuevo colaborador','employee'=>$employee,'skills'=>$db->table('employee_skills')->where('status',1)->orderBy('name')->get()->getResultArray(),'assignedSkills'=>$assigned];
    }
    private function nullable(string $f):?string{$v=trim((string)$this->request->getPost($f));return $v===''?null:$v;}
    private function uuidV4():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
