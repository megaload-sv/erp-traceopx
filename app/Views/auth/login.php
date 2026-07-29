<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Iniciar sesión') ?> | ERP TraceOPX</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-900">
<div class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden bg-gradient-to-br from-slate-950 via-slate-900 to-cyan-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-400">Megaload</p>
            <h1 class="mt-4 text-4xl font-bold">ERP TraceOPX</h1>
            <p class="mt-4 max-w-xl text-lg text-slate-300">Trazabilidad integral desde la solicitud de trabajo hasta la facturación y el cobro.</p>
        </div>
        <p class="text-sm text-slate-400">Acceso seguro al panel administrativo.</p>
    </section>

    <main class="flex items-center justify-center bg-slate-100 p-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl shadow-slate-950/10">
            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-600">Acceso administrativo</p>
            <h2 class="mt-3 text-3xl font-bold text-slate-950">Iniciar sesión</h2>
            <p class="mt-2 text-sm text-slate-600">Ingresa tus credenciales para continuar.</p>

            <?php if (session('error')): ?>
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= esc(session('error')) ?></div>
            <?php endif ?>

            <form action="<?= route_to('login.attempt') ?>" method="post" class="mt-8 space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label for="email" class="text-sm font-semibold text-slate-700">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="<?= old('email') ?>" required autocomplete="email" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                    <?php if (session('errors.email')): ?><p class="mt-2 text-sm text-red-600"><?= esc(session('errors.email')) ?></p><?php endif ?>
                </div>
                <div>
                    <label for="password" class="text-sm font-semibold text-slate-700">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                    <?php if (session('errors.password')): ?><p class="mt-2 text-sm text-red-600"><?= esc(session('errors.password')) ?></p><?php endif ?>
                </div>
                <button type="submit" class="w-full rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white transition hover:bg-cyan-600">Ingresar</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
