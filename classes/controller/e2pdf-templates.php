<?php

/**
 * E2Pdf Templates Controller
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Controller_E2pdf_Templates extends Helper_E2pdf_View {

    /**
     * @url admin.php?page=e2pdf-templates
     */
    public function index_action() {
        if ($this->post->get('screenoptionnonce')) {
            if (wp_verify_nonce($this->post->get('screenoptionnonce'), 'screen-options-nonce')) {
                $this->screen_action();
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        } elseif ($this->post->get('_wpnonce')) {
            if (wp_verify_nonce($this->post->get('_wpnonce'), 'e2pdf_templates')) {
                if ($this->post->get('action') == '-1' && $this->post->get('action2') == '-1') {
                    if ($this->post->get('s')) {
                        $location = $this->helper->get_url(
                                array(
                                    'page' => 'e2pdf-templates',
                                    'status' => $this->get->get('status'),
                                    'orderby' => $this->get->get('orderby'),
                                    'order' => $this->get->get('order'),
                                    's' => $this->post->get('s'),
                                )
                        );
                    } else {
                        $location = $this->helper->get_url(
                                array(
                                    'page' => 'e2pdf-templates',
                                    'status' => $this->get->get('status'),
                                    'orderby' => $this->get->get('orderby'),
                                    'order' => $this->get->get('order'),
                                )
                        );
                    }
                    $this->redirect($location);
                } else {
                    $action = $this->post->get('action') != '-1' ? $this->post->get('action') : $this->post->get('action2');
                    if ($this->post->get('post')) {
                        if ($action == 'trash') {
                            foreach ($this->post->get('post') as $key => $value) {
                                $this->trash_template($value);
                            }
                            $this->add_notification('update', sprintf(__('Trashed: %d', 'e2pdf'), count($this->post->get('post'))));
                        } elseif ($action == 'activate') {
                            foreach ($this->post->get('post') as $key => $value) {
                                $this->activate_template($value);
                            }
                            $this->add_notification('update', sprintf(__('Activated: %d', 'e2pdf'), count($this->post->get('post'))));
                        } elseif ($action == 'deactivate') {
                            foreach ($this->post->get('post') as $key => $value) {
                                $this->deactivate_template($value);
                            }
                            $this->add_notification('update', sprintf(__('Deactivated: %d', 'e2pdf'), count($this->post->get('post'))));
                        } elseif ($action == 'restore') {
                            foreach ($this->post->get('post') as $key => $value) {
                                $this->restore_template($value);
                            }
                            $this->add_notification('update', sprintf(__('Restored: %d', 'e2pdf'), count($this->post->get('post'))));
                        } elseif ($action == 'delete') {
                            foreach ($this->post->get('post') as $key => $value) {
                                $this->delete_template($value);
                            }
                            $this->add_notification('update', sprintf(__('Deleted: %d', 'e2pdf'), count($this->post->get('post'))));
                        }
                        $this->helper->get('license')->reload_license();
                    }
                }
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        }
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=view&id=$id
     * @return file
     */
    public function view_action() {
        if ($this->get->get('id')) {
            $template = new Model_E2pdf_Template();
            if ($template->load($this->get->get('id'))) {
                $pages = $template->get('pages');
                foreach ($pages as $page_key => $page) {
                    if (!empty($page['elements'])) {
                        foreach ($page['elements'] as $element_key => $element) {
                            if (isset($element['type'])) {
                                switch ($element['type']) {
                                    case 'e2pdf-image':
                                        if (isset($element['properties']['quality']) && $element['properties']['quality']) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                        } else {
                                            $element['properties']['quality'] = $template->get('optimization');
                                            $pages[$page_key]['elements'][$element_key]['properties']['quality'] = $template->get('optimization');
                                        }
                                        if ($image = $this->helper->load('image')->get_image($element['value'], null, $element)) {
                                            $pages[$page_key]['elements'][$element_key]['value'] = $image;
                                        } else {
                                            $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/upload.svg');
                                            $pages[$page_key]['elements'][$element_key]['properties']['preview'] = '1';
                                        }
                                        break;
                                    case 'e2pdf-signature':
                                        $esig = isset($element['properties']['esig']) && $element['properties']['esig'] ? true : false;
                                        if (!$esig) {
                                            if ($image = $this->helper->load('image')->get_image($element['value'])) {
                                                $pages[$page_key]['elements'][$element_key]['value'] = $image;
                                            } else {
                                                $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/signature.svg');
                                                $pages[$page_key]['elements'][$element_key]['properties']['preview'] = '1';
                                            }
                                        }
                                        break;
                                    case 'e2pdf-qrcode':
                                        $pages[$page_key]['elements'][$element_key]['type'] = 'e2pdf-image';
                                        $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/qrcode.svg');
                                        $pages[$page_key]['elements'][$element_key]['properties']['preview'] = '1';
                                        break;
                                    case 'e2pdf-barcode':
                                        $pages[$page_key]['elements'][$element_key]['type'] = 'e2pdf-image';
                                        $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/barcode.svg');
                                        $pages[$page_key]['elements'][$element_key]['properties']['preview'] = '1';
                                        break;
                                    case 'e2pdf-graph':
                                        $pages[$page_key]['elements'][$element_key]['type'] = 'e2pdf-image';
                                        $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/graph.svg');
                                        $pages[$page_key]['elements'][$element_key]['properties']['preview'] = '1';
                                        break;
                                    case 'e2pdf-html':
                                        if (isset($pages[$page_key]['elements'][$element_key]['properties']['hide_page_if_empty'])) {
                                            unset($pages[$page_key]['elements'][$element_key]['properties']['hide_page_if_empty']);
                                        }
                                        if (isset($pages[$page_key]['elements'][$element_key]['properties']['hide_if_empty'])) {
                                            unset($pages[$page_key]['elements'][$element_key]['properties']['hide_if_empty']);
                                        }
                                        if (isset($element['properties']['nl2br']) && $element['properties']['nl2br']) {
                                            $element['value'] = nl2br($element['value']);
                                        }
                                        if (isset($element['properties']['html_worker']) && $element['properties']['html_worker']) {
                                            $element['value'] = $this->helper->load('properties')->html_worker(
                                                    $element['value']
                                            );
                                        }
                                        $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('filter')->filter_html_tags(
                                                $element['value']
                                        );
                                        break;
                                    case 'e2pdf-page-number':
                                        if (isset($element['properties']['html_worker']) && $element['properties']['html_worker']) {
                                            $element['value'] = $this->helper->load('properties')->html_worker(
                                                    $element['value']
                                            );
                                        }
                                        $pages[$page_key]['elements'][$element_key]['value'] = $this->helper->load('filter')->filter_html_tags(
                                                $element['value']
                                        );
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }

                $template->set('pages', $pages);
                $request = $template->render();

                if (isset($request['error'])) {
                    $this->add_notification('error', $request['error']);
                    $this->render('blocks', 'notifications');
                } else {
                    $filename = $template->get_name();
                    $file = $request['file'];
                    $this->download_response($template->get('format'), $file, $filename, 'inline');
                    exit;
                }
            } else {
                $this->add_notification('error', __('Something went wrong!', 'e2pdf'));
                $this->render('blocks', 'notifications');
            }
        } else {
            $location = $this->helper->get_url(
                    array(
                        'page' => 'e2pdf',
                    )
            );
            $this->redirect($location);
        }
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=preview
     */
    public function preview_action() {
        $preview = array();
        if ($this->post->get('preview')) {
            $preview = json_decode(stripslashes($this->post->get('preview')), true);
        }
        if (empty($preview)) {
            $this->close_tab_response();
        } else {
            $data = $preview;
            $pages = $preview['pages'];
            unset($preview['pages']);
            if (wp_verify_nonce(isset($preview['_wpnonce']) ? $preview['_wpnonce'] : '', 'e2pdf_templates')) {
                $template = new Model_E2pdf_Template();
                foreach ($data as $key => $value) {
                    $template->set($key, $value);
                }
                $template->set('uid', $template->get('uid', true));
                $template->set('activated', $template->get('activated', true));

                $preview_pages = array();
                foreach ($pages as $page_key => $page) {
                    $new_page = new Model_E2pdf_Page();
                    foreach ($page as $page_set_key => $page_set_value) {
                        $new_page->set($page_set_key, $page_set_value);
                    }
                    $new_page->set('page_id', $page_key);

                    $elements = array();
                    foreach ($page['elements'] as $element_key => $element) {
                        if (isset($element['type'])) {
                            switch ($element['type']) {
                                case 'e2pdf-image':
                                    if (isset($element['properties']['quality']) && $element['properties']['quality']) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                    } else {
                                        $element['properties']['quality'] = $template->get('optimization');
                                        $pages[$page_key]['elements'][$element_key]['properties']['quality'] = $template->get('optimization');
                                    }
                                    if ($image = $this->helper->load('image')->get_image($element['value'], null, $element)) {
                                        $element['value'] = $image;
                                    } else {
                                        $element['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/upload.svg');
                                        $element['properties']['preview'] = '1';
                                    }
                                    break;
                                case 'e2pdf-signature':
                                    $esig = isset($element['properties']['esig']) && $element['properties']['esig'] ? true : false;
                                    if (!$esig) {
                                        if ($image = $this->helper->load('image')->get_image($element['value'])) {
                                            $element['value'] = $image;
                                        } else {
                                            $element['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/signature.svg');
                                            $element['properties']['preview'] = '1';
                                        }
                                    }
                                    break;
                                case 'e2pdf-qrcode':
                                    $element['type'] = 'e2pdf-image';
                                    $element['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/qrcode.svg');
                                    $element['properties']['preview'] = '1';
                                    break;
                                case 'e2pdf-barcode':
                                    $element['type'] = 'e2pdf-image';
                                    $element['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/barcode.svg');
                                    $element['properties']['preview'] = '1';
                                    break;
                                case 'e2pdf-graph':
                                    $element['type'] = 'e2pdf-image';
                                    $element['value'] = $this->helper->load('image')->get_image($this->helper->get('plugin_dir') . 'img/graph.svg');
                                    $element['properties']['preview'] = '1';
                                    break;
                                case 'e2pdf-html':
                                    if (isset($element['properties']['hide_page_if_empty'])) {
                                        unset($element['properties']['hide_page_if_empty']);
                                    }
                                    if (isset($element['properties']['hide_if_empty'])) {
                                        unset($element['properties']['hide_if_empty']);
                                    }
                                    if (isset($element['properties']['nl2br']) && $element['properties']['nl2br']) {
                                        $element['value'] = nl2br($element['value']);
                                    }
                                    if (isset($element['properties']['html_worker']) && $element['properties']['html_worker']) {
                                        $element['value'] = $this->helper->load('properties')->html_worker(
                                                $element['value']
                                        );
                                    }
                                    $element['value'] = $this->helper->load('filter')->filter_html_tags(
                                            $element['value']
                                    );
                                    break;
                                case 'e2pdf-page-number':
                                    $element['value'] = str_replace(array('[e2pdf-page-number]', '[e2pdf-page-total]'), array('e2pdf-page-number', 'e2pdf-page-total'), $element['value']);
                                    if (isset($element['properties']['html_worker']) && $element['properties']['html_worker']) {
                                        $element['value'] = $this->helper->load('properties')->html_worker(
                                                $element['value']
                                        );
                                    }
                                    $element['value'] = $this->helper->load('filter')->filter_html_tags(
                                            $element['value']
                                    );
                                    break;
                                default:
                                    break;
                            }
                        }

                        $new_element = new Model_E2pdf_Element();
                        foreach ($element as $element_set_key => $element_set_value) {
                            $new_element->set($element_set_key, $element_set_value);
                        }

                        if (isset($element['properties'])) {
                            $element_properties = $element['properties'];
                        } else {
                            $element_properties = array();
                        }
                        $new_element->set('properties', $element_properties);
                        $elements[] = $new_element->get_element();
                    }

                    $new_page->set('elements', $elements);
                    $preview_pages[] = $new_page->get_page();
                }

                $template->set('pages', $preview_pages);
                $template->load_fonts();

                $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : false;
                if ($type == 'php') {
                    $request = $template->render('php');
                    if (isset($request['error'])) {
                        $this->add_notification('error', $request['error']);
                        $this->render('blocks', 'notifications');
                    } else {
                        $filename = 'template';
                        $file = $request['file'];
                        $this->download_response('php', $file, $filename);
                        exit;
                    }
                } else {
                    $request = $template->render();
                    if (isset($request['error'])) {
                        $this->add_notification('error', $request['error']);
                        $this->render('blocks', 'notifications');
                    } else {
                        $filename = $template->get_name();
                        $file = $request['file'];
                        $this->download_response($template->get('format'), $file, $filename, 'inline', false, true);
                        exit;
                    }
                }
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        }
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=create
     */
    public function create_action() {
        $template = new Model_E2pdf_Template();
        $this->load_metaboxes();
        $this->load_scripts();
        $this->load_styles();
        $this->view('license', $this->helper->get('license'));
        $this->view('template', $template);
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=edit&id=$id
     */
    public function edit_action() {
        if ($this->get->get('id')) {
            $template = new Model_E2pdf_Template();
            $revision_id = (int) $this->get->get('revision_id');
            if ($template->load($this->get->get('id'), true, $revision_id)) {
                if (is_array($this->helper->get('license')->get('activated_templates'))) {
                    if ($template->get('activated') && !in_array($template->get('uid'), $this->helper->get('license')->get('activated_templates'))) {
                        $template->deactivate();
                    } elseif (!$template->get('activated') && in_array($template->get('uid'), $this->helper->get('license')->get('activated_templates'))) {
                        $template->activate();
                    }
                }
                $this->view('license', $this->helper->get('license'));
                $this->view('template', $template);
                $this->load_metaboxes();
                $this->load_scripts();
                $this->load_styles($template->get('extension'));
            } else {
                $this->add_notification('error', __('Something went wrong!', 'e2pdf'));
                $this->render('blocks', 'notifications');
            }
        } else {
            $location = $this->helper->get_url(
                    array(
                        'page' => 'e2pdf-templates',
                    )
            );
            $this->redirect($location);
        }
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=trash&id=$id
     */
    public function trash_action() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            if ($this->get->get('id')) {
                $this->trash_template($this->get->get('id'));
                $this->add_notification('update', sprintf(__('Trashed: %d', 'e2pdf'), '1'));
            }
        } else {
            wp_die($this->message('wp_verify_nonce_error'));
        }
        $location = $this->helper->get_url(
                array(
                    'page' => 'e2pdf-templates',
                    'status' => $this->get->get('status'),
                    'orderby' => $this->get->get('orderby'),
                    'order' => $this->get->get('order'),
                    's' => $this->get->get('s'),
                )
        );
        $this->redirect($location);
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=restore&id=$id&status=$status
     */
    public function restore_action() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            if ($this->get->get('id')) {
                $this->restore_template($this->get->get('id'));
                $this->add_notification('update', sprintf(__('Restored: %d', 'e2pdf'), '1'));
            }
        } else {
            wp_die($this->message('wp_verify_nonce_error'));
        }
        $location = $this->helper->get_url(
                array(
                    'page' => 'e2pdf-templates',
                    'status' => $this->get->get('status'),
                    'orderby' => $this->get->get('orderby'),
                    'order' => $this->get->get('order'),
                    's' => $this->get->get('s'),
                )
        );
        $this->redirect($location);
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=delete&id=$id&status=$status
     */
    public function delete_action() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            if ($this->get->get('id')) {
                $this->delete_template($this->get->get('id'));
                $this->add_notification('update', sprintf(__('Deleted: %d', 'e2pdf'), '1'));
            }
        } else {
            wp_die($this->message('wp_verify_nonce_error'));
        }
        $location = $this->helper->get_url(
                array(
                    'page' => 'e2pdf-templates',
                    'status' => $this->get->get('status'),
                    'orderby' => $this->get->get('orderby'),
                    'order' => $this->get->get('order'),
                    's' => $this->get->get('s'),
                )
        );
        $this->redirect($location);
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=duplicate&id=$id
     */
    public function duplicate_action() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            if ($this->get->get('id')) {
                $this->duplicate_template($this->get->get('id'));
                $this->add_notification('update', sprintf(__('Duplicated: %d', 'e2pdf'), '1'));
            }
        } else {
            wp_die($this->message('wp_verify_nonce_error'));
        }
        $location = $this->helper->get_url(
                array(
                    'page' => 'e2pdf-templates',
                    'status' => $this->get->get('status'),
                    'orderby' => $this->get->get('orderby'),
                    'order' => $this->get->get('order'),
                    's' => $this->get->get('s'),
                )
        );
        $this->redirect($location);
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=import
     */
    public function import_action() {
        if ($this->post->get('_wpnonce')) {
            if (wp_verify_nonce($this->post->get('_wpnonce'), 'e2pdf_templates')) {
                $errors = array();
                $import = $this->files->get('template');
                $name = $import['name'];
                $tmp = $import['tmp_name'];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!$tmp) {
                    $this->add_notification('error', __('Choose Template file to upload', 'e2pdf'));
                } elseif ($import['error']) {
                    $this->add_notification('error', $import['error']);
                } elseif (!in_array($ext, array('xml'))) {
                    $this->add_notification('error', sprintf(__('Only %s files allowed', 'e2pdf'), '.xml'));
                } elseif ($import['type'] != 'text/xml') {
                    $this->add_notification('error', __('Invalid Type', 'e2pdf'));
                } else {
                    $options = $this->post->get('options');
                    $xml = simplexml_load_file($import['tmp_name'], 'SimpleXMLElement', LIBXML_PARSEHUGE);
                    if (!isset($xml->template->pages)) {
                        $this->add_notification('error', __('The file is not a valid E2Pdf Template', 'e2pdf'));
                    } else {
                        $pages = $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->pages));
                        if (is_array($pages)) {
                            foreach ($pages as $page_key => $page) {
                                if (isset($page['elements']) && !empty($page['elements'])) {
                                    foreach ($page['elements'] as $element_key => $element) {
                                        if ($element['type'] === 'e2pdf-image' && isset($element['base64']) && $element['base64']) {
                                            if ($options['images']) {
                                                $image = base64_decode($element['base64']);
                                                $file_name = basename($element['value']);
                                                $upload_file = wp_upload_bits($file_name, null, $image);
                                                if (!$upload_file['error']) {
                                                    $wp_filetype = wp_check_filetype($file_name, null);
                                                    $attachment = array(
                                                        'post_mime_type' => $wp_filetype['type'],
                                                        'post_parent' => 0,
                                                        'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                                                        'post_content' => '',
                                                        'post_status' => 'inherit',
                                                    );
                                                    $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], 0);
                                                    if (!is_wp_error($attachment_id)) {
                                                        require_once ABSPATH . 'wp-admin/includes/image.php';
                                                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                                                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                                                        $pages[$page_key]['elements'][$element_key]['value'] = $upload_file['url'];
                                                    }
                                                }
                                            }
                                            unset($pages[$page_key]['elements'][$element_key]['base64']);
                                        }
                                    }
                                }
                            }
                        }

                        $template = new Model_E2pdf_Template();
                        if ($options['overwrite']) {
                            $template->load((string) $xml->template->ID);
                        }
                        $template->set('title', (string) $xml->template->title);
                        $template->set('flatten', (string) $xml->template->flatten);
                        if (isset($xml->template->tab_order)) {
                            $template->set('tab_order', (string) $xml->template->tab_order);
                        }
                        $template->set('compression', (string) $xml->template->compression);
                        if (isset($xml->template->optimization)) {
                            $template->set('optimization', (string) $xml->template->optimization);
                        }
                        $template->set('appearance', (string) $xml->template->appearance);
                        $template->set('width', (string) $xml->template->width);
                        $template->set('height', (string) $xml->template->height);
                        $template->set('extension', (string) $xml->template->extension);
                        if (isset($xml->template->item)) {
                            $template->set('item', (string) $xml->template->item);
                        }
                        if (isset($xml->template->item1)) {
                            $template->set('item1', (string) $xml->template->item1);
                        }
                        if (isset($xml->template->item2)) {
                            $template->set('item2', (string) $xml->template->item2);
                        }

                        $fpro2pdf_backup = false;
                        if ($xml->source) {
                            if ((string) $xml->source == 'fpro2pdf') {
                                $fpro2pdf_backup = true;
                            }
                        }

                        $extension = new Model_E2pdf_Extension();
                        $extension->load($template->get('extension'));
                        if ($options['item'] && $xml->item && !$fpro2pdf_backup) {
                            $extension->set('item', $template->get('item'));
                            if ($template->get('item') == '-2') {
                                $extension->set('item1', $template->get('item1'));
                                $extension->set('item2', $template->get('item2'));
                            }

                            $updated_items = $extension->import($xml->item, $options);
                            if ($updated_items) {
                                if (isset($updated_items['errors'])) {
                                    $errors = array_merge($errors, $updated_items['errors']);
                                } else {
                                    if ($template->get('item') == '-2') {
                                        if ($template->get('item1') && isset($updated_items[$template->get('item1')])) {
                                            $item1 = $updated_items[$template->get('item1')];
                                            $template->set('item1', $item1);
                                            $extension->set('item1', $item1);
                                        } else {
                                            $template->set('item1', '');
                                            $extension->set('item1', '');
                                        }
                                        if ($template->get('item2') && isset($updated_items[$template->get('item2')])) {
                                            $item2 = $updated_items[$template->get('item2')];
                                            $template->set('item2', $item2);
                                            $extension->set('item2', $item2);
                                        } else {
                                            $template->set('item2', '');
                                            $extension->set('item2', '');
                                        }
                                    } else {
                                        if (isset($updated_items[$template->get('item')])) {
                                            $item = $updated_items[$template->get('item')];
                                            $template->set('item', $item);
                                            $extension->set('item', $item);
                                        } else {
                                            $template->set('item', '');
                                            $extension->set('item', '');
                                        }
                                    }
                                }
                            }
                        }

                        $template->set('format', (string) $xml->template->format);
                        if (isset($xml->template->resample)) {
                            $template->set('resample', (string) $xml->template->resample);
                        }
                        $template->set(
                                'dataset_title',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->dataset_title, $options, $xml, $template, $extension)
                        );
                        if (isset($xml->template->dataset_title1)) {
                            $template->set(
                                    'dataset_title1',
                                    apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->dataset_title1, $options, $xml, $template, $extension)
                            );
                        }
                        if (isset($xml->template->dataset_title2)) {
                            $template->set(
                                    'dataset_title2',
                                    apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->dataset_title2, $options, $xml, $template, $extension)
                            );
                        }
                        $template->set(
                                'button_title',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->button_title, $options, $xml, $template, $extension)
                        );
                        if (isset($xml->template->dpdf)) {
                            $template->set(
                                    'dpdf',
                                    apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->dpdf, $options, $xml, $template, $extension)
                            );
                        }
                        if (isset($xml->template->attachments)) {
                            $template->set(
                                    'attachments',
                                    apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->attachments, $options, $xml, $template, $extension)
                            );
                        }
                        $template->set('inline', (string) $xml->template->inline);
                        $template->set('auto', (string) $xml->template->auto);
                        if (isset($xml->template->rtl)) {
                            $template->set('rtl', (string) $xml->template->rtl);
                        }
                        if (isset($xml->template->font_processor)) {
                            $template->set('font_processor', (string) $xml->template->font_processor);
                        }
                        $template->set(
                                'name',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->name, $options, $xml, $template, $extension)
                        );
                        if (isset($xml->template->savename)) {
                            $template->set(
                                    'savename',
                                    apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->savename, $options, $xml, $template, $extension)
                            );
                        }
                        $template->set(
                                'password',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->password, $options, $xml, $template, $extension)
                        );
                        if (isset($xml->template->owner_password)) {
                            $template->set(
                                    'owner_password',
                                    apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->owner_password, $options, $xml, $template, $extension)
                            );
                        }
                        if (isset($xml->template->permissions)) {
                            $template->set('permissions', $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->permissions)));
                        } else {
                            $template->set('permissions', array('printing'));
                        }
                        if (isset($xml->template->hooks)) {
                            $template->set(
                                    'hooks',
                                    (string) $xml->template->hooks
                            );
                        }

                        $template->set(
                                'meta_title',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->meta_title, $options, $xml, $template, $extension)
                        );
                        $template->set(
                                'meta_subject',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->meta_subject, $options, $xml, $template, $extension)
                        );
                        $template->set(
                                'meta_author',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->meta_author, $options, $xml, $template, $extension)
                        );
                        $template->set(
                                'meta_keywords',
                                apply_filters('e2pdf_controller_templates_import_replace_shortcodes', (string) $xml->template->meta_keywords, $options, $xml, $template, $extension)
                        );
                        if (isset($xml->template->lang_code)) {
                            $template->set('lang_code', (string) $xml->template->lang_code);
                        }
                        $template->set('font', (string) $xml->template->font);
                        $template->set('font_size', (string) $xml->template->font_size);
                        $template->set('font_color', (string) $xml->template->font_color);
                        $template->set('line_height', (string) $xml->template->line_height);
                        if (isset($xml->template->text_align)) {
                            $template->set('text_align', (string) $xml->template->text_align);
                        }
                        $template->set('fonts', $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->fonts)));
                        $template->set(
                                'properties',
                                apply_filters('e2pdf_controller_templates_import_properties', $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->properties)), $options, $xml, $template, $extension)
                        );
                        $template->set(
                                'actions',
                                apply_filters('e2pdf_controller_templates_import_actions', $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->actions)), $options, $xml, $template, $extension)
                        );
                        $template->set(
                                'pages',
                                apply_filters('e2pdf_controller_templates_import_pages', $pages, $options, $xml, $template, $extension)
                        );

                        if ($options['fonts'] && $xml->fonts) {
                            $model_e2pdf_font = new Model_E2pdf_Font();
                            $fonts = $model_e2pdf_font->get_fonts();

                            if ($xml->fonts) {
                                foreach ($xml->fonts->children() as $key => $font) {
                                    $title = (string) $font->title;
                                    $name = (string) $font->name;
                                    $value = (string) $font->value;

                                    if ($title == 'Noto Sans' && $name == 'NotoSans-Regular.ttf') {
                                        $title = 'Noto Sans Regular';
                                    }

                                    $exist = array_search($title, $fonts);
                                    if (!$exist) {
                                        if (!file_exists($this->helper->get('fonts_dir') . $name)) {
                                            $f_name = $name;
                                        } else {
                                            $i = 0;
                                            do {
                                                $f_name = $i . '_' . $name;
                                                $i++;
                                            } while (file_exists($this->helper->get('fonts_dir') . $f_name));
                                        }
                                        $font_ext = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
                                        if (in_array($font_ext, $model_e2pdf_font->get_allowed_extensions())) {
                                            file_put_contents($this->helper->get('fonts_dir') . $f_name, base64_decode($value));
                                        }
                                    }
                                }
                            }
                        }

                        if ($xml->pdf && $xml->pdf->source) {

                            $pdf_name = md5(time());
                            $pdf_dir = $this->helper->get('pdf_dir') . $pdf_name . '/';
                            $pdf_images_dir = $pdf_dir . 'images/';

                            $this->helper->create_dir($pdf_dir);
                            $this->helper->create_dir($pdf_images_dir);

                            $pdf_source = (string) $xml->pdf->source;
                            file_put_contents($pdf_dir . $pdf_name . '.pdf', base64_decode($pdf_source));

                            if ($xml->pdf->images) {
                                foreach ($xml->pdf->images->children() as $key => $image) {
                                    $page_id = (string) $image->page_id;
                                    $image_source = (string) $image->source;
                                    file_put_contents($pdf_dir . 'images/' . $page_id . '.png', base64_decode($image_source));
                                }
                            }
                            $template->set('pdf', $pdf_name);
                        }

                        if (empty($errors)) {
                            $template->save(true);
                            if ($options['item']) {
                                $extension->after_import((string) $xml->template->ID, $template->get('ID'));
                            }
                            $template->activate();
                            if ($template->get('ID')) {
                                $this->add_notification('update', sprintf(
                                                __('Imported: %1$d: <a target="_blank" href="%2$s">View</a> | <a target="_blank" href="%3$s">Edit</a>', 'e2pdf'),
                                                '1',
                                                $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'view', 'id' => $template->get('ID'))),
                                                $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'edit', 'id' => $template->get('ID')))
                                        )
                                );
                            } else {
                                $this->add_notification('error', __('Something went wrong!', 'e2pdf'));
                            }
                        } else {
                            foreach ($errors as $key => $error) {
                                $this->add_notification('error', $error);
                            }
                        }
                    }
                    unlink($import['tmp_name']);
                }
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        }

        $this->view('import_disabled', false);
        if (!$this->helper->load('xml')->check()) {
            $this->add_notification('error', sprintf(__('The PHP %s extension is required', 'e2pdf'), 'SimpleXml'));
            $this->view('import_disabled', true);
        }

        $options = array(
            'common' => array(
                'name' => __('Template Options', 'e2pdf'),
                'options' => array(
                    array(
                        'name' => __('Overwrite Template by ID', 'e2pdf'),
                        'key' => 'options[overwrite]',
                        'value' => '0',
                        'default_value' => '0',
                        'type' => 'checkbox',
                        'placeholder' => '',
                    ),
                    array(
                        'name' => __('Import Images', 'e2pdf'),
                        'key' => 'options[images]',
                        'value' => '1',
                        'default_value' => '0',
                        'type' => 'checkbox',
                        'placeholder' => '',
                    ),
                    array(
                        'name' => __('Import Fonts', 'e2pdf'),
                        'key' => 'options[fonts]',
                        'value' => '1',
                        'default_value' => '0',
                        'type' => 'checkbox',
                        'placeholder' => '',
                    ),
                ),
            ),
            'item' => array(
                'name' => __('Connection Options', 'e2pdf'),
                'options' => array(
                    array(
                        'name' => __('Import Connection', 'e2pdf'),
                        'key' => 'options[item]',
                        'value' => '0',
                        'default_value' => '0',
                        'type' => 'checkbox',
                        'placeholder' => '',
                        'class' => 'e2pdf-collapse',
                        'data-collapse' => 'e2pdf-import-extension-option',
                    ),
                ),
            ),
        );

        $options = apply_filters('e2pdf_controller_templates_import_options', $options);
        $this->view('options', $options);
        $this->view('upload_max_filesize', $this->helper->load('files')->get_upload_max_filesize());
    }

    /**
     * @url admin.php?page=e2pdf-templates&action=backup&id=$id
     */
    public function download_action() {
        if ($this->post->get('_wpnonce')) {
            if (wp_verify_nonce($this->post->get('_wpnonce'), 'e2pdf_templates')) {
                if ($this->helper->load('xml')->check()) {
                    $options = $this->post->get('options');
                    $xml = $this->helper->load('xml')->create('backup');
                    $template = new Model_E2pdf_Template();
                    if ($template->load($this->post->get('id'))) {
                        if ($template->get('title') != '') {
                            $filename = $template->get('title') . '.' . date('Y-m-d.H-i-s') . '.e2pdf';
                        } else {
                            $filename = date('Y-m-d.H-i-s') . '.e2pdf';
                        }
                        $pages = $template->get('pages');
                        if ($options['images']) {
                            foreach ($pages as $page_key => $page) {
                                if (isset($page['elements']) && !empty($page['elements'])) {
                                    foreach ($page['elements'] as $element_key => $element) {
                                        if ($element['type'] === 'e2pdf-image') {
                                            $pages[$page_key]['elements'][$element_key]['base64'] = $this->helper->load('image')->get_image($element['value']);
                                        }
                                    }
                                }
                            }
                        }

                        /*
                         *  Build Xml
                         */
                        $xml->addChildCData('version', $this->helper->get('version'));
                        $xml->addChildCData('date', date('Y-m-d H:i:s'));

                        $options_xml = $xml->addChild('options');
                        foreach ($options as $option_key => $option) {
                            $options_xml->addChildCData($option_key, $option);
                        }
                        $template_xml = $xml->addChild('template');
                        $template_xml->addChildCData('ID', $template->get('ID'));
                        $template_xml->addChildCData('title', $template->get('title'));
                        $template_xml->addChildCData('created_at', $template->get('created_at'));
                        $template_xml->addChildCData('updated_at', $template->get('updated_at'));
                        $template_xml->addChildCData('flatten', $template->get('flatten'));
                        $template_xml->addChildCData('tab_order', $template->get('tab_order'));
                        $template_xml->addChildCData('compression', $template->get('compression'));
                        $template_xml->addChildCData('optimization', $template->get('optimization'));
                        $template_xml->addChildCData('appearance', $template->get('appearance'));
                        $template_xml->addChildCData('width', $template->get('width'));
                        $template_xml->addChildCData('height', $template->get('height'));
                        $template_xml->addChildCData('extension', $template->get('extension'));
                        $template_xml->addChildCData('item', $template->get('item'));
                        $template_xml->addChildCData('item1', $template->get('item1'));
                        $template_xml->addChildCData('item2', $template->get('item2'));
                        $template_xml->addChildCData('format', $template->get('format'));
                        $template_xml->addChildCData('resample', $template->get('resample'));
                        $template_xml->addChildCData(
                                'dataset_title',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('dataset_title'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'dataset_title1',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('dataset_title1'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'dataset_title2',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('dataset_title2'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'button_title',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('button_title'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'dpdf',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('dpdf'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'attachments',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('attachments'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData('inline', $template->get('inline'));
                        $template_xml->addChildCData('auto', $template->get('auto'));
                        $template_xml->addChildCData('rtl', $template->get('rtl'));
                        $template_xml->addChildCData('font_processor', $template->get('font_processor'));
                        $template_xml->addChildCData(
                                'name',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('name'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'savename',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('savename'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'password',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('password'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'owner_password',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('owner_password'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData('permissions', base64_encode(serialize($template->get('permissions'))));
                        $template_xml->addChildCData('hooks', $template->get('hooks'));
                        $template_xml->addChildCData(
                                'meta_title',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('meta_title'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'meta_subject',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('meta_subject'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'meta_author',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('meta_author'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData(
                                'meta_keywords',
                                apply_filters('e2pdf_controller_templates_backup_replace_shortcodes', $template->get('meta_keywords'), $options, $template, $template->extension())
                        );
                        $template_xml->addChildCData('lang_code', $template->get('lang_code'));
                        $template_xml->addChildCData('font', $template->get('font'));
                        $template_xml->addChildCData('font_size', $template->get('font_size'));
                        $template_xml->addChildCData('font_color', $template->get('font_color'));
                        $template_xml->addChildCData('line_height', $template->get('line_height'));
                        $template_xml->addChildCData('text_align', $template->get('text_align'));
                        $template_xml->addChildCData('fonts', base64_encode(serialize($template->get('fonts'))));
                        $template_xml->addChildCData(
                                'properties', base64_encode(
                                        serialize(
                                                apply_filters('e2pdf_controller_templates_backup_properties', $template->get('properties'), $options, $template, $template->extension())
                                        )
                                )
                        );
                        $template_xml->addChildCData(
                                'actions', base64_encode(
                                        serialize(
                                                apply_filters('e2pdf_controller_templates_backup_actions', $template->get('actions'), $options, $template, $template->extension())
                                        )
                                )
                        );
                        $template_xml->addChildCData('pages', base64_encode(
                                        serialize(
                                                apply_filters('e2pdf_controller_templates_backup_pages', $pages, $options, $template, $template->extension())
                                        )
                                )
                        );

                        /*
                         * Include PDF
                         */
                        if ($template->get('pdf')) {
                            $pdf_xml = $xml->addChild('pdf');
                            $pdf_xml->addChildCData('source', base64_encode(file_get_contents($this->helper->get('pdf_dir') . $template->get('pdf') . '/' . $template->get('pdf') . '.pdf')));
                            $pdf_xml_images = $pdf_xml->addChild('images');
                            foreach ($template->get('pages') as $key => $page) {
                                $pdf_xml_image = $pdf_xml_images->addChild('image');
                                $pdf_xml_image->addChildCData('page_id', $page['page_id']);
                                $pdf_xml_image->addChildCData('source', base64_encode(file_get_contents($this->helper->get('pdf_dir') . $template->get('pdf') . '/images/' . $page['page_id'] . '.png')));
                            }
                        }

                        /*
                         * Include Fonts
                         */
                        if ($options['fonts']) {
                            $fonts_xml = $xml->addChild('fonts');
                            if ($template->get('fonts')) {
                                $model_e2pdf_font = new Model_E2pdf_Font();
                                foreach ($template->get('fonts') as $key => $value) {
                                    $font = $fonts_xml->addChild('font');
                                    $font->addChildCData('title', $value);
                                    $font->addChildCData('name', $key);
                                    $font->addChildCData('value', $model_e2pdf_font->get_font($key));
                                }
                            }
                        }

                        /*
                         * Include Item
                         */
                        if ($options['item']) {
                            if ($template->extension()->method('backup')) {
                                $item = $xml->addChild('item');
                                $template->extension()->backup($item);
                            }
                        }

                        $file = $this->helper->load('xml')->get_xml();
                        $this->download_response('xml', $file, $filename);
                        exit;
                    } else {
                        $this->add_notification('error', __('Something went wrong!', 'e2pdf'));
                        $this->render('blocks', 'notifications');
                    }
                }
            } else {
                wp_die($this->message('wp_verify_nonce_error'));
            }
        }

        $this->view('export_disabled', false);

        if (!$this->helper->load('xml')->check()) {
            $this->add_notification('error', sprintf(__('The PHP %s extension is required', 'e2pdf'), 'SimpleXml'));
            $this->view('export_disabled', true);
        }

        $template = new Model_E2pdf_Template();
        if ($template->load($this->get->get('id'))) {

            $this->view('template', $template);

            $options = array(
                'common' => array(
                    'name' => __('Options', 'e2pdf'),
                    'options' => array(
                        array(
                            'name' => __('Include Connection', 'e2pdf'),
                            'key' => 'options[item]',
                            'value' => $template->extension()->method('backup') ? '1' : '0',
                            'default_value' => 0,
                            'type' => 'checkbox',
                            'placeholder' => '',
                            'disabled' => $template->extension()->method('backup') ? false : 'disabled',
                        ),
                        array(
                            'name' => __('Include Images', 'e2pdf'),
                            'key' => 'options[images]',
                            'value' => 1,
                            'default_value' => 0,
                            'type' => 'checkbox',
                            'placeholder' => '',
                        ),
                        array(
                            'name' => __('Include Fonts', 'e2pdf'),
                            'key' => 'options[fonts]',
                            'value' => 1,
                            'default_value' => 0,
                            'type' => 'checkbox',
                            'placeholder' => '',
                        ),
                    ),
                ),
            );

            $options = apply_filters('e2pdf_controller_templates_backup_options', $options, $template, $template->extension());
            $this->view('options', $options);
        } else {
            $this->add_notification('error', __('Something went wrong!', 'e2pdf'));
            $this->render('blocks', 'notifications');
        }
    }

    public function screen_action() {
        $option = $this->post->get('wp_screen_options');
        if (is_array($option) && isset($option['option']) && isset($option['value']) && $option['value']) {
            update_option($option['option'], $option['value']);
        }
        $location = $this->helper->get_url(
                array(
                    'page' => 'e2pdf-templates',
                )
        );
        $this->redirect($location);
    }

    /**
     * Save template via ajax
     * action: wp_ajax_e2pdf_save_form
     * function: e2pdf_save_form
     * @return json
     */
    public function ajax_save_form() {
        global $wpdb;
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = json_decode($this->post->get('data'), true);
            $template = new Model_E2pdf_Template();
            if (isset($data['ID'])) {
                $template->load($data['ID']);
            }
            if (!isset($data['permissions'])) {
                $data['permissions'] = array();
            }
            foreach ($data as $key => $value) {
                if ($key == 'pdf' && $value) {
                    if (file_exists($this->helper->get('pdf_dir') . $value . '/' . $value . '.pdf')) {
                        $template->set($key, $value);
                    }
                } else {
                    $template->set($key, $value);
                }
            }
            $template_id = $template->save(true);
            if (!$template_id) {
                if ($wpdb->last_error) {
                    $response = array(
                        'error' => $wpdb->last_error,
                    );
                } else {
                    $response = array(
                        'error' => __('Something went wrong!', 'e2pdf'),
                    );
                }
            } else {
                if (isset($data['item']) && $data['item'] == '-1') {
                    $template->load($template_id);
                    if ($template->extension()->method('auto_form')) {
                        $template = $template->extension()->auto_form($template, $data);
                    } else {
                        $template->set('item', '');
                    }
                    $template->save(true);
                }
                if (!isset($data['ID'])) {
                    $template->activate();
                }
                $response = array(
                    'redirect' => $this->helper->get_url(
                            array(
                                'page' => 'e2pdf-templates',
                                'action' => 'edit',
                                'id' => $template->get('ID'),
                            )
                    ),
                );
                $this->add_notification('update', sprintf(__('Updated: %d', 'e2pdf'), '1'));
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_get_styles() {

        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');
            $content = array();
            if (isset($data['extension']) && isset($data['item'])) {
                $extension = new Model_E2pdf_Extension();
                if ($extension->load($data['extension'])) {
                    $content = $extension->styles($data['item']);
                }
            }
            $response = array(
                'content' => $content,
            );
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    /**
     * Get extensions via ajax
     * action: wp_ajax_e2pdf_extension
     * function: e2pdf_extension
     * @return json
     */
    public function ajax_extension() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');
            $extension = new Model_E2pdf_Extension();
            $content = array();
            if ($extension->load($data)) {
                $content = $extension->items();
                if ($extension->method('merged_items')) {
                    $select = new stdClass();
                    $select->id = '-2';
                    $select->url = 'javascript:void(0);';
                    $select->name = __('Merged Connections', 'e2pdf');
                    array_unshift($content, $select);
                }
                if ($extension->method('auto_form')) {
                    $select = new stdClass();
                    $select->id = '-1';
                    $select->url = 'javascript:void(0);';
                    $select->name = __('Auto Form from PDF', 'e2pdf');
                    array_unshift($content, $select);
                }
            }
            $select = new stdClass();
            $select->id = '';
            $select->url = 'javascript:void(0);';
            $select->name = __('--- Select ---', 'e2pdf');
            array_unshift($content, $select);
            $response = array(
                'content' => $content,
            );
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    /**
     * Get extensions via ajax
     * action: wp_ajax_e2pdf_extension
     * function: e2pdf_extension
     * @return json
     */
    public function ajax_visual_mapper() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');
            $item = isset($data['item']) ? $data['item'] : '';
            $item1 = isset($data['item1']) ? $data['item1'] : '';
            $item2 = isset($data['item2']) ? $data['item2'] : '';
            $extension = new Model_E2pdf_Extension();
            $extension->load($data['extension']);
            $extension->set('item', $item);
            if ($item == '-2') {
                $extension->set('item1', $item1);
                $extension->set('item2', $item2);
            }
            $visual_mapper = $extension->visual_mapper();
            if ($visual_mapper) {
                $response = array(
                    'content' => $visual_mapper,
                );
            } elseif (!$item || ($item == '-2' && !$item1 && !$item2)) {
                $response = array(
                    'content' => __('You must set the Connection to use the Visual Mapper', 'e2pdf'),
                );
            } else {
                $response = array(
                    'content' => __("Sorry, this Extension doesn't support the Visual Mapper", 'e2pdf'),
                );
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    /**
     * Upload Template via Ajax
     * action: wp_ajax_e2pdf_upload
     * function: e2pdf_upload
     * @return json
     */
    public function ajax_upload() {

        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get();
            $post_extension = $data['extension'];
            $item = isset($data['item']) ? $data['item'] : '';
            $template_id = isset($data['template_id']) && $data['template_id'] ? $data['template_id'] : false;

            $font = isset($data['font']) ? $data['font'] : false;
            $font_size = isset($data['font_size']) ? $data['font_size'] : false;
            $line_height = isset($data['line_height']) ? $data['line_height'] : false;
            $title = isset($data['title']) ? $data['title'] : __('(no title)', 'e2pdf');
            $rtl = isset($data['rtl']) && $data['rtl'] ? '1' : '0';
            $text_align = isset($data['text_align']) ? $data['text_align'] : 'left';
            $pdf = $this->files->get('pdf');
            $name = strtolower($pdf['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($pdf['error']) {
                $error = $pdf['error'];
            } elseif (!in_array($ext, array('pdf'))) {
                $error = sprintf(__('Only %s files allowed', 'e2pdf'), '.pdf');
            } elseif ($pdf['type'] != 'application/pdf') {
                $error = __('Invalid Type', 'e2pdf');
            } else {
                $error = false;
            }

            if ($error) {
                $response = array(
                    'error' => $error,
                );
            } else {
                wp_raise_memory_limit('admin');
                $model_e2pdf_api = new Model_E2pdf_Api();
                $model_e2pdf_api->set(
                        array(
                            'action' => 'template/upload',
                            'data' => array(
                                'title' => $name,
                                'pdf' => base64_encode(file_get_contents($pdf['tmp_name'])),
                            ),
                        )
                );
                $request = $model_e2pdf_api->request();

                if (!isset($request['error'])) {

                    $extension = new Model_E2pdf_Extension();
                    if ($post_extension) {
                        $extension->load($post_extension);
                        if ($item && $item != '-1') {
                            $extension->set('item', $item);
                        }
                    }

                    $pdf_name = md5(time());
                    $pdf_dir = $this->helper->get('pdf_dir') . $pdf_name . '/';
                    $pdf_images_dir = $pdf_dir . 'images/';

                    $this->helper->create_dir($pdf_dir);
                    $this->helper->create_dir($pdf_images_dir);

                    move_uploaded_file($pdf['tmp_name'], $pdf_dir . $pdf_name . '.pdf');

                    $xml = simplexml_load_string(base64_decode($request['file']), 'SimpleXMLElement', LIBXML_PARSEHUGE);
                    $pages = $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->pages));

                    if (is_array($pages)) {
                        foreach ($pages as $page_key => $page) {

                            $pages[$page_key]['page_id'] = $page_key;

                            if (isset($page['properties']['background'])) {
                                file_put_contents($pdf_images_dir . $page_key . '.png', base64_decode($page['properties']['background']));
                                do_action('e2pdf_pdf_upload_background_save_after');
                                unset($pages[$page_key]['properties']['background']);
                            }

                            if (isset($page['elements']) && !empty($page['elements'])) {
                                foreach ($page['elements'] as $element_key => $element) {
                                    if ($element['type'] === 'e2pdf-image' && isset($element['base64']) && $element['base64']) {
                                        $image = base64_decode($element['base64']);
                                        $file_name = basename($element['value']);
                                        if (!$file_name) {
                                            $ext = $this->helper->load('image')->get_extension($image);
                                            if ($ext) {
                                                $file_name = md5(mktime()) . '.' . $ext;
                                            }
                                        }
                                        if ($file_name) {
                                            $upload_file = wp_upload_bits($file_name, null, $image);
                                            if (!$upload_file['error']) {
                                                $wp_filetype = wp_check_filetype($file_name, null);
                                                $attachment = array(
                                                    'post_mime_type' => $wp_filetype['type'],
                                                    'post_parent' => 0,
                                                    'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                                                    'post_content' => '',
                                                    'post_status' => 'inherit',
                                                );
                                                $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], 0);
                                                if (!is_wp_error($attachment_id)) {
                                                    require_once ABSPATH . 'wp-admin/includes/image.php';
                                                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                                                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                                                    $pages[$page_key]['elements'][$element_key]['value'] = $upload_file['url'];
                                                }
                                            }

                                            unset($pages[$page_key]['elements'][$element_key]['base64']);
                                        } else {
                                            unset($pages[$page_key]['elements'][$element_key]);
                                        }
                                    } elseif (isset($element['name']) && $element['name']) {
                                        $el_value = $extension->auto_map($element['name']);
                                        if ($el_value !== false) {
                                            $pages[$page_key]['elements'][$element_key]['value'] = $el_value;
                                        }
                                    }
                                }
                            }
                        }

                        $this->helper->load('sort')->stable_uasort($pages, 'sort_by_pageid');

                        $template = new Model_E2pdf_Template();

                        if ($template_id) {
                            $template->load($template_id);
                            $template->set('title', $title);
                            $template->set('pages', $pages);
                            $template->set('pdf', $pdf_name);
                            $template->set('width', (string) $xml->template->width);
                            $template->set('height', (string) $xml->template->height);
                            $template->set('extension', $post_extension);
                            $template->set('item', $item);
                            $template->set('rtl', $rtl);
                            $template->set('font', $font ? $font : (string) $xml->template->font);
                            $template->set('font_size', $font_size ? $font_size : (string) $xml->template->font_size);
                            $template->set('line_height', $line_height ? $line_height : (string) $xml->template->font_size);
                            $template->set('text_align', $text_align);
                        } else {
                            $template->set('title', $title);
                            $template->set('flatten', '1');
                            $template->set('format', (string) $xml->template->format);
                            $template->set('compression', (string) $xml->template->compression);
                            $template->set('appearance', (string) $xml->template->appearance);
                            $template->set('width', (string) $xml->template->width);
                            $template->set('height', (string) $xml->template->height);
                            $template->set('extension', $post_extension);
                            $template->set('item', $item);
                            $template->set('dataset_title', '');
                            $template->set('button_title', '');
                            $template->set('dpdf', '');
                            $template->set('attachments', '');
                            $template->set('inline', '');
                            $template->set('auto', '');
                            $template->set('rtl', $rtl);
                            $template->set('name', '[e2pdf-dataset]');
                            $template->set('savename', '[e2pdf-dataset]');
                            $template->set('password', '');
                            $template->set('meta_title', (string) $xml->template->meta_title);
                            $template->set('meta_subject', (string) $xml->template->meta_subject);
                            $template->set('meta_author', (string) $xml->template->meta_author);
                            $template->set('meta_keywords', (string) $xml->template->meta_keywords);
                            $template->set('lang_code', (string) $xml->template->lang_code);
                            $template->set('font', $font ? $font : (string) $xml->template->font);
                            $template->set('font_size', $font_size ? $font_size : (string) $xml->template->font_size);
                            $template->set('line_height', $line_height ? $line_height : (string) $xml->template->font_size);
                            $template->set('text_align', $text_align);
                            $template->set('font_color', (string) $xml->template->font_color);
                            $template->set('pages', $pages);
                            $template->set('pdf', $pdf_name);
                        }

                        if ($xml->fonts) {

                            $model_e2pdf_font = new Model_E2pdf_Font();
                            $fonts = $model_e2pdf_font->get_fonts();

                            foreach ($xml->fonts->children() as $key => $font) {
                                $font_title = (string) $font->title;
                                $name = (string) $font->name;
                                $value = (string) $font->value;
                                $exist = array_search($font_title, $fonts);
                                if (!$exist) {
                                    if (!file_exists($this->helper->get('fonts_dir') . $name)) {
                                        $f_name = $name;
                                    } else {
                                        $i = 0;
                                        do {
                                            $f_name = $i . '_' . $name;
                                            $i++;
                                        } while (file_exists($this->helper->get('fonts_dir') . $f_name));
                                    }
                                    $font_ext = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
                                    if (in_array($font_ext, $model_e2pdf_font->get_allowed_extensions())) {
                                        file_put_contents($this->helper->get('fonts_dir') . $f_name, base64_decode($value));
                                    }
                                }
                            }
                        }
                        $template->save(true);

                        if ($item == '-1' && $extension->method('auto_form')) {
                            $template = $extension->auto_form($template, $data);
                            $template->save(true);
                        }

                        if (!$template_id) {
                            $template->activate();
                        }

                        $response = array(
                            'redirect' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf-templates',
                                        'action' => 'edit',
                                        'id' => $template->get('ID'),
                                    )
                            ),
                        );
                    } else {
                        $this->helper->delete_dir($pdf_dir);
                        $response = array(
                            'error' => __('Something went wrong!', 'e2pdf'),
                        );
                    }
                } else {
                    $response = array(
                        'error' => $request['error'],
                    );
                }
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    /**
     * Upload Template via Ajax
     * action: wp_ajax_e2pdf_upload
     * function: e2pdf_upload
     * @return json
     */
    public function ajax_reupload() {

        $error = false;
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get();
            $new = isset($data['new']) ? $data['new'] : array();
            $flush = isset($data['flush']) ? $data['flush'] : array();
            $positions = isset($data['positions']) ? $data['positions'] : array();
            $template_id = isset($data['template_id']) && $data['template_id'] ? $data['template_id'] : false;
            if (!$template_id) {
                return false;
            }
            $pdf = $this->files->get('pdf');
            $name = strtolower($pdf['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($pdf['error']) {
                $error = $pdf['error'];
            } elseif (!in_array($ext, array('pdf'))) {
                $error = sprintf(__('Only %s files allowed', 'e2pdf'), '.pdf');
            } elseif ($pdf['type'] != 'application/pdf') {
                $error = __('Invalid Type', 'e2pdf');
            }

            if ($error) {
                $response = array(
                    'error' => $error,
                );
            } else {
                wp_raise_memory_limit('admin');
                $model_e2pdf_api = new Model_E2pdf_Api();
                $model_e2pdf_api->set(
                        array(
                            'action' => 'template/upload',
                            'data' => array(
                                'title' => $name,
                                'pdf' => base64_encode(file_get_contents($pdf['tmp_name'])),
                            ),
                        )
                );
                $request = $model_e2pdf_api->request();

                if (!isset($request['error'])) {
                    $template = new Model_E2pdf_Template();
                    if ($template->load($template_id)) {
                        $extension = new Model_E2pdf_Extension();
                        if ($template->get('extension') && $template->get('item')) {
                            $extension->load($template->get('extension'));
                            $extension->set('item', $template->get('item'));
                        }

                        $xml = simplexml_load_string(base64_decode($request['file']), 'SimpleXMLElement', LIBXML_PARSEHUGE);
                        $pages = $this->helper->load('convert')->unserialize(base64_decode((string) $xml->template->pages));

                        if (is_array($pages)) {
                            foreach ($pages as $page_key => $page) {
                                $pages[$page_key]['page_id'] = $page_key;
                            }
                        }

                        $this->helper->load('sort')->stable_uasort($pages, 'sort_by_pageid');

                        $model_e2pdf_element = new Model_E2pdf_Element();
                        $last_element_id = $model_e2pdf_element->get_last_element_id($template_id);

                        $original_pages = $template->get('pages');

                        foreach ($positions as $pos_key => $pos_page) {
                            if (!in_array($pos_key, $flush)) {
                                if ($pos_page == '0') {
                                    $flush[] = $pos_key;
                                } elseif (!isset($pages[$pos_page])) {
                                    $error = sprintf(__('Invalid position of %s page', 'e2pdf'), $pos_key);
                                } elseif ($pages[$pos_page]['properties']['width'] < $original_pages[$pos_key]['properties']['width']) {
                                    $error = sprintf(__('The width of PDF page %1$s can\'t be less than the width of Template page %2$s', 'e2pdf'), $pos_page, $pos_key);
                                } elseif ($pages[$pos_page]['properties']['height'] < $original_pages[$pos_key]['properties']['height']) {
                                    $error = sprintf(__('The height of PDF page %1$s can\'t be less than the height of Template page %2$s', 'e2pdf'), $pos_page, $pos_key);
                                }
                            }
                        }

                        if (!$error) {

                            $pdf_name = md5(time());
                            $pdf_dir = $this->helper->get('pdf_dir') . $pdf_name . '/';
                            $pdf_images_dir = $pdf_dir . 'images/';

                            $this->helper->create_dir($pdf_dir);
                            $this->helper->create_dir($pdf_images_dir);

                            move_uploaded_file($pdf['tmp_name'], $pdf_dir . $pdf_name . '.pdf');

                            if (is_array($pages) && !empty($pages)) {
                                foreach ($pages as $page_key => $page) {

                                    if (isset($page['properties']['background'])) {
                                        file_put_contents($pdf_images_dir . $page_key . '.png', base64_decode($page['properties']['background']));
                                        do_action('e2pdf_pdf_upload_background_save_after');
                                        unset($pages[$page_key]['properties']['background']);
                                    }

                                    if (isset($page['elements']) && !empty($page['elements'])) {
                                        foreach ($page['elements'] as $element_key => $element) {

                                            $last_element_id++;
                                            $pages[$page_key]['elements'][$element_key]['element_id'] = $last_element_id;

                                            if ($element['type'] === 'e2pdf-image' && isset($element['base64']) && $element['base64']) {

                                                $image = base64_decode($element['base64']);
                                                $file_name = basename($element['value']);

                                                if (!$file_name) {
                                                    $ext = $this->helper->load('image')->get_extension($image);
                                                    if ($ext) {
                                                        $file_name = md5(mktime()) . '.' . $ext;
                                                    }
                                                }

                                                if ($file_name) {
                                                    $upload_file = wp_upload_bits($file_name, null, $image);
                                                    if (!$upload_file['error']) {
                                                        $wp_filetype = wp_check_filetype($file_name, null);
                                                        $attachment = array(
                                                            'post_mime_type' => $wp_filetype['type'],
                                                            'post_parent' => 0,
                                                            'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                                                            'post_content' => '',
                                                            'post_status' => 'inherit',
                                                        );
                                                        $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], 0);
                                                        if (!is_wp_error($attachment_id)) {
                                                            require_once ABSPATH . 'wp-admin/includes/image.php';
                                                            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                                                            wp_update_attachment_metadata($attachment_id, $attachment_data);
                                                            $pages[$page_key]['elements'][$element_key]['value'] = $upload_file['url'];
                                                        }
                                                    }
                                                    unset($pages[$page_key]['elements'][$element_key]['base64']);
                                                } else {
                                                    unset($pages[$page_key]['elements'][$element_key]);
                                                }
                                            } elseif (isset($element['name']) && $element['name']) {
                                                $el_value = $extension->auto_map($element['name']);
                                                if ($el_value !== false) {
                                                    $pages[$page_key]['elements'][$element_key]['value'] = $el_value;
                                                }
                                            }
                                        }
                                    }

                                    foreach ($positions as $pos_key => $pos_page) {
                                        if ($pos_page == $page_key) {
                                            if (in_array($pos_key, $new)) {
                                                if (isset($original_pages[$pos_key]['elements']) && !in_array($pos_key, $flush)) {
                                                    $elements = $original_pages[$pos_key]['elements'];
                                                    $pages[$page_key]['elements'] = array_merge($pages[$page_key]['elements'], $elements);
                                                }
                                            } else {
                                                if (isset($original_pages[$pos_key]['elements']) && !in_array($pos_key, $flush)) {
                                                    $elements = $original_pages[$pos_key]['elements'];
                                                    $pages[$page_key]['elements'] = $elements;
                                                } else {
                                                    $pages[$page_key]['elements'] = array();
                                                }
                                            }
                                            if (isset($original_pages[$pos_key]['actions'])) {
                                                $pages[$page_key]['actions'] = $original_pages[$pos_key]['actions'];
                                            }
                                        }
                                    }
                                }

                                $template->set('pdf', $pdf_name);
                                $template->set('pages', $pages);
                                $template->save(true);

                                $response = array(
                                    'redirect' => $this->helper->get_url(
                                            array(
                                                'page' => 'e2pdf-templates',
                                                'action' => 'edit',
                                                'id' => $template->get('ID'),
                                            )
                                    ),
                                );
                            } else {
                                $this->helper->delete_dir($pdf_dir);
                                $response = array(
                                    'error' => __('Something went wrong!', 'e2pdf'),
                                );
                            }
                        } else {
                            $response = array(
                                'error' => $error,
                            );
                        }
                    }
                } else {
                    $response = array(
                        'error' => $request['error'],
                    );
                }
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }

        $this->json_response($response);
    }

    /**
     * Auto Generation of Template
     * action: wp_ajax_e2pdf_auto
     * function: e2pdf_auto
     * @return json
     */
    public function ajax_auto() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');

            $extension = $data['extension'];
            $item = isset($data['item']) ? $data['item'] : '';
            $item1 = isset($data['item1']) ? $data['item1'] : '';
            $item2 = isset($data['item2']) ? $data['item2'] : '';
            $font_size = isset($data['font_size']) ? $data['font_size'] : '14';
            $line_height = isset($data['line_height']) ? $data['line_height'] : '14';

            if (!$extension || !$item) {
                return;
            }

            $model_e2pdf_extension = new Model_E2pdf_Extension();
            $model_e2pdf_extension->load($extension);
            $model_e2pdf_extension->set('item', $item);
            $model_e2pdf_extension->set('item1', $item1);
            $model_e2pdf_extension->set('item2', $item2);
            $model_e2pdf_extension->set('font_size', $font_size);
            $model_e2pdf_extension->set('line_height', $line_height);

            if (method_exists($model_e2pdf_extension->extension(), 'auto')) {
                $content = $model_e2pdf_extension->auto();
            } else {
                $content = array();
            }

            $response = array(
                'content' => $content,
            );
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_activate_template() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');
            $id = $data['id'];
            if (!$id) {
                return;
            }
            $request = $this->activate_template($id);
            if (isset($request['error'])) {
                $response = array(
                    'error' => $request['error'],
                );
            } else {
                $response = array(
                    'content' => 'activated',
                );
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_deactivate_template() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');
            $id = $data['id'];
            if (!$id) {
                return;
            }
            $request = $this->deactivate_template($id);
            if (isset($request['error'])) {
                $response = array(
                    'error' => $request['error'],
                );
            } else {
                $response = array(
                    'content' => 'deactivated',
                );
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    /**
     * Confirm Email via ajax
     * action: wp_ajax_e2pdf_email
     * function: e2pdf_email
     * @return json
     */
    public function ajax_email() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf_templates')) {
            $data = $this->post->get('data');
            $email = isset($data['email']) ? trim($data['email']) : '';
            $email_code = isset($data['email_code']) ? trim($data['email_code']) : '';
            $model_e2pdf_api = new Model_E2pdf_Api();
            $model_e2pdf_api->set(
                    array(
                        'action' => 'common/email',
                        'data' => array(
                            'email' => $email,
                            'email_code' => $email_code,
                            'email_confirm' => isset($data['email_code']),
                        ),
                    )
            );
            $request = $model_e2pdf_api->request();
            if (isset($request['error'])) {
                if ($request['error'] === 'incorrect_email') {
                    $response = array(
                        'error' => __('Invalid E-mail address format', 'e2pdf'),
                    );
                } elseif ($request['error'] === 'incorrect_code') {
                    $response = array(
                        'error' => __('Invalid Confirmation Code', 'e2pdf'),
                    );
                } else {
                    $response = array(
                        'error' => $request['error'],
                    );
                }
            } elseif (isset($request['success'])) {
                $response = array(
                    'content' => $request['success'],
                );
                if ($request['success'] == 'subscribed') {
                    update_option('e2pdf_email', $email);
                }
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    /**
     * Move template to trash
     * @param int $id - ID of template
     */
    public function trash_template($id = false) {
        $id = (int) $id;
        $template = new Model_E2pdf_Template();
        if ($id && $template->load($id, false)) {
            $template->set('trash', '1');
            $template->update(array('trash'));
            $this->deactivate_template($template->get('ID'));
            return true;
        }
        return false;
    }

    /**
     * Delete template
     * @param int $id - ID of template
     */
    public function delete_template($id = false) {
        $id = (int) $id;
        $template = new Model_E2pdf_Template();
        if ($id && $template->load($id)) {
            $this->deactivate_template($template->get('ID'));
            $template->delete();
            return true;
        }
        return false;
    }

    /**
     * Restore template
     * @param int $id - ID of template
     */
    public function restore_template($id = false) {
        $id = (int) $id;
        $template = new Model_E2pdf_Template();
        if ($id && $template->load($id, false)) {
            $template->set('trash', '0');
            $template->update(array('trash'));
            return true;
        }
        return false;
    }

    /**
     * Duplicate template
     * @param int $id - ID of template
     */
    public function duplicate_template($id = false) {

        $id = (int) $id;

        $template = new Model_E2pdf_Template();
        if ($id && $template->load($id)) {
            $title = $template->get('title') . ' ' . __('(Copy)', 'e2pdf');
            $template->set('ID', false);
            $template->set('title', $title);
            $template->set('uid', '');
            $template->set('activated', '0');
            if ($template->get('pdf')) {
                $pdf_name = md5(time());
                $pdf_dir = $this->helper->get('pdf_dir') . $pdf_name . '/';
                $pdf_images_dir = $pdf_dir . 'images/';

                $this->helper->create_dir($pdf_dir);
                $this->helper->create_dir($pdf_images_dir);

                if (file_exists($this->helper->get('pdf_dir') . $template->get('pdf') . '/' . $template->get('pdf') . '.pdf')) {
                    $images = glob($this->helper->get('pdf_dir') . $template->get('pdf') . "/images/*");
                    foreach ($images as $image) {
                        copy($image, $pdf_images_dir . pathinfo($image, PATHINFO_BASENAME));
                    }
                    copy($this->helper->get('pdf_dir') . $template->get('pdf') . '/' . $template->get('pdf') . '.pdf', $this->helper->get('pdf_dir') . $pdf_name . '/' . $pdf_name . '.pdf');
                    $template->set('pdf', $pdf_name);
                }
            }
            $template->save(true);
            return true;
        }
        return false;
    }

    /**
     * Activate template
     * @param int $id - ID of template
     */
    public function activate_template($id = false) {
        $id = (int) $id;
        $template = new Model_E2pdf_Template();
        if ($id && $template->load($id, false)) {
            return $template->activate();
        } else {
            return array(
                'error' => __('Something went wrong!', 'e2pdf'),
            );
        }
    }

    /**
     * Deactivate template
     * @param int $id - ID of template
     */
    public function deactivate_template($id = false) {
        $id = (int) $id;
        $template = new Model_E2pdf_Template();
        if ($id && $template->load($id, false)) {
            return $template->deactivate();
        } else {
            return array(
                'error' => __('Something went wrong!', 'e2pdf'),
            );
        }
    }

    /**
     * Get templates list
     * @param array() $filters - Array of filter/order conditions
     * @param bool $count - Return number of templates
     * @return mixed - IF $count - int ELSE array()
     */
    public function get_templates_list($filters = array(), $count = false) {
        global $wpdb;

        $model_e2pdf_template = new Model_E2pdf_Template();
        $condition = array();
        if (isset($filters['status']) && $filters['status'] == 'trash') {
            $condition = array(
                'trash' => array(
                    'condition' => '=',
                    'value' => '1',
                    'type' => '%d',
                ),
            );
        } else {
            $condition = array(
                'trash' => array(
                    'condition' => '<>',
                    'value' => '1',
                    'type' => '%d',
                ),
            );
        }

        if (isset($filters['s']) && $filters['s']) {
            $condition['title'] = array(
                'condition' => 'LIKE',
                'value' => '%%' . $filters['s'] . '%%',
                'type' => '%s',
                'or' => array(
                    array(
                        'ID' => array(
                            'condition' => 'LIKE',
                            'value' => '%%' . $filters['s'] . '%%',
                            'type' => '%s',
                        ),
                    ),
                ),
            );
        }

        $order_condition = array();
        $sortable = array('id', 'title', 'updated_at');
        if (isset($filters['orderby']) && in_array($filters['orderby'], $sortable) && isset($filters['order'])) {
            $order_condition = array(
                'orderby' => $filters['orderby'],
                'order' => $filters['order'] == 'asc' ? 'asc' : 'desc',
            );
        } else {
            $order_condition = array(
                'orderby' => 'id',
                'order' => 'desc',
            );
        }

        $limit_condition = array();
        if (!$count) {
            $paged = isset($filters['paged']) && $filters['paged'] ? $filters['paged'] : '0';
            $paged = (int) $paged <= 0 ? 1 : (int) $paged;
            $per_page = get_option('e2pdf_templates_screen_per_page', '20');

            $limit_condition = array(
                'limit' => (int) $per_page,
                'offset' => (int) ($paged - 1) * $per_page,
            );
        }

        $where = $this->helper->load('db')->prepare_where($condition);
        $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
        $limit = $this->helper->load('db')->prepare_limit($limit_condition);
        $tpls = $wpdb->get_results($wpdb->prepare('SELECT `ID` FROM `' . $model_e2pdf_template->get_table() . '`' . $where['sql'] . $orderby . $limit . '', $where['filter']));

        if ($count) {
            return count($tpls);
        }

        $templates = array();
        foreach ($tpls as $key => $tpl) {
            $template = new Model_E2pdf_Template();
            $template->load($tpl->ID, false);
            if (is_array($this->helper->get('license')->get('activated_templates'))) {
                if ($template->get('activated') && !in_array($template->get('uid'), $this->helper->get('license')->get('activated_templates'))) {
                    $template->deactivate();
                } elseif (!$template->get('activated') && in_array($template->get('uid'), $this->helper->get('license')->get('activated_templates'))) {
                    $template->activate();
                }
            }
            $templates[] = $template;
        }
        return $templates;
    }

    /**
     * Get paper sizes list
     * @return array() - Sizes list
     */
    public function get_sizes_list($size = false, $attr = false) {

        $sizes = array(
            'A4 (PORTRAIT)' => array(
                'width' => '595',
                'height' => '842',
            ),
            'A4 (LANDSCAPE)' => array(
                'width' => '842',
                'height' => '595',
            ),
            'LETTER' => array(
                'width' => '612',
                'height' => '792',
            ),
            'NOTE' => array(
                'width' => '540',
                'height' => '720',
            ),
            'LEGAL' => array(
                'width' => '612',
                'height' => '1008',
            ),
            'TABLOID' => array(
                'width' => '792',
                'height' => '1224',
            ),
            'EXECUTIVE' => array(
                'width' => '522',
                'height' => '756',
            ),
            'POSTCARD' => array(
                'width' => '283',
                'height' => '416',
            ),
        );

        if (!$size) {
            return $sizes;
        } else {
            if (isset($sizes[$size][$attr])) {
                return $sizes[$size][$attr];
            }
            return false;
        }
    }

    /**
     * Get fonts list
     * @return array() - Fonts list
     */
    public function get_fonts($with_path = false) {

        $model_e2pdf_font = new Model_E2pdf_Font();
        $fonts = $model_e2pdf_font->get_fonts();

        if ($with_path) {
            foreach ($fonts as $key => $value) {
                $fonts[$key] = array(
                    'key' => $value,
                    'subfield' => array(
                        'path' => $key,
                    ),
                    'value' => $value,
                );
            }
        }

        return $fonts;
    }

    /**
     * Get font sizes list
     * @return array() - Font sizes list
     */
    public function get_font_sizes() {

        $max_font_size = apply_filters('e2pdf_controller_templates_max_font_size', 512);
        $sizes = array();
        for ($i = 1; $i <= $max_font_size; $i++) {
            $sizes[$i] = $i;
        }
        return $sizes;
    }

    /**
     * Get line heights list
     * @return array() - Line heights list
     */
    public function get_line_heights() {

        $max_line_height = apply_filters('e2pdf_controller_templates_max_line_height', 512);
        $line_heights = array();
        for ($i = 1; $i <= $max_line_height; $i++) {
            $line_heights[$i] = $i;
        }
        return $line_heights;
    }

    public function get_lang_codes() {
        return array(
            '' => '',
            'aa' => 'aa',
            'ab' => 'ab',
            'ae' => 'ae',
            'af' => 'af',
            'ak' => 'ak',
            'am' => 'am',
            'an' => 'an',
            'ar' => 'ar',
            'ar-AE' => 'ar-AE',
            'ar-BH' => 'ar-BH',
            'ar-DZ' => 'ar-DZ',
            'ar-EG' => 'ar-EG',
            'ar-IQ' => 'ar-IQ',
            'ar-JO' => 'ar-JO',
            'ar-KW' => 'ar-KW',
            'ar-LB' => 'ar-LB',
            'ar-LY' => 'ar-LY',
            'ar-MA' => 'ar-MA',
            'ar-OM' => 'ar-OM',
            'ar-QA' => 'ar-QA',
            'ar-SA' => 'ar-SA',
            'ar-SY' => 'ar-SY',
            'ar-TN' => 'ar-TN',
            'ar-YE' => 'ar-YE',
            'as' => 'as',
            'av' => 'av',
            'ay' => 'ay',
            'az' => 'az',
            'ba' => 'ba',
            'be' => 'be',
            'bg' => 'bg',
            'bh' => 'bh',
            'bi' => 'bi',
            'bm' => 'bm',
            'bn' => 'bn',
            'bo' => 'bo',
            'br' => 'br',
            'bs' => 'bs',
            'ca' => 'ca',
            'ce' => 'ce',
            'ch' => 'ch',
            'co' => 'co',
            'cr' => 'cr',
            'cs' => 'cs',
            'cu' => 'cu',
            'cv' => 'cv',
            'cy' => 'cy',
            'da' => 'da',
            'de' => 'de',
            'de-AT' => 'de-AT',
            'de-CH' => 'de-CH',
            'de-DE' => 'de-DE',
            'de-LI' => 'de-LI',
            'de-LU' => 'de-LU',
            'div' => 'div',
            'dv' => 'dv',
            'dz' => 'dz',
            'ee' => 'ee',
            'el' => 'el',
            'en' => 'en',
            'en-AU' => 'en-AU',
            'en-BZ' => 'en-BZ',
            'en-CA' => 'en-CA',
            'en-CB' => 'en-CB',
            'en-GB' => 'en-GB',
            'en-IE' => 'en-IE',
            'en-JM' => 'en-JM',
            'en-NZ' => 'en-NZ',
            'en-PH' => 'en-PH',
            'en-TT' => 'en-TT',
            'en-US' => 'en-US',
            'en-ZA' => 'en-ZA',
            'en-ZW' => 'en-ZW',
            'eo' => 'eo',
            'es' => 'es',
            'es-AR' => 'es-AR',
            'es-BO' => 'es-BO',
            'es-CL' => 'es-CL',
            'es-CO' => 'es-CO',
            'es-CR' => 'es-CR',
            'es-DO' => 'es-DO',
            'es-EC' => 'es-EC',
            'es-ES' => 'es-ES',
            'es-GT' => 'es-GT',
            'es-HN' => 'es-HN',
            'es-MX' => 'es-MX',
            'es-NI' => 'es-NI',
            'es-PA' => 'es-PA',
            'es-PE' => 'es-PE',
            'es-PR' => 'es-PR',
            'es-PY' => 'es-PY',
            'es-SV' => 'es-SV',
            'es-US' => 'es-US',
            'es-UY' => 'es-UY',
            'es-VE' => 'es-VE',
            'et' => 'et',
            'eu' => 'eu',
            'fa' => 'fa',
            'ff' => 'ff',
            'fi' => 'fi',
            'fj' => 'fj',
            'fo' => 'fo',
            'fr' => 'fr',
            'fr-BE' => 'fr-BE',
            'fr-CA' => 'fr-CA',
            'fr-CH' => 'fr-CH',
            'fr-FR' => 'fr-FR',
            'fr-LU' => 'fr-LU',
            'fr-MC' => 'fr-MC',
            'fy' => 'fy',
            'ga' => 'ga',
            'gd' => 'gd',
            'gl' => 'gl',
            'gn' => 'gn',
            'gu' => 'gu',
            'gv' => 'gv',
            'ha' => 'ha',
            'he' => 'he',
            'hi' => 'hi',
            'ho' => 'ho',
            'hr' => 'hr',
            'hr-BA' => 'hr-BA',
            'hr-HR' => 'hr-HR',
            'ht' => 'ht',
            'hu' => 'hu',
            'hy' => 'hy',
            'hz' => 'hz',
            'ia' => 'ia',
            'id' => 'id',
            'ie' => 'ie',
            'ig' => 'ig',
            'ii' => 'ii',
            'ik' => 'ik',
            'in' => 'in',
            'io' => 'io',
            'is' => 'is',
            'it' => 'it',
            'it-CH' => 'it-CH',
            'it-IT' => 'it-IT',
            'iu' => 'iu',
            'iw' => 'iw',
            'ja' => 'ja',
            'ji' => 'ji',
            'jv' => 'jv',
            'jw' => 'jw',
            'ka' => 'ka',
            'kg' => 'kg',
            'ki' => 'ki',
            'kj' => 'kj',
            'kk' => 'kk',
            'kl' => 'kl',
            'km' => 'km',
            'kn' => 'kn',
            'ko' => 'ko',
            'kok' => 'kok',
            'kr' => 'kr',
            'ks' => 'ks',
            'ku' => 'ku',
            'kv' => 'kv',
            'kw' => 'kw',
            'ky' => 'ky',
            'kz' => 'kz',
            'la' => 'la',
            'lb' => 'lb',
            'lg' => 'lg',
            'li' => 'li',
            'ln' => 'ln',
            'lo' => 'lo',
            'ls' => 'ls',
            'lt' => 'lt',
            'lu' => 'lu',
            'lv' => 'lv',
            'mg' => 'mg',
            'mh' => 'mh',
            'mi' => 'mi',
            'mk' => 'mk',
            'ml' => 'ml',
            'mn' => 'mn',
            'mo' => 'mo',
            'mr' => 'mr',
            'ms' => 'ms',
            'ms-BN' => 'ms-BN',
            'ms-MY' => 'ms-MY',
            'mt' => 'mt',
            'my' => 'my',
            'na' => 'na',
            'nb' => 'nb',
            'nd' => 'nd',
            'ne' => 'ne',
            'ng' => 'ng',
            'nl' => 'nl',
            'nl-BE' => 'nl-BE',
            'nl-NL' => 'nl-NL',
            'nn' => 'nn',
            'no' => 'no',
            'nr' => 'nr',
            'ns' => 'ns',
            'nv' => 'nv',
            'ny' => 'ny',
            'oc' => 'oc',
            'oj' => 'oj',
            'om' => 'om',
            'or' => 'or',
            'os' => 'os',
            'pa' => 'pa',
            'pi' => 'pi',
            'pl' => 'pl',
            'ps' => 'ps',
            'pt' => 'pt',
            'pt-BR' => 'pt-BR',
            'pt-PT' => 'pt-PT',
            'qu' => 'qu',
            'qu-BO' => 'qu-BO',
            'qu-EC' => 'qu-EC',
            'qu-PE' => 'qu-PE',
            'rm' => 'rm',
            'rn' => 'rn',
            'ro' => 'ro',
            'ru' => 'ru',
            'rw' => 'rw',
            'sa' => 'sa',
            'sb' => 'sb',
            'sc' => 'sc',
            'sd' => 'sd',
            'se' => 'se',
            'se-FI' => 'se-FI',
            'se-NO' => 'se-NO',
            'se-SE' => 'se-SE',
            'sg' => 'sg',
            'sh' => 'sh',
            'si' => 'si',
            'sk' => 'sk',
            'sl' => 'sl',
            'sm' => 'sm',
            'sn' => 'sn',
            'so' => 'so',
            'sq' => 'sq',
            'sr' => 'sr',
            'sr-BA' => 'sr-BA',
            'sr-SP' => 'sr-SP',
            'ss' => 'ss',
            'st' => 'st',
            'su' => 'su',
            'sv' => 'sv',
            'sv-FI' => 'sv-FI',
            'sv-SE' => 'sv-SE',
            'sw' => 'sw',
            'sx' => 'sx',
            'syr' => 'syr',
            'ta' => 'ta',
            'te' => 'te',
            'tg' => 'tg',
            'th' => 'th',
            'ti' => 'ti',
            'tk' => 'tk',
            'tl' => 'tl',
            'tn' => 'tn',
            'to' => 'to',
            'tr' => 'tr',
            'ts' => 'ts',
            'tt' => 'tt',
            'tw' => 'tw',
            'ty' => 'ty',
            'ug' => 'ug',
            'uk' => 'uk',
            'ur' => 'ur',
            'us' => 'us',
            'uz' => 'uz',
            've' => 've',
            'vi' => 'vi',
            'vo' => 'vo',
            'wa' => 'wa',
            'wo' => 'wo',
            'xh' => 'xh',
            'yi' => 'yi',
            'yo' => 'yo',
            'za' => 'za',
            'zh' => 'zh',
            'zh-CN' => 'zh-CN',
            'zh-HK' => 'zh-HK',
            'zh-MO' => 'zh-MO',
            'zh-SG' => 'zh-SG',
            'zh-TW' => 'zh-TW',
            'zu' => 'zu',
        );
    }

    /**
     * Load metaboxes on template edit/create action
     */
    public function load_metaboxes() {

        add_meta_box(
                'e2pdf_templates_save', __('Preset', 'e2pdf'), array($this, 'render_metabox'), null, 'side', 'default', array('tpl' => 'e2pdf_templates_save')
        );
        add_meta_box(
                'e2pdf_templates_builder', __('PDF Builder', 'e2pdf'), array($this, 'render_metabox'), null, 'side', 'default', array('tpl' => 'e2pdf_templates_builder')
        );
        add_meta_box(
                'e2pdf_templates_settings', __('Settings', 'e2pdf'), array($this, 'render_metabox'), null, 'side', 'default', array('tpl' => 'e2pdf_templates_settings')
        );
    }

    /**
     * Load javascript on template edit/create action
     */
    public function load_scripts() {

        wp_enqueue_script('postbox');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('jquery-ui-selectable');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();
    }

    /**
     * Load styles on template edit/create action
     */
    public function load_styles($ext = false) {
        $version = get_option('e2pdf_debug', '0') === '1' ? strtotime('now') : $this->helper->get('version');
        wp_enqueue_style('css/e2pdf.jquery-ui', plugins_url('css/jquery-ui.css', $this->helper->get('plugin_file_path')), false, $version, false);
        wp_enqueue_style('wp-color-picker');
        if ($ext) {
            $extension = new Model_E2pdf_Extension();
            if ($extension->load($ext)) {
                $styles = $extension->styles();
                if ($styles && is_array($styles)) {
                    foreach ($styles as $key => $value) {
                        wp_enqueue_style('e2pdf-dynamic-style-' . $key, $value, false, $version, false);
                    }
                }
            }
        }
    }
}
