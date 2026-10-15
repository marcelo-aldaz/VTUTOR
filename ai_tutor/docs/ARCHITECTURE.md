# Arquitectura de cierre 10.6.5

```text
Usuario Moodle
  -> capacidad block/ai_tutor:use
  -> filtro de seguridad
  -> contexto del curso y perfil efectivo
  -> Mini-RAG (si el curso lo habilita)
       -> documento activo
       -> lote atómico activo
       -> chunks page-local del mismo courseid
       -> consulta vectorial local + ranking léxico/estructural
       -> fuentes verificables
  -> DeepSeek (respuesta final)
  -> filtro de salida
  -> historial según política de retención
```

## Pipeline documental

```text
Archivo de curso
  -> validación tipo/tamaño
  -> extracción PDF/TXT/Markdown
  -> Markdown page-aware
  -> chunks page-local
  -> reutilización por contenthash
  -> firma semántica DeepSeek por lotes
  -> lote nuevo inactivo
  -> transacción: desactivar lote anterior + activar lote completo
```

Si falla la nueva indexación antes de la activación, el índice anterior permanece disponible.

## Human-in-the-Loop

La máquina calcula/sugiere una ruta; Moodle entrega la información a una persona autorizada; la persona valida, ajusta, marca seguimiento o rechaza. La decisión permanece humana y vinculada al curso.
