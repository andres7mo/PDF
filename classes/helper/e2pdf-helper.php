<?php

/**
 * E2Pdf Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Helper {

    protected static $instance = null;
    private $helper;

    const CHMOD_DIR = 0755;
    const CHMOD_FILE = 0644;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set option by Key
     * @param string $key - Key of option
     * @param mixed $value - Value of option
     */
    public function set($key, $value) {
        if (!$this->helper) {
            $this->helper = new stdClass();
        }
        $this->helper->$key = $value;
    }

    /**
     * Add value to option by Key
     * @param string $key - Key of option
     *  @param mixed $value - Value of option
     */
    public function add($key, $value) {
        if (!$this->helper) {
            $this->helper = new stdClass();
        }

        if (isset($this->helper->$key)) {
            if (is_array($this->helper->$key)) {
                array_push($this->helper->$key, $value);
            }
        } else {
            $this->helper->$key = array();
            array_push($this->helper->$key, $value);
        }
    }

    /**
     * Unset option
     * @param string $key - Key of option
     */
    public function deset($key) {
        if (isset($this->helper->$key)) {
            unset($this->helper->$key);
        }
    }

    /**
     * Set option
     * @param string $key - Key of option
     * @return mixed - Get value of option by Key
     */
    public function get($key) {
        if (isset($this->helper->$key)) {
            return $this->helper->$key;
        } else {
            return '';
        }
    }

    /**
     * Get url path
     * @param string $url - Url path
     * @return string - Url path
     */
    public function get_url_path($url) {
        return plugins_url($url, $this->get('plugin_file_path'));
    }

    /**
     * Get url
     * @param array $data - Array list of url params
     * @param string $prefix -  Prefix of url
     * @return string Url
     */
    public function get_url($data = array(), $prefix = 'admin.php?') {
        $url = $prefix . http_build_query($data);
        return admin_url($url);
    }

    /**
     * Get Ip address
     * @return mixed - IP address or FALSE
     */
    public function get_ip() {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = false;
        }
        return $ip;
    }

    /**
     * Remove dir and its content
     * @param string $dir - Path of directory to remove
     */
    public function delete_dir($dir) {
        if (!is_dir($dir)) {
            return;
        }
        if (substr($dir, strlen($dir) - 1, 1) != '/') {
            $dir .= '/';
        }
        $files = glob($dir . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->delete_dir($file);
            } else {
                unlink($file);
            }
        }
        if (file_exists($dir . '.htaccess')) {
            unlink($dir . '.htaccess');
        }
        rmdir($dir);
    }

    public function create_dir($dir = false, $recursive = false, $create_index = true, $create_htaccess = false) {
        if ($dir && !file_exists($dir)) {
            if (mkdir($dir, self::CHMOD_DIR, $recursive)) {
                if ($create_index) {
                    $index = $dir . 'index.php';
                    if (!file_exists($index)) {
                        $this->create_file($index, "<?php\n// Silence is golden.\n?>");
                    }
                }
                if ($create_htaccess) {
                    $htaccess = $dir . '.htaccess';
                    if (!file_exists($htaccess)) {
                        $this->create_file($htaccess, 'DENY FROM ALL');
                    }
                }
            }
        }
        return is_dir($dir);
    }

    public function create_file($file = false, $content = '') {
        if ($file && !file_exists($file)) {
            if (file_put_contents($file, $content)) {
                chmod($file, self::CHMOD_FILE);
            }
        }
        return is_file($file);
    }

    public function get_wp_upload_dir($key = 'basedir') {

        $wp_upload_dir = wp_upload_dir();
        if (defined('E2PDF_UPLOADS')) {
            $siteurl = get_option('siteurl');
            $upload_path = trim(get_option('upload_path'));

            if (empty($upload_path) || 'wp-content/uploads' === $upload_path) {
                $dir = WP_CONTENT_DIR . '/uploads';
            } elseif (0 !== strpos($upload_path, ABSPATH)) {
                // $dir is absolute, $upload_path is (maybe) relative to ABSPATH.
                $dir = path_join(ABSPATH, $upload_path);
            } else {
                $dir = $upload_path;
            }

            $url = get_option('upload_url_path');
            if (!$url) {
                if (empty($upload_path) || ( 'wp-content/uploads' === $upload_path ) || ( $upload_path == $dir )) {
                    $url = WP_CONTENT_URL . '/uploads';
                } else {
                    $url = trailingslashit($siteurl) . $upload_path;
                }
            }

            if (!(is_multisite() && get_site_option('ms_files_rewriting'))) {
                $dir = ABSPATH . E2PDF_UPLOADS;
                $url = trailingslashit($siteurl) . E2PDF_UPLOADS;
            }

            if (is_multisite() && !( is_main_network() && is_main_site() && defined('MULTISITE') )) {
                if (!get_site_option('ms_files_rewriting')) {
                    if (defined('MULTISITE')) {
                        $ms_dir = '/sites/' . get_current_blog_id();
                    } else {
                        $ms_dir = '/' . get_current_blog_id();
                    }
                    $dir .= $ms_dir;
                    $url .= $ms_dir;
                } elseif (!ms_is_switched()) {
                    $dir = ABSPATH . E2PDF_UPLOADS;
                    $url = trailingslashit($siteurl) . 'files';
                }
            }

            $basedir = $dir;
            $baseurl = $url;

            $subdir = '';
            if (get_option('uploads_use_yearmonth_folders')) {
                $time = current_time('mysql');
                $y = substr($time, 0, 4);
                $m = substr($time, 5, 2);
                $subdir = "/$y/$m";
            }

            $dir .= $subdir;
            $url .= $subdir;

            if (!file_exists($basedir)) {
                $this->create_dir($basedir, true, false, false);
            }

            $wp_upload_dir = array(
                'path' => $dir,
                'url' => $url,
                'subdir' => $subdir,
                'basedir' => $basedir,
                'baseurl' => $baseurl,
                'error' => false,
            );
        }

        if ($key && isset($wp_upload_dir[$key])) {
            return $wp_upload_dir[$key];
        } else {
            return '';
        }
    }

    public function get_upload_url($path = false) {
        if ($path) {
            return $this->get_wp_upload_dir('baseurl') . '/' . basename(untrailingslashit($this->get('upload_dir'))) . '/' . $path;
        } else {
            return $this->get_wp_upload_dir('baseurl') . '/' . basename(untrailingslashit($this->get('upload_dir')));
        }
    }

    /**
     * Check if array is multidimensional
     * @return boolean
     */
    public function is_multidimensional($a) {
        if (is_array($a)) {
            foreach ($a as $v) {
                if (is_array($v) || is_object($v)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get Capabilities
     * @return array()
     */
    public function get_caps() {
        $caps = array(
            'e2pdf' => array(
                'name' => __('Create PDF', 'e2pdf'),
                'cap' => 'e2pdf',
            ),
            'e2pdf_templates' => array(
                'name' => __('Templates', 'e2pdf'),
                'cap' => 'e2pdf_templates',
            ),
            'e2pdf_settings' => array(
                'name' => __('Settings', 'e2pdf'),
                'cap' => 'e2pdf_settings',
            ),
            'e2pdf_license' => array(
                'name' => __('License', 'e2pdf'),
                'cap' => 'e2pdf_license',
            ),
            'e2pdf_debug' => array(
                'name' => __('Debug', 'e2pdf'),
                'cap' => 'e2pdf_debug',
            ),
        );
        return $caps;
    }

    /**
     * Load sub-helper
     * @return object
     */
    public function load($helper) {
        $model = null;
        $class = 'Helper_E2pdf_' . ucfirst($helper);
        if (class_exists($class)) {
            if (!$this->get($class)) {
                $this->set($class, new $class());
            }
            $model = $this->get($class);
        }
        return $model;
    }

    /**
     * Get Frontend Site URL
     * @return string
     */
    public function get_frontend_site_url() {
        return get_option('e2pdf_url_format', 'siteurl') === 'home' ? home_url('/') : site_url('/');
    }

    /**
     * Get Frontend PDF URL
     * @return string
     */
    public function get_frontend_pdf_url($url_data = array(), $site_url = false, $filters = array()) {

        if ($site_url === false) {
            $site_url = $this->get_frontend_site_url();
        }

        $site_url = apply_filters('e2pdf_helper_get_frontend_pdf_url_pre_site_url', $site_url);

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $site_url = apply_filters($filter, $site_url);
            }
        }

        $url_query = wp_parse_url($site_url, PHP_URL_QUERY);
        if ($url_query) {
            $site_url = str_replace('?' . $url_query, '', $site_url);
            $queries = explode('&', $url_query);
            foreach ($queries as $query) {
                $q = explode('=', $query);
                if (isset($q[0]) && isset($q[1])) {
                    $url_data[$q[0]] = $q[1];
                } elseif (isset($q[0])) {
                    $url_data[$q[0]] = '';
                }
            }
        }

        if (get_option('e2pdf_mod_rewrite', '0')) {
            $site_url = rtrim($site_url, '/') . '/' . get_option('e2pdf_mod_rewrite_url', 'e2pdf/%uid%/');
            if (isset($url_data['uid'])) {
                $site_url = str_replace('%uid%', $url_data['uid'], $site_url);
                unset($url_data['uid']);
            } else {
                $site_url = str_replace('%uid%', '', $site_url);
            }
            if (isset($url_data['page'])) {
                unset($url_data['page']);
            }
        }

        if (isset($url_data['download_name'])) {
            $site_url = trailingslashit($site_url) . $url_data['download_name'];
            unset($url_data['download_name']);
        }

        $site_url = apply_filters('e2pdf_helper_get_frontend_pdf_url_site_url', $site_url);
        $url_data = apply_filters('e2pdf_helper_get_frontend_pdf_url_url_data', $url_data);

        $url = add_query_arg($url_data, $site_url);

        return $this->load('translator')->translate_url($url);
    }

    public function get_frontend_local_pdf_url($pdf, $site_url = false, $filters = array()) {
        if ($site_url === false) {
            $site_url = $this->get_frontend_site_url();
        }

        $site_url = apply_filters('e2pdf_helper_get_frontend_pdf_url_pre_site_url', $site_url);

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $site_url = apply_filters($filter, $site_url);
            }
        }
        return $site_url . str_replace(ABSPATH, '', $pdf);
    }

    /**
     * Get Domain
     * @return string - Domain
     */
    public function get_site_url() {
        $site_url = false;
        if (class_exists('SitePress')) {
            $settings = get_option('icl_sitepress_settings');
            if (isset($settings['language_negotiation_type']) && $settings['language_negotiation_type'] == '2') {
                global $wpdb;
                $site_url = $wpdb->get_var($wpdb->prepare('SELECT option_value FROM `' . $wpdb->options . '` WHERE option_name = %s LIMIT 1', 'siteurl'));
            }
        }
        if (!$site_url) {
            $site_url = site_url();
        }
        return $site_url;
    }
}
