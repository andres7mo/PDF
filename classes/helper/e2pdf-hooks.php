<?php

/**
 * E2Pdf Hooks
 * 
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Hooks {

    public function get($extension = '', $hook = '', $item = '') {
        global $wpdb;
        $model_e2pdf_template = new Model_E2pdf_Template();
        $hooks = $wpdb->get_col($wpdb->prepare('SELECT `ID` FROM `' . $model_e2pdf_template->get_table() . '` WHERE trash = %s AND activated = %s AND extension = %s AND item = %s AND FIND_IN_SET(%s, hooks)', '0', '1', $extension, $item, $hook));
        return $hooks;
    }

    public function get_items($extension = '', $hook = '') {
        global $wpdb;
        $model_e2pdf_template = new Model_E2pdf_Template();
        if ($hook == 'hook_wordpress_metabox') {
            $hooks = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `item` FROM `' . $model_e2pdf_template->get_table() . '` WHERE trash = %s AND activated = %s AND extension = %s AND item != %s AND item != %s AND FIND_IN_SET(%s, hooks)', '0', '1', $extension, '', '-3', $hook));
        } else {
            $hooks = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `item` FROM `' . $model_e2pdf_template->get_table() . '` WHERE trash = %s AND activated = %s AND extension = %s AND item != %s AND FIND_IN_SET(%s, hooks)', '0', '1', $extension, '', $hook));
        }
        return $hooks;
    }
}
