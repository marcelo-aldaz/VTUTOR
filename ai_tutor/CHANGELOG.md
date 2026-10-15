# Changelog

## v10.6.5 STABLE — build 2026082406

- Promoción metadata-only de FINAL QA6 / `2026082405` tras aceptación LIVE completa del alcance declarado.
- `MATURITY_STABLE`; identidad pública `10.6.5-stable`.
- Sin cambios funcionales en RAG, PDF, chat, seguridad, privacidad, HITL, XMLDB o tareas.
- READ-ONLY LIVE aprobado sin mutación; índice institucional 18/18 y 45 filas legacy aisladas como INFO.
- HITL LIVE aprobado: persistencia, deduplicación, validación humana y permisos negativos.
- Upgrade savepoint-only; no reindexa.

## v10.6.5 FINAL QA6 — build 2026082405

- Corrige el falso ERROR de paridad READ-ONLY cuando coexistían chunks institucionales activos y filas legacy históricas `documentid IS NULL`.
- El auditor cuenta como “fragmentos institucionales activos” únicamente el mismo row-set que puede consumir el retriever Mini-RAG: curso coincidente en embed/doc/batch, documento `indexed`, lote `active`, fila `active` y estado `ready|lexical`.
- Las filas legacy activas se reportan por separado como históricas/aisladas y no participan en la paridad documento/chunks/vectores.
- Fortalece el control de filas institucionales rotas usando LEFT JOIN para que referencias documentales o de lote inválidas no desaparezcan del auditor.
- Ajusta diagnóstico de vectores/léxico para excluir legado del estado operativo institucional.
- Añade regresión de aislamiento: un índice limpio sigue aprobado con filas legacy preservadas; una fila institucional cruzada sigue bloqueando la aprobación.
- Upgrade savepoint-only; cero mutaciones de índice y cero reindexación automática.

## v10.6.5 FINAL QA5 — build 2026082404

- Corrige la regresión LIVE donde un saludo puro era bloqueado por RAG estricto.
- Introduce clasificación determinista: conversacional / aprendizaje / documental.
- El fallo cerrado de RAG estricto se aplica solo a peticiones documentales explícitas sin evidencia.
- Saludos, agradecimientos y confirmaciones puras usan respuesta local: 0 RAG / 0 DeepSeek.
- Restaura fallback acotado al contenido Moodle visible para preguntas pedagógicas sin evidencia documental.
- Selecciona localmente texto relevante de actividades visibles sin crear un segundo índice ni efectuar reranking remoto.
- Añade guard de seguridad: contenido Moodle es referencia, nunca instrucción del sistema.
- Mantiene sin regresión: piso léxico QA2, integridad inicial QA3, continuidad/costo QA4, extracción PDF, aislamiento triple, HITL, Privacy API y auditor READ-ONLY.
- Upgrade 2026082404: savepoint únicamente; sin reindexación ni mutación de datos RAG.

## v10.6.5 FINAL QA4 — build 2026082403

- Sustituye el bucle de hasta dos continuaciones largas por una única continuación breve y conclusiva.
- Convierte el máximo de palabras por perfil en límite operativo de cada respuesta nueva cuando la política está habilitada.
- Elimina del usuario el banner técnico de agotamiento tras continuaciones; ante un segundo `finish_reason=length`, conserva solo texto completo ya recibido y no realiza más llamadas.
- Mantiene `max_tokens` configurable (512–4096) y limita la continuación a 600 tokens como máximo.
- No modifica documentos, lotes, chunks, vectores, activación Mini-RAG ni configuración por curso.
- Upgrade 2026082403: savepoint únicamente.

# Changelog

## v10.6.5 FINAL QA3 — build 2026082402

- Corrige una pérdida ocasional del inicio de respuestas largas observada en LIVE.
- Añade una invariante determinista de integridad del comienzo: una respuesta nueva no puede iniciar con un fragmento huérfano (`...`) salvo en citas literales o continuaciones explícitas.
- La corrección no realiza llamadas LLM adicionales ni modifica RAG, documentos, chunks, vectores o lotes.
- Alinea `version.php`, identidad runtime y pruebas de metadata, eliminando una inconsistencia residual de FINAL QA2.
- Impide que el fallback PDF global reincorpore streams de páginas huérfanas cuando existe un árbol de páginas válido.
- Sanea la localización `lang/en` manteniendo paridad exacta de 460 claves con español.
- Upgrade 2026082402: savepoint únicamente; no reindexa datos.

## v10.6.5 FINAL QA2 — build 2026082401

Corrección de regresión detectada en aceptación LIVE de FINAL QA.

- Mantiene la consulta vectorial local y el reranking remoto OFF por defecto.
- Corrige el blend híbrido: una coincidencia léxica válida ya no puede ser reducida por un vector semántico débil.
- Conserva `rag_semantic_weight` como mecanismo de mejora de ranking, no como penalización de evidencia textual.
- Añade regresión automatizada para el caso 0.15 léxico / 0.00 semántico / peso 0.75, que antes caía a 0.0375 bajo el umbral 0.12.
- Upgrade 2026082401: savepoint únicamente; no reindexa ni modifica documentos, lotes, chunks o vectores.

**Estado:** `MATURITY_RC` hasta aceptación LIVE de esta misma build.

## Historial FINAL QA — build 2026081707

FINAL QA introdujo la optimización de consulta vectorial local, pero la validación LIVE detectó una regresión de puntuación híbrida. Se conserva como antecedente, no como candidata de cierre.

## v10.6.5 FINAL QA — build 2026081707

Cierre integral de la rama 10.6.5 tras auditoría iterativa por hitos.

- Reduce el contrato operativo a DeepSeek y elimina conectores no validados del runtime.
- Fija endpoint oficial y modelos permitidos; normaliza temperatura y límite de salida.
- Elimina reparaciones de esquema durante chat/navegación; las migraciones quedan en `upgrade.php`.
- Endurece mutaciones administrativas con POST, sesskey y capacidades propias del plugin.
- Corrige aislamiento HITL por curso, decisiones permitidas y visibilidad de rutas.
- Completa Privacy API para historial, proveedor/modelo/tokens y validación humana.
- Corrige filtrado de identificadores normativos y sanitización de credenciales.
- Corrige superficies XSS en launcher y valida URLs/colores administrativos.
- Elimina reindexación implícita al guardar configuración.
- Desactiva RAG heredado y reranking remoto por defecto.
- Convierte la consulta RAG normal en retrieval local; DeepSeek se reserva para respuesta final y firma semántica al indexar.
- Reutiliza firmas por `contenthash`; vectoriza por lotes de 4 y limita a un reintento compacto recuperable.
- Hace determinista la extracción PDF nativa; `pdftotext` queda como opt-in explícito.
- Corrige árbol/paginación PDF, recursos heredados, Form XObjects, fuentes `ToUnicode`, `/Encoding` + `/Differences`, `/ActualText`, strings literales y hexadecimales.
- Mantiene chunks page-local, encabezados estructurales como evidencia literal y recuperación dirigida por documento/página.
- Fortalece atomicidad documental: un fallo de reemplazo no destruye el índice activo anterior.
- Mantiene auditoría de integridad READ-ONLY sin operaciones de escritura.
- Retira tarea automática de procesamiento documental; solo quedan tareas de limpieza controladas.
- Limpia documentación, metadatos y licencia del paquete distribuible.
- Upgrade `2026081707`: normalización segura de configuración; no modifica documentos, Markdown, lotes, chunks ni vectores.

**Estado:** `MATURITY_RC` hasta una última aceptación LIVE. La promoción a STABLE será metadata-only si no aparecen defectos.

## Historial anterior

Los builds RC1–RC7 y HF previos permanecen trazables en el historial Git/GitHub. Se retiraron del ZIP instalable los informes intermedios para evitar documentación contradictoria en una release de cierre.
