<?php

/**
 * E2pdf Get Encryption
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

class Helper_E2pdf_Encryption {

    public function random_md5() {
        return md5(microtime() . '_' . wp_generate_password());
    }
}
