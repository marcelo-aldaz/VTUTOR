# Auditoría de promoción — v10.6.5 STABLE / 2026082406

## Resultado

**METADATA_ONLY_AUDIT_PASS**

FINAL QA6 `2026082405` fue congelada después de completar la aceptación LIVE. La promoción a STABLE `2026082406` no altera lógica funcional.

## Validación estática previa al empaquetado

- PHP: **71/71**, 0 errores de sintaxis.
- AMD JavaScript: **4/4**, 0 errores de sintaxis.
- XMLDB: válido.
- Idiomas: **464 ES / 464 EN**, paridad exacta, 0 duplicados.
- Metadata Moodle: `block_ai_tutor|2026082406|v10.6.5 STABLE: aislamiento triple y auditoría READ-ONLY|200`.
- `.zenodo.json`: JSON válido.

## Radio de cambio QA6 → STABLE

Se compararon los SHA-256 de ambos árboles. Resultado: **0 cambios funcionales inesperados**.

Archivos críticos verificados byte-a-byte contra QA6:

- `classes/ai_service.php`
- `classes/rag/hybrid_retriever.php`
- `classes/rag/index_integrity_auditor.php`
- `classes/rag/deepseek_semantic_vectorizer.php`
- `classes/document/native_pdf_extractor.php`
- `classes/document/document_processor.php`
- `classes/route_engine.php`
- `classes/security_filter.php`
- `classes/privacy/provider.php`
- `classes/local/query_intent.php`
- `classes/local/course_context_selector.php`
- `chat.php`, `routes.php`, `manage.php`, `diagnostico.php`, `settings.php`, `admin.php`, `reindex.php`
- `db/install.xml`, `db/access.php`, `db/tasks.php`, `db/services.php`

Solo se modificaron identidad/metadatos, documentación, pruebas de identidad, `db/upgrade.php` con savepoint-only y el manifiesto.

## Evidencia LIVE heredada de FINAL QA6

- Saludo puro: aprobado.
- Consulta pedagógica + contexto Moodle: aprobado.
- RAG positivo/multipágina: aprobado.
- Aislamiento negativo entre cursos: aprobado.
- Índice institucional: 1 documento / 1 lote / 18 chunks / 18 vectores / 0 incompatibles.
- Legacy aislado: 45 filas INFO.
- Triple aislamiento: 0 filas cruzadas/lotes inconsistentes.
- READ-ONLY: APROBADO y sin mutación tras F5.
- HITL: `route_log` 0→1; deduplicación 1→1; `suggested→validated`; estudiante sin acceso de administración.

## Upgrade STABLE

El bloque `2026082406` de `db/upgrade.php` ejecuta únicamente `upgrade_block_savepoint`. No reindexa, no modifica documentos, batches, chunks, vectores, credenciales, activaciones de curso ni rutas HITL.
