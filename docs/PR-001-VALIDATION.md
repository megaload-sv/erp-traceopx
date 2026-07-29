# PR 1 — Validación local y despliegue

## Entregable

Base navegable del ERP TraceOPX con dashboard administrativo inicial.

## Validación local

```bash
git fetch origin
git checkout feature/project-foundation
composer install
php spark serve
```

Abrir:

```text
http://localhost:8080
```

## Criterios de aceptación

- La ruta `/` redirige a `/dashboard`.
- El dashboard responde sin errores.
- Se muestra el layout administrativo.
- Se muestran cuatro indicadores iniciales.
- La navegación lateral muestra los módulos planificados.
- La interfaz es adaptable a escritorio y móvil.

## Base de datos

Este incremento no crea ni modifica tablas.

```bash
php spark migrate:status
```

No es necesario ejecutar migraciones ni seeders.

## Despliegue a producción

1. Crear un respaldo del código actual.
2. Publicar el contenido fusionado de `main`.
3. Ejecutar `composer install --no-dev --optimize-autoloader`.
4. Confirmar que el archivo `.env` mantiene `CI_ENVIRONMENT = production`.
5. Limpiar caché con `php spark cache:clear` si el hosting permite CLI.
6. Visitar `/dashboard` y comprobar los criterios de aceptación.

## Reversión

Como no existen cambios de base de datos, la reversión consiste en restaurar el commit anterior o desplegar nuevamente la versión previa del código.
