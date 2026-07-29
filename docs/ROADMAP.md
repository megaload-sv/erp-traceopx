# ERP TraceOPX — Roadmap incremental

Cada Pull Request debe entregar una capacidad funcional, verificable en local y desplegable de forma controlada a producción.

## Estados

- Pendiente
- En desarrollo
- En revisión
- Aprobado
- Desplegado

## Incrementos

| Incremento | Entregable | Estado |
|---|---|---|
| PR 1 | Base técnica, layout y dashboard inicial | En desarrollo |
| PR 2 | Autenticación, usuarios, roles y permisos | Pendiente |
| PR 3 | Catálogo y gestión de clientes | Pendiente |
| PR 4 | Solicitudes de trabajo | Pendiente |
| PR 5 | Cotizaciones y control de estados | Pendiente |
| PR 6 | Órdenes de trabajo y seguimiento operativo | Pendiente |
| PR 7 | Cobros y preparación para facturación | Pendiente |
| PR 8 | Integración con facturación electrónica | Pendiente |
| PR 9 | Auditoría y trazabilidad transversal | Pendiente |

## Definición de terminado por PR

Una PR se considera entregable cuando incluye:

1. Objetivo funcional claramente definido.
2. Código completo del incremento.
3. Migraciones y seeders cuando correspondan.
4. Instrucciones para instalación local.
5. Comandos para pruebas y validación.
6. Consideraciones de despliegue a producción.
7. Plan de reversión cuando exista cambio de base de datos.
8. Criterios de aceptación verificables.

## Flujo de promoción

1. Desarrollo en rama `feature/*`.
2. Validación local.
3. Revisión de Pull Request.
4. Merge a `main`.
5. Respaldo previo a producción cuando aplique.
6. Despliegue.
7. Ejecución de migraciones y seeders autorizados.
8. Validación funcional posterior al despliegue.
