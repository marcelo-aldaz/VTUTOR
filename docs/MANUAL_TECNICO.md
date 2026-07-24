# Technical manual

## Identification

- Moodle component: `block_ai_tutor`
- Release: `10.2.1`
- Project label: `v10.2.1 Vstable`
- Moodle build: `2026022203`
- Freeze date: 22 February 2026
- Requirement: Moodle 4.5 or later

## Purpose

VTutor provides AI-supported conversational guidance inside Moodle courses.
The preserved line is an engineering artifact for technical stabilization, not
evidence of pedagogical effectiveness.

## Main components

- `block_ai_tutor.php`: Moodle block integration.
- `settings.php` and `admin.php`: administrative configuration.
- `classes/ai_service.php`: provider calls, prompting, and history persistence.
- `classes/external/`: external functions used by the interface.
- `classes/privacy/provider.php`: Moodle privacy-provider implementation.
- `db/`: capabilities, services, tasks, and database schema.
- `amd/` and `templates/`: conversational interface.
- `lang/`: English and Spanish language resources.

## Data model

The plugin defines conversation-history storage with course ID, user ID, user
message, model response, provider, model, and creation time. The snapshot also
defines experimental retrieval and webhook-log tables. Their presence does not
mean advanced RAG was evaluated in the Vstable study.

## Stable-study boundary

The evaluated experience concerns the conversational line. Set
`block_ai_tutor/rag_enabled = 0` to reproduce that configuration.

Later VTutor Pro, VTutor Pro VZona, and post-`2026022203` builds require
separate documentation and publication records.
