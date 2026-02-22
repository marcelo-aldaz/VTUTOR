# Guía de instalación (Moodle)

## Recomendación para producción
Utilice **Vstable = v10.2.1**.

## Instalación desde ZIP
1. Ir a **Administración del sitio → Plugins → Instalar plugins**
2. Subir el ZIP del plugin
3. Completar instalación/actualización
4. Ir a **Notificaciones** si Moodle lo solicita
5. **Purgar cachés**
6. Añadir el bloque en un curso

## Checklist post-instalación
- Aparece en plugins
- Aparece en “Añadir un bloque”
- VTutor abre y responde

## Si Moodle se desestabiliza
1. Renombrar `/blocks/ai_tutor` → `/blocks/ai_tutor_OFF`
2. Restaurar `v10.2.1`
3. Purgar cachés
