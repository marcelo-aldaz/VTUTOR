# VTutor v10.2.1 Vstable — build 2026022203

This release preserves the VTutor (`block_ai_tutor`) technical-stabilization
artifact frozen on 22 February 2026 for Moodle 4.5 or later.

## Identity

- Component: `block_ai_tutor`
- Release declared in `version.php`: `10.2.1`
- Project label and Git tag: `v10.2.1-Vstable`
- Moodle build: `2026022203`
- Maturity declared in code: `MATURITY_STABLE`
- License: GNU GPL v3.0 or later

## Scope

The release documents the stabilization of the conversational tutoring block:
installation, course availability, provider configuration, conversational
interaction, history handling, interface behavior, and operational recovery.

The historically reported `8/8` acceptance status is retained as documentary
evidence from the development record. It is not presented as an independent
revalidation performed for this release.

## Limitations

- No claim of improved learning outcomes or pedagogical effectiveness.
- No controlled user study.
- No complete original log set, per-test screenshot set, or signed
  second-evaluator protocol accompanies the release.
- No comprehensive security, performance, load, or accessibility certification.
- Advanced RAG is outside the evaluated stable study.

The source snapshot contains experimental retrieval-related structures and code
paths. To reproduce the studied Vstable configuration, explicitly set
`block_ai_tutor/rag_enabled = 0`.

## Installable artifact

Install the ZIP attached to this release. Do not install GitHub's automatically
generated “Source code” archives as Moodle plugin packages.

Expected historical package already present in the repository:

- File: `moodle-block-ai_tutor_github_v10_2_1_Vstable_docs.zip`
- SHA-256: `34d0d4f59a9cc39a8f9d5bb43805d30269da491f8f2fe64e245a2d004646b924`

Verify the checksum before installation.

## Scientific traceability

This software record corresponds only to VTutor v10.2.1 Vstable, build
`2026022203`. Later VTutor Pro, VTutor Pro VZona, and post-freeze builds are
separate artifacts.

The associated paper should be linked after it receives a persistent public
identifier. Until then, no paper DOI is asserted in the release metadata.
