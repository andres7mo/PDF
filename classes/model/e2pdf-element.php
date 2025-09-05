<?php

/**
 * File: /model/e2pdf-element.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Element extends Model_E2pdf_Model {

    private $element = array();
    private $table;

    public function __construct() {
        global $wpdb;
        parent::__construct();
        $this->table = $wpdb->prefix . 'e2pdf_elements';
    }

    public function get_table() {
        return $this->table;
    }

    public function set($key, $value) {
        switch ($key) {
            case 'properties':
            case 'actions':
                if (!is_array($value)) {
                    $this->element[$key] = array();
                } else {
                    $this->element[$key] = $value;
                }
                break;

            default:
                $this->element[$key] = $value;
                break;
        }
    }

    public function get($key) {
        if (isset($this->element[$key])) {
            $value = $this->element[$key];
            return $value;
        } else {
            switch ($key) {
                case 'top':
                case 'left':
                case 'width':
                case 'height':
                case 'page_id':
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

    public function get_element() {
        return $this->element;
    }

    public function before_save() {
        $element = array(
            'type' => $this->get('type'),
            'page_id' => $this->get('page_id'),
            'template_id' => $this->get('template_id'),
            'element_id' => $this->get('element_id'),
            'name' => $this->get('name'),
            'top' => $this->get('top'),
            'left' => $this->get('left'),
            'width' => $this->get('width'),
            'height' => $this->get('height'),
            'value' => $this->get('value'),
            'properties' => serialize($this->get('properties')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'actions' => serialize($this->get('actions')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'revision_id' => $this->get('revision_id'),
        );
        return $element;
    }

    public function save() {
        global $wpdb;

        $element = $this->before_save();

        if ($this->get('element_id') && $this->get('page_id') && $this->get('template_id')) {
            $wpdb->insert($this->get_table(), $element);
            return $this->get('element_id');
        }
    }

    public function load($element_id, $template_id, $revision_id = 0) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $element = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE element_id = %d AND template_id = %d AND revision_id = %d', $element_id, $template_id, $revision_id), ARRAY_A);
        if ($element) {
            if ($element['type'] == 'e2pdf-html' || $element['type'] == 'e2pdf-page-number') {
                $element['value'] = $this->helper->load('filter')->filter_html_tags($element['value']);
            }
            $this->element = $element;
            $this->set('properties', $this->helper->load('convert')->unserialize($element['properties']));
            $this->set('actions', $this->helper->load('convert')->unserialize($element['actions']));
            return true;
        }
        return false;
    }

    public function get_last_element_id($template_id, $revision_id = 0) {
        global $wpdb;

        $element = false;
        if ($template_id) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $element = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE template_id = %d AND revision_id = %d ORDER BY element_id DESC', $template_id, $revision_id), ARRAY_A);
        }

        if ($element) {
            return $element['element_id'];
        } else {
            return 0;
        }
    }

    public function delete() {
        global $wpdb;
        if ($this->get('element_id') && $this->get('template_id')) {
            $where = array(
                'element_id' => $this->get('element_id'),
                'template_id' => $this->get('template_id'),
                'revision_id' => $this->get('revision_id'),
            );
            $wpdb->delete($this->get_table(), $where);
        }
    }

    public function get_elements($page_id, $template_id, $revision_id = 0) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $elements = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE page_id = %d AND template_id = %d AND revision_id = %d', $page_id, $template_id, $revision_id), ARRAY_A);
        if ($elements) {
            $elements_list = array();
            foreach ($elements as $element_key => $element) {
                $this->load($element['element_id'], $element['template_id'], $element['revision_id']);
                $elements_list[] = $this->get_element();
            }
            return $elements_list;
        }
        return array();
    }
}
