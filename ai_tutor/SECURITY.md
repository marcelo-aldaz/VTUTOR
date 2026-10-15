# Seguridad — VTutor Pro VZona v10.6.5 STABLE

v10.6.5 STABLE mantiene el contrato de seguridad consolidado de la rama 10.6.5:

- DeepSeek es el único proveedor LLM soportado en runtime; el endpoint de chat es el endpoint oficial fijado en código.
- Las API keys no se precargan en formularios y una contraseña vacía conserva el secreto configurado.
- Mutaciones administrativas requieren permisos Moodle; acciones web de reindexación/HITL/configuración usan POST + sesskey cuando corresponde.
- El Mini-RAG exige coincidencia de curso en chunk, documento y batch activos.
- El auditor de índice es READ-ONLY.
- Identificadores normativos con guiones se preservan; correos, teléfonos e identificadores personales aislados se enmascaran.
- El contenido recuperado de documentos y el contenido visible del curso se tratan como referencia no confiable: no pueden cambiar reglas del sistema, permisos ni solicitar secretos.
- El fallback de contexto Moodle solo lee actividades `uservisible` para el usuario actual.

## Frontera READ-ONLY institucional (STABLE)

El auditor de integridad no escribe en base de datos. La paridad se calcula solo sobre filas institucionales que el runtime Mini-RAG podría recuperar: embed/documento/lote del mismo curso, activos y coherentes. Las filas legacy `documentid IS NULL` se conservan y se reportan separadamente; no pueden elevar ni degradar la aprobación del índice institucional. Referencias institucionales rotas o cruzadas continúan siendo ERROR.
