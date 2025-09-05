<?php

/**
 * File: /model/e2pdf-bulk.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Bulk extends Model_E2pdf_Model {

    private $table;
    private $bulk = array();

    public function __construct() {
        global $wpdb;
        parent::__construct();
        $this->table = $wpdb->prefix . 'e2pdf_bulks';
        if (defined('NONCE_KEY')) {
            $this->key = hash('sha256', md5(NONCE_KEY));
        } else {
            $this->key = hash('sha256', md5(get_option('e2pdf_nonce_key')));
        }
    }

    public function load($bulk_id) {
        global $wpdb;

        $bulk = false;
        if ($this->helper->get('cache')) {
            $bulk = wp_cache_get($bulk_id, 'e2pdf_bulks');
        }

        if ($bulk === false) {
            $this->helper->load('cache')->pre_objects_cache();
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $bulk = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE ID = %d', $bulk_id), ARRAY_A);
            if ($this->helper->get('cache')) {
                wp_cache_set($bulk_id, $bulk, 'e2pdf_bulks');
            }
        }

        if ($bulk) {
            $this->bulk = $bulk;
            $template = new Model_E2pdf_Template();
            if ($this->get('template_id') && $template->load($this->get('template_id'), false)) {
                $this->set('template', $template);
            }
            $this->set('options', $this->helper->load('convert')->unserialize($bulk['options']));
            $this->set('datasets', $this->helper->load('convert')->unserialize($bulk['datasets']));
            return true;
        }
        return false;
    }

    // load by uid
    public function load_by_uid($uid = false) {
        global $wpdb;
        $bulk = false;
        if ($this->helper->get('cache')) {
            $bulk = wp_cache_get($uid, 'e2pdf_bulks_uids');
        }
        if ($bulk === false) {
            $this->helper->load('cache')->pre_objects_cache();
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $bulk = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE uid = %s', $uid), ARRAY_A);
            if ($this->helper->get('cache')) {
                wp_cache_set($uid, $bulk, 'e2pdf_bulks_uids');
            }
        }

        if ($bulk) {
            $this->bulk = $bulk;
            $this->set('options', $this->helper->load('convert')->unserialize($bulk['options']));
            $this->set('datasets', $this->helper->load('convert')->unserialize($bulk['datasets']));
            return true;
        }
        return false;
    }

    public function load_by_active_bulk() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $bulk = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE status = %s OR status = %s ORDER BY ID ASC', 'pending', 'busy'), ARRAY_A);
        if ($bulk) {
            $this->load($bulk['ID']);
            return true;
        }
        return false;
    }

    public function get($key) {
        if (isset($this->bulk[$key])) {
            return $this->bulk[$key];
        } else {
            switch ($key) {
                case 'uid':
                    $value = md5(md5(time()) . md5($this->key));
                    break;
                case 'status':
                    $value = 'pending';
                    break;
                case 'count':
                case 'total':
                    $value = '0';
                    break;
                case 'options':
                    $value = array();
                    break;
                default:
                    $value = '';
                    break;
            }
            return $value;
        }
    }

    public function set($key, $value) {
        $this->bulk[$key] = $value;
        return true;
    }

    public function bulk() {
        return $this->bulk;
    }

    public function pre_save() {
        $bulk = array(
            'uid' => $this->get('uid'),
            'template_id' => $this->get('template_id'),
            'count' => $this->get('count'),
            'total' => $this->get('total'),
            'dataset' => $this->get('dataset'),
            'datasets' => serialize($this->get('datasets')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'options' => serialize($this->get('options')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'status' => $this->get('status'),
        );

        if (!$this->get('ID')) {
            $bulk['created_at'] = current_time('mysql', 1);
        }

        return $bulk;
    }

    public function save() {
        global $wpdb;
        $bulk = $this->pre_save();

        $show_errors = false;
        if ($wpdb->show_errors) {
            $wpdb->show_errors(false);
            $show_errors = true;
        }

        if ($this->get('ID')) {
            $where = array(
                'ID' => $this->get('ID'),
            );
            $success = $wpdb->update($this->get_table(), $bulk, $where);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->update($this->get_table(), $bulk, $where);
            }
        } else {
            $success = $wpdb->insert($this->get_table(), $bulk);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->insert($this->get_table(), $bulk);
            }
            $this->set('ID', $wpdb->insert_id);
        }

        if ($show_errors) {
            $wpdb->show_errors();
        }

        if ($this->helper->get('cache') && $this->get('ID')) {
            wp_cache_delete($this->get('ID'), 'e2pdf_bulks');
            wp_cache_delete($this->get('uid'), 'e2pdf_bulks_uids');
        }

        return $this->get('ID');
    }

    public function get_table() {
        return $this->table;
    }

    public function get_active_status() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $status = $wpdb->get_var($wpdb->prepare('SELECT status FROM `' . $this->get_table() . '` WHERE ID = %d', $this->get('ID')));
        return $status;
    }

    // delete
    public function delete() {
        global $wpdb;
        if ($this->get('ID')) {

            $where = array(
                'ID' => $this->get('ID'),
            );
            $wpdb->delete($this->get_table(), $where);

            if ($this->helper->get('cache') && $this->get('ID')) {
                wp_cache_delete($this->get('ID'), 'e2pdf_bulks');
                wp_cache_delete($this->get('uid'), 'e2pdf_bulks_uids');
            }

            $pdf_dir = $this->helper->get('bulk_dir') . $this->get('uid') . '/';
            if (file_exists($pdf_dir)) {
                $this->helper->delete_dir($pdf_dir);
            }

            $zip_path = $this->helper->get('bulk_dir') . $this->get('uid') . '.zip';
            if (file_exists($zip_path)) {
                unlink($zip_path);
            }
        }
    }
}
