# Informe de auditoría integral — FINAL QA6 / 2026082405

## Hallazgo LIVE

El documento `Banco_Preguntas_Base_Estructurada.pdf` figuró como `18 chunks / 18 vectores`, mientras la auditoría READ-ONLY informó `63 fragmentos activos / 18 vectores` y ERROR de paridad.

## Causa raíz

`index_integrity_auditor` contaba `block_ai_tutor_embed` por `courseid + active=1` sin limitarse a la frontera institucional. En la misma tabla permanecen filas históricas legacy (`documentid IS NULL`) preservadas deliberadamente. El runtime `hybrid_retriever` nunca las consume porque exige JOIN con documento y batch institucionales activos. Por ello el auditor mezclaba dos universos y generaba un falso ERROR.

## Corrección

- La paridad usa exactamente el row-set institucional recuperable por runtime.
- El legado activo se reporta aparte (`activelegacy`) y no participa en paridad.
- El control de filas cruzadas usa LEFT JOIN para detectar referencias institucionales rotas, no para ocultarlas.
- Los contadores operativos de vectores/léxico reutilizan directamente el resultado del auditor institucional; toda la página diagnóstica tiene una única fuente de verdad.
- Ninguna fila se reescribe, activa, desactiva o elimina.

## Regresión específica

Arnés offline: 18 filas institucionales `ready` + 45 filas legacy activas => `activechunks=18`, `semanticvectors=18`, `activelegacy=45`, `parity=true`, `approved=true`. Al inyectar una fila institucional cruzada => `crosscourserows=1`, `approved=false`.

## Alcance de cambio

Cambio funcional limitado a `classes/rag/index_integrity_auditor.php` y presentación diagnóstica. RAG/retrieval, PDF, AI service, HITL, privacidad, XMLDB, seguridad y tareas deben permanecer byte-a-byte iguales a QA5 y se verifican antes de empaquetar.

## Estado

`MATURITY_RC` durante QA6. Posteriormente READ-ONLY LIVE y HITL LIVE fueron aprobados; la build quedó autorizada para promoción metadata-only a STABLE.

## Auditoría del ZIP de ensayo post-hardening

Después de unificar la página diagnóstica con el auditor como fuente única de contadores institucionales, se descartó el primer empaquetado y se repitió el proceso. El ZIP de ensayo post-hardening fue reextraído y pasó manifiesto 96/96, 71 PHP/0 errores, 4 JS/0 errores, XMLDB, 464/464 idiomas y todos los arneses de regresión. El ZIP definitivo se reconstruye a partir de este árbol y se audita nuevamente sin modificaciones posteriores.

## Cierre LIVE

READ-ONLY mostró 18 fragmentos institucionales / 18 vectores, 45 legacy aislados, paridad exacta, 0 filas cruzadas y resultado APROBADO; la segunda lectura tras F5 fue idéntica. HITL mostró `route_log` 0→1, deduplicación 1→1, transición `suggested→validated` y ausencia de acceso administrativo para el estudiante.
