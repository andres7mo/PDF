<?php

/**
 * File: /model/e2pdf-model.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Model {

    protected $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

}
