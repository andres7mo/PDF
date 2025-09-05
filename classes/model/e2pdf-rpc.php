<?php

/**
 * File: /model/e2pdf-rpc.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Rpc extends Model_E2pdf_Model {

    protected $options = array(
        'get' => array(),
    );

    public function __construct($url) {
        parent::__construct();
        $request_url = explode('/e2pdf-rpc/', $url);
        if (isset($request_url[1])) {
            list( $version, $method, $action ) = array_pad(explode('/', wp_parse_url($request_url[1], PHP_URL_PATH)), 3, '');
            $this->set('version', $version);
            $this->set('method', $method);
            $this->set('action', $action);
            $this->set('get', wp_parse_args(wp_parse_url($request_url[1], PHP_URL_QUERY)));
        }
    }

    public function get($key) {
        if (isset($this->options[$key])) {
            $value = $this->options[$key];
            if ($key == 'version' && !in_array($value, array('v1'), true)) {
                $value = 'v1';
            }
        } else {
            switch ($key) {
                case 'version':
                    $value = 'v1';
                    break;
                default:
                    $value = '';
                    break;
            }
        }
        return $value;
    }

    public function get_arg($key) {
        return isset($this->options['get'][$key]) ? sanitize_text_field(wp_unslash($this->options['get'][$key])) : '';
    }

    public function set($key, $value) {
        $this->options[$key] = $value;
    }
}
