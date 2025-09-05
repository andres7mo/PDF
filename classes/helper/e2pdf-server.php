<?php

/**
 * E2Pdf Server Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.26.19
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Server {

    public function get($key = false) {
        if ($key) {
            return isset($_SERVER[$key]) ? wp_strip_all_tags(wp_unslash($_SERVER[$key])) : '';
        }
        return '';
    }

    public function isMobile() {
        $userAgent = $this->get('HTTP_USER_AGENT');
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            return true;
        }
        return false;
    }

    public function isIOS() {
        $userAgent = $this->get('HTTP_USER_AGENT');
        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'iPod') !== false) {
            return true;
        }
        return false;
    }

    public function isChrome() {
        $userAgent = $this->get('HTTP_USER_AGENT');
        if (stripos($userAgent, 'CriOS') !== false || stripos($userAgent, 'Chrome') !== false) {
            return true;
        }
        return false;
    }

    public function isOpera() {
        $userAgent = $this->get('HTTP_USER_AGENT');
        if (stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'OPT') !== false) {
            return true;
        }
        return false;
    }

    public function isFirefox() {
        $userAgent = $this->get('HTTP_USER_AGENT');
        if (stripos($userAgent, 'FxiOS') !== false || stripos($userAgent, 'Firefox') !== false) {
            return true;
        }
        return false;
    }

    public function isLoaderSupported() {
        if ($this->isIOS() && $this->isFirefox()) {
            return false;
        } elseif ($this->isMobile() && $this->isOpera()) {
            return false;
        }
        return true;
    }

    public function isPrintingSupported() {
        if ($this->isMobile() || $this->isIOS()) {
            return false;
        }
        return true;
    }

    public function isViewerSupported() {
        if ($this->isMobile() && $this->isChrome()) {
            return false;
        } elseif ($this->isIOS() && $this->isChrome()) {
            return false;
        }
        return true;
    }
}
