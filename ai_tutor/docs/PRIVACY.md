# Privacidad y tratamiento de datos

VTutor Pro VZona puede almacenar historial de conversación y registros de rutas pedagógicas en Moodle según la configuración institucional.

## Datos locales

Puede registrar curso/usuario, mensaje/respuesta, proveedor/modelo, tokens contabilizados por el proveedor cuando están disponibles, ruta sugerida, motivo, decisión Human-in-the-Loop, persona validadora y fecha de validación.

La Privacy API localiza, exporta y elimina/anomiza los datos personales correspondientes. El mapa pedagógico del curso se trata como catálogo del curso, no como dato personal del usuario.

## Proveedor externo

La rama 10.6.5 soporta DeepSeek. Para generar una respuesta pueden transmitirse mensaje actual, historial reciente, contexto del curso, fragmentos RAG recuperados y perfil efectivo necesario para adaptar la interacción. La transferencia externa se declara en la Privacy API como `ai_provider`.

## Minimización y retención

El administrador puede controlar modo de almacenamiento, longitud y días de retención. El filtro de seguridad puede enmascarar correos, teléfonos e identificadores personales; los identificadores normativos/documentales con guiones se preservan para no romper la trazabilidad de fuentes.

## Credenciales

Las API keys no deben aparecer en respuestas, historial ni errores. Los formularios personalizados no precargan el secreto; guardar un campo vacío conserva la credencial existente.
