# Security policy

## Preserved release

This repository preserves VTutor `10.2.1`, project label `v10.2.1 Vstable`,
Moodle build `2026022203`.

`MATURITY_STABLE` is the maturity value declared by the artifact. It is not a
claim of comprehensive security certification.

## Reporting a vulnerability

Do not disclose an unpatched vulnerability in a public issue. Contact the
repository owner through a private channel and include:

- Moodle, PHP, database, web-server, and plugin versions;
- affected configuration;
- reproducible steps;
- expected and observed behavior;
- potential impact; and
- minimal redacted evidence.

Do not send API keys, credentials, student data, or unredacted production logs.

## Security boundaries

- AI-provider credentials must not be committed to the repository.
- Provider endpoints, models, and data-processing terms can change after this
  artifact's freeze date and must be reviewed at deployment time.
- Conversation history contains user-linked educational data.
- External providers can receive message, history, and course context depending
  on configuration.
- Advanced RAG was outside the evaluated stable study.
- The release does not claim a complete penetration test, dependency audit,
  load test, or independent security review.

## Deployment recommendations

1. Back up before installation or upgrade.
2. Test in a non-critical environment.
3. Restrict configuration to authorized administrators.
4. Set `block_ai_tutor/rag_enabled = 0` for the studied Vstable configuration.
5. Apply data-minimization, retention, access-control, and incident-response
   policies.
6. Verify privacy export and deletion behavior in the target Moodle instance.
7. Record the exact deployed checksum and configuration.
