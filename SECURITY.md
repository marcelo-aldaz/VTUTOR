# Política de Seguridad

## Versiones soportadas
Actualmente, la versión soportada para uso estable en producción es:

- `v10.2.1` (**Vstable**)

Las ramas experimentales (por ejemplo, funcionalidades RAG, pruebas de indexación o mejoras en desarrollo) **no se consideran versiones estables para producción**.

---

## Cómo reportar una vulnerabilidad

Si identifica una posible vulnerabilidad de seguridad en VTutor (`block_ai_tutor`), por favor **no la publique primero en un issue público**.

Al reportar una vulnerabilidad, incluya la siguiente información:

- **Versión de Moodle**
- **Versión de PHP**
- **Versión del plugin VTutor**
- **Descripción del problema**
- **Pasos para reproducir**
- **Impacto potencial**
- **Evidencia disponible** (capturas, logs o mensajes de error, si aplica)

---

## Alcance de esta política
Esta política aplica al plugin **VTutor (`block_ai_tutor`)** publicado en este repositorio y, de forma prioritaria, a su línea estable:

- `v10.2.1 (Vstable)`

Las funcionalidades en ramas experimentales pueden cambiar sin previo aviso y no están cubiertas como entorno de producción.

---

## Recomendación de uso seguro
Para entornos institucionales o sitios Moodle en producción, se recomienda utilizar únicamente la versión:

- **`v10.2.1 (Vstable)`**

y validar cualquier mejora futura primero en un entorno de pruebas antes de implementarla en producción.
