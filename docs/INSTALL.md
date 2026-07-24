# Installation guide

## Supported artifact

Use the installable ZIP attached to release `v10.2.1-Vstable`. The expected
Moodle component is `block_ai_tutor`, the installation directory is
`blocks/ai_tutor`, and the recorded build is `2026022203`.

Do not use GitHub's automatically generated source archives as Moodle plugin
packages.

## Before installation

1. Confirm Moodle 4.5 or later.
2. Back up the database and the existing `blocks/ai_tutor` directory.
3. Verify the release ZIP checksum.
4. Use a non-critical test environment or course first.
5. Confirm that no API key is stored in the package.

## Installation from ZIP

1. Go to **Site administration → Plugins → Install plugins**.
2. Upload the installable release ZIP.
3. Confirm that Moodle recognizes `block_ai_tutor`.
4. Complete the installation or upgrade.
5. Visit **Notifications** if requested.
6. Purge caches.
7. Configure the provider, endpoint, model, and API key.
8. Set `block_ai_tutor/rag_enabled = 0` for the studied Vstable configuration.
9. Add the block to a test course.

## Post-install checks

- Moodle reports release `10.2.1` and build `2026022203`.
- The plugin appears in the installed-plugins list.
- VTutor appears in **Add a block**.
- The block opens in a course.
- An authorized test user can send and receive a message.
- Conversation history is scoped to the current user and course.
- Clear-history behavior is verified.
- The selected provider and model are recorded in the test sheet.
- RAG remains disabled for the Vstable study configuration.

Passing these checks in a new environment is a deployment verification. It
does not independently reproduce the complete historical acceptance matrix.

## Recovery

If the site becomes unstable:

1. Put the site in maintenance mode when possible.
2. Rename `blocks/ai_tutor` to a specific backup name such as
   `ai_tutor_OFF_20260222`.
3. Restore the previously verified package and database state.
4. Complete Moodle's plugin checks.
5. Purge caches.
6. Record the exact error, environment, logs, and recovery actions.
