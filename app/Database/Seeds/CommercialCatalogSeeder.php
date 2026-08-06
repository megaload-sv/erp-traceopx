<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class CommercialCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->seedGroups($now);
        $this->seedUnits($now);

        $groupIds = $this->mapIdsByCode('commercial_item_groups');
        $unitIds = $this->mapIdsByCode('commercial_units');
        $items = $this->loadCatalogItems();

        $this->db->transStart();

        foreach ($items as $position => $item) {
            $groupCode = (string) ($item['group_code'] ?? 'OTHER_SERVICES');
            $unitCode = (string) ($item['unit_code'] ?? 'OTHER');

            if (! isset($groupIds[$groupCode])) {
                throw new RuntimeException('Grupo comercial no encontrado: ' . $groupCode);
            }

            if (! isset($unitIds[$unitCode])) {
                throw new RuntimeException('Unidad comercial no encontrada: ' . $unitCode);
            }

            $payload = [
                'item_type' => (string) ($item['item_type'] ?? 'service'),
                'item_group_id' => $groupIds[$groupCode],
                'name' => trim((string) ($item['name'] ?? '')),
                'long_description' => $this->nullableString($item['long_description'] ?? null),
                'default_unit_id' => $unitIds[$unitCode],
                'suggested_price' => max(0, (float) ($item['suggested_price'] ?? 0)),
                'allows_price_override' => 1,
                'allows_unit_override' => (int) ($item['allows_unit_override'] ?? 1),
                'display_order' => $position + 1,
                'source_reference' => $this->nullableString($item['source_reference'] ?? null),
                'normalization_notes' => $this->nullableString($item['normalization_notes'] ?? null),
                'status' => 1,
                'modify_user' => 'seeder',
                'modify_date' => $now,
            ];

            if ($payload['name'] === '') {
                throw new RuntimeException('Se encontró un concepto comercial sin nombre.');
            }

            $code = trim((string) ($item['code'] ?? ''));
            if ($code === '') {
                throw new RuntimeException('Se encontró un concepto comercial sin código.');
            }

            $existing = $this->db->table('commercial_items')
                ->where('code', $code)
                ->get()
                ->getRowArray();

            if ($existing !== null) {
                $this->db->table('commercial_items')
                    ->where('id', $existing['id'])
                    ->update($payload);
                continue;
            }

            $payload['uuid'] = $this->uuidV4();
            $payload['code'] = $code;
            $payload['entry_user'] = 'seeder';
            $payload['entry_date'] = $now;

            $this->db->table('commercial_items')->insert($payload);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new RuntimeException('No fue posible guardar el catálogo comercial normalizado.');
        }
    }

    private function seedGroups(string $now): void
    {
        $groups = [
            ['code' => 'CRANES', 'name' => 'Grúas Telescópicas', 'display_order' => 10],
            ['code' => 'FORKLIFTS', 'name' => 'Montacargas', 'display_order' => 20],
            ['code' => 'TELEHANDLERS', 'name' => 'Telehandlers', 'display_order' => 30],
            ['code' => 'MANLIFT', 'name' => 'ManLift', 'display_order' => 40],
            ['code' => 'TRANSPORT', 'name' => 'Equipos de Transporte', 'display_order' => 50],
            ['code' => 'EARTHMOVING', 'name' => 'Maquinaria de Terracería', 'display_order' => 60],
            ['code' => 'OTHER_SERVICES', 'name' => 'Otros Servicios', 'display_order' => 70],
        ];

        foreach ($groups as $group) {
            $existing = $this->db->table('commercial_item_groups')
                ->where('code', $group['code'])
                ->get()
                ->getRowArray();

            $payload = $group + [
                'description' => null,
                'icon' => null,
                'status' => 1,
                'modify_user' => 'seeder',
                'modify_date' => $now,
            ];

            if ($existing !== null) {
                $this->db->table('commercial_item_groups')->where('id', $existing['id'])->update($payload);
                continue;
            }

            $payload['entry_user'] = 'seeder';
            $payload['entry_date'] = $now;
            $this->db->table('commercial_item_groups')->insert($payload);
        }
    }

    private function seedUnits(string $now): void
    {
        $units = [
            ['code' => 'UNIT', 'name' => 'Unidad', 'symbol' => 'Und', 'display_order' => 10],
            ['code' => 'GLOBAL', 'name' => 'Suma Global', 'symbol' => 'SG', 'display_order' => 20],
            ['code' => 'FREIGHT', 'name' => 'Flete', 'symbol' => 'Flt', 'display_order' => 30],
            ['code' => 'HOUR', 'name' => 'Hora', 'symbol' => 'h', 'display_order' => 40],
            ['code' => 'DAY', 'name' => 'Día', 'symbol' => 'd', 'display_order' => 50],
            ['code' => 'WEEK', 'name' => 'Semana', 'symbol' => 'sem', 'display_order' => 60],
            ['code' => 'MONTH', 'name' => 'Mes', 'symbol' => 'mes', 'display_order' => 70],
            ['code' => 'YEAR', 'name' => 'Año', 'symbol' => 'año', 'display_order' => 80],
            ['code' => 'SERVICE', 'name' => 'Servicio', 'symbol' => 'Srv', 'display_order' => 90],
            ['code' => 'OTHER', 'name' => 'Otro', 'symbol' => null, 'display_order' => 100],
        ];

        foreach ($units as $unit) {
            $existing = $this->db->table('commercial_units')
                ->where('code', $unit['code'])
                ->get()
                ->getRowArray();

            $payload = [
                'name' => $unit['name'],
                'symbol' => $unit['symbol'],
                'display_order' => $unit['display_order'],
                'status' => 1,
                'modify_user' => 'seeder',
                'modify_date' => $now,
            ];

            if ($existing !== null) {
                $this->db->table('commercial_units')->where('id', $existing['id'])->update($payload);
                continue;
            }

            $payload['code'] = $unit['code'];
            $payload['entry_user'] = 'seeder';
            $payload['entry_date'] = $now;
            $this->db->table('commercial_units')->insert($payload);
        }
    }

    private function loadCatalogItems(): array
    {
        $dataDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'Data';
        $parts = [
            $dataDirectory . DIRECTORY_SEPARATOR . 'commercial_catalog_part_01.php',
            $dataDirectory . DIRECTORY_SEPARATOR . 'commercial_catalog_part_02.php',
            $dataDirectory . DIRECTORY_SEPARATOR . 'commercial_catalog_part_03.php',
        ];

        $encoded = '';

        foreach ($parts as $part) {
            if (! is_file($part)) {
                throw new RuntimeException('No se encontró el archivo de datos: ' . basename($part));
            }

            $chunk = require $part;
            if (! is_string($chunk) || $chunk === '') {
                throw new RuntimeException('El archivo de datos no contiene una cadena válida: ' . basename($part));
            }

            $encoded .= $chunk;
        }

        $compressed = base64_decode($encoded, true);
        if ($compressed === false) {
            throw new RuntimeException('El catálogo comercial no contiene Base64 válido.');
        }

        $json = gzdecode($compressed);
        if ($json === false) {
            throw new RuntimeException('El catálogo comercial comprimido está dañado o incompleto.');
        }

        $items = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($items)) {
            throw new RuntimeException('El catálogo comercial normalizado no tiene una estructura válida.');
        }

        return $items;
    }

    private function mapIdsByCode(string $table): array
    {
        $result = [];

        foreach ($this->db->table($table)->select('id, code')->get()->getResultArray() as $row) {
            $result[(string) $row['code']] = (int) $row['id'];
        }

        return $result;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
