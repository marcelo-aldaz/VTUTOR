# Matriz de aceptación LIVE — v10.6.5 STABLE / 2026082406

Esta versión hereda sin cambios funcionales la build FINAL QA6 `2026082405`, aceptada LIVE antes de la promoción metadata-only.

| Control | Evidencia LIVE | Estado |
|---|---|---|
| Identidad y enrutamiento | saludo puro responde sin RAG estricto; consulta pedagógica usa contexto/RAG | APROBADO |
| RAG positivo | `Banco_Preguntas_Base_Estructurada.pdf` recuperado con fuentes/páginas | APROBADO |
| Aislamiento negativo | curso Filosofía no recuperó el PDF de Lengua y Literatura | APROBADO |
| Índice institucional | 1 documento, 1 lote, 18 chunks, 18 vectores, 0 incompatibles | APROBADO |
| Legacy aislado | 45 fragmentos heredados reportados como INFO | APROBADO |
| Triple aislamiento | 0 filas cruzadas o lotes inconsistentes | APROBADO |
| READ-ONLY integridad | paridad documento/lote/chunks/vectores exacta | APROBADO |
| READ-ONLY no mutación | segunda lectura tras F5 idéntica | APROBADO |
| HITL generación | `route_log` 0→1 con ruta `understanding` | APROBADO |
| HITL deduplicación | repetición inmediata mantuvo `route_log=1` | APROBADO |
| HITL validación | `suggested→validated` sobre el mismo registro | APROBADO |
| HITL permisos negativos | estudiante sin acceso a administración/validación/modificación | APROBADO |

La promoción STABLE no requiere reindexación y no modifica datos funcionales.
