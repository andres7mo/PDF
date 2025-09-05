<?php

/**
 * File: /model/e2pdf-convert.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Convert extends Model_E2pdf_Model {

    // to PHP
    public function toPHP($data = array()) {
        $content = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $content .= "'" . $key . "' => [";
                $content .= $this->toPHP($value);
                $content .= "],\n";
            } else {
                $content .= "'" . $key . "' => '" . str_replace("'", "\'", $value) . "',\n";
            }
        }
        return $content;
    }
}
