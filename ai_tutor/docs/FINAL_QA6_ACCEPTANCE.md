# Matriz de aceptación LIVE — FINAL QA6 / 2026082405 — COMPLETADA

La misma build funcional FINAL QA6 fue aceptada LIVE antes de su promoción metadata-only a STABLE. No se reindexó por la promoción.

| Control | Resultado LIVE |
|---|---|
| Identidad QA6 | APROBADO |
| Saludo puro / enrutamiento | APROBADO |
| Consulta pedagógica y contexto Moodle | APROBADO |
| Consulta documental positiva | APROBADO |
| Aislamiento negativo entre cursos | APROBADO |
| Índice institucional | 1 documento / 1 lote / 18 chunks / 18 vectores |
| Legacy aislado | 45 filas activas, INFO, fuera de paridad Mini-RAG |
| Paridad documento/chunks/vectores | Exacta |
| Filas cruzadas/lotes inconsistentes | 0 |
| READ-ONLY integridad | APROBADO |
| READ-ONLY no mutación tras F5 | APROBADO |
| HITL generación | `route_log` 0→1 |
| HITL deduplicación | `route_log` 1→1 |
| HITL validación humana | `suggested→validated` |
| HITL permisos negativos | estudiante sin acceso de administración/validación |

Resultado: **APROBADO PARA PROMOCIÓN METADATA-ONLY A STABLE**.
