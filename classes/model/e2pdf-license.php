<?php

/**
 * File: /model/e2pdf-license.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_License extends Model_E2pdf_Model {

    private $license;

    public function __construct() {
        parent::__construct();
        $this->load_license();
    }

    public function get($key) {
        if (isset($this->license[$key])) {
            return $this->license[$key];
        } else {
            return false;
        }
    }

    // load license
    public function load_license() {
        $model_e2pdf_api = new Model_E2pdf_Api();
        $model_e2pdf_api->set(
                array(
                    'action' => 'license/info',
                )
        );
        $license = $model_e2pdf_api->request();
        $this->license = $license;
    }

    // reload license
    public function reload_license() {
        $this->load_license();
    }

    // load templates
    public function load_templates() {
        global $wpdb;

        $condition = array(
            'activated' => array(
                'condition' => '=',
                'value' => '1',
                'type' => '%d',
            ),
            'uid' => array(
                'condition' => '=',
                'value' => '',
                'type' => '%s',
            ),
        );
        $where = $this->helper->load('db')->prepare_where($condition);
        $model_e2pdf_template = new Model_E2pdf_Template();

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $tpls = $wpdb->get_results($wpdb->prepare('SELECT `ID` FROM `' . $model_e2pdf_template->get_table() . '`' . $where['sql'] . '', $where['filter']));
        foreach ($tpls as $key => $tpl) {
            $template = new Model_E2pdf_Template();
            $template->load($tpl->ID, false);
            $template->activate();
        }
    }
}
