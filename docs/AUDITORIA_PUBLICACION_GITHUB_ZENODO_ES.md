# Auditoría de la publicación GitHub–Zenodo

Fecha de auditoría: 24 de julio de 2026  
Repositorio: `marcelo-aldaz/VTUTOR`  
Rama auditada: `main`

## Dictamen

El repositorio conserva el artefacto correcto de VTutor v10.2.1 Vstable, build
`2026022203`, y su historia Git comienza el 22 de febrero de 2026. Sin embargo,
la publicación inicial no estaba lista para una ingestión rigurosa en Zenodo
porque carecía de metadatos de citación y presentaba afirmaciones o referencias
que no coincidían plenamente con el estado observable.

La presente actualización corrige la documentación y los metadatos. No modifica
el código PHP, JavaScript, plantillas, esquema de base de datos ni el ZIP
histórico.

## Hallazgos comprobados

| N.º | Hallazgo en `main` | Riesgo | Corrección preparada |
|---:|---|---|---|
| 1 | No existía `CITATION.cff` | GitHub y los usuarios no disponían de una cita normalizada | Se creó `CITATION.cff` con autor, ORCID, versión, fecha, licencia y alcance |
| 2 | No existía `.zenodo.json` | Zenodo podía inferir autoría y metadatos de forma incompleta | Se creó metadato explícito para el flujo GitHub–Zenodo |
| 3 | No existía ninguna etiqueta Git | No había un objeto inmutable claramente asociable a la versión | Se propone `v10.2.1-Vstable` |
| 4 | El README anunciaba ramas `develop`, `feature/ux-v10.2.2` y `feature/rag-v11` | Esas ramas no existen en el repositorio | Se eliminaron las referencias |
| 5 | README y changelog no identificaban build ni fecha | Ambigüedad frente a versiones posteriores homónimas | Se fijaron `2026022203` y 22-02-2026 |
| 6 | Las notas afirmaban validación en entorno real sin calificación metodológica | Podía interpretarse como una nueva validación independiente | Se definió el `8/8` como estado histórico documentado |
| 7 | Se decía que el RAG estaba desactivado, pero el snapshot conserva tablas, ajustes y lógica de recuperación | Contradicción entre documentación y código | Se declara la presencia de rutas experimentales y la exigencia de `rag_enabled = 0` para reproducir la configuración estudiada |
| 8 | No se distinguía el ZIP instalable de los archivos fuente automáticos de GitHub | Riesgo de instalar un paquete incorrecto | Se identifica el ZIP y su SHA-256 |
| 9 | No existía una declaración clara de limitaciones científicas | Riesgo de afirmar eficacia pedagógica no evaluada | Se creó `VALIDATION_AND_LIMITATIONS.md` |
| 10 | La privacidad y Human-in-the-Loop no estaban documentados de forma suficiente | Riesgo institucional y de tratamiento de datos | Se creó `GOVERNANCE_AND_PRIVACY.md` |
| 11 | El repositorio mezcla código, un ZIP instalable y un PDF histórico en `main` | Puede confundirse el snapshot fuente con el artefacto de instalación o el expediente | Se documenta la función de cada elemento; no se eliminan para preservar trazabilidad |
| 12 | No existía un cuerpo exacto para GitHub Release ni una ruta de publicación Zenodo | Riesgo de metadatos incoherentes | Se crearon el cuerpo del release y la guía paso a paso |

## Integridad comprobada

`version.php` declara:

```php
$plugin->component = 'block_ai_tutor';
$plugin->version = 2026022203;
$plugin->release = '10.2.1';
$plugin->requires = 2024100700;
$plugin->maturity = MATURITY_STABLE;
```

Paquete histórico:

```text
moodle-block-ai_tutor_github_v10_2_1_Vstable_docs.zip
SHA-256 34d0d4f59a9cc39a8f9d5bb43805d30269da491f8f2fe64e245a2d004646b924
```

PDF histórico:

```text
Reporte_Final_VTutor_v10_2_1_Vstable_v2.pdf
SHA-256 dae2cea6e66c034bb2f71a1e5f42def7fa85a0ab98ffbb1e55a18352086511d6
```

El ZIP contiene 37 archivos y conserva el build AMD necesario
`amd/build/chat.min.js`. La comparación con el árbol del repositorio no mostró
diferencias de código; las diferencias observadas se limitaron a archivos
auxiliares y a revisiones posteriores de `LICENSE` y `SECURITY.md`.

## Reserva técnica

No fue posible ejecutar `php -l` en este entorno porque el intérprete PHP no
estaba disponible. Por ello, esta actualización no debe presentarse como una
nueva auditoría ejecutable completa del plugin.

Antes de crear el tag definitivo conviene ejecutar, en un entorno con PHP y
Moodle 4.5:

1. lint de todos los archivos PHP;
2. instalación limpia;
3. actualización desde la versión previa pertinente;
4. pruebas funcionales documentadas;
5. revisión de privacidad;
6. comprobación de `rag_enabled = 0`; y
7. verificación del ZIP y del checksum.

## Límites de esta actualización

- No corrige ni modifica código.
- No repite T01–T08.
- No demuestra eficacia pedagógica.
- No añade resultados de usuarios.
- No atribuye a v10.2.1 funciones posteriores.
- No incorpora VTutor Pro, VTutor Pro VZona ni v10.3.x.
- No inventa DOI.

## Decisión recomendada

La documentación está lista para revisión y fusión. El release y la ingestión
en Zenodo deben ejecutarse únicamente después de:

1. aprobar estos cambios;
2. realizar la comprobación mínima con PHP/Moodle;
3. confirmar que el ZIP es el artefacto que se desea archivar;
4. crear el tag `v10.2.1-Vstable`; y
5. verificar los metadatos generados por Zenodo antes de considerar cerrada la
   publicación.
