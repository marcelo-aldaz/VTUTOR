<?php
// blocks/ai_tutor/block_ai_tutor.php
defined('MOODLE_INTERNAL') || die();

class block_ai_tutor extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ai_tutor');
    }

    public function applicable_formats() {
        return ['course' => true];
    }

    public function get_content() {
        global $COURSE, $PAGE, $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Verificar permisos
        $context = context_course::instance($COURSE->id);
        if (!has_capability('block/ai_tutor:use', $context)) {
            $this->content->text = get_string('nopermissions', 'error');
            return $this->content;
        }

        // Datos para la plantilla
        $canadmin = has_capability('moodle/site:config', context_system::instance());
        $data = [
            'courseid' => $COURSE->id,
            'coursename' => format_string($COURSE->fullname),
            'userid' => $USER->id,
            'showadminlink' => $canadmin,
            'adminurl' => (new moodle_url('/blocks/ai_tutor/admin.php'))->out(false),
        ];

        // Renderizar interfaz
        $this->content->text = $OUTPUT->render_from_template('block_ai_tutor/chat_interface', $data);
        
        // Cargar JavaScript
        $cmid = optional_param('id', 0, PARAM_INT);
        $PAGE->requires->js_call_amd('block_ai_tutor/chat', 'init', [$COURSE->id, $cmid]);

        return $this->content;
    }
}