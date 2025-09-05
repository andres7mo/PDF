<?php

/**
 * File: /model/e2pdf-revision.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Revision extends Model_E2pdf_Model {

    private $template = array();
    private $extension = null;
    private $table;

    public function __construct() {
        global $wpdb;
        parent::__construct();
        $this->table = $wpdb->prefix . 'e2pdf_revisions';
    }

    // load
    public function load($template_id, $full = true, $revision_id = 0) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $template = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $this->get_table() . '` WHERE template_id = %d AND revision_id = %d', $template_id, $revision_id), ARRAY_A);

        if ($template && $revision_id) {
            $this->template = $template;
            $extension = new Model_E2pdf_Extension();
            if ($this->get('extension')) {
                $extension->load($this->get('extension'));
            }
            if ($this->get('item')) {
                $extension->set('item', $this->get('item'));
            }
            if ($this->get('template_id')) {
                $extension->set('template_id', $this->get('template_id'));
            }
            $this->extension = $extension;

            if ($full) {
                $this->set('fonts', $this->helper->load('convert')->unserialize($template['fonts']));
                $this->set('permissions', $this->helper->load('convert')->unserialize($template['permissions']));
                $this->set('properties', $this->helper->load('convert')->unserialize($template['properties']));
                $this->set('actions', $this->helper->load('convert')->unserialize($template['actions']));

                $model_e2pdf_page = new Model_E2pdf_Page();
                $this->set('pages', $model_e2pdf_page->get_pages($this->get('template_id'), $revision_id));
            }
            return true;
        }
        return false;
    }

    public function revision($template_id = false) {
        global $wpdb;
        if ($template_id) {
            $template = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $wpdb->prefix . 'e2pdf_templates` WHERE ID = %d', $template_id), ARRAY_A);
            if ($template) {
                $this->template = $template;

                $this->set('fonts', $this->helper->load('convert')->unserialize($template['fonts']));
                $this->set('permissions', $this->helper->load('convert')->unserialize($template['permissions']));
                $this->set('properties', $this->helper->load('convert')->unserialize($template['properties']));
                $this->set('actions', $this->helper->load('convert')->unserialize($template['actions']));

                $this->set('template_id', $template_id);

                $condition = array(
                    'template_id' => array(
                        'condition' => '=',
                        'value' => $this->get('template_id'),
                        'type' => '%d',
                    ),
                );
                $where = $this->helper->load('db')->prepare_where($condition);

                // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $wpdb->query($wpdb->prepare('UPDATE `' . $wpdb->prefix . 'e2pdf_elements` set revision_id = revision_id + 1 ' . $where['sql'] . '', $where['filter']));
                $wpdb->query($wpdb->prepare('UPDATE `' . $wpdb->prefix . 'e2pdf_pages` set revision_id = revision_id + 1 ' . $where['sql'] . '', $where['filter']));
                $wpdb->query($wpdb->prepare('UPDATE `' . $wpdb->prefix . 'e2pdf_revisions` set revision_id = revision_id + 1 ' . $where['sql'] . '', $where['filter']));
                // phpcs:enable

                $this->save();
            }
        }
        return false;
    }

    public function pre_save() {

        $template = array(
            'template_id' => $this->get('template_id'),
            'revision_id' => '1',
            'title' => $this->get('title'),
            'pdf' => $this->get('pdf'),
            'updated_at' => current_time('mysql', 1),
            'flatten' => $this->get('flatten'),
            'tab_order' => $this->get('tab_order'),
            'format' => $this->get('format'),
            'resample' => $this->get('resample'),
            'compression' => $this->get('compression'),
            'optimization' => $this->get('optimization'),
            'appearance' => $this->get('appearance'),
            'width' => $this->get('width'),
            'height' => $this->get('height'),
            'extension' => $this->get('extension'),
            'item' => $this->get('item'),
            'item1' => $this->get('item1'),
            'item2' => $this->get('item2'),
            'dataset_title' => $this->get('dataset_title'),
            'dataset_title1' => $this->get('dataset_title1'),
            'dataset_title2' => $this->get('dataset_title2'),
            'button_title' => $this->get('button_title'),
            'dpdf' => $this->get('dpdf'),
            'inline' => $this->get('inline'),
            'auto' => $this->get('auto'),
            'rtl' => $this->get('rtl'),
            'font_processor' => $this->get('font_processor'),
            'name' => $this->get('name'),
            'savename' => $this->get('savename'),
            'password' => $this->get('password'),
            'owner_password' => $this->get('owner_password'),
            'permissions' => serialize($this->get('permissions')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'hooks' => $this->get('hooks'),
            'meta_title' => $this->get('meta_title'),
            'meta_subject' => $this->get('meta_subject'),
            'meta_author' => $this->get('meta_author'),
            'meta_keywords' => $this->get('meta_keywords'),
            'lang_code' => $this->get('lang_code'),
            'fonts' => serialize($this->get('fonts')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'font' => $this->get('font'),
            'font_size' => $this->get('font_size'),
            'font_color' => $this->get('font_color'),
            'line_height' => $this->get('line_height'),
            'text_align' => $this->get('line_height'),
            'author' => $this->get('author'),
            'properties' => serialize($this->get('properties')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'actions' => serialize($this->get('actions')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            'attachments' => $this->get('attachments'),
            'created_at' => $this->get('created_at'),
        );

        return $template;
    }

    // template
    public function template() {
        return $this->template;
    }

    // extension
    public function extension() {
        return $this->extension;
    }

    // set
    public function set($key, $value) {
        switch ($key) {
            case 'format':
                if (in_array($value, array('pdf', 'jpg'), true)) {
                    $this->template[$key] = $value;
                    return true;
                } else {
                    return false;
                }
                break;
            case 'permissions':
                if (!is_array($value)) {
                    $this->template[$key] = array('printing');
                } else {
                    $this->template[$key] = $value;
                }
                break;
            case 'fonts':
            case 'properties':
            case 'actions':
                if (!is_array($value)) {
                    $this->template[$key] = array();
                } else {
                    $this->template[$key] = $value;
                }
                break;
            default:
                $this->template[$key] = $value;
                break;
        }
        return true;
    }

    // get
    public function get($key) {
        if (isset($this->template[$key])) {
            $value = $this->template[$key];
            return $value;
        } else {
            switch ($key) {
                case 'title':
                    $value = __('(no title)', 'e2pdf');
                    break;
                case 'width':
                case 'height':
                case 'inline':
                case 'auto':
                case 'trash':
                case 'locked':
                case 'activated':
                case 'revision_id':
                case 'rtl':
                case 'tab_order':
                    $value = '0';
                    break;
                case 'flatten':
                case 'appearance':
                    $value = '1';
                    break;
                case 'compression':
                    $value = '-1';
                    break;
                case 'optimization':
                    $value = '-1';
                    break;
                case 'format':
                    $value = 'pdf';
                    break;
                case 'resample':
                    $value = '100';
                    break;
                case 'text_align':
                    $value = 'left';
                    break;
                case 'permissions':
                    $value = array(
                        'printing',
                    );
                    break;
                case 'fonts':
                case 'properties':
                case 'actions':
                    $value = array();
                    break;
                case 'pages':
                case 'revisions':
                    $value = array();
                    break;
                case 'font_processor':
                    $value = get_option('e2pdf_font_processor', '0');
                    break;
                default:
                    $value = '';
                    break;
            }
            return $value;
        }
    }

    // get table
    public function get_table() {
        return $this->table;
    }

    // delete
    public function delete() {
        global $wpdb;
        if ($this->get('template_id') && $this->get('revision_id')) {

            if ($this->get('pdf')) {
                $pdf_dir = $this->helper->get('pdf_dir') . $this->get('pdf') . '/';
                $this->helper->delete_dir($pdf_dir);
            }

            $where = array(
                'template_id' => $this->get('template_id'),
                'revision_id' => $this->get('revision_id'),
            );
            $wpdb->delete($this->get_table(), $where);

            foreach ($this->get('pages') as $page) {
                $model_e2pdf_page = new Model_E2pdf_Page();
                $model_e2pdf_page->load($page['page_id'], $page['template_id'], $page['revision_id']);
                $model_e2pdf_page->delete();
            }
        }
    }

    // save
    public function save() {
        global $wpdb;

        $show_errors = false;
        if ($wpdb->show_errors) {
            $wpdb->show_errors(false);
            $show_errors = true;
        }

        if ($this->get('template_id')) {
            $template = $this->pre_save();
            $success = $wpdb->insert($this->get_table(), $template);
            if ($success === false) {
                $this->helper->load('db')->db_init($wpdb->prefix);
                $wpdb->insert($this->get_table(), $template);
            }
        }

        if ($show_errors) {
            $wpdb->show_errors();
        }
    }

    // flush
    public function flush() {
        if ($this->get('template_id')) {
            $revisions_limit = max(1, (int) get_option('e2pdf_revisions_limit', '3'));
            foreach ($this->revisions($this->get('template_id')) as $revision) {
                if ($revision['revision_id'] > $revisions_limit) {
                    $this->load($this->get('template_id'), true, $revision['revision_id']);
                    $this->delete();
                }
            }
        }
    }

    // revisions
    public function revisions($template_id = false) {
        global $wpdb;

        $revisions = array();
        if ($template_id) {
            $condition = array(
                'template_id' => array(
                    'condition' => '=',
                    'value' => $template_id,
                    'type' => '%d',
                ),
            );

            $where = $this->helper->load('db')->prepare_where($condition);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $revisions = $wpdb->get_results($wpdb->prepare('SELECT `revision_id`, `updated_at` FROM `' . $this->get_table() . '`' . $where['sql'] . ' ORDER BY revision_id ASC', $where['filter']), ARRAY_A);
        }

        return $revisions;
    }
}
