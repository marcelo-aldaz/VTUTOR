# Changelog

All notable changes preserved in this repository are documented here.

## [10.2.1] — 2026-02-22

Project label: **v10.2.1 Vstable**

Moodle build: `2026022203`

Status: frozen technical-stabilization artifact.

### Documented stabilization scope

- Preserved the floating conversational interface for Moodle courses.
- Consolidated AI-provider configuration and conversational operation.
- Preserved user- and course-scoped conversation history.
- Consolidated the stable deployment and recovery procedure.
- Documented the separation between the conversational stable line and
  experimental retrieval work.

### Evidence qualification

- The historically documented acceptance matrix reports eight accepted
  functional criteria.
- The original complete logs, per-test screenshots, exact environment capture,
  and a signed second-evaluator protocol were not recovered with this release.
- The `8/8` result is therefore historical documentary evidence, not a new
  independent validation.
- No claim of pedagogical effectiveness is made.

### RAG boundary

The snapshot retains experimental retrieval-related structures and code paths.
Advanced RAG was excluded from the evaluated stable study. Reproduction of the
studied Vstable configuration requires `block_ai_tutor/rag_enabled = 0`.

## Earlier development history

Earlier `10.2.x` development informed this release, but the repository does not
currently contain sufficiently complete dated evidence to reconstruct a
release-by-release changelog without speculation.

## Later developments

VTutor Pro, VTutor Pro VZona, and builds after `2026022203` belong to later
development lines and are not part of this release.
