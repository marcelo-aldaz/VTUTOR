# Validation status and limitations

## Artifact under study

- Component: `block_ai_tutor`
- Release: `10.2.1`
- Project label: `v10.2.1 Vstable`
- Moodle build: `2026022203`
- Freeze date: 22 February 2026
- Target platform: Moodle 4.5 or later

## Nature of the available evidence

The development record documents an eight-criterion functional acceptance
matrix and records all eight criteria as accepted. That record supports a
historical account of the stabilization process.

The present repository audit did not recover:

- the complete original raw logs for every criterion;
- a screenshot linked to every individual test;
- a complete machine-readable environment capture;
- automated regression results;
- a signed protocol from an independent second evaluator; or
- a new execution of the eight tests under controlled conditions.

Accordingly, `8/8` means **historically documented acceptance status**. It does
not mean that this repository update independently reproduced all tests.

## Claims supported by the record

- A Moodle plugin artifact was iteratively developed and frozen as build
  `2026022203`.
- The development record documents functional problems, corrective cycles,
  acceptance criteria, and a stable-line decision.
- The repository provides complementary traceability through code,
  `version.php`, documentation, and the installable historical ZIP.
- The process is documented and transferable as an engineering experience.

## Claims not supported by this record

- Causal improvement in student learning, retention, motivation, or achievement.
- Superiority over another tutoring system.
- Generalization across Moodle installations or institutional contexts.
- Complete security, privacy, accessibility, load, or performance certification.
- Independent replication of the historical acceptance matrix.
- Production validation of advanced RAG.

## RAG qualification

The code snapshot contains experimental retrieval-related tables, configuration
fields, and logic. Those elements are not evidence that advanced RAG was
evaluated as part of the Vstable study. The studied stable configuration must
be documented with `block_ai_tutor/rag_enabled = 0`.

Any later miniRAG or VTutor Pro VZona experiment requires its own version,
evidence set, release, and publication record.

## Recommended future verification

1. Preserve the installable ZIP and its SHA-256 checksum.
2. Capture Moodle, PHP, database, server, theme, and browser versions.
3. Repeat the acceptance criteria using dated test sheets.
4. Retain Moodle, PHP, web-server, and browser-console logs.
5. Add screenshots or recordings mapped to each criterion.
6. Use a second evaluator.
7. Add automated regression, security, accessibility, and performance checks.
8. Publish an immutable Git tag and the corresponding Zenodo version DOI.
