<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$editing = is_array($address);
$selectedCountry = (string) old('country_id', $address['country_id'] ?? '');
$selectedDepartment = (string) old('department_id', $address['department_id'] ?? '');
$selectedMunicipality = (string) old('municipality_id', $address['municipality_id'] ?? '');
$selectedDistrict = (string) old('district_id', $address['district_id'] ?? '');
?>
<div class="mx-auto max-w-4xl">
    <a href="<?= route_to('customers.show', $customer['id']) ?>" class="text-sm font-semibold text-cyan-700">← Volver al perfil</a>
    <div class="mt-4"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-600">Ubicaciones del cliente</p><h3 class="mt-2 text-3xl font-bold text-slate-950"><?= $editing ? 'Editar ubicación' : 'Agregar ubicación' ?></h3><p class="mt-2 text-slate-600"><?= esc($customer['business_name']) ?></p></div>

    <?php if (session('errors')): ?><div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?></div><?php endif ?>

    <form method="post" action="<?= $editing ? route_to('customers.addresses.update', $customer['id'], $address['id']) : route_to('customers.addresses.store', $customer['id']) ?>" class="mt-6 space-y-6">
        <?= csrf_field() ?>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre de la ubicación</span><input name="label" value="<?= esc(old('label', $address['label'] ?? '')) ?>" placeholder="Casa matriz, Sucursal Santa Ana..." class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo</span><select name="address_type"><option value="fiscal" <?= old('address_type', $address['address_type'] ?? 'operational') === 'fiscal' ? 'selected' : '' ?>>Fiscal</option><option value="operational" <?= old('address_type', $address['address_type'] ?? 'operational') === 'operational' ? 'selected' : '' ?>>Operativa / sucursal</option><option value="other" <?= old('address_type', $address['address_type'] ?? '') === 'other' ? 'selected' : '' ?>>Otra</option></select></label>
                <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Dirección completa *</span><textarea required name="address_line" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('address_line', $address['address_line'] ?? '')) ?></textarea></label>
                <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">País *</span><select id="country_id" name="country_id" data-placeholder="Buscar país"><option value="">Seleccionar país</option><?php foreach ($countries as $country): ?><option value="<?= esc($country['id']) ?>" data-code="<?= esc($country['code']) ?>" <?= $selectedCountry === (string) $country['id'] ? 'selected' : '' ?>><?= esc($country['code'] . ' - ' . $country['name']) ?></option><?php endforeach ?></select></label>
            </div>

            <div id="sv-fields" class="mt-5 grid gap-5 rounded-2xl border border-cyan-100 bg-cyan-50/40 p-5 md:grid-cols-3">
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Departamento *</span><select id="department_id" name="department_id" data-placeholder="Buscar departamento"><option value="">Seleccionar</option><?php foreach ($departments as $department): ?><option value="<?= esc($department['id']) ?>" data-code="<?= esc($department['code']) ?>" <?= $selectedDepartment === (string) $department['id'] ? 'selected' : '' ?>><?= esc($department['code'] . ' - ' . $department['name']) ?></option><?php endforeach ?></select></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Municipio *</span><select id="municipality_id" name="municipality_id" data-placeholder="Buscar municipio"><option value="">Seleccionar</option></select></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Distrito *</span><select id="district_id" name="district_id" data-placeholder="Buscar distrito"><option value="">Seleccionar</option></select></label>
            </div>

            <div id="foreign-fields" class="mt-5 grid gap-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 md:grid-cols-2">
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Estado / Provincia / Región</span><input name="foreign_state" value="<?= esc(old('foreign_state', $address['foreign_state'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Ciudad</span><input name="foreign_city" value="<?= esc(old('foreign_city', $address['foreign_city'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
            </div>

            <?php if ($editing): ?><div class="mt-5"><label><span class="mb-2 block text-sm font-semibold text-slate-700">Estado</span><select name="status"><option value="1" <?= (int) old('status', $address['status']) === 1 ? 'selected' : '' ?>>Activa</option><option value="0" <?= (int) old('status', $address['status']) === 0 ? 'selected' : '' ?>>Inactiva</option></select></label><?php if ((int) $address['is_primary'] === 1): ?><p class="mt-2 text-xs text-amber-700">Para desactivarla, primero selecciona otra ubicación como principal.</p><?php endif ?></div><?php endif ?>
        </section>
        <div class="flex justify-end gap-3"><a href="<?= route_to('customers.show', $customer['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</a><button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white"><?= $editing ? 'Guardar cambios' : 'Agregar ubicación' ?></button></div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const municipalities = <?= json_encode(array_map(static fn ($r) => ['value' => (string) $r['id'], 'label' => $r['code'] . ' - ' . $r['name'], 'department' => $r['department_code']], $municipalities), JSON_UNESCAPED_UNICODE) ?>;
    const districts = <?= json_encode(array_map(static fn ($r) => ['value' => (string) $r['id'], 'label' => $r['code'] . ' - ' . $r['name'], 'department' => $r['department_code']], $districts), JSON_UNESCAPED_UNICODE) ?>;
    const initialMunicipality = <?= json_encode($selectedMunicipality) ?>;
    const initialDistrict = <?= json_encode($selectedDistrict) ?>;
    const country = document.getElementById('country_id');
    const department = document.getElementById('department_id');
    const municipality = document.getElementById('municipality_id');
    const district = document.getElementById('district_id');
    const svFields = document.getElementById('sv-fields');
    const foreignFields = document.getElementById('foreign-fields');

    const codeOf = select => select.options[select.selectedIndex]?.dataset.code || '';
    const rebuild = (select, rows, selected) => {
        const instance = window.traceOpxChoices.get(select);
        if (instance) instance.destroy();
        select.innerHTML = '<option value="">Seleccionar</option>' + rows.map(row => `<option value="${row.value}" ${row.value === selected ? 'selected' : ''}>${row.label}</option>`).join('');
        window.traceOpxChoices.delete(select);
        window.initTraceOpxSelect(select);
    };
    const filterTerritory = (keepSelection = false) => {
        const departmentCode = codeOf(department);
        rebuild(municipality, municipalities.filter(row => row.department === departmentCode), keepSelection ? initialMunicipality : '');
        rebuild(district, districts.filter(row => row.department === departmentCode), keepSelection ? initialDistrict : '');
    };
    const toggleCountry = () => {
        const isSv = codeOf(country) === 'SV';
        svFields.classList.toggle('hidden', !isSv);
        foreignFields.classList.toggle('hidden', isSv);
    };

    country.addEventListener('change', toggleCountry);
    department.addEventListener('change', () => filterTerritory(false));
    toggleCountry();
    filterTerritory(true);
});
</script>
<?= $this->endSection() ?>
