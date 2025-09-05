<?php

/*
  Plugin Name: E2Pdf
  Plugin URI:  https://e2pdf.com
  Description: Export PDF tool
  Version:     1.28.07
  Author:      E2Pdf.com
  Author URI:  https://e2pdf.com/contributors
  Text Domain: e2pdf
  Domain Path: /languages
  License:     GPLv3

  E2pdf is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  any later version.

  E2pdf is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with E2pdf. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

if (!defined('ABSPATH')) {
    die('Access denied.');
}

// recoverty mode email
if (get_option('e2pdf_debug', '0') && get_option('e2pdf_recovery_mode_email', '')) {
    if (!defined('RECOVERY_MODE_EMAIL')) {
        define('RECOVERY_MODE_EMAIL', get_option('e2pdf_recovery_mode_email', ''));
    }
}

// autoloader name to filename
function e2pdf_autoloader_convert_name($class_name) {
    $search = array(
        '_',
        'Controller-Frontend-',
        'Controller-',
        'Model-',
        'Helper-',
        'Extension-',
        'Api-',
    );
    $replace = array(
        '-',
        '',
        '',
        '',
        '',
        '',
        '',
    );
    return strtolower(
            str_replace($search, $replace, $class_name)
    );
}

// e2pdf autoloader
function e2pdf_autoloader($class_name) {
    if (!preg_match('/^(.*?)E2pdf(.*?)$/', $class_name)) {
        return;
    }
    $path = dirname(__FILE__);
    $path .= '/classes';
    if (preg_match('/^Helper.+$/', $class_name)) {
        $path .= '/helper/';
    } elseif (preg_match('/^Controller_Frontend.+$/', $class_name)) {
        $path .= '/controller/frontend/';
    } elseif (preg_match('/^Controller.+$/', $class_name)) {
        $path .= '/controller/';
    } elseif (preg_match('/^Model.+$/', $class_name)) {
        $path .= '/model/';
    } elseif (preg_match('/^View.+$/', $class_name)) {
        $path .= '/view/';
    } elseif (preg_match('/^Extension.+$/', $class_name)) {
        $path .= '/extension/';
    } elseif (preg_match('/^Api.+$/', $class_name)) {
        $path .= '/api/';
    }
    $class_path = e2pdf_autoloader_convert_name($class_name);
    $path .= $class_path . '.php';
    if (file_exists($path)) {
        include $path;
    }
}

if (is_array(spl_autoload_functions()) && in_array('__autoload', spl_autoload_functions(), false)) {
    spl_autoload_register('__autoload');
}
spl_autoload_register('e2pdf_autoloader');

$e2pdf_plugin_data = get_file_data(__FILE__, array('version' => 'Version'), false);
$helper = Helper_E2pdf_Helper::instance();
$helper->set('plugin_dir', plugin_dir_path(__FILE__));
$helper->set('upload_dir', $helper->get_wp_upload_dir('basedir') . '/e2pdf/');
$helper->set('tmp_dir', $helper->get('upload_dir') . 'tmp/');
$helper->set('cache_dir', $helper->get('upload_dir') . 'tmp/cache/');
$helper->set('pdf_dir', $helper->get('upload_dir') . 'pdf/');
$helper->set('fonts_dir', $helper->get('upload_dir') . 'fonts/');
$helper->set('tpl_dir', $helper->get('upload_dir') . 'tpl/');
$helper->set('viewer_dir', $helper->get('upload_dir') . 'viewer/');
$helper->set('bulk_dir', $helper->get('upload_dir') . 'bulks/');
$helper->set('wpcf7_dir', $helper->get('upload_dir') . 'wpcf7/');
$helper->set('plugin_file_path', __FILE__);
$helper->set('version', $e2pdf_plugin_data['version']);
$helper->set('plugin', plugin_basename(__FILE__));
$helper->set('slug', dirname(plugin_basename(__FILE__)));
$helper->set('cache', get_option('e2pdf_cache', '1'));
$e2pdf_wp_parse_args = wp_parse_args(home_url(add_query_arg(null, null)));
$helper->set('page', reset($e2pdf_wp_parse_args));
if (get_option('e2pdf_memory_time', '0')) {
    $helper->set('memory_debug', memory_get_usage());
    $helper->set('time_debug', microtime(true));
}
$loader = new Model_E2pdf_Loader();
$loader->load();
