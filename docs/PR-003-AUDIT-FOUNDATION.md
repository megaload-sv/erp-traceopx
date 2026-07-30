# PR 3 — Base corporativa de auditoría

## Objetivo

Establecer un mecanismo reutilizable de auditoría para los modelos y tablas del ERP TraceOPX.

## Campos estándar

```text
entry_user
entry_date
modify_user
modify_date
delete_user
delete_date
```

Los campos de usuario almacenan el correo del usuario autenticado. Cuando la operación se ejecuta desde consola, migración o seeder, se utiliza `system`.

## Incluye

- `App\Models\BaseModel` como modelo padre auditable.
- Registro automático de creación, modificación y eliminación lógica.
- Migración de auditoría para las cinco tablas de seguridad.
- Migración de datos existentes usando `created_at` cuando esté disponible.
- Actualización de `UserModel` para heredar de `BaseModel`.
- Actualización de `SecuritySeeder` para completar los campos de auditoría.

## Compatibilidad

Esta PR no elimina todavía los campos existentes:

```text
created_at
updated_at
deleted_at
```

Se mantienen temporalmente para reducir riesgos durante la transición. Los modelos nuevos deberán utilizar los campos corporativos de auditoría.

## Instalación

```bash
git checkout feature/audit-foundation
git pull origin feature/audit-foundation
php spark migrate
php spark cache:clear
```

También puede ejecutarse mediante la ruta protegida:

```text
/system/migrate?token=TOKEN_CONFIGURADO
```

## Validaciones

1. Confirmar que la migración agrega los seis campos a `users`, `roles`, `permissions`, `user_roles` y `role_permissions`.
2. Confirmar que los registros existentes reciben `entry_user = system`.
3. Iniciar sesión y verificar que `last_login_at` actualiza `modify_user` y `modify_date`.
4. Ejecutar nuevamente `SecuritySeeder` y comprobar que no duplica registros.
5. Confirmar que el login y el dashboard continúan funcionando.

## Rollback

```bash
php spark migrate:rollback
```

El rollback de esta migración elimina solamente los seis campos corporativos de auditoría agregados por la PR.
