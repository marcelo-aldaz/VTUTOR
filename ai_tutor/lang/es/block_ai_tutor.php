<?php
// blocks/ai_tutor/lang/es/block_ai_tutor.php
defined('MOODLE_INTERNAL') || die();

$string['block/ai_tutor:addinstance'] = 'Añadir bloque VTutor';
$string['block/ai_tutor:use'] = 'Usar VTutor';

// Configuración
$string['mainsettings'] = 'Configuración Principal';
$string['mainsettings_desc'] = 'Configuración básica del proveedor de Inteligencia Artificial';
$string['provider'] = 'Proveedor de IA';
$string['provider_desc'] = 'Selecciona el proveedor de inteligencia artificial';
$string['apikey'] = 'Clave API';
$string['apikey_desc'] = 'Tu clave de API del proveedor seleccionado';
$string['apikey_keep_configured'] = 'Clave configurada; deje vacío para conservarla';
$string['model'] = 'Modelo de IA';
$string['model_desc'] = 'Nombre exacto del modelo. Para DeepSeek use deepseek-v4-flash o deepseek-v4-pro en minúsculas.';


// Pedagógica
$string['pedagogysettings'] = 'Configuración Pedagógica';
$string['pedagogysettings_desc'] = 'Opciones para controlar el comportamiento del tutor';
$string['guideonly'] = 'Solo orientar (no dar respuestas)';
$string['guideonly_desc'] = 'El tutor guiará a los estudiantes sin proporcionar respuestas directas a tareas';
$string['temperature'] = 'Creatividad (Temperatura)';
$string['temperature_desc'] = 'Valor entre 0.0 (preciso) y 2.0 (creativo). Recomendado: 0.7';
$string['maxtokens'] = 'Máximo de tokens';
$string['maxtokens_desc'] = 'Tokens por llamada de generación (512–4096). Recomendado: 1200. Si el proveedor alcanza este límite, VTutor permite una única continuación breve de cierre para evitar bucles y consumo innecesario.';

// RAG
$string['ragsettings'] = 'Búsqueda de Contexto (RAG)';
$string['ragsettings_desc'] = 'Configuración para búsqueda en contenido del curso';
$string['vectorstore'] = 'Almacén Vectorial';
$string['vectorstore_desc'] = 'Dónde se almacenan los embeddings para búsqueda';
$string['chunksize'] = 'Tamaño de fragmentos';
$string['chunksize_desc'] = 'Palabras por fragmento para indexación';

// Webhooks
$string['webhookssettings'] = 'Webhooks (Opcional)';
$string['webhookssettings_desc'] = 'Notificaciones a sistemas externos';
$string['webhookurl'] = 'URL del Webhook';
$string['webhookurl_desc'] = 'URL que recibirá las notificaciones';
$string['webhooksecret'] = 'Secreto del Webhook';
$string['webhooksecret_desc'] = 'Clave para verificar la autenticidad';

// Privacidad
$string['privacy:metadata:block_ai_tutor_msg'] = 'El bloque VTutor almacena mensajes de chat para proporcionar continuidad en las conversaciones.';
$string['privacy:metadata:block_ai_tutor_msg:courseid'] = 'ID del curso';
$string['privacy:metadata:block_ai_tutor_msg:userid'] = 'ID del usuario';
$string['privacy:metadata:block_ai_tutor_msg:message'] = 'Mensaje del estudiante';
$string['privacy:metadata:block_ai_tutor_msg:response'] = 'Respuesta de la IA';
$string['privacy:metadata:block_ai_tutor_msg:timecreated'] = 'Fecha de creación';
$string['privacy:metadata:block_ai_tutor_msg:provider'] = 'Proveedor IA usado para generar la respuesta.';
$string['privacy:metadata:block_ai_tutor_msg:model'] = 'Modelo IA usado para generar la respuesta.';
$string['privacy:metadata:block_ai_tutor_msg:tokens_used'] = 'Conteo de tokens registrado para la interacción, cuando esté disponible.';

// Varios
$string['clearconfirm'] = '¿Estás seguro de que deseas eliminar toda la conversación? Esta acción no se puede deshacer.';
$string['task_cleanup_old_messages'] = 'Limpiar mensajes antiguos de VTutor';

$string['gensettings'] = 'Ajustes de generación';
$string['gensettings_desc'] = 'Controles de salida del modelo.';
$string['advanced'] = 'Avanzado';
$string['advanced_desc'] = 'Ajustes opcionales para integraciones ampliadas.';

$string['adminpage'] = 'VTutor Pro V-Zona - Configuración rápida';
$string['adminpage_desc'] = 'Página de configuración rápida para proveedor IA e identidad institucional V-Zona en entornos donde el menú estándar del plugin no se visualiza con facilidad.';
$string['missingconfigfields'] = 'Faltan campos obligatorios de configuración: {$a}';

$string['provider_deepseek'] = 'DeepSeek (API directa)';
$string['openquickconfig'] = 'Abrir página de configuración rápida';
$string['providerexamples'] = 'Proveedor validado y soportado en esta rama final: DeepSeek. Los conectores experimentales de otros proveedores no forman parte del contrato de esta release.';
$string['provider_hint_deepseek'] = 'DeepSeek: provider=deepseek, baseurl=https://api.deepseek.com/v1, model=deepseek-v4-flash';

$string['rag_topk'] = 'Top-k de fragmentos RAG';
$string['rag_topk_desc'] = 'Número de fragmentos recuperados enviados al modelo.';
$string['courseid'] = 'ID del curso';


// Filtro de seguridad (solo administrador)
$string['securityfilter'] = 'Filtro de seguridad';
$string['securityfilter_desc'] = 'Controles de seguridad y privacidad para entrada, salida e historial de VTutor. Recomendado: administrado solo por el Administrador.';
$string['security_filter_enabled'] = 'Activar filtro de seguridad';
$string['security_filter_enabled_desc'] = 'Aplica límites, recortes y políticas de persistencia a mensajes y respuestas.';
$string['security_max_input_chars'] = 'Máximo de caracteres en entrada';
$string['security_max_input_chars_desc'] = 'Límite máximo del mensaje enviado por el usuario.';
$string['security_max_output_chars'] = 'Máximo de caracteres en salida';
$string['security_max_output_chars_desc'] = 'Límite máximo de la respuesta mostrada al usuario. Recomendado: 8000; el recorte siempre se realiza en un límite legible.';
$string['security_history_max_limit'] = 'Máximo de historial recuperable';
$string['security_history_max_limit_desc'] = 'Tope de mensajes que puede devolver el endpoint de historial.';
$string['security_store_mode'] = 'Modo de almacenamiento del historial';
$string['security_store_mode_desc'] = 'Define cuánto contenido se almacena en la tabla de historial del chat. Use Completo para conservar respuestas íntegras al recargar.';
$string['security_store_mode_full'] = 'Completo';
$string['security_store_mode_truncate'] = 'Truncado';
$string['security_store_mode_metadata'] = 'Solo marcador (sin contenido)';
$string['security_store_mode_none'] = 'No guardar historial';
$string['security_store_truncate_chars'] = 'Caracteres para truncar historial';
$string['security_store_truncate_chars_desc'] = 'Cantidad máxima de caracteres guardados por mensaje/respuesta en modo truncado.';
$string['security_mask_sensitive'] = 'Enmascarar datos sensibles básicos';
$string['security_mask_sensitive_desc'] = 'Oculta correos, teléfonos e identificaciones largas en mensajes, respuestas y logs.';
$string['security_block_patterns'] = 'Patrones de bloqueo (uno por línea)';
$string['security_block_patterns_desc'] = 'Si el mensaje contiene alguno de estos textos, se bloquea. Use una línea por patrón. Las líneas que empiezan con # se ignoran.';

$string['target_population'] = 'Población objetivo';
$string['target_population_desc'] = 'Población objetivo para adaptar explicaciones y ejemplos. El perfil del usuario (estudiante, docente o administrador) se detecta automáticamente. Valor recomendado: personas con escolaridad inconclusa. ';
$string['short_answers'] = 'Respuestas breves';
$string['short_answers_desc'] = 'Si está activado, VTutor responderá de forma breve por defecto (máximo 4 líneas cortas), salvo que el usuario pida más detalle. ';
$string['always_examples'] = 'Siempre incluir ejemplo';
$string['always_examples_desc'] = 'Si está activado, VTutor incluirá siempre un ejemplo breve adaptado a la población objetivo. ';
$string['profile_autodetect_note'] = 'El perfil del usuario se detecta automáticamente según permisos del curso: estudiante, docente o administrador.';
$string['voice_limited_environment'] = 'Funciones de voz limitadas en este entorno. Recomendado: Chrome/Edge compatibles y sitio HTTPS.';
$string['voice_apk_environment'] = 'La app/APK actual limita la voz. Para escuchar y dictar use Chrome compatible o una integración nativa de Android.';
$string['voice_mobile_only'] = 'El dictado por voz está disponible cuando el navegador y los permisos del dispositivo lo permiten, tanto en PC como en tablet y celular.';

$string['humanized_release'] = 'Versión humanizada segura';

$string['ai_tutor:addinstance'] = 'Añadir bloque VTutor';
$string['ai_tutor:myaddinstance'] = 'Añadir bloque VTutor al tablero';
$string['ai_tutor:use'] = 'Usar VTutor';

$string['studentresponsepolicy'] = 'Política de respuesta para estudiantes';
$string['studentresponsepolicy_desc'] = 'Reglas administrables para mantener la longitud de las respuestas dirigidas a estudiantes.';
$string['student_word_policy_enabled'] = 'Activar directriz de 150 a 200 palabras para estudiantes';
$string['student_word_policy_enabled_desc'] = 'Si está activado, VTutor responde dentro del rango configurado y trata el máximo como límite operativo por respuesta nueva.';
$string['student_word_min'] = 'Mínimo aproximado de palabras para estudiantes';
$string['student_word_min_desc'] = 'Cantidad mínima aproximada de palabras por respuesta dirigida a estudiantes.';
$string['student_word_max'] = 'Máximo aproximado de palabras para estudiantes';
$string['student_word_max_desc'] = 'Cantidad máxima aproximada de palabras por respuesta dirigida a estudiantes.';
$string['copyresponse'] = 'Copiar respuesta';
$string['copiedresponse'] = 'Respuesta copiada';


$string['teacherresponsepolicy'] = 'Política de respuesta para docentes';
$string['teacherresponsepolicy_desc'] = 'Reglas administrables para mantener la longitud de las respuestas a docentes dentro de un rango definido.';
$string['teacher_word_policy_enabled'] = 'Activar rango de palabras para docentes';
$string['teacher_word_policy_enabled_desc'] = 'Si está activado, VTutor responde a docentes dentro del rango configurado y trata el máximo como límite operativo por respuesta nueva.';
$string['teacher_word_min'] = 'Mínimo aproximado de palabras para docentes';
$string['teacher_word_min_desc'] = 'Cantidad mínima aproximada de palabras por respuesta dirigida a docentes.';
$string['teacher_word_max'] = 'Máximo aproximado de palabras para docentes';
$string['teacher_word_max_desc'] = 'Cantidad máxima aproximada de palabras por respuesta dirigida a docentes.';
$string['adminresponsepolicy'] = 'Política de respuesta para administradores';
$string['adminresponsepolicy_desc'] = 'Reglas administrables para mantener la longitud de las respuestas a administradores dentro de un rango definido.';
$string['admin_word_policy_enabled'] = 'Activar rango de palabras para administradores';
$string['admin_word_policy_enabled_desc'] = 'Si está activado, VTutor responde a administradores dentro del rango configurado y trata el máximo como límite operativo por respuesta nueva.';
$string['admin_word_min'] = 'Mínimo aproximado de palabras para administradores';
$string['admin_word_min_desc'] = 'Cantidad mínima aproximada de palabras por respuesta dirigida a administradores.';
$string['admin_word_max'] = 'Máximo aproximado de palabras para administradores';
$string['admin_word_max_desc'] = 'Cantidad máxima aproximada de palabras por respuesta dirigida a administradores.';


$string['pluginname'] = 'VTutor Pro - Asistente IA Profesional';
$string['professionalvariant'] = 'Variante profesional de producción';
$string['professionalvariant_desc'] = 'Ajustes para centros de capacitación, formación continua y cursos dirigidos a profesionales docentes y profesiones liberales.';
$string['site_variant'] = 'Tipo de instalación';
$string['site_variant_desc'] = 'Define si VTutor se comporta como tutor académico general o como tutor profesional de producción.';
$string['site_variant_standard'] = 'Estándar académico';
$string['site_variant_professional'] = 'Profesional de producción';
$string['professional_sector'] = 'Sector o campo profesional';
$string['professional_sector_desc'] = 'Campo principal para contextualizar el lenguaje, los ejemplos y la orientación profesional.';
$string['professional_response_focus'] = 'Enfoque profesional de respuesta';
$string['professional_response_focus_desc'] = 'Directriz institucional para orientar respuestas con mayor rigor técnico y aplicabilidad.';
$string['professional_source_priority'] = 'Priorizar documentos del curso sobre conocimiento general';
$string['professional_source_priority_desc'] = 'Cuando haya contexto recuperado o documentos indexados, VTutor los usará como base principal de la respuesta y solo complementará con conocimiento general si es necesario.';
$string['professional_natural_sources'] = 'Mencionar documentos del curso de forma natural';
$string['professional_natural_sources_desc'] = 'Permite que VTutor mencione títulos de documentos o recursos del curso de forma natural cuando eso mejore la orientación profesional.';
$string['professionalvariant_admin_note'] = 'Esta edición institucional procesa una base documental mínima en PDF, Markdown o TXT para reducir dependencias y mantener trazabilidad.';

$string['institutionalvariant'] = 'Identidad institucional V-Zona';
$string['institutionalvariant_desc'] = 'Parámetros para presentar VTutor como asistente institucional del Centro de Capacitación V-Zona, con tono profesional y orientación documental.';
$string['institution_name'] = 'Nombre institucional visible';
$string['institution_name_desc'] = 'Nombre que VTutor mostrará en la interfaz institucional.';
$string['institution_subtitle'] = 'Subtítulo institucional';
$string['institution_subtitle_desc'] = 'Frase breve visible en la cabecera del asistente.';
$string['institution_response_directive'] = 'Directriz institucional de respuesta';
$string['institution_response_directive_desc'] = 'Regla de estilo y propósito institucional que VTutor integrará al prompt principal.';
$string['institution_document_guidance'] = 'Directriz institucional para uso documental';
$string['institution_document_guidance_desc'] = 'Indica qué tipos de documentos del curso deben ser priorizados como base profesional de respuesta.';
$string['institution_footer_note'] = 'Nota institucional de pie';
$string['institution_footer_note_desc'] = 'Aviso visible en la parte inferior de la interfaz.';
$string['institutional_admin_note'] = 'Edición institucional V-Zona: esta interfaz y el comportamiento del asistente están optimizados para capacitación profesional con apoyo documental en Moodle.';

$string['rag_reindex_error'] = 'No fue posible completar la reindexación: {$a}';
$string['rag_reindex_course_done'] = 'Reindexación completada en {$a->course}: {$a->sources} fuentes procesadas y {$a->chunks} fragmentos indexados.';
$string['documentsectiontitle'] = 'Base documental profesional recomendada';
$string['documentsectiondesc'] = 'Cree una sección del curso con este nombre y cargue allí los materiales que VTutor debe usar como base de respuesta profesional.';
$string['documentsectiontypes'] = 'Formatos activos en ALPHA3: PDF con texto seleccionable, Markdown y TXT. Los PDF escaneados requieren OCR opcional.';
$string['documentsectionaction'] = 'Después de subir o actualizar documentos, guarde el centro de control con procesamiento activado o use el botón interno. La extracción se ejecuta directamente desde Moodle y no requiere cron web, tareas ad hoc ni cPanel.';
$string['reindexcoursebutton'] = '🔄 Procesar/reindexar documentos del curso';


$string['institution_cover_image_url'] = 'URL de imagen de portada institucional';
$string['institution_cover_image_url_desc'] = 'Opcional. Si se deja vacío, VTutor usará la portada visual institucional incluida en el plugin.';
$string['institution_logo_image_url'] = 'URL de logotipo institucional';
$string['institution_logo_image_url_desc'] = 'Opcional. Si se deja vacío, VTutor usará el isotipo institucional incluido en el plugin.';
$string['institution_cover_kicker'] = 'Franja superior de portada';
$string['institution_cover_kicker_desc'] = 'Texto breve superior para la portada institucional de VTutor.';
$string['institution_cover_title'] = 'Título principal de portada';
$string['institution_cover_title_desc'] = 'Título principal visible en la portada institucional.';
$string['institution_cover_message'] = 'Mensaje principal de portada';
$string['institution_cover_message_desc'] = 'Texto descriptivo que se muestra en la portada institucional de VTutor.';
$string['institution_cover_cta'] = 'Mensaje complementario de portada';
$string['institution_cover_cta_desc'] = 'Frase breve de apoyo visible en la portada institucional.';
$string['documentstatuspaneltitle'] = 'Estado de la base documental del curso';
$string['documentstatscountlabel'] = 'Documentos indexados';
$string['documentstatschunkslabel'] = 'Fragmentos indexados';
$string['documentstatslastlabel'] = 'Última reindexación';
$string['documentstatsstatuslabel'] = 'Estado';
$string['documentstatsnever'] = 'Aún no disponible';
$string['documentstatusempty'] = 'Sin base documental indexada todavía. Suba materiales y ejecute la reindexación del curso.';
$string['documentstatusready'] = 'Base documental activa y lista para apoyar respuestas profesionales en este curso.';
$string['documentstatusstale'] = 'Base documental disponible, pero conviene reindexarla para reflejar cambios recientes del curso.';
$string['documentstatusdisabled'] = 'La indexación documental está desactivada por configuración administrativa.';
$string['documentstatusinternalmasterdisabled'] = 'El motor mini-RAG institucional está desactivado globalmente.';
$string['documentstatuscoursedisabled'] = 'El mini-RAG documental no está activado para este curso. Actívelo en el centro de control antes de procesar documentos.';
$string['managedocumentsbutton'] = '⚙️ Centro de control VTutor Pro';
$string['managecoursepage'] = 'Centro de control VTutor Pro';
$string['managecoursepage_intro'] = 'Desde este centro de control puede administrar la base documental profesional del curso {$a}, revisar el estado de indexación, definir prompts del curso, ajustar la imagen y personalizar el acceso visible de VTutor Pro.';
$string['managecoursepage_note'] = 'La conversación principal de VTutor Pro se abre ahora en una ventana independiente y redimensionable. Toda la gestión documental, de prompts, imagen y acceso visible del curso se concentra en este centro de control.';
$string['managecoursepage_backcourse'] = '← Volver al curso';
$string['managecoursepage_docs_header'] = 'Base documental profesional del curso';
$string['managecoursepage_docs_help'] = 'Cargue PDF con texto seleccionable, Markdown o TXT. VTutor genera Markdown revisable y construye vectores locales con análisis semántico DeepSeek.';
$string['managecoursepage_filemanager'] = 'Documentos del curso para VTutor';
$string['managecoursepage_prompt_header'] = 'Prompt o directriz específica del curso';
$string['managecoursepage_prompt'] = 'Prompt/directriz adicional del curso';
$string['managecoursepage_prompt_help'] = 'Escriba aquí una directriz específica para orientar el tratamiento profesional de los documentos cargados o la forma en que VTutor debe responder dentro de este curso. Ejemplo: priorizar normativa interna, usar formato de checklist, responder como asesor metodológico o separar claramente criterio técnico y recomendación operativa.';
$string['managecoursepage_launcher_header'] = 'Personalización del botón flotante';
$string['managecoursepage_launcher_label'] = 'Texto visible del botón flotante';
$string['managecoursepage_launcher_title'] = 'Texto emergente del botón flotante';
$string['managecoursepage_launcher_bg'] = 'Color/fondo del botón flotante';
$string['managecoursepage_launcher_fg'] = 'Color del texto del botón flotante';
$string['managecoursepage_launcher_border'] = 'Color del borde del botón flotante';
$string['managecoursepage_reindex_now'] = 'Procesar documentos directamente después de guardar';
$string['managecoursepage_saved'] = 'Se guardaron los cambios del curso para VTutor.';
$string['managecoursepage_saved_reindexed'] = 'Reindexación completada: {$a->sources} fuentes y {$a->chunks} fragmentos.';
$string['managecoursepage_saved_but_reindex_failed'] = 'Los cambios se guardaron, pero la reindexación no pudo completarse: {$a}';
$string['managecoursepage_internal_reindex_done'] = 'Reindexación interna completada desde el centro de control: {$a->sources} fuentes, {$a->chunks} fragmentos y {$a->errors} errores.';
$string['managecoursepage_internal_reindex_error'] = 'No fue posible ejecutar la reindexación interna desde el centro de control: {$a}';
$string['managecoursepage_file_save_error'] = 'No fue posible guardar los documentos del curso: {$a}';
$string['managecoursepage_currentfiles'] = 'Documentos actualmente cargados';
$string['managecoursepage_nofiles'] = 'Todavía no hay documentos cargados en la base documental propia de VTutor para este curso.';


$string['openglobalconfigbutton'] = '🛠 Panel global: IA, imagen y prompts';
$string['opensettingspage'] = 'Abrir panel administrativo de VTutor Pro';
$string['purgecacheslabel'] = 'Purgar cachés';
$string['admintab_ai'] = 'IA y proveedor';
$string['admintab_visual'] = 'Imagen e identidad VTutor Pro';
$string['admintab_prompts'] = 'Prompts y directrices';
$string['global_master_prompt'] = 'Prompt maestro institucional';
$string['global_master_prompt_desc'] = 'Directriz global transversal que VTutor aplicará en toda la instalación antes de las directrices específicas de curso.';
$string['global_document_prompt'] = 'Prompt global para tratamiento documental';
$string['global_document_prompt_desc'] = 'Directriz global para interpretar, resumir, comparar y priorizar documentos indexados en VTutor.';
$string['managecoursepage_document_prompt'] = 'Prompt/directriz documental del curso';
$string['managecoursepage_document_prompt_help'] = 'Escriba aquí una directriz específica para indicar cómo tratar los documentos del curso. Ejemplo: priorizar normativa interna, extraer procedimientos paso a paso, comparar protocolos o responder en formato de informe ejecutivo.';
$string['managecoursepage_global_header'] = 'Configuración global visible en esta página';
$string['managecoursepage_global_help'] = 'Como administrador, desde aquí también puede ajustar el proveedor de IA, la imagen institucional VTutor Pro y los prompts globales sin salir de la gestión del curso.';
$string['managecoursepage_admin_note'] = 'Si ingresa como administrador, este centro de control también incorpora acceso y edición rápida del proveedor IA, la portada institucional y los prompts globales sin salir de la página.';


$string['launchwindowlabel'] = 'Abrir VTutor Pro';
$string['launchsamepagelabel'] = 'Abrir en pestaña independiente';
$string['launchdescription'] = 'La conversación de VTutor Pro se abre fuera del bloque del curso, en una ventana independiente y redimensionable. La gestión documental, los prompts y la configuración visual quedan en el Centro de control.';
$string['controlcenterlabel'] = '⚙️ Centro de control VTutor Pro';
$string['openchatwindowbutton'] = 'Abrir ventana VTutor Pro';

// Mini-RAG semántico DeepSeek con vectores locales.
$string['rag_internal_settings'] = 'Mini-RAG interno institucional';
$string['rag_internal_settings_desc'] = 'Configuración del índice vectorial disperso, el respaldo léxico, la extracción y el reranking. DeepSeek es el único proveedor semántico externo.';
$string['internal_rag_enabled'] = 'Habilitar motor mini-RAG institucional';
$string['internal_rag_enabled_desc'] = 'Hace disponible el flujo documental institucional. Cada curso continúa desactivado hasta que una persona autorizada lo habilite en su centro de control.';
$string['rag_show_sources'] = 'Mostrar fuentes documentales verificadas';
$string['rag_show_sources_desc'] = 'Anexa al texto de respuesta las fuentes construidas por el servidor desde documento, páginas y encabezados reales.';
$string['rag_relational_causality_guard'] = 'Control de causalidad en relaciones documentales';
$string['rag_relational_causality_guard_desc'] = 'Evita que una síntesis relacional introduzca causalidad no respaldada por los fragmentos recuperados; no realiza una segunda llamada LLM.';
$string['rag_semantic_candidates'] = 'Candidatos semánticos';
$string['rag_semantic_candidates_desc'] = 'Número de candidatos evaluados antes de seleccionar el top-k final.';
$string['rag_semantic_weight'] = 'Peso semántico';
$string['rag_semantic_weight_desc'] = 'Peso entre 0 y 1 de la similitud coseno. La señal semántica puede mejorar el ranking, pero nunca reducir una coincidencia léxica documental válida.';
$string['rag_min_score'] = 'Umbral mínimo de recuperación';
$string['rag_min_score_desc'] = 'Puntuación híbrida mínima entre 0 y 1 para considerar que existe evidencia suficiente.';
$string['pdftotext_path'] = 'Ruta opcional de pdftotext';
$string['pdftotext_path_desc'] = 'Acelerador opcional. Si no existe o falla, ALPHA3 utiliza el extractor PHP interno.';
$string['pdf_native_fallback'] = 'Activar extractor PDF interno';
$string['pdf_native_fallback_desc'] = 'Extrae la capa de texto del PDF sin ejecutar programas del servidor. No realiza OCR.';
$string['rag_max_pdf_pages'] = 'Máximo de páginas por PDF';
$string['rag_max_pdf_pages_desc'] = 'Límite institucional inicial por documento PDF.';
$string['rag_max_documents'] = 'Máximo de documentos por curso';
$string['rag_max_documents_desc'] = 'Cantidad máxima de archivos de la base documental administrada.';
$string['rag_max_file_bytes'] = 'Tamaño máximo por archivo en bytes';
$string['rag_max_file_bytes_desc'] = 'Límite de tamaño aplicado por Moodle File API y por el procesador documental.';
$string['rag_chunk_target'] = 'Tamaño objetivo del chunk';
$string['rag_chunk_target_desc'] = 'Tamaño objetivo en caracteres después de dividir por encabezados, páginas y párrafos.';
$string['rag_chunk_max'] = 'Tamaño máximo del chunk';
$string['rag_chunk_max_desc'] = 'Límite absoluto de caracteres de un chunk.';
$string['rag_chunk_overlap'] = 'Solapamiento del chunk';
$string['rag_chunk_overlap_desc'] = 'Caracteres de contexto repetidos solo cuando una unidad extensa debe dividirse.';
$string['rag_chunk_min'] = 'Tamaño mínimo útil del chunk';
$string['rag_chunk_min_desc'] = 'Los fragmentos finales menores se fusionan cuando es seguro.';
$string['rag_batch_retention_days'] = 'Retención de lotes inactivos';
$string['rag_batch_retention_days_desc'] = 'Días que se conservan lotes inactivos antes de una limpieza programada. El lote activo nunca se elimina.';
$string['rag_cleanup_task'] = 'Limpiar lotes inactivos del mini-RAG de VTutor Pro';
$string['rag_course_enabled'] = 'Activar mini-RAG documental en este curso';
$string['rag_course_enabled_help'] = 'Aísla documentos, chunks, vectores, cachés y consultas mediante el courseid actual. Debe activarse únicamente en cursos institucionales autorizados.';
$string['rag_internal_global_disabled'] = 'El motor mini-RAG institucional está desactivado globalmente. Un administrador debe habilitarlo en la configuración del bloque.';
$string['rag_course_disabled_reindex'] = 'La reindexación institucional no se ejecutó: active primero el mini-RAG únicamente para este curso desde el centro de control. El índice heredado no fue modificado.';
$string['rag_reindex_no_documents'] = 'No hay documentos en la base documental de este curso. Cargue al menos un archivo y guarde antes de reindexar.';
$string['rag_reindex_readiness_global_disabled'] = 'Procesamiento bloqueado: el motor mini-RAG institucional está desactivado globalmente. Un administrador debe habilitarlo antes de autorizar cursos.';
$string['rag_reindex_readiness_course_and_documents_missing'] = 'Preparación pendiente: active “Mini-RAG documental en este curso”, cargue al menos un PDF, TXT o Markdown y guarde una sola vez con “Procesar documentos directamente después de guardar” seleccionado. No se modificó el índice heredado.';
$string['rag_reindex_readiness_course_disabled'] = 'Preparación pendiente: active “Mini-RAG documental en este curso” y guarde con el procesamiento directo seleccionado. Los archivos ya cargados y el índice heredado permanecen intactos.';
$string['rag_reindex_readiness_documents_missing'] = 'Preparación pendiente: el mini-RAG del curso está activo, pero no hay archivos. Cargue al menos un PDF, TXT o Markdown y guarde con el procesamiento directo seleccionado.';
$string['rag_reindex_readiness_ready'] = 'Listo para procesar los documentos institucionales de este curso sin modificar el índice heredado.';
$string['rag_semantic_migration_prerequisites'] = 'Disponible después de activar el mini-RAG, cargar, extraer, revisar y aprobar al menos un documento.';
$string['rag_embedding_not_ready_diag'] = 'Los embeddings no están configurados; se utilizará recuperación léxica local.';
$string['rag_course_scope'] = 'Fuentes del RAG';
$string['rag_scope_managed'] = 'Solo base documental administrada';
$string['rag_scope_managed_moodle'] = 'Base documental y recursos Moodle (compatibilidad heredada)';
$string['rag_course_mode'] = 'Modo de respuesta documental';
$string['rag_mode_strict'] = 'Estricto: responder solo con evidencia recuperada';
$string['rag_mode_mixed'] = 'Mixto: permitir complemento general identificado';
$string['rag_review_workflow_help'] = 'Flujo seguro sin cron: guardar archivo → extracción directa → Markdown revisable → aprobación humana → firma semántica DeepSeek → vector disperso local → activación atómica.';
$string['rag_strict_no_evidence'] = 'No encontré información suficiente en los documentos indexados de este curso.';
$string['rag_sources_heading'] = 'Fuentes consultadas:';
$string['rag_document_task'] = 'Procesar documento institucional de VTutor Pro';
$string['rag_pdftotext_unavailable'] = 'El extractor PDF pdftotext no está disponible o su ruta no es ejecutable.';
$string['rag_process_execution_disabled'] = 'El servidor no permite ejecutar el extractor PDF configurado.';
$string['rag_pdftotext_failed'] = 'La extracción PDF no pudo iniciarse.';
$string['rag_pdftotext_failed_detail'] = 'La extracción PDF falló: {$a}';
$string['rag_pdf_too_many_pages'] = 'El PDF supera el límite de {$a} páginas.';
$string['rag_pdf_encrypted'] = 'El PDF está cifrado o protegido y no puede procesarse. Cargue una copia sin contraseña.';
$string['rag_pending_ocr_detail'] = 'El PDF no contiene texto suficiente o legible; requiere OCR antes de indexarse.';
$string['rag_pdf_text_encoding_unsupported'] = 'El PDF contiene operaciones de texto, pero su codificación de fuente todavía no puede interpretarse con seguridad. No se clasificará como escaneado; revise el archivo original.';
$string['rag_pdf_structure_unsupported'] = 'La estructura interna del PDF no contiene texto ni imágenes identificables mediante el extractor seguro. Revise o vuelva a exportar el archivo.';
$string['rag_extraction_too_short'] = 'La extracción produjo muy poco texto y no se indexará.';
$string['rag_markdown_missing'] = 'No existe un Markdown revisable para este documento.';
$string['rag_no_useful_chunks'] = 'El documento no produjo chunks útiles.';
$string['rag_embedding_config_incomplete'] = 'La configuración HTTPS del proveedor de embeddings está incompleta.';
$string['rag_embedding_transport_error'] = 'Error de transporte del proveedor de embeddings ({$a}).';
$string['rag_embedding_http_error'] = 'El proveedor de embeddings respondió HTTP {$a}.';
$string['rag_embedding_invalid_response'] = 'El proveedor de embeddings devolvió una respuesta inválida.';
$string['rag_embedding_invalid_vector'] = 'El proveedor devolvió un vector vacío o no numérico.';
$string['rag_embedding_dimension_mismatch'] = 'Los vectores del lote tienen dimensiones diferentes.';
$string['rag_embedding_expected_dimension_mismatch'] = 'La dimensión devuelta no coincide con las {$a} dimensiones configuradas.';
$string['rag_embedding_count_mismatch'] = 'La cantidad de vectores no coincide con la cantidad de textos enviados.';
$string['rag_embedding_optional_diag'] = 'Los embeddings no están configurados. El mini-RAG seguirá operativo en modo léxico local; puede añadir embeddings posteriormente para activar recuperación híbrida semántica.';
$string['rag_deepseek_semantic_enabled'] = 'Activar vectorización semántica DeepSeek';
$string['rag_deepseek_semantic_enabled_desc'] = 'Usa la misma API key y el mismo modelo DeepSeek del tutor para generar firmas JSON, convertidas localmente en vectores dispersos. No utiliza otro proveedor ni un endpoint /embeddings.';
$string['rag_semantic_timeout'] = 'Tiempo máximo de análisis semántico';
$string['rag_semantic_timeout_desc'] = 'Segundos máximos para cada solicitud JSON de vectorización o reranking a DeepSeek (10–180).';
$string['rag_semantic_batch_size'] = 'Chunks por lote semántico';
$string['rag_semantic_batch_size_desc'] = 'Cantidad de fragmentos analizados por solicitud DeepSeek (1–8). El valor 1 maximiza la confiabilidad de la salida JSON; RC4 degrada automáticamente a solicitudes individuales si falla un lote.';
$string['rag_semantic_recovery_note'] = 'RC4 distingue respuestas vacías, truncadas, inválidas e incompletas. Ante un fallo recuperable reintenta de forma acotada y degrada el lote a fragmentos individuales antes de activar el índice.';
$string['rag_semantic_configured_not_probed'] = 'Configurada; esta pantalla no ejecuta ni factura una solicitud de prueba. La operación real se confirma con chunks y vectores activos.';
$string['rag_deepseek_rerank_enabled'] = 'Activar reranking DeepSeek condicional';
$string['rag_deepseek_rerank_enabled_desc'] = 'Realiza una llamada adicional únicamente si los candidatos están próximos o la confianza es baja.';
$string['rag_rerank_ambiguity_delta'] = 'Diferencia para considerar resultados ambiguos';
$string['rag_rerank_ambiguity_delta_desc'] = 'Si la distancia entre los dos mejores resultados es menor o igual a este valor, DeepSeek los reordena.';
$string['rag_rerank_confidence'] = 'Umbral de confianza para reranking';
$string['rag_rerank_confidence_desc'] = 'Si el mejor resultado queda por debajo de este valor, se aplica reranking DeepSeek.';
$string['rag_deepseek_semantic_not_configured'] = 'La vectorización semántica requiere que DeepSeek sea el proveedor activo, una API key configurada y la URL oficial https://api.deepseek.com.';
$string['rag_deepseek_semantic_not_ready_diag'] = 'La recuperación léxica permanece disponible, pero la vectorización semántica espera una configuración DeepSeek válida.';
$string['rag_deepseek_semantic_invalid_response'] = 'DeepSeek devolvió una firma semántica JSON inválida o incompleta.';
$string['rag_deepseek_semantic_empty_response'] = 'DeepSeek devolvió contenido semántico vacío. VTutor reintentó de forma segura sin activar un índice parcial.';
$string['rag_deepseek_semantic_truncated_response'] = 'DeepSeek truncó la firma semántica por límite de salida. VTutor reintentó de forma segura sin activar un índice parcial.';
$string['rag_deepseek_semantic_empty_vector'] = 'La firma semántica no produjo un vector local utilizable.';
$string['rag_deepseek_semantic_request_failed'] = 'No se pudo completar la solicitud semántica DeepSeek ({$a}). El índice activo anterior permanece intacto.';
$string['rag_deepseek_rerank_invalid_response'] = 'DeepSeek devolvió un orden de candidatos inválido; se conserva el ranking local.';
$string['rag_semantic_migration_no_documents'] = 'No hay documentos indexados y aprobados con Markdown disponible para migrar.';
$string['rag_semantic_migration_done'] = 'Migración vectorial completada: {$a->documents} documentos, {$a->chunks} chunks, {$a->reused} vectores reutilizados y {$a->generated} generados. El cambio de índice fue atómico.';
$string['rag_semantic_migration_failed'] = 'La migración vectorial no se activó: {$a}. El índice anterior continúa operativo.';
$string['rag_action_migrate_semantic'] = 'Construir índice vectorial DeepSeek';
$string['cachedef_semantic_vectors'] = 'Vectores semánticos de consultas y fragmentos, identificados por hash.';
$string['cachedef_semantic_rerank'] = 'Resultados temporales del reranking documental DeepSeek.';
$string['managecoursepage_saved_no_reindex_disabled'] = 'No se ejecutó la reindexación porque el mini-RAG permanece desactivado para este curso; el índice heredado se conservó sin cambios.';
$string['rag_active_model_mismatch'] = 'El curso ya tiene un índice activo con otro modelo o dimensión. No se mezclarán vectores incompatibles.';
$string['rag_openxml_invalid'] = 'El archivo OpenXML no es válido o está dañado.';
$string['rag_too_many_documents'] = 'El curso supera el máximo de {$a} documentos.';
$string['rag_unsupported_extension'] = 'El formato .{$a} no está habilitado para el mini-RAG.';
$string['rag_empty_file'] = 'El archivo está vacío.';
$string['rag_file_too_large'] = 'El archivo supera el tamaño máximo de {$a}.';
$string['rag_mime_mismatch'] = 'El tipo MIME real no coincide con la extensión del archivo.';
$string['rag_document_index_done'] = 'Documento aprobado e indexado correctamente desde Moodle.';
$string['rag_document_index_failed'] = 'El documento fue aprobado, pero no pudo indexarse: {$a}';
$string['rag_document_extract_done'] = 'Extracción terminada. Revise el Markdown antes de aprobar e indexar.';
$string['rag_document_extract_failed'] = 'La extracción no pudo completarse: {$a}';
$string['rag_processing_error_detail'] = 'Detalle técnico: {$a}';
$string['rag_document_not_reviewable'] = 'El documento no está en estado pendiente de revisión.';
$string['rag_index_interrupted'] = 'La solicitud de indexación semántica se interrumpió antes de finalizar. Se conservó el Markdown revisado; reintente la indexación.';
$string['rag_index_still_running'] = 'La indexación semántica todavía está dentro de su ventana de ejecución activa. Espere unos minutos y actualice la página antes de intentar recuperarla.';
$string['rag_processing_phase'] = 'Fase técnica: {$a}';
$string['rag_document_table_heading'] = 'Ciclo de vida documental del mini-RAG';
$string['rag_table_document'] = 'Documento';
$string['rag_table_status'] = 'Estado';
$string['rag_table_extractor'] = 'Extractor';
$string['rag_table_pages'] = 'Páginas';
$string['rag_table_characters'] = 'Caracteres';
$string['rag_table_chunks'] = 'Chunks';
$string['rag_table_embeddings'] = 'Vectores semánticos';
$string['rag_table_index_mode'] = 'Modo del índice';
$string['rag_table_actions'] = 'Acciones';
$string['rag_index_mode_lexical'] = 'Léxico local';
$string['rag_index_mode_semantic'] = 'Vectorial DeepSeek';
$string['rag_index_mode_pending'] = 'Pendiente';
$string['rag_action_markdown'] = 'Ver Markdown';
$string['rag_action_download_markdown'] = 'Descargar Markdown';
$string['rag_action_approve'] = 'Aprobar e indexar';
$string['rag_action_retry'] = 'Reintentar';
$string['rag_action_regenerate'] = 'Regenerar Markdown';
$string['rag_documentstatus_attention'] = 'La base conserva los documentos indexados, pero existen archivos que requieren revisión.';
$string['rag_status_uploaded'] = 'Cargado';
$string['rag_status_processing'] = 'Procesando';
$string['rag_status_extracted'] = 'Extraído';
$string['rag_status_pending_review'] = 'Pendiente de revisión';
$string['rag_status_ready'] = 'Aprobado';
$string['rag_status_indexing'] = 'Indexando';
$string['rag_status_indexed'] = 'Indexado';
$string['rag_status_extraction_failed'] = 'Falló la extracción';
$string['rag_status_pending_ocr'] = 'Pendiente de OCR';
$string['rag_status_embedding_failed'] = 'Falló el análisis semántico';
$string['rag_status_inactive'] = 'Inactivo';
$string['errorupgradingschema'] = 'No se pudo completar la ampliación segura del esquema de VTutor Pro: {$a}';

// VTutor Pro Institucional v10.3 - HITL y rutas pedagógicas.
$string['hitlgovernance'] = 'Gobernanza IA y Human-in-the-Loop';
$string['hitlgovernance_desc'] = 'Define el principio institucional obligatorio: VTutor Pro puede orientar, sugerir rutas y generar alertas, pero no toma decisiones finales automáticas.';
$string['hitl_enabled'] = 'Activar declaración Human-in-the-Loop';
$string['hitl_enabled_desc'] = 'Muestra y aplica el principio Human-in-the-Loop en respuestas, rutas y documentación del plugin.';
$string['routes_enabled'] = 'Activar motor de rutas pedagógicas';
$string['routes_enabled_desc'] = 'Permite que VTutor Pro sugiera rutas de trabajo basadas en Tutoría, Refuerzo, Autoevaluación y Recursos.';
$string['routes_append_student_response'] = 'Agregar ruta sugerida al final de la respuesta del estudiante';
$string['routes_append_student_response_desc'] = 'Si está activo, VTutor Pro anexará una ruta pedagógica orientativa a las respuestas dirigidas al estudiante.';

$string['routes_dedup_minutes'] = 'Minutos para evitar duplicados de rutas';
$string['routes_dedup_minutes_desc'] = 'Tiempo mínimo para no registrar nuevamente la misma ruta sugerida al mismo estudiante dentro del mismo curso. Valor recomendado: 30.';
$string['hitl_policy_text'] = 'Política institucional Human-in-the-Loop';
$string['hitl_policy_text_desc'] = 'Texto oficial de gobernanza que se aplica a la operación institucional de VTutor Pro.';
$string['hitl_policy_text_default'] = 'VTutor Pro aplica el principio Human-in-the-Loop: la IA, ML, Deep Learning o STI pueden generar alertas, recomendaciones, análisis, predicciones e itinerarios sugeridos, pero nunca toman decisiones finales automáticas sobre estudiantes o procesos institucionales. Toda decisión académica, pedagógica, administrativa o institucional debe ser revisada, validada y ejecutada por una persona autorizada.';
$string['hitl_statement_short'] = 'Principio Human-in-the-Loop: las rutas generadas por VTutor Pro son recomendaciones de apoyo. La decisión final corresponde siempre a una persona autorizada.';
$string['routespage'] = 'Rutas pedagógicas VTutor Pro';
$string['routespage_intro'] = 'Esta vista permite revisar las rutas pedagógicas sugeridas por VTutor Pro y registrar validación humana docente o institucional.';
$string['routes_no_records'] = 'Todavía no existen rutas sugeridas para este curso.';
$string['routes_action_saved'] = 'La decisión humana sobre la ruta fue registrada.';
$string['routes_table_date'] = 'Fecha';
$string['routes_table_student'] = 'Estudiante';
$string['routes_table_route'] = 'Ruta sugerida';
$string['routes_table_status'] = 'Estado';
$string['routes_table_action'] = 'Validación humana';
$string['routes_action_validated'] = 'Validar';
$string['routes_action_adjusted'] = 'Ajustar';
$string['routes_action_followup'] = 'Seguimiento';
$string['routes_action_rejected'] = 'Rechazar';
$string['routes_controlcenter_link'] = '🧭 Rutas pedagógicas sugeridas';
$string['privacy:metadata:block_ai_tutor_route_log'] = 'Registro mínimo de rutas pedagógicas sugeridas por VTutor Pro para acompañamiento y validación humana.';
$string['privacy:metadata:block_ai_tutor_route_log:courseid'] = 'ID del curso en el que se sugirió la ruta.';
$string['privacy:metadata:block_ai_tutor_route_log:userid'] = 'ID del usuario al que se sugirió la ruta.';
$string['privacy:metadata:block_ai_tutor_route_log:routekey'] = 'Tipo técnico de ruta pedagógica sugerida.';
$string['privacy:metadata:block_ai_tutor_route_log:routename'] = 'Nombre visible de la ruta pedagógica sugerida.';
$string['privacy:metadata:block_ai_tutor_route_log:status'] = 'Estado de la ruta sugerida.';
$string['privacy:metadata:block_ai_tutor_route_log:teachervalidated'] = 'Indica si la ruta fue validada por una persona autorizada.';
$string['privacy:metadata:block_ai_tutor_route_log:human_decision'] = 'Decisión humana registrada sobre la ruta sugerida.';
$string['privacy:metadata:block_ai_tutor_route_log:timecreated'] = 'Fecha de creación de la ruta sugerida.';
$string['coursemap_title'] = 'Mapa pedagógico del curso';
$string['coursemap_rebuild_button'] = 'Actualizar mapa pedagógico del curso';
$string['coursemap_rebuilt'] = 'Mapa pedagógico actualizado. Elementos registrados: {$a}.';
$string['coursemap_section'] = 'Sección';
$string['coursemap_type'] = 'Tipo pedagógico';
$string['coursemap_activity'] = 'Recurso o actividad';
$string['coursemap_module'] = 'Módulo Moodle';
$string['coursemap_type_tutorial'] = 'Tutoría';
$string['coursemap_type_reinforcement'] = 'Refuerzo';
$string['coursemap_type_selfassessment'] = 'Autoevaluación';
$string['coursemap_type_resource'] = 'Recurso';
$string['coursemap_type_evidence'] = 'Evidencia';
$string['routes_suggestions_title'] = 'Rutas sugeridas para validación docente';

$string['diagnosticobutton'] = 'Diagnóstico VTutor';
$string['diagnosticopage'] = 'Diagnóstico VTutor Pro V-Zona';
$string['diagnosticohelp'] = 'Esta página verifica tablas, configuración IA, archivos cargados, fragmentos RAG y estado de reindexación del curso. Sirve para aislar fallas de carga documental, upgrade incompleto o error AJAX del tutor.';
$string['diagnosticosectionconfig'] = 'Configuración activa';
$string['diagnosticosectionschema'] = 'Esquema de base de datos';
$string['diagnosticosectioncourse'] = 'Estado del curso';
$string['diagnosticosectionfiles'] = 'Archivos en base documental';
$string['runtime_identity_version'] = 'Soy VTutor Pro V-Zona. Release instalado: {$a->release}. Build: {$a->build}.';
$string['runtime_identity_model'] = 'El proveedor LLM activo para esta conversación es {$a->provider} y el modelo efectivo enviado a su API es {$a->model}.';
$string['runtime_identity_profile'] = 'Tu perfil efectivo, determinado por los permisos de Moodle, es: {$a}.';
$string['runtime_identity_secret_protected'] = 'Las credenciales, API keys, tokens y secretos están protegidos y no se muestran en el chat.';
$string['runtime_identity_role_locked'] = 'Una instrucción escrita no cambia el perfil ni concede permisos; conservaré el perfil efectivo asignado por Moodle.';
$string['runtime_profile_admin'] = 'Administrador';
$string['runtime_profile_teacher'] = 'Docente';
$string['runtime_profile_student'] = 'Estudiante';
$string['rag_relational_fidelity_notice'] = 'Nota de fidelidad documental: las fuentes recuperadas permiten relacionar estos conceptos; para mantener una formulación conservadora, la respuesta los vincula sin atribuirles causalidad directa.';
$string['rag_relational_fidelity_fallback'] = 'Las fuentes recuperadas permiten tratar los conceptos solicitados como elementos relacionados del tema, pero la respuesta generada intentó organizarlos como una cadena de causa y efecto que no puede sostenerse con seguridad. Para preservar la fidelidad documental, no presentaré esa síntesis. Revise las fuentes anexas o formule una pregunta separada sobre cada concepto.';

// Privacy metadata for external AI processing and HITL validation.
$string['privacy:metadata:block_ai_tutor_route_log:triggerreason'] = 'Razón resumida que activó la sugerencia de ruta pedagógica.';
$string['privacy:metadata:block_ai_tutor_route_log:validatedby'] = 'ID de la persona autorizada que registró la validación humana.';
$string['privacy:metadata:block_ai_tutor_route_log:timevalidated'] = 'Fecha y hora de la validación humana.';
$string['privacy:metadata:block_ai_tutor_route_log:timemodified'] = 'Fecha y hora de la última modificación del registro.';
$string['privacy:metadata:ai_provider'] = 'VTutor puede enviar datos de conversación y contexto a un proveedor de IA externo configurado por la institución para generar la respuesta.';
$string['privacy:metadata:ai_provider:message'] = 'Mensaje actual enviado por el usuario.';
$string['privacy:metadata:ai_provider:conversationhistory'] = 'Historial reciente de conversación incluido según la configuración institucional.';
$string['privacy:metadata:ai_provider:coursecontext'] = 'Contexto del curso necesario para formular la respuesta.';
$string['privacy:metadata:ai_provider:retrievedcontext'] = 'Fragmentos documentales recuperados por el Mini-RAG cuando está habilitado.';
$string['privacy:metadata:ai_provider:userprofile'] = 'Perfil efectivo de Moodle utilizado para ajustar la interacción y preservar permisos.';

$string['errorschemamissing'] = 'Falta la tabla requerida {$a}. Complete la actualización de Moodle desde Administración del sitio → Notificaciones antes de usar VTutor.';

$string['ai_tutor:manage'] = 'Gestionar configuración y documentos de VTutor';
$string['ai_tutor:manageroutes'] = 'Gestionar y validar rutas Human-in-the-Loop de VTutor';

$string['history_retention_days'] = 'Retención del historial de chat';
$string['history_retention_days_desc'] = 'Días que se conserva el historial almacenado por VTutor antes de la limpieza programada. Ajuste este valor a la política institucional de privacidad.';

// Conversational routing: zero-cost social turns must never be blocked by strict RAG.
$string['conversation_greeting'] = 'Hola. ¿En qué puedo ayudarte con este curso?';
$string['conversation_thanks'] = 'Con gusto. Si necesitas revisar un tema, actividad o documento del curso, puedo ayudarte.';
$string['conversation_ack'] = 'Perfecto. ¿Qué tema, actividad o documento quieres revisar?';
$string['conversation_farewell'] = 'Hasta luego. Cuando quieras continuar con el curso, aquí estaré para ayudarte.';
