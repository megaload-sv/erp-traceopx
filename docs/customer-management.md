# Customer Management

## Alcance

La PR incorpora el primer módulo funcional de ERP TraceOPX:

- Clientes con código corporativo `CLI-000001`.
- Contacto principal opcional.
- Dirección fiscal principal opcional.
- Búsqueda por código, nombre, documento, correo o teléfono.
- Activación e inactivación del cliente.
- Bitácora funcional transversal mediante `activity_events`.

## Instalación

```bash
git checkout feature/customer-management
git pull origin feature/customer-management
php spark migrate
php spark cache:clear
```

También puede ejecutarse la migración mediante la ruta protegida:

```text
/system/migrate?token=TOKEN_CONFIGURADO
```

## Validación funcional

1. Iniciar sesión.
2. Abrir `/customers`.
3. Crear un cliente con contacto y dirección.
4. Confirmar que el código tenga formato `CLI-000001`.
5. Abrir el detalle del cliente.
6. Confirmar los eventos:
   - Cliente creado.
   - Contacto principal agregado.
   - Dirección fiscal agregada.
7. Editar el cliente y confirmar el evento `Cliente actualizado`.
8. Buscarlo por código, nombre, NIT, correo y teléfono.

## Tablas

- `customers`
- `customer_contacts`
- `customer_addresses`
- `activity_events`

## Rollback

```bash
php spark migrate:rollback
```

El rollback elimina las cuatro tablas creadas por esta PR.
