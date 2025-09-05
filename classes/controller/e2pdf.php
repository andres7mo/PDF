<?php

/**
 * E2Pdf Controller
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Controller_E2pdf extends Helper_E2pdf_View {

    /**
     * @url admin.php?page=e2pdf
     */
    public function index_action() {

        if ($this->get->get('template_id')) {
            $url = array(
                'page' => 'e2pdf',
                'id' => $this->get->get('template_id'),
            );
            if ($this->get->get('dataset_id')) {
                $url['dataset'] = $this->get->get('dataset_id');
            }
            if ($this->get->get('dataset_id2')) {
                $url['dataset2'] = $this->get->get('dataset_id2');
            }
            $this->redirect($this->helper->get_url($url));
        }

        $users_tmp = get_users(
                array(
                    'fields' => array(
                        'ID', 'user_login',
                    ),
                )
        );

        $users = array(
            '0' => __('--- Select ---', 'e2pdf'),
        );
        foreach ($users_tmp as $user) {
            $users[$user->ID] = $user->user_login;
        }

        $this->view('users', $users);
    }

    /**
     * @url admin.php?page=e2pdf&action=bulk
     */
    public function bulk_action() {

        if ($this->get->get('uid')) {
            $model_e2pdf_bulk = new Model_E2pdf_Bulk();
            if ($model_e2pdf_bulk->load_by_uid($this->get->get('uid')) && file_exists($this->helper->get('bulk_dir') . $model_e2pdf_bulk->get('uid') . '.zip')) {
                $this->download_response('zip', $this->helper->get('bulk_dir') . $model_e2pdf_bulk->get('uid') . '.zip', $model_e2pdf_bulk->get('uid'), '', true);
                exit;
            }
        }

        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {  // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
            } else {
                $this->add_notification('error', __('WP Cron is disabled. You must stay on this page to complete the Bulk PDFs creation', 'e2pdf'));
            }
        }

        if ($this->helper->get('license')->get('type') == 'FREE' && !get_option('e2pdf_hide_warnings', '0')) {
            $this->add_notification('notice', sprintf(__("The Bulk PDFs creation is not available with Free License Type. Check <a target='_blank' href='%s'>%s</a> for upgrade options", 'e2pdf'), 'https://e2pdf.com/price', 'https://e2pdf.com'));
        }

        $users_tmp = get_users(
                array(
                    'fields' => array(
                        'ID', 'user_login',
                    ),
                )
        );

        $users = array(
            '0' => __('--- Select ---', 'e2pdf'),
        );
        foreach ($users_tmp as $user) {
            $users[$user->ID] = $user->user_login;
        }

        $this->view('users', $users);
    }

    /**
     * @url admin.php?page=e2pdf&action=export
     */
    public function export_action() {
        if ($this->get->get('template_id')) {
            $url = array(
                'page' => 'e2pdf',
                'action' => 'export',
                'id' => $this->get->get('template_id'),
            );
            if ($this->get->get('dataset_id')) {
                $url['dataset'] = $this->get->get('dataset_id');
            }
            if ($this->get->get('dataset_id2')) {
                $url['dataset2'] = $this->get->get('dataset_id2');
            }
            $this->redirect($this->helper->get_url($url));
        }

        $template_id = (int) $this->get->get('id');
        $dataset = $this->get->get('dataset') ? $this->get->get('dataset') : false;
        $dataset2 = $this->get->get('dataset2') ? $this->get->get('dataset2') : false;
        $wc_order_id = $this->get->get('wc_order_id') ? $this->get->get('wc_order_id') : false;
        $wc_product_item_id = $this->get->get('wc_product_item_id') ? $this->get->get('wc_product_item_id') : false;
        $disposition = 'inline';
        if ($this->post->get('disposition') === 'attachment') {
            $disposition = 'attachment';
        }

        $atts = array(
            'user_id' => 0,
            'inline' => $disposition == 'inline' ? 'true' : 'false',
        );

        $args = array();
        if ($this->post->get('options')) {
            foreach ($this->post->get('options') as $key => $value) {
                if ($key == 'args') {
                    if (is_array($value)) {
                        foreach ($value as $sub_key => $sub_value) {
                            if ($sub_value !== '') {
                                $args['arg' . ($sub_key + 1)] = stripslashes($sub_value);
                            }
                        }
                    }
                } else {
                    $atts[$key] = stripslashes($value);
                }
            }
        }

        if ($template_id && ($dataset || $dataset2)) {

            $template = new Model_E2pdf_Template();
            if ($template->load($template_id)) {

                $entry = new Model_E2pdf_Entry();

                $entry->set_data('template_id', $template_id);
                $template->extension()->set('template_id', $template_id);

                if ($dataset) {
                    $entry->set_data('dataset', $dataset);
                    $template->extension()->set('dataset', $dataset);
                }

                if ($dataset2) {
                    $entry->set_data('dataset2', $dataset2);
                    $template->extension()->set('dataset2', $dataset2);
                }

                if ($wc_order_id) {
                    $entry->set_data('wc_order_id', $wc_order_id);
                    $template->extension()->set('wc_order_id', $wc_order_id);
                }

                if ($wc_product_item_id) {
                    $entry->set_data('wc_product_item_id', $wc_product_item_id);
                    $template->extension()->set('wc_product_item_id', $wc_product_item_id);
                }

                if ($template->get('extension') == 'wordpress' && $template->get('item') == '-3') { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                } else {
                    if (array_key_exists('user_id', $atts)) {
                        $user_id = (int) $atts['user_id'];
                        $entry->set_data('user_id', $user_id);
                        $template->extension()->set('user_id', $user_id);
                    } else {
                        $user_id = get_current_user_id();
                        $entry->set_data('user_id', $user_id);
                        $template->extension()->set('user_id', $user_id);
                    }
                }

                if (!empty($args)) {
                    $entry->set_data('args', $args);
                    $template->extension()->set('args', $args);
                }

                if ($template->extension()->get_storing_engine() !== false) {
                    $entry->set_data('storing_engine', $template->extension()->get_storing_engine());
                    $template->extension()->set('storing_engine', $template->extension()->get_storing_engine());
                }

                if ($template->extension()->verify()) {

                    if (array_key_exists('inline', $atts)) {
                        $inline = $atts['inline'] == 'true' ? '1' : '0';
                        if ($template->get('inline') !== $inline) {
                            $entry->set_data('inline', $inline);
                        }
                    }

                    if (array_key_exists('flatten', $atts)) {
                        $flatten = strval((int) $atts['flatten']);
                        if ($template->get('flatten') !== $flatten) {
                            $entry->set_data('flatten', $flatten);
                            $template->set('flatten', $flatten);
                        }
                    }

                    if (array_key_exists('format', $atts)) {
                        $format = $atts['format'];
                        if ($template->get('format') !== $format) {
                            if ($template->set('format', $format)) {
                                $entry->set_data('format', $format);
                            }
                        }
                    }

                    if (array_key_exists('name', $atts) && $template->get('name') !== $atts['name']) {
                        if (!array_key_exists('filter', $atts)) {
                            $name = $template->extension()->render($atts['name']);
                        } else {
                            $name = $template->extension()->convert_shortcodes($atts['name'], true);
                        }
                        $entry->set_data('name', $name);
                        $template->set('name', $name);
                    } else {
                        $template->set('name', $template->extension()->render($template->get('name')));
                    }

                    if (array_key_exists('savename', $atts) && $template->get('savename') !== $atts['savename']) {
                        if (!array_key_exists('filter', $atts)) {
                            $savename = $template->extension()->render($atts['savename']);
                        } else {
                            $savename = $template->extension()->convert_shortcodes($atts['savename'], true);
                        }
                        $entry->set_data('savename', $name);
                        $template->set('savename', $name);
                    } else {
                        $template->set('savename', $template->extension()->render($template->get('savename')));
                    }

                    if (array_key_exists('password', $atts) && $template->get('password') !== $atts['password']) {
                        if (!array_key_exists('filter', $atts)) {
                            $password = $template->extension()->render($atts['password']);
                        } else {
                            $password = $template->extension()->convert_shortcodes($atts['password'], true);
                        }
                        $entry->set_data('password', $password);
                        $template->set('password', $password);
                    } else {
                        $template->set('password', $template->extension()->render($template->get('password')));
                    }

                    if (array_key_exists('dpdf', $atts)) {
                        if (!array_key_exists('filter', $atts)) {
                            $dpdf = $template->extension()->render($atts['dpdf']);
                        } else {
                            $dpdf = $template->extension()->convert_shortcodes($atts['dpdf'], true);
                        }
                        $entry->set_data('dpdf', $dpdf);
                        $template->set('dpdf', $dpdf);
                    } else {
                        $template->set('dpdf', $template->extension()->render($template->get('dpdf')));
                    }

                    if (array_key_exists('meta_title', $atts)) {
                        if (!array_key_exists('filter', $atts)) {
                            $meta_title = $template->extension()->render($atts['meta_title']);
                        } else {
                            $meta_title = $template->extension()->convert_shortcodes($atts['meta_title'], true);
                        }
                        $entry->set_data('meta_title', $meta_title);
                        $template->set('meta_title', $meta_title);
                    } else {
                        $template->set('meta_title', $template->extension()->render($template->get('meta_title')));
                    }

                    if (array_key_exists('meta_subject', $atts)) {
                        if (!array_key_exists('filter', $atts)) {
                            $meta_subject = $template->extension()->render($atts['meta_subject']);
                        } else {
                            $meta_subject = $template->extension()->convert_shortcodes($atts['meta_subject'], true);
                        }
                        $entry->set_data('meta_subject', $meta_subject);
                        $template->set('meta_subject', $meta_subject);
                    } else {
                        $template->set('meta_subject', $template->extension()->render($template->get('meta_subject')));
                    }

                    if (array_key_exists('meta_author', $atts)) {
                        if (!array_key_exists('filter', $atts)) {
                            $meta_author = $template->extension()->render($atts['meta_author']);
                        } else {
                            $meta_author = $template->extension()->convert_shortcodes($atts['meta_author'], true);
                        }
                        $entry->set_data('meta_author', $meta_author);
                        $template->set('meta_author', $meta_author);
                    } else {
                        $template->set('meta_author', $template->extension()->render($template->get('meta_author')));
                    }

                    if (array_key_exists('meta_keywords', $atts)) {
                        if (!array_key_exists('filter', $atts)) {
                            $meta_keywords = $template->extension()->render($atts['meta_keywords']);
                        } else {
                            $meta_keywords = $template->extension()->convert_shortcodes($atts['meta_keywords'], true);
                        }
                        $entry->set_data('meta_keywords', $meta_keywords);
                        $template->set('meta_keywords', $meta_keywords);
                    } else {
                        $template->set('meta_keywords', $template->extension()->render($template->get('meta_keywords')));
                    }

                    $template->extension()->set('entry', $entry);
                    $template->fill();
                    $request = $template->render();

                    if (isset($request['error'])) {
                        $this->add_notification('error', $request['error']);
                        $this->render('blocks', 'notifications');
                        $this->index_action();
                    } else {
                        $filename = $template->get_name();
                        $file = $request['file'];
                        $this->download_response($template->get('format'), $file, $filename, $disposition, false, true);
                        exit;
                    }
                } else {
                    $this->add_notification('error', __('Incorrect Dataset', 'e2pdf'));
                    $this->render('blocks', 'notifications');
                }
            } else {
                $this->add_notification('error', __("Template can't be loaded", 'e2pdf'));
                $this->render('blocks', 'notifications');
            }
        } else {
            $this->handle_404();
        }
    }

    public function get_bulks_list($filters = array(), $count = false) {
        global $wpdb;

        $model_e2pdf_bulk = new Model_E2pdf_Bulk();

        $condition = array();

        if (isset($filters['s']) && $filters['s']) {
            $condition['title'] = array(
                'condition' => 'LIKE',
                'value' => '%%' . $filters['s'] . '%%',
                'type' => '%s',
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
            $per_page = get_option('e2pdf_bulks_screen_per_page', '20');

            $limit_condition = array(
                'limit' => (int) $per_page,
                'offset' => (int) ($paged - 1) * $per_page,
            );
        }

        $where = $this->helper->load('db')->prepare_where($condition);
        $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
        $limit = $this->helper->load('db')->prepare_limit($limit_condition);

        $blks = $wpdb->get_results($wpdb->prepare('SELECT `ID` FROM `' . $model_e2pdf_bulk->get_table() . '`' . $where['sql'] . $orderby . $limit . '', $where['filter']));

        if ($count) {
            return count($blks);
        }

        $bulks = array();
        foreach ($blks as $key => $blk) {
            $bulk = new Model_E2pdf_Bulk();
            $bulk->load($blk->ID);
            $bulks[] = $bulk;
        }

        return $bulks;
    }

    /**
     * Get activated templates list
     * @return array() - Activated templates list
     */
    public function get_active_templates() {
        global $wpdb;

        $model_e2pdf_template = new Model_E2pdf_Template();

        $condition = array(
            'trash' => array(
                'condition' => '<>',
                'value' => '1',
                'type' => '%d',
            ),
            'activated' => array(
                'condition' => '=',
                'value' => '1',
                'type' => '%d',
            ),
        );

        $order_condition = array(
            'orderby' => 'id',
            'order' => 'desc',
        );

        $where = $this->helper->load('db')->prepare_where($condition);
        $orderby = $this->helper->load('db')->prepare_orderby($order_condition);

        $templates = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $model_e2pdf_template->get_table() . '`' . $where['sql'] . $orderby . '', $where['filter']));
        $export_templates = array();

        $export_templates[] = array(
            'key' => '0',
            'value' => __('--- Select ---', 'e2pdf'),
        );

        if (!empty($templates)) {
            foreach ($templates as $key => $value) {
                $export_templates[] = array(
                    'key' => $value->ID,
                    'value' => $value->title,
                );
            }
        }

        return $export_templates;
    }

    /**
     * Get entries for template
     * @return array() - Entries for template
     */
    public function get_datasets($template_id = false, $item = false, $dataset_title = false) {
        $datasets = array(
            array(
                'key' => '',
                'value' => __('--- Select ---', 'e2pdf'),
            )
        );
        if ($template_id) {
            $template = new Model_E2pdf_Template();
            if ($template->load($template_id)) {
                if ($item) {
                    $datasets_tmp = $template->extension()->
                            datasets(
                                    $item, $dataset_title ? $dataset_title : $template->get('dataset_title')
                            );
                } else {
                    $datasets_tmp = $template->extension()->
                            datasets(
                                    $template->get('item'), $dataset_title ? $dataset_title : $template->get('dataset_title')
                            );
                }
                if ($datasets_tmp && is_array($datasets_tmp)) {
                    $datasets = array_merge($datasets, $datasets_tmp);
                }
            }
        }

        return $datasets;
    }

    /**
     * Get templates list via ajax
     * action: wp_ajax_e2pdf_templates
     * function: e2pdf_templates
     * @return json - Templates list
     */
    public function ajax_templates() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {
            $content = array(
                'id' => 0,
                'datasets' => array(),
                'options' => array(),
                'actions' => array(),
            );
            $template_id = (int) $this->post->get('data');
            $template = new Model_E2pdf_Template();
            if ($template->load($template_id)) {
                $content['id'] = $template_id;
                if ($template->get('item') == '-2') {
                    $content['datasets']['dataset'] = $this->get_datasets($template_id, $template->get('item1'), $template->get('dataset_title1'));
                    $content['datasets']['dataset2'] = $this->get_datasets($template_id, $template->get('item2'), $template->get('dataset_title2'));
                } else {
                    $content['datasets']['dataset'] = $this->get_datasets($template_id);
                }

                $actions = $template->extension()->get_template_actions($template_id);
                if (isset($actions->delete) && $actions->delete) {
                    $content['actions'][] = sprintf('<a target="_blank" class="e2pdf-delete-items e2pdf-link" template="%s" href="javascript:void(0);" _wpnonce="%s">%s</a>', $template_id, wp_create_nonce('e2pdf'), __('Delete All Datasets', 'e2pdf'));
                }
                $content['actions'][] = sprintf(
                        '<a target="_blank" class="e2pdf-link" href="%s">%s</a>', $this->helper->get_url(
                                array(
                                    'page' => 'e2pdf-templates',
                                    'action' => 'edit',
                                    'id' => $template_id,
                                )
                        ),
                        __('Edit Template', 'e2pdf')
                );
            }
            $content['options'] = array(
                'name' => $template->get('name'),
                'savename' => $template->get('savename'),
                'password' => $template->get('password'),
                'user_id' => get_current_user_id(),
                'flatten' => $template->get('flatten'),
                'format' => $template->get('format'),
                'args' => '',
            );
            if ($template->get('extension') == 'wordpress' && $template->get('item') == '-3') {
                unset($content['options']['user_id']);
            }
            $response = array(
                'content' => $content,
            );
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_datasets_refresh() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {
            $data = $this->post->get('data');
            $template_id = (int) $data['id'];
            $name = $data['name'];
            $dataset = $data['dataset'];
            $content = array(
                'dataset' => $dataset,
                'datasets' => array(),
            );
            $template = new Model_E2pdf_Template();
            if ($template->load($template_id)) {
                $content['id'] = $template_id;
                if ($template->get('item') == '-2') {
                    if ($name == 'dataset') {
                        $content['datasets'] = $this->get_datasets($template_id, $template->get('item1'), $template->get('dataset_title1'));
                    } else {
                        $content['datasets'] = $this->get_datasets($template_id, $template->get('item2'), $template->get('dataset_title2'));
                    }
                } else {
                    $content['datasets'] = $this->get_datasets($template_id);
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
     * Get entries list via ajax
     * action: wp_ajax_e2pdf_entry
     * function: e2pdf_entry
     * @return json - Entries list
     */
    public function ajax_dataset() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {
            $data = $this->post->get('data');
            $template_id = (int) $data['id'];
            $datasets = isset($data['datasets']) ? $data['datasets'] : array();
            $template = new Model_E2pdf_Template();
            $content = array(
                'id' => $template_id,
                'export' => false,
                'datasets' => array(),
            );
            if ($template->load($template_id)) {
                foreach ($datasets as $key => $dataset_id) {
                    $actions = $template->extension()->get_dataset_actions($dataset_id);
                    if ($actions) {
                        $content['datasets'][$key] = array(
                            'id' => $dataset_id,
                            'actions' => array(),
                        );
                        if (isset($actions->view) && $actions->view) {
                            $content['datasets'][$key]['actions'][] = sprintf('<a target="_blank" class="e2pdf-link" href="%s">%s</a>', $actions->view, __('View Dataset', 'e2pdf'));
                        }
                        if (isset($actions->delete) && $actions->delete) {
                            $content['datasets'][$key]['actions'][] = sprintf('<a target="_blank" class="e2pdf-link e2pdf-delete-item" href="javascript:void(0);" template="%s" dataset="%s" _wpnonce="%s">%s</a>', $template_id, $dataset_id, wp_create_nonce('e2pdf'), __('Delete Dataset', 'e2pdf'));
                        }
                        $content['export'] = true;
                    } else {
                        $content['datasets'][$key] = array(
                            'id' => '',
                            'actions' => array(),
                        );
                    }
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

    public function ajax_delete_item() {

        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {

            $data = $this->post->get('data');

            $template_id = (int) $data['template'];
            $dataset_id = (int) $data['dataset'];

            if (!$template_id || !$dataset_id) {
                return;
            }

            $template = new Model_E2pdf_Template();

            $action = false;
            if ($template->load($template_id)) {
                $action = $template->extension()->delete_item($template_id, $dataset_id);
            }

            if ($action) {
                $response = array(
                    'content' => true,
                );
            } else {
                $response = array(
                    'error' => __("Dataset can't be removed!", 'e2pdf'),
                );
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_delete_items() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {

            $data = $this->post->get('data');
            $template_id = (int) $data['template'];

            if (!$template_id) {
                return;
            }

            $template = new Model_E2pdf_Template();

            $action = false;
            if ($template->load($template_id)) {
                $action = $template->extension()->delete_items($template_id);
            }

            if ($action) {
                $response = array(
                    'content' => true,
                );
            } else {
                $response = array(
                    'error' => __("Datasets can't be removed!", 'e2pdf'),
                );
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_bulk_create() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {
            $this->helper->set('license', new Model_E2pdf_License());
            if ($this->helper->get('license')->get('type') === false) {
                $response = array(
                    'error' => __('Something went wrong!', 'e2pdf'),
                );
            } elseif ($this->helper->get('license')->get('type') == 'FREE') {
                $response = array(
                    'error' => sprintf(__("The Bulk PDFs generation is not available with Free License Type. Check <a target='_blank' href='%s'>%s</a> for upgrade options", 'e2pdf'), 'https://e2pdf.com/price', 'https://e2pdf.com')
                );
            } elseif (!class_exists('ZipArchive')) {
                $response = array(
                    'error' => sprintf(__('The PHP %s extension is required', 'e2pdf'), 'Zip'),
                );
            } else {
                $data = json_decode($this->post->get('data'), true);
                $template_id = isset($data['id']) ? (int) $data['id'] : 0;
                $template = new Model_E2pdf_Template();
                if ($template->load($template_id)) {
                    $model_e2pdf_bulk = new Model_E2pdf_Bulk();
                    $model_e2pdf_bulk->set('template_id', $template->get('ID'));
                    if (isset($data['options'])) {
                        $data['options']['args'] = isset($data['options[args]']) ? $data['options[args]'] : array();
                        $model_e2pdf_bulk->set('options', $data['options']);
                    }
                    $datasets = isset($data['dataset']) ? $data['dataset'] : array();
                    if (isset($datasets['0']) && $datasets['0'] === '') {
                        unset($datasets['0']);
                    }
                    if (!empty($datasets)) {
                        $datasets = array_reverse($datasets);
                        $model_e2pdf_bulk->set('datasets', $datasets);
                        $model_e2pdf_bulk->set('total', count($datasets));
                        $model_e2pdf_bulk->save();
                        $response = array(
                            'redirect' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'bulk',
                                    )
                            ),
                        );
                    }
                    $this->cron_bulk_export();
                    $this->add_notification('update', 'The Bulk PDFs generation Task is created successfully', 'e2pdf');
                }
            }
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_bulk_action() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {
            $data = $this->post->get('data');
            $bulk_id = (int) $data['bulk'];
            $action = isset($data['action']) && in_array($data['action'], array('delete', 'start', 'stop')) ? $data['action'] : false;
            if (!$bulk_id || !$action) {
                return;
            }
            $bulk = new Model_E2pdf_Bulk();
            if ($bulk->load($bulk_id)) {
                if ($action == 'delete') {
                    $bulk->delete();
                } elseif ($action == 'start') {
                    if ($bulk->get('status') != 'pending') {
                        $bulk->set('status', 'pending');
                        $bulk->save();
                    }
                    $this->cron_bulk_export();
                } elseif ($action == 'stop') {
                    if ($bulk->get('status') != 'stop') {
                        $bulk->set('status', 'stop');
                        $bulk->save();
                    }
                }
            }
            $response = array(
                'content' => array(
                    'action' => $action,
                ),
            );
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function ajax_bulk_progress() {
        if (wp_verify_nonce($this->get->get('_wpnonce'), 'e2pdf')) {
            $data = $this->post->get('data');
            $bulks = isset($data['bulks']) && is_array($data['bulks']) ? $data['bulks'] : array();
            $progress = array();
            foreach ($bulks as $bulk_id) {
                $bulk = new Model_E2pdf_Bulk();
                if ($bulk->load($bulk_id)) {
                    $progress[] = array(
                        'ID' => $bulk->get('ID'),
                        'uid' => $bulk->get('uid'),
                        'count' => $bulk->get('count'),
                        'status' => $bulk->get('status'),
                    );
                }
            }
            $response = array(
                'content' => array(
                    'bulks' => $progress,
                ),
            );
            $this->cron_bulk_export();
        } else {
            $response['error'] = $this->message('wp_verify_nonce_error');
        }
        $this->json_response($response);
    }

    public function cron_bulk_export() {
        $model_e2pdf_bulk = new Model_E2pdf_Bulk();
        if ($model_e2pdf_bulk->load_by_active_bulk()) {
            if (!get_transient('e2pdf_bulk_export')) {
                set_transient('e2pdf_bulk_export', true, max(10, (int) get_option('e2pdf_bulk_export_interval', '10')));
                if ($model_e2pdf_bulk->get('ID') && $model_e2pdf_bulk->get_active_status() !== 'busy' && $model_e2pdf_bulk->get_active_status() !== 'stop') {
                    $model_e2pdf_bulk->set('status', 'busy');
                    $model_e2pdf_bulk->save();
                    $template_id = $model_e2pdf_bulk->get('template_id');
                    $datasets = $model_e2pdf_bulk->get('datasets');
                    $dataset = array_shift($datasets);
                    $pdf_dir = $this->helper->get('bulk_dir') . $model_e2pdf_bulk->get('uid') . '/';
                    if (!file_exists($pdf_dir)) {
                        $this->helper->create_dir($pdf_dir, false, true, true);
                    }
                    register_shutdown_function(array($this, 'cron_bulk_export_shutdown'), $model_e2pdf_bulk, $datasets, $pdf_dir);
                    if ($template_id && $dataset) {
                        $model_e2pdf_shortcode = new Model_E2pdf_Shortcode();
                        $atts = array(
                            'id' => $template_id,
                            'dataset' => $dataset,
                            'user_id' => 0,
                            'create_index' => 'false',
                            'create_htaccess' => 'false',
                            'overwrite' => '2',
                            'dir' => $pdf_dir,
                            'apply' => 'true',
                        );
                        if ($model_e2pdf_bulk->get('options')) {
                            foreach ($model_e2pdf_bulk->get('options') as $key => $value) {
                                if ($key == 'args') {
                                    if (is_array($value)) {
                                        foreach ($value as $sub_key => $sub_value) {
                                            if ($sub_value) {
                                                $atts['arg' . ($sub_key + 1)] = $sub_value;
                                            }
                                        }
                                    }
                                } else {
                                    $atts[$key] = $value;
                                }
                            }
                        }
                        $model_e2pdf_shortcode->e2pdf_save($atts);
                    }
                }
            }
            if (!wp_next_scheduled('e2pdf_bulk_export_cron')) {
                wp_schedule_event(time(), 'e2pdf_bulk_export_interval', 'e2pdf_bulk_export_cron');
            }
        } else {
            if (wp_next_scheduled('e2pdf_bulk_export_cron')) {
                wp_clear_scheduled_hook('e2pdf_bulk_export_cron');
            }
        }
    }

    public function cron_bulk_export_shutdown($model_e2pdf_bulk, $datasets, $pdf_dir) {
        $model_e2pdf_bulk->set('datasets', $datasets);
        $model_e2pdf_bulk->set('count', $model_e2pdf_bulk->get('count') + 1);
        if (empty($datasets)) {
            $zip_path = $this->helper->get('bulk_dir') . $model_e2pdf_bulk->get('uid') . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE) === true) {
                do_action('e2pdf_controller_e2pdf_bulk_export_completed_dir', $pdf_dir, $model_e2pdf_bulk);
                $dir = opendir($pdf_dir);
                while ($file = readdir($dir)) {
                    if (is_file($pdf_dir . $file) && $file !== 'index.php' && $file !== '.htaccess') {
                        $zip->addFile($pdf_dir . $file, $file);
                    }
                }
                closedir($dir);
                $zip->close();
                do_action('e2pdf_controller_e2pdf_bulk_export_completed_zip', $zip_path, $model_e2pdf_bulk);
            }
            $this->helper->delete_dir($pdf_dir);
            $model_e2pdf_bulk->set('status', 'completed');
        } else {
            if ($model_e2pdf_bulk->get_active_status() == 'stop') {
                $model_e2pdf_bulk->set('status', 'stop');
            } else {
                $model_e2pdf_bulk->set('status', 'pending');
            }
        }
        $model_e2pdf_bulk->save();
    }
}
