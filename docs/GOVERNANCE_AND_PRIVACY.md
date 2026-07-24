# AI governance and privacy

## Human-in-the-Loop principle

VTutor is an assistive educational tool. It does not replace the teacher,
administrator, or other authorized institutional decision-maker.

- AI output is guidance, not an automatic final academic decision.
- Teachers remain responsible for instructional and assessment decisions.
- Administrators remain responsible for configuration, provider selection,
  security, data protection, and deployment approval.
- Users should be informed that generated responses can be incomplete or
  incorrect.
- High-impact decisions must not be made solely from model output.

## Data processed by the plugin

The preserved code defines storage for course-scoped and user-scoped
conversation history, including:

- Moodle course identifier;
- Moodle user identifier;
- user message;
- model response;
- configured provider and model; and
- creation time.

Depending on configuration, user messages, recent conversation history, and
course context can be sent to an external AI provider. Administrators must
review the selected provider's terms, retention practices, processing location,
and contractual safeguards before enabling the plugin.

## Deployment requirements

Before institutional use:

1. Establish a lawful basis and an institutional purpose for processing.
2. Provide clear notice to users.
3. Apply data minimization; do not enter unnecessary personal or sensitive data.
4. Restrict configuration to authorized administrators.
5. Store API credentials only in Moodle configuration, never in Git.
6. Define retention and deletion rules for conversation history.
7. Verify Moodle privacy export and deletion behavior in the target environment.
8. Establish incident reporting and service-disable procedures.
9. Validate the provider and model in a non-critical course.
10. Keep experimental RAG disabled for the studied Vstable configuration.

## Educational safeguard

The guidance-oriented mode is a technical support for pedagogical mediation; it
does not itself guarantee academic integrity or appropriate feedback. Teachers
must define permitted uses for assignments, examinations, and assessed work.

## Research boundary

This release does not contain evidence of a study involving student
participants. Any future user study requires the applicable ethics,
consent/assent, privacy, and institutional-review procedures.
