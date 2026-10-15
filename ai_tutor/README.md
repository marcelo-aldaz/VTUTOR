# VTutor Pro VZona v10.6.5 STABLE

**Build Moodle:** `2026082406`  
**Release:** `v10.6.5 STABLE: aislamiento triple y auditoría READ-ONLY`  
**Maturity:** `MATURITY_STABLE`  
**Componente:** `block_ai_tutor`  
**Baseline funcional congelado:** FINAL QA6 / `2026082405`

## Estado

Esta versión es la promoción **metadata-only** de FINAL QA6 después de completar la aceptación LIVE. No introduce cambios funcionales sobre chat, enrutamiento, contexto Moodle, Mini-RAG, extractor PDF, vectorización, seguridad, privacidad, HITL, XMLDB o tareas programadas.

La aceptación LIVE cerró, entre otros, los siguientes controles:

- saludo puro y enrutamiento de intención sin `strict_no_evidence` espurio;
- contexto Moodle visible como fallback pedagógico;
- recuperación documental positiva y multipágina;
- aislamiento negativo entre cursos y aislamiento triple `embed/doc/batch`;
- índice institucional DeepSeek con paridad **18 chunks / 18 vectores** y 0 incompatibles;
- filas legacy aisladas reportadas como INFO, sin contaminar la paridad Mini-RAG;
- auditoría READ-ONLY **APROBADA** y sin mutación después de F5;
- HITL LIVE: generación `0→1`, deduplicación `1→1`, validación `suggested→validated` y denegación de administración al rol estudiante.

## Alcance estable declarado

Moodle 4.5+ + MySQL + DeepSeek + Mini-RAG institucional + chat/historial + contexto Moodle visible + gestión documental + diagnóstico READ-ONLY + rutas pedagógicas HITL + privacidad/retención. Los proveedores secundarios no forman parte del contrato estable de esta rama.

## Instalación / actualización

Instale la carpeta como `blocks/ai_tutor`. El upgrade a `2026082406` es **savepoint-only**: no reindexa, no reconstruye documentos, no modifica chunks/vectores y no altera rutas HITL. No es necesario reindexar por la promoción a STABLE.

Consulte `docs/STABLE_ACCEPTANCE.md`, `docs/STABLE_RELEASE_AUDIT.md` y `docs/INSTALL.md` para la evidencia de cierre y el procedimiento de despliegue.
