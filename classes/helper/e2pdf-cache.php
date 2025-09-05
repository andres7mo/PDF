<?php

/**
 * E2Pdf Cache Helper
 * 
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.24.05
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Cache {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function purge_cache() {
        $this->purge_objects_cache();
        $this->purge_fonts_cache();
        $this->purge_pdfs_cache();
    }

    public function pre_objects_cache() {
        if (function_exists('w3tc_dbcache_flush')) {
            w3tc_dbcache_flush();
        }
        if (
                class_exists('SiteGround_Optimizer\Supercacher\Supercacher') &&
                class_exists('SitePress') &&
                get_option('siteground_optimizer_enable_memcached') &&
                function_exists('wp_cache_flush')
        ) {
            wp_cache_flush();
        }
    }

    public function purge_objects_cache() {
        if (function_exists('w3tc_dbcache_flush')) {
            w3tc_dbcache_flush();
        }
        wp_cache_flush();
    }

    public function purge_fonts_cache() {
        update_option('e2pdf_cached_fonts', array());
    }

    public function cache_pdf($cached_pdf = '', $request = array()) {
        if ($cached_pdf && get_option('e2pdf_cache_pdfs', '0') && !@file_exists($this->helper->get('cache_dir') . $cached_pdf)) {
            if (!isset($request['error']) && !empty($request['file'])) {
                file_put_contents($this->helper->get('cache_dir') . $cached_pdf, $request['file']);
            }
        }
    }

    public function get_cached_pdf($cached_pdf = '') {
        $request = array();
        if ($cached_pdf && get_option('e2pdf_cache_pdfs', '0') && @file_exists($this->helper->get('cache_dir') . $cached_pdf)) {
            $request = array(
                'file' => @file_get_contents($this->helper->get('cache_dir') . $cached_pdf),
            );
            $this->purge_pdfs_cache_ttl();
        }
        return $request;
    }

    public function purge_pdfs_cache_ttl() {
        $files = glob($this->helper->get('cache_dir') . '*', GLOB_MARK);
        $ttl = max(10, (int) get_option('e2pdf_cache_pdfs_ttl', '180'));
        foreach ($files as $file) {
            if (false === strpos($file, 'index.php') && false === strpos($file, '.htaccess') && (time() - filectime($file)) > $ttl) {
                @unlink($file);
            }
        }
    }

    public function purge_pdfs_cache() {
        $files = glob($this->helper->get('cache_dir') . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (false === strpos($file, 'index.php') && false === strpos($file, '.htaccess')) {
                @unlink($file);
            }
        }
    }

    public function purge_tmp_cache() {
        $files = glob($this->helper->get('tmp_dir') . '*', GLOB_MARK);
        $ttl = (int) get_option('e2pdf_cache_tmp_ttl', '7200');
        foreach ($files as $file) {
            if (0 === strpos(basename($file), 'e2pdf') && (time() - filectime($file)) > $ttl) {
                $this->helper->delete_dir($file);
            }
        }
    }
}
