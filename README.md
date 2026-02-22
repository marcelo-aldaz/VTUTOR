# VTutor para Moodle (block_ai_tutor)

VTutor es un bloque para Moodle que integra un tutor conversacional con IA dentro de los cursos, con una experiencia de uso flotante (UX) optimizada para lectura y uso en escritorio, tablet y móvil.

## Versión estable actual
- **Vstable:** `v10.2.1`

## Estado del proyecto
- ✅ **Línea estable para producción:** `v10.2.1`
- 🧪 **Línea experimental (RAG):** en rama separada (no recomendada para producción)

## Características de la línea estable
- Bloque de tutor IA dentro de cursos Moodle
- Interfaz flotante (UX flotante)
- Integración con proveedores de IA (según configuración de despliegue)
- Base estable para uso institucional
- Panel RAG experimental desactivado en la línea estable

## Nota importante
Use la línea estable en entornos productivos. Las funciones RAG avanzadas se mantienen en ramas experimentales.

## Instalación rápida (ZIP)
1. Administración del sitio → Plugins → Instalar plugins
2. Subir ZIP
3. Completar actualización
4. Purgar cachés
5. Añadir el bloque en un curso

📌 Ver: [`docs/INSTALL.md`](docs/INSTALL.md)

## Ramas recomendadas
- `main` → estable
- `develop` → integración
- `feature/ux-v10.2.2` → UX
- `feature/rag-v11` → RAG experimental
