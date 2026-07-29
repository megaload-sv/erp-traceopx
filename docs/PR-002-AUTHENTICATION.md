# PR 2 — Autenticación, roles y permisos

## Entregable

Base de seguridad del ERP TraceOPX con usuarios, roles, permisos, inicio y cierre de sesión, CSRF y protección del dashboard.

## Configuración previa

Agregar en `.env`:

```ini
AUTH_ADMIN_NAME = 'Administrador TraceOPX'
AUTH_ADMIN_EMAIL = 'admin@traceopx.local'
AUTH_ADMIN_PASSWORD = 'Usa-una-clave-segura-aqui'
```

En producción nunca se debe usar una contraseña de ejemplo.

## Instalación local

```bash
git fetch origin
git checkout feature/authentication
git pull origin feature/authentication
composer install
php spark migrate
php spark db:seed SecuritySeeder
php spark serve
```

## Flujo de validación

1. Abrir `/dashboard` sin sesión.
2. Confirmar redirección a `/auth/login`.
3. Intentar credenciales incorrectas.
4. Ingresar con las credenciales definidas en `.env`.
5. Confirmar acceso al dashboard.
6. Confirmar que la sesión se mantiene entre solicitudes.
7. Ejecutar cierre de sesión mediante POST.
8. Confirmar que `/dashboard` vuelve a estar protegido.

## Base de datos

Tablas creadas:

- `users`
- `roles`
- `permissions`
- `user_roles`
- `role_permissions`

El seeder es idempotente: puede ejecutarse nuevamente sin duplicar roles, permisos o asignaciones.

## Producción

```bash
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader
php spark migrate
php spark db:seed SecuritySeeder
php spark cache:clear
```

Antes del seeder, definir una contraseña administrativa exclusiva del entorno productivo.

## Reversión

```bash
php spark migrate:rollback
```

Advertencia: el rollback elimina las tablas de seguridad y todos sus datos. Realizar respaldo antes de revertir en un entorno con usuarios reales.
