# Guía de actualización de GitHub y publicación en Zenodo

## Objeto exacto de la publicación

- Software: VTutor.
- Componente Moodle: `block_ai_tutor`.
- Versión: `10.2.1`.
- Etiqueta del proyecto: `v10.2.1 Vstable`.
- Build Moodle: `2026022203`.
- Fecha de congelamiento: 22 de febrero de 2026.
- Etiqueta Git propuesta: `v10.2.1-Vstable`.
- Licencia: GNU GPL v3.0 o posterior.

Esta publicación no corresponde a VTutor Pro, VTutor Pro VZona, v10.3.x ni a
ningún build posterior.

## Correcciones frente a la publicación inicial

1. Identificar versión, build y fecha exactos.
2. Eliminar referencias a ramas inexistentes.
3. No presentar el resultado histórico `8/8` como validación independiente.
4. No afirmar eficacia pedagógica.
5. Declarar que el RAG avanzado quedó fuera del estudio estable.
6. Advertir que el snapshot conserva rutas experimentales de recuperación y
   que la configuración estudiada exige `rag_enabled = 0`.
7. Incorporar `CITATION.cff` y `.zenodo.json`.
8. Diferenciar el ZIP instalable de los archivos “Source code” de GitHub.
9. Publicar el SHA-256 del ZIP.
10. No añadir un DOI inventado o reservado antes de que Zenodo lo emita.

## Secuencia de GitHub

1. Revisar y fusionar los cambios documentales.
2. Confirmar que `version.php` conserva:
   - `$plugin->version = 2026022203;`
   - `$plugin->release = '10.2.1';`
   - `$plugin->requires = 2024100700;`
   - `$plugin->maturity = MATURITY_STABLE;`
3. Verificar que no existen claves API, datos personales, logs ni archivos
   temporales.
4. Verificar el ZIP:

   ```text
   moodle-block-ai_tutor_github_v10_2_1_Vstable_docs.zip
   SHA-256 34d0d4f59a9cc39a8f9d5bb43805d30269da491f8f2fe64e245a2d004646b924
   ```

5. Crear la etiqueta anotada `v10.2.1-Vstable` sobre el commit documental
   aprobado.
6. Crear un release de GitHub con el mismo nombre.
7. Copiar el contenido de
   `GITHUB_RELEASE_BODY_v10.2.1-Vstable.md`.
8. Adjuntar el ZIP instalable como asset.
9. Marcarlo como release final, no como pre-release.

## Secuencia de Zenodo

1. Ingresar a Zenodo con la cuenta vinculada a GitHub.
2. Abrir **GitHub** desde el menú de perfil.
3. Sincronizar los repositorios.
4. Confirmar que `marcelo-aldaz/VTUTOR` está habilitado.
5. Crear el release en GitHub solo después de fusionar `.zenodo.json`.
6. Esperar la ingestión del release.
7. Abrir el registro generado y revisar:
   - título;
   - autor y ORCID;
   - afiliación;
   - versión;
   - fecha;
   - tipo `Software`;
   - licencia GPL-3.0-or-later;
   - descripción y palabras clave;
   - archivos;
   - relación con GitHub.
8. Confirmar que Zenodo asignó un DOI de versión.
9. Registrar también el DOI conceptual para citar el conjunto de versiones.
10. Actualizar `CITATION.cff` y README con el DOI real mediante una nueva
    corrección documental.

Zenodo utilizará `.zenodo.json` y no `CITATION.cff` para la ingestión desde
GitHub cuando ambos archivos estén presentes. Por ello, los dos deben ser
coherentes, pero `.zenodo.json` es el metadato decisivo para este flujo.

## Campos para control manual en Zenodo

| Campo | Valor |
|---|---|
| Tipo | Software |
| Título | VTutor v10.2.1 Vstable (block_ai_tutor): a Moodle block for AI-supported conversational tutoring |
| Autor | Aldaz Herrera, Nelson Marcelo |
| ORCID | 0000-0002-4004-7378 |
| Afiliación | Unidad Educativa PCEI Nuevo Amanecer |
| Versión | 10.2.1-Vstable |
| Idioma | English |
| Acceso | Open |
| Licencia | GPL-3.0-or-later |
| Fecha del release | 2026-02-22 |

## Relación con el paper

No debe colocarse el DOI del paper mientras no exista. Cuando el artículo sea
publicado:

- el registro de software debe indicar que está documentado por el artículo;
- el artículo debe citar el DOI específico de esta versión del software; y
- la autocita debe respetar la política de anonimización durante la revisión
  doble ciego.
