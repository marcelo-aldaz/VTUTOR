# Diagnóstico y resolución de problemas

## La IA responde pero no usa el documento

1. Confirme que el Mini-RAG institucional está habilitado globalmente y en el curso.
2. Revise **Diagnóstico → Auditoría READ-ONLY**.
3. Compruebe documento `indexed`, lote activo, chunks activos y paridad con vectores.
4. Si pregunta por un documento concreto, utilice su nombre exacto; el retriever restringirá el alcance al curso/documento indicado.
5. Reindexe solo si el documento fue modificado o si necesita adoptar mejoras de extracción de una build nueva.

## PDF

El extractor PHP nativo es el comportamiento predeterminado. `pdftotext` no se autodetecta: solo se usa si el administrador configura explícitamente un ejecutable válido. PDFs de imagen sin capa textual requieren una versión con texto/OCR externo; esta rama no declara OCR integrado.

## Respuesta truncada

VTutor solicita una continuación únicamente cuando la API informa truncamiento. El ensamblador evita reiniciar párrafos ya entregados y limita continuaciones para no crear bucles de consumo.

## Indexación semántica

Las firmas se generan en lotes y pueden reutilizarse por `contenthash`. Una salida semántica recuperablemente inválida admite un único reintento compacto por elemento; un índice parcial no se activa.

## No use reindexación como prueba de diagnóstico

Abrir o recargar la auditoría READ-ONLY no debe modificar índices. La reindexación es una acción separada y explícita.
