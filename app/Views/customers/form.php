<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$editing = is_array($customer);
$selectedCountry = (string) old('tax_country_id', $customer['tax_country_id'] ?? '');
$selectedDepartment = (string) old('tax_department_id', $customer['tax_department_id'] ?? '');
$selectedMunicipality = (string) old('tax_municipality_id', $customer['tax_municipality_id'] ?? '');
$selectedDistrict = (string) old('tax_district_id', $customer['tax_district_id'] ?? '');
?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('errors')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?></div><?php endif ?>

<div class="mb-7">
    <a href="<?= route_to('customers.index') ?>" class="text-sm font-semibold text-cyan-700">← Volver a clientes</a>
    <h3 class="mt-3 text-3xl font-bold text-slate-950"><?= $editing ? 'Editar cliente' : 'Nuevo cliente' ?></h3>
    <p class="mt-2 text-slate-600">Registra su identidad, perfil tributario y relación comercial en un flujo guiado.</p>
</div>

<form method="post" action="<?= $editing ? route_to('customers.update', $customer['id']) : route_to('customers.store') ?>" class="space-y-6">
    <?= csrf_field() ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600">Identidad</p>
        <h4 class="mt-2 text-lg font-bold text-slate-950">Información general</h4>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Naturaleza del cliente</span><select name="customer_type" data-placeholder="Seleccionar naturaleza"><option value="company" <?= old('customer_type', $customer['customer_type'] ?? 'company') === 'company' ? 'selected' : '' ?>>Empresa</option><option value="person" <?= old('customer_type', $customer['customer_type'] ?? '') === 'person' ? 'selected' : '' ?>>Persona natural</option></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Razón social o nombre *</span><input required name="business_name" value="<?= esc(old('business_name', $customer['business_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre comercial</span><input name="trade_name" value="<?= esc(old('trade_name', $customer['trade_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Correo general</span><input type="email" name="email" value="<?= esc(old('email', $customer['email'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Teléfono general</span><input name="phone" value="<?= esc(old('phone', $customer['phone'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Sitio web</span><input name="website" value="<?= esc(old('website', $customer['website'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <?php if ($editing): ?><label><span class="mb-2 block text-sm font-semibold text-slate-700">Registro habilitado</span><select name="status"><option value="1" <?= (int) old('status', $customer['status']) === 1 ? 'selected' : '' ?>>Sí</option><option value="0" <?= (int) old('status', $customer['status']) === 0 ? 'selected' : '' ?>>No</option></select></label><?php endif ?>
        </div>
    </section>

    <section class="rounded-2xl border border-violet-200 bg-violet-50/40 p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">Perfil fiscal</p>
        <h4 class="mt-2 text-lg font-bold text-slate-950">Clasificación tributaria y domicilio fiscal</h4>
        <p class="mt-1 text-sm text-slate-600">Los catálogos oficiales se muestran como código - descripción y pueden buscarse escribiendo cualquiera de los dos valores.</p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo de contribuyente</span><select name="mh_taxpayer_type_id" data-placeholder="Buscar tipo de contribuyente"><option value="">Seleccionar</option><?php foreach ($taxpayerTypes as $type): ?><option value="<?= esc($type['id']) ?>" <?= (string) old('mh_taxpayer_type_id', $customer['mh_taxpayer_type_id'] ?? '') === (string) $type['id'] ? 'selected' : '' ?>><?= esc($type['code'] . ' - ' . $type['name']) ?></option><?php endforeach ?></select></label>

            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Actividad económica</span><select name="mh_economic_activity_id" data-placeholder="Buscar código o actividad"><option value="">Seleccionar actividad</option><?php foreach ($economicActivities as $activity): ?><option value="<?= esc($activity['id']) ?>" <?= (string) old('mh_economic_activity_id', $customer['mh_economic_activity_id'] ?? '') === (string) $activity['id'] ? 'selected' : '' ?>><?= esc($activity['code'] . ' - ' . $activity['name']) ?></option><?php endforeach ?></select></label>

            <label><span class="mb-2 block text-sm font-semibold text-slate-700">NIT / Documento</span><input name="tax_id" value="<?= esc(old('tax_id', $customer['tax_id'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">NRC / Registro</span><input name="registration_number" value="<?= esc(old('registration_number', $customer['registration_number'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>

            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">País fiscal</span><select id="tax_country_id" name="tax_country_id" data-placeholder="Buscar país"><option value="">Seleccionar país</option><?php foreach ($countries as $country): ?><option value="<?= esc($country['id']) ?>" data-code="<?= esc($country['code']) ?>" <?= $selectedCountry === (string) $country['id'] ? 'selected' : '' ?>><?= esc($country['code'] . ' - ' . $country['name']) ?></option><?php endforeach ?></select></label>
        </div>

        <div id="sv-location-fields" class="mt-5 grid gap-5 rounded-2xl border border-violet-200 bg-white/70 p-5 md:grid-cols-3">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Departamento</span><select id="tax_department_id" name="tax_department_id" data-placeholder="Buscar departamento"><option value="">Seleccionar</option><?php foreach ($departments as $department): ?><option value="<?= esc($department['id']) ?>" data-code="<?= esc($department['code']) ?>" <?= $selectedDepartment === (string) $department['id'] ? 'selected' : '' ?>><?= esc($department['code'] . ' - ' . $department['name']) ?></option><?php endforeach ?></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Municipio</span><select id="tax_municipality_id" name="tax_municipality_id" data-placeholder="Buscar municipio"><option value="">Seleccionar</option></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Distrito</span><select id="tax_district_id" name="tax_district_id" data-placeholder="Buscar distrito"><option value="">Seleccionar</option></select></label>
            <p class="text-xs text-slate-500 md:col-span-3">Los municipios y distritos se filtran según el departamento seleccionado, respetando los códigos del catálogo proporcionado por Hacienda.</p>
        </div>

        <div id="foreign-location-fields" class="mt-5 grid gap-5 rounded-2xl border border-violet-200 bg-white/70 p-5 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Estado / Provincia / Región</span><input name="foreign_state" value="<?= esc(old('foreign_state', $customer['foreign_state'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Ciudad</span><input name="foreign_city" value="<?= esc(old('foreign_city', $customer['foreign_city'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
        </div>
    </section>

    <section class="rounded-2xl border border-cyan-200 bg-cyan-50/40 p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Relación comercial</p><h4 class="mt-2 text-lg font-bold text-slate-950">Seguimiento y clasificación</h4>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Etapa de relación</span><select name="lifecycle_stage"><option value="potential" <?= old('lifecycle_stage', $customer['lifecycle_stage'] ?? 'potential') === 'potential' ? 'selected' : '' ?>>Potencial</option><option value="active" <?= old('lifecycle_stage', $customer['lifecycle_stage'] ?? '') === 'active' ? 'selected' : '' ?>>Activo</option><option value="inactive" <?= old('lifecycle_stage', $customer['lifecycle_stage'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactivo / por reactivar</option></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Categoría comercial</span><select name="relationship_tier"><option value="standard" <?= old('relationship_tier', $customer['relationship_tier'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Estándar</option><option value="preferential" <?= old('relationship_tier', $customer['relationship_tier'] ?? '') === 'preferential' ? 'selected' : '' ?>>Preferencial</option><option value="strategic" <?= old('relationship_tier', $customer['relationship_tier'] ?? '') === 'strategic' ? 'selected' : '' ?>>Estratégico</option></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Ejecutivo comercial</span><input name="assigned_sales_user" value="<?= esc(old('assigned_sales_user', $customer['assigned_sales_user'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Próximo seguimiento</span><input type="date" name="next_follow_up_date" value="<?= esc(old('next_follow_up_date', $customer['next_follow_up_date'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
        </div>
    </section>

    <?php if (! $editing): ?>
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h4 class="text-lg font-bold">Contacto comercial principal</h4><div class="mt-5 space-y-4"><input name="contact_name" value="<?= esc(old('contact_name')) ?>" placeholder="Nombre completo" class="w-full rounded-xl border border-slate-300 px-4 py-3"><input name="contact_position" value="<?= esc(old('contact_position')) ?>" placeholder="Cargo" class="w-full rounded-xl border border-slate-300 px-4 py-3"><input type="email" name="contact_email" value="<?= esc(old('contact_email')) ?>" placeholder="Correo" class="w-full rounded-xl border border-slate-300 px-4 py-3"><input name="contact_phone" value="<?= esc(old('contact_phone')) ?>" placeholder="Teléfono" class="w-full rounded-xl border border-slate-300 px-4 py-3"></div></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h4 class="text-lg font-bold">Dirección detallada</h4><div class="mt-5"><textarea name="address_line" rows="5" placeholder="Calle, número, colonia, referencias y detalles adicionales" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('address_line')) ?></textarea></div></section>
    </div>
    <?php endif ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><label><span class="mb-2 block text-sm font-semibold text-slate-700">Observaciones</span><textarea name="notes" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('notes', $customer['notes'] ?? '')) ?></textarea></label></section>
    <div class="flex justify-end gap-3"><a href="<?= route_to('customers.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</a><button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white"><?= $editing ? 'Guardar cambios' : 'Crear cliente' ?></button></div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const municipalities = <?= json_encode(array_map(static fn ($row) => ['value' => (string) $row['id'], 'label' => $row['code'] . ' - ' . $row['name'], 'department' => $row['department_code']], $municipalities), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const districts = <?= json_encode(array_map(static fn ($row) => ['value' => (string) $row['id'], 'label' => $row['code'] . ' - ' . $row['name'], 'department' => $row['department_code']], $districts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const selectedMunicipality = <?= json_encode($selectedMunicipality) ?>;
    const selectedDistrict = <?= json_encode($selectedDistrict) ?>;
    const country = document.getElementById('tax_country_id');
    const department = document.getElementById('tax_department_id');
    const municipality = document.getElementById('tax_municipality_id');
    const district = document.getElementById('tax_district_id');
    const svFields = document.getElementById('sv-location-fields');
    const foreignFields = document.getElementById('foreign-location-fields');

    const selectedCode = (select) => select.options[select.selectedIndex]?.dataset.code || '';
    const choicesFor = (select) => window.traceOpxChoices.get(select);

    const replaceChoices = (select, rows, selectedValue) => {
        const instance = choicesFor(select);
        const options = [{value: '', label: 'Seleccionar', selected: selectedValue === ''}].concat(rows.map(row => ({...row, selected: row.value === selectedValue})));
        if (instance) instance.setChoices(options, 'value', 'label', true);
    };

    const refreshTerritory = (keepSelection = false) => {
        const departmentCode = selectedCode(department);
        replaceChoices(municipality, municipalities.filter(row => row.department === departmentCode), keepSelection ? selectedMunicipality : '');
        replaceChoices(district, districts.filter(row => row.department === departmentCode), keepSelection ? selectedDistrict : '');
    };

    const refreshCountryMode = () => {
        const isElSalvador = selectedCode(country) === 'SV';
        svFields.classList.toggle('hidden', !isElSalvador);
        foreignFields.classList.toggle('hidden', isElSalvador);
        if (isElSalvador) refreshTerritory(true);
    };

    country.addEventListener('change', refreshCountryMode);
    department.addEventListener('change', () => refreshTerritory(false));
    refreshCountryMode();
});
</script>
<?= $this->endSection() ?>
