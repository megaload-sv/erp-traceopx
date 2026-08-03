# Principios de Arquitectura de ERP TraceOPX

## Propósito

Estas reglas guían las decisiones funcionales, visuales y técnicas del ERP. No sustituyen el análisis del negocio; sirven para evitar que el producto pierda coherencia mientras crece.

1. **El cliente es el eje comercial del ERP.** Cotizaciones, servicios, coordinaciones, órdenes, facturación, cobros y evidencias deben conservar su relación con el cliente.
2. **Todo proceso relevante genera trazabilidad.** La auditoría técnica indica quién modificó datos; la bitácora funcional explica qué ocurrió en el negocio.
3. **Cada pantalla tiene un objetivo principal.** La interfaz debe ayudar al usuario a completar una tarea concreta sin ruido innecesario.
4. **El usuario conserva siempre el contexto.** Códigos, estados, cliente, responsable, próxima acción y etapa del proceso deben ser fáciles de identificar.
5. **Los datos se capturan una vez y se reutilizan.** Contactos, ubicaciones, servicios y condiciones comerciales no deben duplicarse entre módulos.
6. **Cada PR entrega un incremento funcional y verificable.** Debe incluir instalación, validación, despliegue y rollback cuando corresponda.
7. **Los componentes visuales deben ser reutilizables.** El patrón Workspace debe mantener consistencia sin crear abstracciones innecesarias.
8. **UX, negocio y arquitectura tienen la misma prioridad.** Una solución técnicamente correcta no se considera terminada si resulta confusa o lenta para el usuario.
9. **La arquitectura se diseña de forma incremental.** Se evita construir capacidades futuras sin una necesidad actual, pero se preservan extensiones razonables.
10. **El ERP debe facilitar decisiones y próximos pasos.** No solo registra datos; debe mostrar alertas, seguimiento, relaciones y acciones útiles.

## Regla de alcance

La creación de componentes o infraestructura dentro de una PR debe apoyar directamente el incremento funcional en curso. No se ampliará el alcance únicamente para construir un framework genérico.
