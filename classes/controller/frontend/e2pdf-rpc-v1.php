<?php

/**
 * E2Pdf Frontend Download Controller
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Controller_Frontend_E2pdf_Rpc_V1 extends Helper_E2pdf_View {

    public function zapier($rpc) {
        if (is_a($rpc, 'Model_E2pdf_Rpc')) {
            switch ($rpc->get('action')) {
                case 'auth':
                    if ($rpc->get_arg('api_key') == get_option('e2pdf_zapier_api_key')) {
                        wp_send_json_success();
                    } else {
                        wp_send_json_error(null, 401);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function adobe($rpc) {
        if (is_a($rpc, 'Model_E2pdf_Rpc')) {
            switch ($rpc->get('action')) {
                case 'auth':
                    if ($rpc->get_arg('api_key') == get_option('e2pdf_adobe_api_key')) {
                        if ($rpc->get_arg('code')) {
                            $this->redirect($this->helper->get_url(
                                            array(
                                                'page' => 'e2pdf-integrations',
                                                'action' => 'adobesign',
                                                'code' => $rpc->get_arg('code'),
                                                '_wpnonce' => wp_create_nonce('e2pdf_adobe'),
                                            )
                                    )
                            );
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
