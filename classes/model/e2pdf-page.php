<?php

/**
 * File: /model/e2pdf-page.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Page extends Model_E2pdf_Model {

    private $page = array();
    private $table;

    public function __construct() {
        global $wpdb;
        parent::__construct();
        $this->table = $wpdb->prefix . 'e2pdf_pages';
    }

    public function get_table() {
        return $this->table;
    }

    public function set($key, $value) {
        switch ($key) {
            case 'properties':
            case 'actions':
                if (!is_array($value)) {
                    $this->page[$key] = array();
                } else {
                    $this->page[$key] = $value;
                }
                break;

            default:
                $this->page[$key] = $value;
                break;
        }
    }

    public function get($key) {
        if (isset($this->page[$key])) {
            $value = $this->page[$key];
            return $value;
        } else {
            switch ($key) {
                case 'template_id':
                case 'revision_id':
                    $value = 0;
                    break;
                case 'properties':
                case 'actions':
                    $value = array();
                    break;
                default:
                    $value = '';
                    break;
            }
            return $value;
        }
    }

    public function get_page() {
        return $this->page;
    }

    public function before_save() {
        $page = array(
            'template_id' => $this->get('template_id'),
            'page_id' => $this->get('page_id'),
            'properties' => serialize($this->get('properties')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'actions' => serialize($this->get('actions')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'revision_id' => $this->get('revision_id'),
        );
        return $page;
    }

    public function save() {
        global $wpdb;

        $page = $this->before_save();
        if ($this->get('page_id') && $this->get('template_id')) {
            $show_errors = false;
            if ($wpdb->show_errors) {
                $wpdb->show_errors(false);
                $show_errors = true;
            }

            $success = $wpdb->insert($this->get_table(), $page);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->insert($this->get_table(), $page);
            }

            if ($show_errors) {
                $wpdb->show_errors();
            }
            return $this->get('page_id');
        }
    }

    public function load($page_id, $template_id, $revision_id = 0) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $page = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE page_id = %d AND template_id = %d AND revision_id = %d', $page_id, $template_id, $revision_id), ARRAY_A);
        if ($page) {
            $this->page = $page;
            $this->set('properties', $this->helper->load('convert')->unserialize($page['properties']));
            $this->set('actions', $this->helper->load('convert')->unserialize($page['actions']));
            $model_e2pdf_element = new Model_E2pdf_Element();
            $this->set('elements', $model_e2pdf_element->get_elements($this->get('page_id'), $this->get('template_id'), $this->get('revision_id')));
            return true;
        }
        return false;
    }

    public function delete() {
        global $wpdb;
        if ($this->get('page_id') && $this->get('template_id')) {
            $where = array(
                'page_id' => $this->get('page_id'),
                'template_id' => $this->get('template_id'),
                'revision_id' => $this->get('revision_id'),
            );
            $wpdb->delete($this->get_table(), $where);

            foreach ($this->get('elements') as $element) {
                $model_e2pdf_element = new Model_E2pdf_Element();
                $model_e2pdf_element->load($element['element_id'], $element['template_id'], $element['revision_id']);
                $model_e2pdf_element->delete();
            }
        }
    }

    public function get_pages($template_id, $revision_id = 0) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $pages = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE template_id = %d AND revision_id = %d ORDER BY page_id ASC', $template_id, $revision_id), ARRAY_A);
        if ($pages) {
            $pages_list = array();
            $pages_list[] = array();
            foreach ($pages as $page_key => $page) {
                $this->load($page['page_id'], $page['template_id'], $page['revision_id']);
                $pages_list[] = $this->get_page();
            }
            unset($pages_list[0]);
            return $pages_list;
        }
        return array();
    }
}
