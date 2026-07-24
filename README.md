# VTutor v10.2.1 Vstable for Moodle (`block_ai_tutor`)

VTutor is a Moodle block that provides AI-supported conversational tutoring
inside a course. This repository preserves the software artifact studied in the
technical stabilization experience completed on 22 February 2026.

## Release identity

| Field | Value |
|---|---|
| Moodle component | `block_ai_tutor` |
| Software release | `10.2.1` |
| Project label | `v10.2.1 Vstable` |
| Moodle build | `2026022203` |
| Frozen on | 22 February 2026 |
| Required Moodle version | 4.5 or later (`2024100700`) |
| Declared maturity | `MATURITY_STABLE` |
| License | GNU GPL v3.0 or later |

The proposed archival tag is `v10.2.1-Vstable`. The values above are taken
from `version.php`; the `Vstable` suffix is the project label used to identify
the frozen research artifact.

## Scope of the preserved experience

The documented experience concerns the iterative development and functional
stabilization of the conversational tutoring block. It covers installation,
course-level availability, provider configuration, conversational interaction,
history handling, interface behavior, and operational recovery.

The repository does **not** provide evidence of:

- pedagogical effectiveness or improved learning outcomes;
- a controlled study with students;
- an independent replication of the historically documented acceptance tests;
- full security, performance, load, or accessibility certification; or
- a production-validated advanced RAG system.

The historically reported `8/8` acceptance status is preserved as documentary
evidence from the development record. It must not be interpreted as a new
independent validation performed from this repository.

## Important boundary concerning RAG

The source snapshot contains experimental retrieval-related structures and code
paths inherited from development. Advanced RAG was outside the evaluated
stable study. For reproduction of the studied stable configuration,
administrators must keep `block_ai_tutor/rag_enabled` disabled. No claim about
RAG effectiveness is made for this release.

Later developments named **VTutor Pro**, **VTutor Pro VZona**, or numbered
after build `2026022203` are outside this release and outside the associated
stabilization study.

## Main capabilities in the studied line

- Moodle course block with a floating conversational interface.
- Configurable AI provider, endpoint, model, and generation parameters.
- Course- and user-scoped conversation history.
- A guidance-oriented tutoring mode.
- Moodle capability checks and privacy-provider implementation.
- English and Spanish language strings.

## Installation

Use the installable ZIP attached to the GitHub release, not GitHub's automatic
“Source code” archives.

1. Back up the Moodle site and database.
2. In Moodle, go to **Site administration → Plugins → Install plugins**.
3. Upload the release ZIP and complete Moodle's validation.
4. Finish the database upgrade if Moodle requests it.
5. Purge caches.
6. Configure the AI provider without committing credentials to source control.
7. Explicitly disable `block_ai_tutor/rag_enabled` for the studied Vstable
   configuration.
8. Add the VTutor block to a non-critical test course and run the post-install
   checks in [`docs/INSTALL.md`](docs/INSTALL.md).

## Evidence and limitations

See:

- [`docs/AUDITORIA_PUBLICACION_GITHUB_ZENODO_ES.md`](docs/AUDITORIA_PUBLICACION_GITHUB_ZENODO_ES.md)
- [`docs/VALIDATION_AND_LIMITATIONS.md`](docs/VALIDATION_AND_LIMITATIONS.md)
- [`docs/GOVERNANCE_AND_PRIVACY.md`](docs/GOVERNANCE_AND_PRIVACY.md)
- [`docs/releases/RELEASE_NOTES_v10.2.1_VSTABLE.md`](docs/releases/RELEASE_NOTES_v10.2.1_VSTABLE.md)

## Citation and archival metadata

- Human- and machine-readable citation: [`CITATION.cff`](CITATION.cff)
- Zenodo release metadata: [`.zenodo.json`](.zenodo.json)
- GitHub release text: [`GITHUB_RELEASE_BODY_v10.2.1-Vstable.md`](GITHUB_RELEASE_BODY_v10.2.1-Vstable.md)
- Publication checklist: [`docs/ZENODO_PUBLICATION_GUIDE_ES.md`](docs/ZENODO_PUBLICATION_GUIDE_ES.md)

Do not add a DOI to the repository until Zenodo has issued it. After deposit,
update `CITATION.cff` and the README with the version DOI, and cite the
concept DOI when referring to the software family across versions.

## Author

Nelson Marcelo Aldaz Herrera

ORCID: [0000-0002-4004-7378](https://orcid.org/0000-0002-4004-7378)

## License

Copyright © 2026 Nelson Marcelo Aldaz Herrera.

VTutor is licensed under the GNU General Public License v3.0 or later. See
[`LICENSE`](LICENSE).
