<?php

/**
 * E2pdf Post Helper
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

class Helper_E2pdf_Tplargs {

    private $tpl_args = array();

    /**
     * On init
     * Assign Tpl args
     * 
     * @param array $tpl_args - List of arguments
     */
    public function __construct($tpl_args = array()) {
        $this->tpl_args = $tpl_args;
    }

    /**
     * Get Tpl Value
     * 
     * @param string $key - Tpl key
     * 
     * @return mixed - Tpl value
     */
    public function get($key = false) {
        if (!$key) {
            if (!empty($this->tpl_args)) {
                return $this->tpl_args;
            } else {
                return array();
            }
        } else {

            if (isset($this->tpl_args[$key])) {
                return $this->tpl_args[$key];
            } else {
                return null;
            }
        }
    }

}
