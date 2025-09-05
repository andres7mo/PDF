<?php

/**
 * File: /model/e2pdf-entry.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Entry extends Model_E2pdf_Model {

    private $table;
    private $key;
    private $entry = array();

    public function __construct() {
        global $wpdb;
        parent::__construct();
        $this->table = $wpdb->prefix . 'e2pdf_entries';
        if (defined('NONCE_KEY')) {
            $this->key = hash('sha256', md5(NONCE_KEY));
        } else {
            $this->key = hash('sha256', md5(get_option('e2pdf_nonce_key')));
        }
    }

    public function load_by_uid($uid = false) {
        global $wpdb;
        if (!$uid) {
            $uid = $this->get('uid');
        }
        $entry = false;
        if ($this->helper->get('cache')) {
            $entry = wp_cache_get($uid, 'e2pdf_uid_entries');
        }
        if ($entry === false) {
            $this->helper->load('cache')->pre_objects_cache();
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $entry = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE uid = %s', $uid), ARRAY_A);
            if ($this->helper->get('cache')) {
                wp_cache_set($uid, $entry, 'e2pdf_uid_entries');
            }
        }
        if ($entry) {
            $entry = apply_filters('e2pdf_load_entry_by_uid', $entry, $this);
            $this->entry = $entry;
            $this->set('entry', $this->helper->load('convert')->unserialize($entry['entry']));
            return true;
        }
        return false;
    }

    public function set($key, $value) {
        $this->entry[$key] = $value;
    }

    public function get($key) {
        if (isset($this->entry[$key])) {
            $value = $this->entry[$key];
            if ($key == 'entry') {
                ksort($value);
            }
        } else {
            switch ($key) {
                case 'pdf_num':
                    $value = 0;
                    break;
                case 'entry':
                    $value = array();
                    break;
                case 'uid':
                    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                    $value = md5(md5(serialize($this->get('entry'))) . md5($this->key));
                    break;
                default:
                    $value = '';
                    break;
            }
        }
        return $value;
    }

    public function get_data($key) {
        if (isset($this->entry['entry'][$key])) {
            $value = $this->entry['entry'][$key];
        } else {
            $value = false;
        }
        return $value;
    }

    public function set_data($key, $value) {
        if (!isset($this->entry['entry'])) {
            $this->set('entry', array());
        }
        $this->entry['entry'][$key] = $value;
    }

    public function pre_save() {

        $entry = array(
            'uid' => $this->get('uid'),
            'entry' => serialize($this->get('entry')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'pdf_num' => $this->get('pdf_num'),
        );
        return $entry;
    }

    public function save() {
        global $wpdb;

        $entry = $this->pre_save();

        if ($this->get('ID')) {
            $where = array(
                'ID' => $this->get('ID'),
            );
            $success = $wpdb->update($this->get_table(), $entry, $where);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->update($this->get_table(), $entry, $where);
            }
        } else {
            $success = $wpdb->insert($this->get_table(), $entry);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->insert($this->get_table(), $entry);
            }
            $this->set('ID', $wpdb->insert_id);
            $this->set('uid', $this->get('uid'));
        }

        if ($this->helper->get('cache') && $this->get('ID')) {
            wp_cache_delete($this->get('uid'), 'e2pdf_uid_entries');
        }
    }

    public function delete() {
        global $wpdb;
        if ($this->get('ID')) {
            $where = array(
                'ID' => $this->get('ID'),
            );
            $wpdb->delete($this->get_table(), $where);

            if ($this->helper->get('cache')) {
                wp_cache_delete($this->get('uid'), 'e2pdf_uid_entries');
            }
        }
    }

    public function get_table() {
        return $this->table;
    }
}
