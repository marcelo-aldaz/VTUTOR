# Solución de problemas

## Moodle se desestabiliza tras actualizar
1. Renombre `/blocks/ai_tutor` a `/blocks/ai_tutor_OFF`
2. Ingrese a Moodle
3. Restaure `v10.2.1`
4. Purgue cachés

## `sectionerror` en configuración
- Verifique que usa la versión estable
- Purgue cachés
- Guarde archivo/línea exacta del error

## El bloque aparece pero el chat no responde
- Revise proveedor, endpoint, API key y modelo
- Revise F12 (consola)
