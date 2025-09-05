<?php

/**
 * E2pdf Js Helper
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

class Helper_E2pdf_Js {

    /**
     * Prepare Key/Value to show in JavaScript
     * 
     * @param string $key - Key
     * @param array $data - Value
     * 
     * @return string - Prepared string to insert in JS
     */
    public function get_js_array($key, $data = array()) {
        $string = "var {$key} = " . json_encode($data);
        return $string;
    }

    /**
     * Prepare Key/Value to show in JavaScript
     * 
     * @param string $key - Key
     * @param string $value - Value
     * 
     * @return string - Prepared string to insert in JS
     */
    public function get_js_value($key, $value) {
        $string = "var {$key} = '{$value}'";
        return $string;
    }

}
