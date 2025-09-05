<?php

/**
 * E2Pdf Debug Controller
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Controller_E2pdf_Debug extends Helper_E2pdf_View {

    /**
     * @url admin.php?page=e2pdf-debug
     */
    public function index_action() {
        if ($this->post->get('_wpnonce')) {
            if (wp_verify_nonce($this->post->get('_wpnonce'), 'e2pdf_debug')) {
                if ($this->post->get('e2pdf_updated')) {
                    update_option('e2pdf_version', '1.00.00');
                    $this->add_notification('update', sprintf(__('Success: %s', 'e2pdf'), __('Reinitialize Activation Hooks', 'e2pdf')));
                    $this->redirect($this->helper->get_url(array('page' => 'e2pdf-debug')));
                }
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        }
        $this->view('api', new Model_E2pdf_Api());
        if (ini_get('disable_functions')) {
            $disabled_functions = explode(',', ini_get('disable_functions'));
        } else {
            $disabled_functions = array();
        }
        $this->view('disabled_functions', $disabled_functions);
    }

    /**
     * @url admin.php?page=e2pdf-debug&action=db
     */
    public function db_action() {
        global $wpdb;
        if ($this->post->get('_wpnonce')) {
            if (wp_verify_nonce($this->post->get('_wpnonce'), 'e2pdf_debug')) {
                if ($this->post->get('e2pdf_db_repair')) {
                    $db_prefix = $wpdb->prefix;
                    $this->helper->load('db')->db_repair($db_prefix);
                    $this->add_notification('update', sprintf(__('Success: %s', 'e2pdf'), __('Repair DB', 'e2pdf')));
                } elseif ($this->post->get('e2pdf_db_repair_collate')) {
                    $db_prefix = $wpdb->prefix;
                    $this->helper->load('db')->db_repair_collate($db_prefix);
                    $this->add_notification('update', sprintf(__('Success: %s', 'e2pdf'), __('Repair DB Collate', 'e2pdf')));
                } elseif ($this->post->get('e2pdf_db')) {
                    $db_prefix = $wpdb->prefix;
                    $this->helper->load('db')->db_init($db_prefix);
                    $this->add_notification('update', sprintf(__('Success: %s', 'e2pdf'), __('Reinitialize DB Hooks', 'e2pdf')));
                }
                $this->redirect(
                        $this->helper->get_url(
                                array(
                                    'page' => 'e2pdf-debug',
                                    'action' => 'db',
                                )
                        )
                );
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        }
        $this->view('db_structure', $this->helper->load('db')->db_structure($wpdb->prefix, true));
        $this->view('db_check_collate', $this->helper->load('db')->db_check_collate($wpdb->prefix));
    }

    /**
     * @url admin.php?page=e2pdf-debug&action=phpinfo
     */
    public function phpinfo_action() {
        $this->view('phpinfo', $this->get_php_info());
    }

    /**
     * @url admin.php?page=e2pdf-debug&action=requests
     */
    public function connections_action() {
        $connections = array(
            'self_connection' => array(),
            'api_connection_upload' => array(),
            'api_connection_download' => array(),
        );

        $url = plugins_url('img/loader.svg?v=' . time(), $this->helper->get('plugin_file_path'));
        $image = $this->helper->load('image')->get_by_url($url);
        if ($image !== '<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" width="20px" height="20px" viewBox="0 0 128 128" xml:space="preserve"><g><path d="M63.9.45A63.46 63.46 0 1 1 .47 63.9 63.46 63.46 0 0 1 63.9.46zM41.8 19.38a22.27 22.27 0 1 1-22.3 22.27 22.27 22.27 0 0 1 22.27-22.27z" fill="#808080" fill-rule="evenodd"/><animateTransform attributeName="transform" type="rotate" from="0 64 64" to="360 64 64" dur="960ms" repeatCount="indefinite"></animateTransform></g></svg>') {
            $connections['self_connection'] = array(
                'error' => '',
            );
        }

        $file = 10000000;
        $times = array();
        $times[] = microtime(true);
        $model_e2pdf_api = new Model_E2pdf_Api();
        $model_e2pdf_api->set(
                array(
                    'action' => 'common/connection',
                    'data' => array(
                        'upload' => str_repeat(" ", $file),
                    ),
                )
        );
        $request = $model_e2pdf_api->request();
        $times[] = microtime(true);
        if (isset($request['error'])) {
            $connections['api_connection_upload'] = array(
                'error' => $request['error'],
                'result' => '',
            );
        } else {
            $connections['api_connection_upload'] = array(
                'result' => $this->helper->load('convert')->from_bytes($file / ($times[1] - $times[0])) . '/s (' . $this->helper->load('convert')->from_bytes($file) . ' in ' . number_format(($times[1] - $times[0]), 2) . 's)'
            );
        }

        $times[] = microtime(true);
        $model_e2pdf_api = new Model_E2pdf_Api();
        $model_e2pdf_api->set(
                array(
                    'action' => 'common/connection',
                    'data' => array(
                        'download' => true,
                    ),
                )
        );
        $request = $model_e2pdf_api->request();
        $times[] = microtime(true);
        if (isset($request['error'])) {
            $connections['api_connection_download'] = array(
                'error' => $request['error'],
                'result' => '',
            );
        } else {
            $connections['api_connection_download'] = array(
                'result' => $this->helper->load('convert')->from_bytes($file / ($times[3] - $times[2])) . '/s (' . $this->helper->load('convert')->from_bytes($file) . ' in ' . number_format(($times[3] - $times[2]), 2) . 's)'
            );
        }
        $this->view('connections', $connections);
    }

    /**
     * Get phpinfo
     * @return string - PHP Info
     */
    public function get_php_info() {
        ob_start();
        phpinfo();
        $contents = ob_get_contents();
        ob_end_clean();
        $php_info = (str_replace('module_Zend Optimizer', 'module_Zend_Optimizer', preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $contents)));
        return $php_info;
    }
}
