<?php

/**
 * File: /model/e2pdf-dataset.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Dataset extends Model_E2pdf_Model {

    private $dataset = array();
    private $table;

    public function __construct() {
        global $wpdb;
        parent::__construct();
        $this->table = $wpdb->prefix . 'e2pdf_datasets';
    }

    public function get_table() {
        return $this->table;
    }

    public function load($id, $item, $extension) {
        global $wpdb;

        $condition = array(
            'ID' => array(
                'condition' => '=',
                'value' => $id,
                'type' => '%d',
            ),
            'extension' => array(
                'condition' => '=',
                'value' => $extension,
                'type' => '%s',
            ),
            'item' => array(
                'condition' => '=',
                'value' => $item,
                'type' => '%s',
            ),
        );
        $where = $this->helper->load('db')->prepare_where($condition);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $dataset = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '`' . $where['sql'] . '', $where['filter']), ARRAY_A);

        if ($dataset) {
            $this->dataset = $dataset;
            $this->set('entry', $this->helper->load('convert')->unserialize($dataset['entry']));
            return true;
        }
        return false;
    }

    public function set($key, $value) {
        $this->dataset[$key] = $value;
        return true;
    }

    public function get($key) {
        if (isset($this->dataset[$key])) {
            $value = $this->dataset[$key];
            return $value;
        } else {
            switch ($key) {
                case 'entry':
                    $value = array();
                    break;
                default:
                    $value = '';
                    break;
            }
            return $value;
        }
    }

    public function save() {
        global $wpdb;

        $dataset = array(
            'extension' => $this->get('extension'),
            'item' => $this->get('item'),
            'entry' => serialize($this->get('entry')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
        );
        if (!$this->get('ID')) {
            $dataset['created_at'] = current_time('mysql', 1);
        }

        $show_errors = false;
        if ($wpdb->show_errors) {
            $wpdb->show_errors(false);
            $show_errors = true;
        }

        if ($this->get('ID')) {
            $where = array(
                'ID' => $this->get('ID'),
            );
            $success = $wpdb->update($this->get_table(), $dataset, $where);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->update($this->get_table(), $dataset, $where);
            }
        } else {
            $success = $wpdb->insert($this->get_table(), $dataset);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->insert($this->get_table(), $dataset);
            }
            $this->set('ID', $wpdb->insert_id);
        }

        if ($show_errors) {
            $wpdb->show_errors();
        }

        return $this->get('ID');
    }
}
