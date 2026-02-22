<?php
// blocks/ai_tutor/lang/es/block_ai_tutor.php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'VTutor - Asistente IA';
$string['block/ai_tutor:addinstance'] = 'Añadir bloque VTutor';
$string['block/ai_tutor:use'] = 'Usar VTutor';

// Configuración
$string['mainsettings'] = 'Configuración Principal';
$string['mainsettings_desc'] = 'Configuración básica del proveedor de Inteligencia Artificial';
$string['provider'] = 'Proveedor de IA';
$string['provider_desc'] = 'Selecciona el proveedor de inteligencia artificial';
$string['apikey'] = 'Clave API';
$string['apikey_desc'] = 'Tu clave de API del proveedor seleccionado';
$string['model'] = 'Modelo de IA';
$string['model_desc'] = 'Nombre del modelo a utilizar (ej: gpt-3.5-turbo)';

$string['baseurl'] = 'Base URL / Endpoint';
$string['baseurl_desc'] = 'URL base del endpoint OpenAI-compatible u Ollama (opcional para OpenAI oficial).';

// Pedagógica
$string['pedagogysettings'] = 'Configuración Pedagógica';
$string['pedagogysettings_desc'] = 'Opciones para controlar el comportamiento del tutor';
$string['guideonly'] = 'Solo orientar (no dar respuestas)';
$string['guideonly_desc'] = 'El tutor guiará a los estudiantes sin proporcionar respuestas directas a tareas';
$string['temperature'] = 'Creatividad (Temperatura)';
$string['temperature_desc'] = 'Valor entre 0.0 (preciso) y 2.0 (creativo). Recomendado: 0.7';
$string['maxtokens'] = 'Máximo de tokens';
$string['maxtokens_desc'] = 'Longitud máxima de la respuesta';

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

// Varios
$string['clearconfirm'] = '¿Estás seguro de que deseas eliminar toda la conversación? Esta acción no se puede deshacer.';
$string['task_cleanup_old_messages'] = 'Limpiar mensajes antiguos de VTutor';

$string['gensettings'] = 'Ajustes de generación';
$string['gensettings_desc'] = 'Controles de salida del modelo.';
$string['advanced'] = 'Avanzado';
$string['advanced_desc'] = 'Ajustes opcionales para integraciones ampliadas.';

$string['adminpage'] = 'VTutor - Configuración rápida';
$string['adminpage_desc'] = 'Página de configuración explícita para entornos donde el menú de ajustes del plugin no se visualiza.';
$string['opensettingspage'] = 'Abrir página estándar de configuración del plugin';
$string['missingconfigfields'] = 'Faltan campos obligatorios de configuración: {$a}';

$string['provider_deepseek'] = 'DeepSeek (API directa)';
$string['provider_qwen'] = 'Qwen (API compatible DashScope)';
$string['provider_openai'] = 'OpenAI';
$string['provider_gemini'] = 'Google Gemini';
$string['provider_openai_compatible'] = 'OpenAI-compatible (endpoint personalizado)';
$string['provider_ollama'] = 'Ollama (local)';
$string['openquickconfig'] = 'Abrir página de configuración rápida';
$string['providerexamples'] = 'Proveedores directos soportados en v9: DeepSeek, Qwen, OpenAI y Gemini. Puede configurar desde esta página o desde Plugins > Bloques.';
$string['provider_hint_deepseek'] = 'DeepSeek: provider=deepseek, baseurl=https://api.deepseek.com/v1, model=deepseek-chat';
$string['provider_hint_qwen'] = 'Qwen (DashScope): provider=qwen, baseurl=https://dashscope.aliyuncs.com/compatible-mode/v1, model=qwen-turbo (o su modelo Qwen habilitado)';
$string['provider_hint_openai'] = 'OpenAI: provider=openai, baseurl=https://api.openai.com/v1, model=gpt-4o-mini (o su modelo habilitado)';
$string['provider_hint_gemini'] = 'Gemini: provider=gemini, baseurl=https://generativelanguage.googleapis.com/v1beta/models, model=gemini-1.5-flash';

$string['rag_enabled'] = 'Activar recuperación RAG';
$string['rag_enabled_desc'] = 'Usar contenido indexado del curso para responder con contexto.';
$string['rag_autolazy'] = 'Autoindexar en la primera pregunta';
$string['rag_autolazy_desc'] = 'Si el curso no tiene índice, construir uno básico automáticamente.';
$string['rag_topk'] = 'Top-k de fragmentos RAG';
$string['rag_topk_desc'] = 'Número de fragmentos recuperados enviados al modelo.';
$string['rag_reindex'] = 'Reindexar contenido del curso (RAG)';
$string['rag_reindex_help'] = 'Ingrese un ID de curso, o deje 0 para reindexar todos los cursos (solo admin).';
$string['rag_reindex_done'] = 'Reindexación RAG completada: {$a}';
$string['courseid'] = 'ID del curso';
$string['ragdisablednotice'] = 'RAG desactivado temporalmente en esta versión estable. Use la rama de pruebas para RAG avanzado.';
