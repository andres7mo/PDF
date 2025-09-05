<?php

/**
 * E2pdf Graph Helper
 * 
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.01.02
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Extension {

    public function is_plugin_active($plugin) {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (is_plugin_active($plugin)) {
            return true;
        }
        list($dir, $file) = explode('/', $plugin);
        $plugins = (array) get_option('active_plugins', array());
        if (is_multisite()) {
            $plugins = array_merge($plugins, array_keys((array) get_site_option('active_sitewide_plugins', array())));
        }
        if (!empty(preg_grep('/' . $file . '$/i', $plugins))) {
            return true;
        }
        return false;
    }

}
