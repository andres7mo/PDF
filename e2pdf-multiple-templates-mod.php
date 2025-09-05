<?php

/**
 * Modificación del plugin E2pdf para permitir 2 templates activos
 * Archivo: e2pdf-multiple-templates-mod.php
 */

class E2pdf_Multiple_Templates {
    
    private $active_templates = array();
    private $max_active_templates = 2;
    
    public function __construct() {
        add_action('init', array($this, 'init_hooks'));
    }
    
    public function init_hooks() {
        // Hook para modificar la consulta de templates activos
        add_filter('e2pdf_get_templates', array($this, 'get_multiple_active_templates'), 10, 2);
        
        // Hook para el admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Ajax para activar/desactivar templates
        add_action('wp_ajax_e2pdf_toggle_template', array($this, 'ajax_toggle_template'));
        
        // Modificar la función original de template activo
        add_filter('e2pdf_template_is_active', array($this, 'check_multiple_active'), 10, 2);
    }
    
    /**
     * Obtener templates activos (hasta 2)
     */
    public function get_multiple_active_templates($templates, $args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'e2pdf_templates';
        
        // Consulta modificada para obtener hasta 2 templates activos
        $active_templates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE activated = %d 
             ORDER BY priority DESC, updated_at DESC 
             LIMIT %d",
            1,
            $this->max_active_templates
        ));
        
        return $active_templates;
    }
    
    /**
     * Verificar si un template puede estar activo
     */
    public function check_multiple_active($is_active, $template_id) {
        $active_templates = $this->get_active_template_ids();
        
        if (count($active_templates) < $this->max_active_templates) {
            return $is_active;
        }
        
        // Si ya hay 2 activos, solo permitir si este template ya está en la lista
        return in_array($template_id, $active_templates);
    }
    
    /**
     * Obtener IDs de templates activos
     */
    private function get_active_template_ids() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'e2pdf_templates';
        
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table_name} 
             WHERE activated = %d 
             ORDER BY priority DESC, updated_at DESC 
             LIMIT %d",
            1,
            $this->max_active_templates
        ));
        
        return array_map('intval', $results);
    }
    
    /**
     * Activar template con control de límite
     */
    public function activate_template($template_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'e2pdf_templates';
        $active_templates = $this->get_active_template_ids();
        
        // Si ya está activo, no hacer nada
        if (in_array($template_id, $active_templates)) {
            return array('success' => true, 'message' => 'Template ya está activo');
        }
        
        // Si hay espacio, activar directamente
        if (count($active_templates) < $this->max_active_templates) {
            $result = $wpdb->update(
                $table_name,
                array('activated' => 1, 'updated_at' => current_time('mysql')),
                array('id' => $template_id),
                array('%d', '%s'),
                array('%d')
            );
            
            return array('success' => true, 'message' => 'Template activado correctamente');
        }
        
        // Si no hay espacio, desactivar el más antiguo y activar el nuevo
        $oldest_template = end($active_templates);
        
        // Desactivar el más antiguo
        $wpdb->update(
            $table_name,
            array('activated' => 0),
            array('id' => $oldest_template),
            array('%d'),
            array('%d')
        );
        
        // Activar el nuevo
        $wpdb->update(
            $table_name,
            array('activated' => 1, 'updated_at' => current_time('mysql')),
            array('id' => $template_id),
            array('%d', '%s'),
            array('%d')
        );
        
        return array('success' => true, 'message' => 'Template activado (reemplazó al más antiguo)');
    }
    
    /**
     * Desactivar template
     */
    public function deactivate_template($template_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'e2pdf_templates';
        
        $result = $wpdb->update(
            $table_name,
            array('activated' => 0),
            array('id' => $template_id),
            array('%d'),
            array('%d')
        );
        
        return array('success' => true, 'message' => 'Template desactivado correctamente');
    }
    
    /**
     * Ajax handler para activar/desactivar templates
     */
    public function ajax_toggle_template() {
        check_ajax_referer('e2pdf_toggle_template', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes');
        }
        
        $template_id = intval($_POST['template_id']);
        $action = sanitize_text_field($_POST['toggle_action']);
        
        if ($action === 'activate') {
            $result = $this->activate_template($template_id);
        } else {
            $result = $this->deactivate_template($template_id);
        }
        
        wp_send_json($result);
    }
    
    /**
     * Agregar página de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'e2pdf',
            'Templates Múltiples',
            'Templates Múltiples',
            'manage_options',
            'e2pdf-multiple-templates',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Página de administración
     */
    public function admin_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'e2pdf_templates';
        $templates = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY title");
        $active_templates = $this->get_active_template_ids();
        
        ?>
        <div class="wrap">
            <h1>Gestión de Templates Múltiples E2pdf</h1>
            <p>Puedes tener hasta <?php echo $this->max_active_templates; ?> templates activos simultáneamente.</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?php echo $template->id; ?></td>
                            <td><?php echo esc_html($template->title); ?></td>
                            <td>
                                <?php if (in_array($template->id, $active_templates)): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span> Activo
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus" style="color: #ccc;"></span> Inactivo
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($template->priority) ? $template->priority : '0'; ?></td>
                            <td>
                                <?php if (in_array($template->id, $active_templates)): ?>
                                    <button class="button toggle-template" data-template-id="<?php echo $template->id; ?>" data-action="deactivate">
                                        Desactivar
                                    </button>
                                <?php else: ?>
                                    <button class="button button-primary toggle-template" data-template-id="<?php echo $template->id; ?>" data-action="activate">
                                        Activar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.toggle-template').click(function(e) {
                e.preventDefault();
                
                var button = $(this);
                var templateId = button.data('template-id');
                var action = button.data('action');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'e2pdf_toggle_template',
                        template_id: templateId,
                        toggle_action: action,
                        nonce: '<?php echo wp_create_nonce('e2pdf_toggle_template'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error de conexión');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Obtener el template apropiado basado en condiciones
     */
    public function get_appropriate_template($conditions = array()) {
        $active_templates = $this->get_multiple_active_templates(array());
        
        if (empty($active_templates)) {
            return null;
        }
        
        // Si solo hay uno activo, devolverlo
        if (count($active_templates) == 1) {
            return $active_templates[0];
        }
        
        // Lógica para seleccionar entre múltiples templates activos
        // Puedes personalizar esta lógica según tus necesidades
        
        // Ejemplo: seleccionar por prioridad
        usort($active_templates, function($a, $b) {
            $priority_a = isset($a->priority) ? $a->priority : 0;
            $priority_b = isset($b->priority) ? $b->priority : 0;
            return $priority_b - $priority_a;
        });
        
        return $active_templates[0];
    }
}

// Inicializar la clase
new E2pdf_Multiple_Templates();

/**
 * Función helper para obtener template apropiado
 */
function e2pdf_get_active_template($conditions = array()) {
    $multiple_templates = new E2pdf_Multiple_Templates();
    return $multiple_templates->get_appropriate_template($conditions);
}

/**
 * Hook adicional para modificar el comportamiento original de E2pdf
 */
add_filter('e2pdf_model_template_get_active', function($template_id) {
    $multiple_templates = new E2pdf_Multiple_Templates();
    $active_template = $multiple_templates->get_appropriate_template();
    
    return $active_template ? $active_template->id : $template_id;
});

/**
 * Función para instalar/actualizar la tabla si es necesario
 */
function e2pdf_multiple_templates_install() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'e2pdf_templates';
    
    // Verificar si la columna priority existe, si no, agregarla
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM {$table_name} LIKE %s", 
        'priority'
    ));
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN priority INT DEFAULT 0");
    }
}

register_activation_hook(__FILE__, 'e2pdf_multiple_templates_install');

?>