# Configuration guide

## Core settings

- AI provider
- API key, except for a deliberately local provider that does not require one
- Model
- Base URL or endpoint
- Guidance-only mode
- Temperature
- Maximum output tokens

The administrator must verify current provider documentation. Example model
names in the source snapshot are historical defaults and are not a guarantee
of current availability.

## Stable-study setting

Advanced RAG is outside the evaluated v10.2.1 Vstable experience. Set:

```text
block_ai_tutor/rag_enabled = 0
```

Do not enable automatic indexing or reindexing when reproducing the studied
stable configuration.

## Credential and data protection

- Never commit API keys to GitHub.
- Restrict plugin configuration to authorized administrators.
- Use the minimum data required for a pedagogical interaction.
- Inform users when content is sent to an external provider.
- Review provider retention, processing location, and contractual terms.
- Define retention and deletion rules for locally stored chat history.

## Change control

Test every provider, endpoint, model, and prompt change in a non-critical course.
Record Moodle, PHP, database, theme, browser, provider, model, and test date.
