# Release notes — VTutor v10.2.1 Vstable

Release date: 22 February 2026  
Moodle build: `2026022203`  
Component: `block_ai_tutor`  
Target: Moodle 4.5 or later  
License: GNU GPL v3.0 or later

## Purpose

This release preserves the artifact used to document the technical
stabilization of VTutor as a conversational tutoring block for Moodle.

## Included in the preserved line

- Course-level Moodle block.
- Floating conversational interface.
- Configurable AI provider, endpoint, and model.
- Guidance-oriented tutor setting.
- User- and course-scoped conversation history.
- Privacy-provider implementation.
- English and Spanish language resources.
- Operational installation and recovery guidance.

## Evidence statement

The development record reports eight accepted functional criteria. The release
notes preserve that historical result but do not claim a fresh independent
execution of the test matrix. See
[`../VALIDATION_AND_LIMITATIONS.md`](../VALIDATION_AND_LIMITATIONS.md).

## RAG boundary

Advanced RAG is not part of the evaluated stable study. Retrieval-related
structures remain in the historical source snapshot, but they are experimental
for the purposes of this release. Set `block_ai_tutor/rag_enabled = 0` when
reproducing the studied configuration.

## Excluded

- Later VTutor Pro or VTutor Pro VZona functions.
- Builds after `2026022203`.
- Claims of pedagogical effectiveness.
- Independent replication.
- Full security, accessibility, performance, or load certification.

## Integrity

Historical installable package:

`moodle-block-ai_tutor_github_v10_2_1_Vstable_docs.zip`

SHA-256:

`34d0d4f59a9cc39a8f9d5bb43805d30269da491f8f2fe64e245a2d004646b924`
