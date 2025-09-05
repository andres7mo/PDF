<?php

/**
 * E2pdf WPBakery Page Builder Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.22.00
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Vc {
    
    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function params($type = 'e2pdf-download') {
        global $wpdb;

        $templates = $wpdb->get_results($wpdb->prepare('SELECT `ID`, `title` FROM `' . $wpdb->prefix . 'e2pdf_templates' . '` WHERE extension = %s OR extension = %s', 'wordpress', 'woocommerce'), ARRAY_A);

        $params = array(
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Template ID', 'e2pdf'),
                'param_name' => 'id',
                'group' => esc_html__('General', 'e2pdf'),
                'value' => array_map(
                        function ($val) {
                            return array(
                                'value' => $val['ID'],
                                'label' => $val['ID'] . ': ' . esc_html($val['title'])
                            );
                        },
                        $templates
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('Class', 'e2pdf'),
                'param_name' => 'class',
                'group' => esc_html__('Style', 'e2pdf'),
                'value' => '',
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('PDF Name', 'e2pdf'),
                'param_name' => 'name',
                'group' => esc_html__('Pdf', 'e2pdf'),
                'value' => '',
            ),
            array(
                'type' => 'textfield',
                'heading' => esc_html__('PDF Open Password', 'e2pdf'),
                'param_name' => 'password',
                'group' => esc_html__('Pdf', 'e2pdf'),
                'value' => '',
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('PDF Flatten', 'e2pdf'),
                'param_name' => 'flatten',
                'group' => esc_html__('Pdf', 'e2pdf'),
                'value' => array(
                    array(
                        'value' => '',
                        'label' => esc_html__('Default', 'e2pdf'),
                    ),
                    array(
                        'value' => '0',
                        'label' => esc_html__('No', 'e2pdf'),
                    ),
                    array(
                        'value' => '1',
                        'label' => esc_html__('Yes', 'e2pdf'),
                    ),
                    array(
                        'value' => '2',
                        'label' => esc_html__('Full', 'e2pdf'),
                    ),
                )
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('Format', 'e2pdf'),
                'param_name' => 'format',
                'group' => esc_html__('Pdf', 'e2pdf'),
                'value' => array(
                    array(
                        'value' => '',
                        'label' => esc_html__('Default', 'e2pdf'),
                    ),
                    array(
                        'value' => 'pdf',
                        'label' => esc_html__('pdf', 'e2pdf'),
                    ),
                    array(
                        'value' => 'jpg',
                        'label' => esc_html__('jpg', 'e2pdf'),
                    ),
                )
            ),
            array(
                'type' => 'dropdown',
                'heading' => esc_html__('PDF Inline', 'e2pdf'),
                'param_name' => 'inline',
                'group' => esc_html__('Pdf', 'e2pdf'),
                'value' => array(
                    array(
                        'value' => '',
                        'label' => esc_html__('Default', 'e2pdf'),
                    ),
                    array(
                        'value' => 'true',
                        'label' => esc_html__('Yes', 'e2pdf'),
                    ),
                    array(
                        'value' => 'false',
                        'label' => esc_html__('No', 'e2pdf'),
                    ),
                )
            ),
        );

        if ($type == 'e2pdf-download') {
            $params[] = array(
                'type' => 'textfield',
                'heading' => esc_html__('Button Title', 'e2pdf'),
                'param_name' => 'button_title',
                'group' => esc_html__('Style', 'e2pdf'),
                'value' => '',
            );
        }

        if ($type == 'e2pdf-view') {
            $params[] = array(
                'type' => 'dropdown',
                'heading' => esc_html__('Zoom', 'e2pdf'),
                'param_name' => 'zoom',
                'group' => esc_html__('Style', 'e2pdf'),
                'value' => array(
                    array(
                        'value' => '',
                        'label' => esc_html__('Default', 'e2pdf'),
                    ),
                    array(
                        'value' => 'page-width',
                        'label' => 'page-width',
                    ),
                    array(
                        'value' => 'page-height',
                        'label' => 'page-height',
                    ),
                    array(
                        'value' => 'page-fit',
                        'label' => 'page-fit',
                    ),
                    array(
                        'value' => 'auto',
                        'label' => 'auto',
                    ),
                )
            );

            $params[] = array(
                'type' => 'dropdown',
                'heading' => esc_html__('Responsive', 'e2pdf'),
                'param_name' => 'responsive',
                'group' => esc_html__('Style', 'e2pdf'),
                'value' => array(
                    array(
                        'value' => '',
                        'label' => esc_html__('Default', 'e2pdf'),
                    ),
                    array(
                        'value' => 'true',
                        'label' => 'true',
                    ),
                    array(
                        'value' => 'page',
                        'label' => 'page',
                    ),
                )
            );
        }

        return $params;
    }
}
