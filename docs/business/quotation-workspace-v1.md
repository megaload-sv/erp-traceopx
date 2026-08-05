# Quotation Workspace V1

## Objetivo

Construir un espacio de trabajo para preparar y guardar cotizaciones en borrador con una experiencia moderna, minimalista y guiada, manteniendo trazabilidad desde la solicitud comercial o desde un cliente existente.

## Orígenes permitidos

- Atención comercial → Solicitud comercial → Cotización.
- Cliente existente → Cotización directa.

Toda cotización deberá registrar su origen.

## Alcance del primer incremento

1. Cliente y contactos dependientes.
2. Contacto principal seleccionado por defecto y posibilidad de cambiarlo.
3. Agente comercial basado inicialmente en el usuario autenticado.
4. Fecha de cotización y fecha de validez.
5. Forma de pago seleccionada desde catálogo.
6. Catálogo comercial nuevo, incorporando únicamente los conceptos aprobados para TraceOPX.
7. Líneas de cotización desde catálogo o de ingreso manual.
8. Términos y condiciones generales precargados desde Settings.
9. Personalización de términos dentro de cada cotización.
10. Snapshot histórico de los términos utilizados.
11. Guardado como borrador.
12. Resumen financiero sin impuestos por línea.

## Líneas de cotización

Cada línea registrará:

- `source_type`: `catalog` o `manual`.
- `commercial_item_id`: requerido únicamente cuando proviene del catálogo.
- Descripción.
- Descripción ampliada.
- Unidad.
- Cantidad.
- Precio unitario.
- Total calculado de línea.
- Orden de presentación.

Las líneas provenientes del catálogo guardarán una copia histórica de sus valores para evitar que cambios futuros alteren cotizaciones existentes.

## Impuestos

En V1 no se utilizarán impuestos por línea. La arquitectura dejará preparado el cálculo global para un incremento posterior.

## Términos y condiciones

Los términos generales se obtendrán desde Settings al crear una cotización. Cada cotización almacenará su propia copia y permitirá agregar o modificar condiciones particulares sin alterar la configuración general.

## Forma de pago

La forma de pago será inicialmente seleccionable desde catálogo. Su impacto sobre anticipos, liberación hacia coordinación y saldos pendientes se implementará progresivamente después de validar el flujo financiero real.

## Fuera de alcance en V1

- PDF definitivo.
- Envío por correo.
- Versionado de cotizaciones.
- Aceptación o rechazo.
- Validación de anticipos.
- Liberación hacia coordinación.
- Impuestos globales.
- Integración real con WhatsApp o correo.

## Principio UX

La cotización debe sentirse como la preparación de una propuesta comercial, no como el llenado de un formulario extenso.
