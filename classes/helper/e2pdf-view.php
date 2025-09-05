<?php

/**
 * E2Pdf View Helper
 * 
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_View {

    protected static $instance = null;
    private $view_dir;
    public $uri;
    public $page;
    public $controller;
    public $helper;
    public $notification;
    public $tpl;
    public $tpl_page;
    public $view = null;
    public $tpl_args = null;
    public $get = null;
    public $post = null;
    public $files = null;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->view = new stdClass();
        $this->uri = home_url(add_query_arg(null, null));
        $this->get = new Helper_E2pdf_Get($this->uri);
        $this->post = new Helper_E2pdf_Post();
        $this->files = new Helper_E2pdf_Files();
        $this->page = $this->get->get_page();
        $this->helper = Helper_E2pdf_Helper::instance();
        $this->notification = new Model_E2pdf_Notification();
        $this->view_dir = $this->helper->get('plugin_dir') . 'classes/view/';
    }

    /**
     * Get Tpl Value
     * @param string $key - Tpl key
     * @return mixed - Tpl value
     */
    public function render($type, $element, $args = array()) {
        $this->tpl_args = new Helper_E2pdf_Tplargs($args);
        $this->tpl = $this->view_dir . $type . '/' . $element . '.php';
        if (file_exists($this->tpl)) {
            include($this->tpl);
        }
    }

    /**
     * Get Tpl Value
     * @param string $key - Tpl key
     * @return mixed - Tpl value
     */
    public function render_metabox($post, $metabox) {
        $this->render('metaboxes', $metabox['args']['tpl']);
    }

    /**
     * Get Tpl Value
     * @param string $key - Tpl key
     * @return mixed - Tpl value
     */
    public function render_page() {
        $this->tpl_page = $this->view_dir . 'page-' . $this->page . '.php';
        $controller = $this->page_to_controller($this->page);
        if (class_exists($controller)) {
            $this->controller = new $controller();
            if ($this->get->get('action')) {
                $method = $this->get->get('action') . '_action';
            } else {
                $method = 'index_action';
            }
            if (method_exists($this->controller, $method)) {
                $this->controller->$method();
                $this->view = $this->controller->view;
            } else {
                $this->handle_404();
            }
        }

        if (file_exists($this->tpl_page)) {
            include($this->tpl_page);
        }
    }

    public function render_frontend_page() {
        if ($this->page == 'e2pdf-download' || get_query_var('e2pdf')) {
            $this->tpl_page = $this->view_dir . '/frontend/page-e2pdf-download.php';
            $controller = $this->page_to_controller('e2pdf-download', true);
            if (class_exists($controller)) {
                $this->controller = new $controller();
                if ($this->get->get('action')) {
                    $method = $this->get->get('action') . '_action';
                } else {
                    $method = 'index_action';
                }
                if (method_exists($this->controller, $method)) {
                    $this->controller->$method();
                } else {
                    $this->handle_404();
                }
            }

            if (file_exists($this->tpl_page)) {
                include($this->tpl_page);
            }
        } elseif ($this->page == 'e2pdf-activation') {
            if (get_transient('e2pdf_activation_key')) {
                $activation_key = get_transient('e2pdf_activation_key');
                if ($this->get->get('key') == $activation_key) {
                    if (ob_get_length() > 0) {
                        while (@ob_end_clean());
                    }
                    header('Content-Type: text/plain');
                    echo $activation_key;
                    die();
                }
            }
        }
    }

    public function rpc() {
        if (ob_get_length() > 0) {
            while (@ob_end_clean());
        }
        $rpc = new Model_E2pdf_Rpc($this->helper->load('server')->get('REQUEST_URI'));
        $controller = $this->page_to_controller('e2pdf-rpc-' . $rpc->get('version'), true);
        if (class_exists($controller)) {
            $this->controller = new $controller();
            $method = $rpc->get('method');
            if ($method && method_exists($this->controller, $method)) {
                $this->controller->$method($rpc);
            }
        }
    }

    /**
     * Get Tpl Value
     * @param string $key - Tpl key
     * @return mixed - Tpl value
     */
    public function page_to_controller($page, $frontend = false) {
        $controller = array(
            'Controller'
        );
        if ($frontend) {
            $controller[] = 'Frontend';
        }
        $controller = array_merge($controller, explode('-', $page));
        $controller = array_map('ucfirst', $controller);
        return implode("_", $controller);
    }

    /**
     * Redirect to page
     * @param string $location - Full url where to redirect
     */
    public function redirect($location) {
        if (headers_sent()) {
            die("<script>window.location='{$location}';</script>");
        } else {
            wp_redirect($location);
            exit();
        }
    }

    /**
     * Add notification
     * @param string $type - Type of notification error|update
     * @param string $text - Text of notification
     */
    public function add_notification($type, $text) {
        $this->notification->add_notification($type, $text);
    }

    /**
     * Get notifications
     * @return array - List of notifications
     */
    public function get_notifications() {
        $notifications = $this->notification->get_notifications();
        return $notifications;
    }

    /**
     * Force Json response
     * @param array $data - Array of data
     * @return json
     */
    public function json_response($data = array()) {
        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (function_exists('wp_json_encode')) {
            echo wp_json_encode($data, JSON_FORCE_OBJECT);
        } else {
            echo json_encode($data, JSON_FORCE_OBJECT);
        }
        wp_die();
    }

    public function json_response_ajax($data = array(), $status = 200) {
        if (ob_get_length() > 0) {
            while (@ob_end_clean());
        }
        status_header($status);
        header('X-Robots-Tag: noindex, nofollow');
        @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        if (function_exists('wp_json_encode')) {
            echo wp_json_encode($data, JSON_FORCE_OBJECT);
        } else {
            echo json_encode($data, JSON_FORCE_OBJECT);
        }
        exit;
    }

    /**
     * Force close browser tab from PHP
     */
    public function close_tab_response() {
        echo "
            <script>
                self.close();
            </script>
            ";
    }

    /**
     * Force Download response
     * @param string $format - Format of file
     * @param string $file - Base64 Encoded File
     * @param string $name - Name of file when download
     * @param string $disposition - Disposition of file inline|attachment
     * @return string
     */
    public function download_response($format, $file, $name = '', $disposition = '', $fpassthru = false, $preview = false) {

        $content_length = true;
        /**
         * External Links – nofollow, noopener & new window compatibility fix filter 
         * https://wordpress.org/plugins/wp-external-links/
         */
        add_filter('wpel_apply_settings', '__return_false');

        /**
         * Compatibility fix with TranslatePress
         * https://wordpress.org/plugins/translatepress-multilingual/
         */
        add_filter('trp_stop_translating_page', '__return_true');

        if (ob_get_length() > 0) {
            while (@ob_end_clean());
        }

        /**
         * PHP 8.0.17 & PHP 8.1.4 compatibility with zlib enabled
         */
        header_remove("Content-Encoding");

        /**
         * W3 Total Cache + OVH compatibility fix
         * https://wordpress.org/plugins/w3-total-cache/
         */
        if (function_exists('w3tc_config')) {
            $w3tc_config = w3tc_config();
            if ($w3tc_config->get_boolean('browsercache.enabled') && $w3tc_config->get_boolean('browsercache.html.compression') && function_exists('php_uname') && false !== stripos(@php_uname(), '.ovh.net')) {
                $content_length = false;
            }
        }

        /**
         * WP Rocket + OVH compatibility fix
         * https://wp-rocket.me/
         */
        if (function_exists('rocket_generate_config_file') && !function_exists('WP_Rocket\Helpers\htaccess\remove_gzip\flush_wp_rocket') && function_exists('php_uname') && false !== stripos(@php_uname(), '.ovh.net')) {
            $content_length = false;
        }

        if (!$name) {
            $name = time();
        }
        $name = rawurlencode($this->helper->load('convert')->to_file_name($name));

        status_header(200);
        header('Content-Transfer-Encoding: binary');
        header('X-Robots-Tag: noindex, nofollow');
        switch ($format) {
            case 'pdf':
                if ($content_length) {
                    header('Content-Length: ' . strlen(base64_decode($file)));
                }
                if (!$disposition) {
                    $disposition = 'inline';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.pdf");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.pdf\"");
                }
                header("Content-Type: application/pdf");
                if ($preview && $disposition == 'inline' && isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
                    header('Cache-Control: private');
                }
                break;
            case 'jpg':
                if ($content_length) {
                    header('Content-Length: ' . strlen(base64_decode($file)));
                }
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.jpg");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.jpg\"");
                }
                header('Content-Type: image/jpeg');
                if ($preview && $disposition == 'inline' && isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
                    header('Cache-Control: private');
                }
                break;
            case 'zip':
                if ($content_length) {
                    if ($fpassthru) {
                        if (ini_get('zlib.output_compression')) {
                            ini_set('zlib.output_compression', 'Off');
                        }
                        header('Content-Length: ' . filesize(trim($file)));
                    } else {
                        header('Content-Length: ' . strlen(base64_decode($file)));
                    }
                }
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.zip");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.zip\"");
                }
                header('Content-Type: application/zip');
                break;
            case 'xml':
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.xml");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.xml\"");
                }
                header('Content-Type: text/xml');
                break;
            case 'txt':
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.txt");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.txt\"");
                }
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                break;
            case 'doc':
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.txt");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.txt\"");
                }
                header('Content-Type: application/msword');
                break;
            case 'docx':
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.txt");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.txt\"");
                }
                header('Content-Type: text/html');
                break;

            case 'php':
                if (!$disposition) {
                    $disposition = 'attachment';
                }
                if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
                    header("Content-Disposition: {$disposition}; filename*=UTF-8''{$name}.php");
                } else {
                    header("Content-Disposition: {$disposition}; filename=\"{$name}.php\"");
                }
                header('Content-Type: text/html');
                break;
            default:
                break;
        }

        if ($fpassthru) {
            $handle = @fopen($file, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
        } else {
            echo base64_decode($file);
        }
    }

    /**
     * Check if exists in array
     * @param string $value - Array Key
     * @param mixed $compare 
     * @param array $data - Array of data
     * @return bool
     */
    public function exist($value, $compare = false, $data = array()) {
        if (isset($data[$value]) && $value === $compare) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Force Error
     * @param string $code - Error Code
     */
    public function handle_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        if (is_admin()) {
            wp_die('404');
        }
    }

    /**
     * Set var available to view template
     * @param string $key - Key of value
     * @param mixed $value - Value
     */
    public function view($key, $value) {
        $this->view->$key = $value;
    }

    /**
     * Check if Array/Object Empty
     * @param mixed $data - Object/Array to check
     * @return bool
     */
    public function is_empty($data) {
        if (is_object($data) || is_array($data)) {

            if (is_object($data)) {
                $data = (array) $data;
            }
            if (count($data) > 0) {
                return false;
            }
        }

        return true;
    }

    public function message($key) {
        $message = '';
        switch ($key) {
            case 'wp_verify_nonce_error':
                $message = 'E2Pdf Bad Request';
                break;
            default:
                break;
        }
        return $message;
    }
}
