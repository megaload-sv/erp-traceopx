# PR 2 — Autenticación, roles y permisos

## Entregable

Base de seguridad del ERP TraceOPX con usuarios, roles, permisos, inicio y cierre de sesión, CSRF y protección del dashboard.

## Configuración previa

La conexión a MySQL se configura normalmente en `.env`.

Para proteger las rutas administrativas de instalación, agregar un token largo y exclusivo:

```ini
SYSTEM_MIGRATION_TOKEN = 'coloca-aqui-un-token-largo-y-seguro'
```

Las credenciales iniciales del administrador se crean mediante `SecuritySeeder`; no requieren variables adicionales en `.env`.

## Administrador inicial

```text
Nombre: Administrador TraceOPX
Correo: admin@traceopx.com
Contraseña temporal: TraceOPX@2026
```

La contraseña es únicamente para la instalación inicial y deberá cambiarse antes de habilitar usuarios reales.

## Instalación local mediante CLI

```bash
git fetch origin
git checkout feature/authentication
git pull origin feature/authentication
composer install
php spark migrate
php spark db:seed SecuritySeeder
php spark serve
```

## Instalación mediante rutas protegidas

Ejecutar migraciones y el seeder inicial en una sola operación:

```text
/system/setup?token=TOKEN_CONFIGURADO
```

Rutas individuales:

```text
/system/migrate?token=TOKEN_CONFIGURADO
/system/seed/SecuritySeeder?token=TOKEN_CONFIGURADO
```

Las respuestas se devuelven en formato JSON. Los errores técnicos completos se registran en `writable/logs` y no se exponen públicamente.

## Flujo de validación

1. Ejecutar la configuración inicial por CLI o mediante `/system/setup`.
2. Abrir `/dashboard` sin sesión.
3. Confirmar redirección a `/auth/login`.
4. Intentar credenciales incorrectas.
5. Ingresar con el administrador inicial.
6. Confirmar acceso al dashboard.
7. Confirmar que la sesión se mantiene entre solicitudes.
8. Ejecutar cierre de sesión mediante POST.
9. Confirmar que `/dashboard` vuelve a estar protegido.

## Base de datos

Tablas creadas:

- `users`
- `roles`
- `permissions`
- `user_roles`
- `role_permissions`

El seeder es idempotente: puede ejecutarse nuevamente sin duplicar roles, permisos, usuarios o asignaciones.

## Producción

```bash
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader
php spark migrate
php spark db:seed SecuritySeeder
php spark cache:clear
```

Alternativamente, usar una sola vez la ruta protegida `/system/setup` y posteriormente retirar o rotar el token de instalación.

## Reversión

```bash
php spark migrate:rollback
```

Advertencia: el rollback elimina las tablas de seguridad y todos sus datos. Realizar respaldo antes de revertir en un entorno con usuarios reales.
