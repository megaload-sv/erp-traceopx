<?php

namespace App\Controllers;

use App\Models\EquipmentModel;
use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class EquipmentController extends BaseController
{
    public function index(): string
    {
        $rows = (new EquipmentModel())->workspaceList();

        return view('equipment/index', [
            'title' => 'Maquinaria y equipo',
            'equipment' => $rows,
            'metrics' => [
                'total' => count($rows),
                'available' => count(array_filter($rows, static fn(array $r): bool => $r['operational_status'] === 'available')),
                'maintenance' => count(array_filter($rows, static fn(array $r): bool => in_array($r['maintenance_status'], ['preventive_due','preventive','corrective','out_of_service'], true))),
            ],
        ]);
    }

    public function create(): string
    {
        return view('equipment/form', $this->formData(null));
    }

    public function store(): RedirectResponse
    {
        return $this->persist(null);
    }

    public function edit(int $id): string
    {
        $equipment = (new EquipmentModel())->detail($id);
        if ($equipment === null) {
            throw new RuntimeException('Equipo no encontrado.');
        }

        return view('equipment/form', $this->formData($equipment));
    }

    public function update(int $id): RedirectResponse
    {
        return $this->persist($id);
    }

    private function persist(?int $id): RedirectResponse
    {
        $db = db_connect();
        $model = new EquipmentModel();
        $db->transBegin();

        try {
            if ($id !== null && $model->find($id) === null) {
                throw new RuntimeException('Equipo no encontrado.');
            }

            $data = [
                'code' => strtoupper(trim((string) $this->request->getPost('code'))),
                'category_id' => $this->nullableInt('category_id'),
                'name' => trim((string) $this->request->getPost('name')),
                'brand' => $this->nullable('brand'),
                'model' => $this->nullable('model'),
                'serial_number' => $this->nullable('serial_number'),
                'plate_number' => $this->nullable('plate_number'),
                'year' => $this->nullableInt('year'),
                'operational_status' => (string) ($this->request->getPost('operational_status') ?: 'available'),
                'maintenance_status' => (string) ($this->request->getPost('maintenance_status') ?: 'ok'),
                'meter_type' => $this->nullable('meter_type'),
                'current_meter' => $this->nullableDecimal('current_meter'),
                'notes' => $this->nullable('notes'),
                'status' => 1,
            ];

            $duplicate = $model->where('code', $data['code']);
            if ($id !== null) {
                $duplicate->where('id !=', $id);
            }
            if ($duplicate->first() !== null) {
                throw new RuntimeException('Ya existe un equipo con ese código.');
            }

            if ($id === null) {
                $data['uuid'] = $this->uuidV4();
                $savedId = $model->insert($data, true);
                if ($savedId === false) {
                    throw new RuntimeException(implode(' ', $model->errors()) ?: 'No fue posible crear el equipo.');
                }
                $id = (int) $savedId;
                $eventKey = 'equipment.created';
                $eventTitle = 'Equipo registrado';
            } else {
                if (! $model->update($id, $data)) {
                    throw new RuntimeException(implode(' ', $model->errors()) ?: 'No fue posible actualizar el equipo.');
                }
                $eventKey = 'equipment.updated';
                $eventTitle = 'Equipo actualizado';
            }

            $db->table('equipment_role_requirements')->where('equipment_id', $id)->delete();
            $roleIds = $this->request->getPost('role_id');
            $requirementTypes = $this->request->getPost('requirement_type');
            $minQuantities = $this->request->getPost('min_quantity');
            $maxQuantities = $this->request->getPost('max_quantity');
            $roleNotes = $this->request->getPost('role_notes');

            foreach ((array) $roleIds as $roleId) {
                $roleId = (int) $roleId;
                if ($roleId <= 0) {
                    continue;
                }

                $type = (string) ($requirementTypes[$roleId] ?? 'optional');
                $min = max(0, (int) ($minQuantities[$roleId] ?? 0));
                $max = max($min, (int) ($maxQuantities[$roleId] ?? $min));
                if ($type === 'required' && $min === 0) {
                    $min = 1;
                    $max = max(1, $max);
                }

                $db->table('equipment_role_requirements')->insert([
                    'equipment_id' => $id,
                    'resource_role_id' => $roleId,
                    'requirement_type' => in_array($type, ['required','optional'], true) ? $type : 'optional',
                    'min_quantity' => $min,
                    'max_quantity' => $max,
                    'notes' => trim((string) ($roleNotes[$roleId] ?? '')) ?: null,
                    'status' => 1,
                    'entry_user' => (string) (session('auth_user_email') ?: 'system'),
                    'entry_date' => date('Y-m-d H:i:s'),
                ]);
            }

            (new ActivityService())->record('equipment', $id, $eventKey, $eventTitle, $data['code'] . ' · ' . $data['name']);
            $db->transCommit();

            return redirect()->to(route_to('equipment.edit', $id))->with('success', 'Maquinaria/equipo guardado correctamente.');
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error guardando equipo: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function formData(?array $equipment): array
    {
        $db = db_connect();
        $requirements = [];
        if ($equipment !== null) {
            foreach ($db->table('equipment_role_requirements')->where('equipment_id', (int) $equipment['id'])->where('status', 1)->get()->getResultArray() as $row) {
                $requirements[(int) $row['resource_role_id']] = $row;
            }
        }

        return [
            'title' => $equipment ? 'Editar maquinaria/equipo' : 'Nueva maquinaria/equipo',
            'equipment' => $equipment,
            'categories' => $db->table('equipment_categories')->where('status', 1)->orderBy('display_order')->orderBy('name')->get()->getResultArray(),
            'roles' => $db->table('resource_roles')->where('status', 1)->orderBy('name')->get()->getResultArray(),
            'requirements' => $requirements,
        ];
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function nullableInt(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : (int) $value;
    }

    private function nullableDecimal(string $field): ?float
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : (float) $value;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
