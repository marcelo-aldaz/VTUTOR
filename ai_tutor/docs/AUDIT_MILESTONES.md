# Hitos de auditoría — FINAL QA6 / build 2026082405

Cada hito tiene condición de avance verificable. Un fallo impide promoción a STABLE.

| Hito | Riesgo auditado | Criterio de salida | Estado offline |
|---|---|---|---|
| M1 | Saludo bloqueado por RAG | `hola` sale antes de RAG/proveedor | PASS |
| M2 | Intención conversación/aprendizaje/documento | rutas deterministas sin strict global | PASS |
| M3 | Contexto Moodle reutilizado | solo recursos `uservisible`, selector local acotado | PASS |
| M4 | Retrieval híbrido QA2 | score léxico nunca disminuye por semántica | PASS |
| M5 | PDF/proveniencia | Form XObject, Differences, ActualText, paginación y huérfanos | PASS |
| M6 | Continuidad | sin duplicación, inicio huérfano ni >1 continuación | PASS |
| M7 | Aislamiento runtime | retriever exige courseid en embed/doc/batch activos | PASS |
| M8 | Seguridad | capacidades, POST/sesskey, endpoint oficial, guards | PASS |
| M9 | Privacidad/retención | Privacy API + tareas solo de limpieza | PASS |
| M10 | Esquema/upgrade | 7 tablas; upgrade QA6 savepoint-only | PASS |
| M11 | Eficiencia | saludo/identidad 0 proveedor; rerank OFF; sin autoindex | PASS |
| M12 | LIVE RAG positivo | documento y fuentes recuperados | PASS LIVE previo |
| M13 | LIVE aislamiento negativo | PDF de otro curso no aparece | PASS LIVE previo |
| M14 | LIVE índice semántico | documento 18 chunks / 18 vectores | PASS LIVE previo |
| M15 | Auditor mezclaba legado | 18 institucionales + legado separado; paridad exacta | PASS LIVE |
| M16 | Auditor no permisivo | fila institucional cruzada/rota => ERROR y `approved=false` | PASS offline |
| M17 | READ-ONLY | código auditor sin escrituras; refresh no muta estado | PASS LIVE |
| M18 | HITL | capacidad/aislamiento estructural + ruta real | PASS LIVE |
| M19 | Paquete | lint, XML, idiomas, manifiesto, ZIP reextraído | PASS ZIP definitivo 96/96 |

## Promoción STABLE

Todos los hitos LIVE pendientes fueron cerrados. FINAL QA6 `2026082405` fue promovida metadata-only a v10.6.5 STABLE `2026082406`.
