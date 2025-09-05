<?php

/**
 * File: /helper/e2pdf-field.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Field {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function pre_shortcodes($value, $extension, $field = array()) {
        if (false !== strpos($value, '[')) {
            $replace = array(
                '[e2pdf-dataset]' => $extension->get('dataset') ? $extension->get('dataset') : '',
                '[e2pdf-userid]' => (int) $extension->get('user_id'),
                '[e2pdf-usercurrentid]' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
                '[pdf_url]' => '[e2pdf-url]',
                '[e2pdf-url]' => '',
                '[e2pdf-uid]' => '',
            );
            if (false !== strpos($value, '[e2pdf-url]') || false !== strpos($value, '[pdf_url]') || false !== strpos($value, '[e2pdf-uid]')) {
                $pdf_url = '';
                if ($extension->get('entry')) {
                    if ($extension->get('entry')->get_data('e2pdf-url')) {
                        $pdf_url = $extension->get('entry')->get_data('e2pdf-url');
                    } else {
                        if (!$extension->get('entry')->load_by_uid()) {
                            $extension->get('entry')->save();
                        }
                        $url_data = array(
                            'page' => 'e2pdf-download',
                            'uid' => $extension->get('entry')->get('uid'),
                        );
                        $pdf_url = esc_url_raw(
                                $this->helper->get_frontend_pdf_url(
                                        $url_data, false,
                                        array(
                                            'e2pdf_extension_render_shortcodes_site_url',
                                            'e2pdf_extension_' . $extension->info('key') . '_render_shortcodes_site_url',
                                        )
                                )
                        );
                        $replace['[e2pdf-uid]'] = $extension->get('entry')->get('uid');
                    }
                }
                $replace['[e2pdf-url]'] = $pdf_url;
            }
            if ($extension instanceof Extension_E2pdf_Formidable) {
                $replace['[e2pdf-dataset2]'] = $extension->get('dataset2') ? $extension->get('dataset2') : '';
            } elseif ($extension instanceof Extension_E2pdf_Wordpress || $extension instanceof Extension_E2pdf_Woocommerce) {
                $replace['[id]'] = isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID ? $extension->get('cached_post')->ID : '';
            }
            $replace = apply_filters('e2pdf_helper_field_pre_shortcodes', $replace, $value, $extension, $field);
            $value = str_replace(array_keys($replace), $replace, $value);
        }
        return $value;
    }

    public function inner_shortcodes($value, $extension, $field = array()) {
        if (false !== strpos($value, '[')) {

            $e2pdf_acf_repeater = 0;

            $args = apply_filters('e2pdf_extension_render_shortcodes_args', $extension->get('args'), isset($field['element_id']) ? $field['element_id'] : false, $extension->get('template_id'), $extension->get('item'), $extension->get('dataset'), false, false);
            $shortcode_tags = array(
                'e2pdf-arg',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($shortcode[2] === 'e2pdf-arg') {
                        if (isset($atts['key']) && isset($args[$atts['key']])) {
                            $value = str_replace($shortcode_value, $extension->strip_shortcodes($args[$atts['key']]), $value);
                        } else {
                            $value = str_replace($shortcode_value, '', $value);
                        }
                    }
                }
            }

            if (false !== strpos($value, '[e2pdf-arg')) {
                $value = preg_replace_callback(
                        '/(\[e2pdf-arg)([0-9]+)(\])/',
                        function ($m) use ($args) {
                            return isset($args['arg' . $m[2]]) ? $args['arg' . $m[2]] : '';
                        },
                        $value
                );
            }

            if ($extension instanceof Extension_E2pdf_Woocommerce || $extension instanceof Extension_E2pdf_Wordpress) {
                // [e2pdf-foreach] backward compatibility
                $shortcode_tags = array(
                    'e2pdf-foreach',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                foreach ($matches[1] as $key => $shortcode) {
                    if (strpos($shortcode, ':') !== false) {
                        $shortcode_tags[] = $shortcode;
                    }
                }
                if (!empty($tagnames)) {
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        if ($shortcode[2] == 'e2pdf-foreach') {
                            if ($extension instanceof Extension_E2pdf_Woocommerce) {
                                switch ($extension->get('item')) {
                                    case 'product':
                                    case 'product_variation':
                                        if (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wc-customer') { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                        } elseif (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wc-order') {
                                            if (!isset($atts['id']) && $extension->get('wc_order_id')) {
                                                $shortcode[3] .= ' id="' . $extension->get('wc_order_id') . '"';
                                            }
                                        } else {
                                            if (!isset($atts['id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                                                $shortcode[3] .= ' id="' . $extension->get('cached_post')->ID . '"';
                                            }
                                            if (!isset($atts['wc_order_id']) && $extension->get('wc_order_id')) {
                                                $shortcode[3] .= ' wc_order_id="' . $extension->get('wc_order_id') . '"';
                                            }

                                            if (!isset($atts['wc_product_item_id']) && $extension->get('wc_product_item_id')) {
                                                $shortcode[3] .= ' wc_product_item_id="' . $extension->get('wc_product_item_id') . '"';
                                            }
                                        }
                                        break;
                                    case 'shop_order':
                                        if (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wc-customer') { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                        } elseif (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wc-product') {
                                            if (!isset($atts['wc_order_id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                                                $shortcode[3] .= ' wc_order_id="' . $extension->get('cached_post')->ID . '"';
                                            }
                                        } else {
                                            if (!isset($atts['id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                                                $shortcode[3] .= ' id="' . $extension->get('cached_post')->ID . '"';
                                            }
                                        }
                                        break;
                                    case 'cart':
                                        if (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wc-customer') { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                        } elseif (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wc-product') {
                                            if (!isset($atts['wc_order_id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                                                $shortcode[3] .= ' wc_order_id="cart"';
                                            }
                                        } else {
                                            if (!isset($atts['id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                                                $shortcode[3] .= ' id="' . $extension->get('cached_post')->ID . '"';
                                            }
                                        }
                                        break;
                                    default:
                                        break;
                                }
                            } elseif ($extension instanceof Extension_E2pdf_Wordpress) {
                                if (isset($atts['shortcode']) && $atts['shortcode'] == 'e2pdf-wp') {
                                    if (!isset($atts['id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                                        $shortcode[3] .= ' id="' . $extension->get('cached_post')->ID . '"';
                                    }
                                }
                            }
                            $value = str_replace($shortcode_value, do_shortcode_tag($shortcode), $value);
                        }
                    }
                }

                if (false !== strpos($value, '[e2pdf-for') && false !== strpos($value, '[e2pdf-acf-repeater') && false !== strpos($value, '[e2pdf-for-key')) {
                    $value = preg_replace('/\[(e2pdf-acf-repeater[^\]]*?\[e2pdf-for-key\][^\]]*?)\](.*?)\[(\/e2pdf-acf-repeater)\]/s', '{{$1}}$2{{$3}}', $value, -1, $e2pdf_acf_repeater);
                }
                $value = $this->e2pdf_acf_repeater($value, $extension);
            }

            $shortcode_tags = array(
                'e2pdf-for',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    $value = str_replace($shortcode_value, $this->helper->load('for')->do_shortcode(is_array($atts) ? $atts : array(), $shortcode[5], 0, $extension), $value);
                }
            }

            if ($e2pdf_acf_repeater > 0) {
                $value = preg_replace('/{{(e2pdf-acf-repeater[^\]]*?)}}(.*?){{(\/e2pdf-acf-repeater)}}/s', '[$1]$2[$3]', $value);
                $value = $this->e2pdf_acf_repeater($value, $extension);
            }

            $shortcode_tags = array(
                'e2pdf-if',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    $value = str_replace($shortcode_value, $this->helper->load('if')->do_shortcode(is_array($atts) ? $atts : array(), $shortcode[5], $extension), $value);
                }
            }

            $shortcode_tags = array(
                'e2pdf-user',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($shortcode[2] === 'e2pdf-user') {
                        if (!isset($atts['id']) && $extension->get('user_id')) {
                            $shortcode[3] .= ' id=' . $extension->get('user_id') . '';
                        }
                        if (substr($shortcode_value, -13) === '[/e2pdf-user]') {
                            if ($shortcode[5]) {
                                $shortcode[5] = $extension->render($shortcode[5], array(), false);
                            }
                            $value = str_replace($shortcode_value, '[e2pdf-user' . $shortcode[3] . ']' . $shortcode[5] . '[/e2pdf-user]', $value);
                        } else {
                            $value = str_replace($shortcode_value, '[e2pdf-user' . $shortcode[3] . ']', $value);
                        }
                    }
                }
            }
        }
        return $value;
    }

    public function wrapper_shortcodes($value, $extension, $field = array(), $do_shortcode = false) {
        if (false !== strpos($value, '[')) {
            $shortcode_tags = array(
                'e2pdf-format-number',
                'e2pdf-format-date',
                'e2pdf-format-output',
                'e2pdf-math',
            );
            if ($extension instanceof Extension_E2pdf_Formidable) {
                $shortcode_tags[] = 'frm-math';
            }
            if ($extension instanceof Extension_E2pdf_Gravity) {
                $shortcode_tags[] = 'gravityforms';
            }
            $shortcode_tags = apply_filters('e2pdf_extension_render_shortcodes_tags', $shortcode_tags, $extension, $field, $do_shortcode);
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    if ($shortcode[2] === 'gravityforms') {
                        if (class_exists('GFCommon')) {
                            $value = str_replace($shortcode_value, GFCommon::replace_variables($shortcode_value, $extension->get('cached_form'), $extension->get('cached_entry'), false, false, false, 'text'), $value);
                        }
                    } else {
                        if ($shortcode[5]) {
                            $shortcode[5] = $extension->render($shortcode[5], array(), false);
                        }
                        if ($do_shortcode) {
                            $value = str_replace($shortcode_value, do_shortcode_tag($shortcode), $value);
                        } else {
                            $value = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']' . $shortcode[5] . '[/' . $shortcode[2] . ']', $value);
                        }
                    }
                }
            }
        }
        return $value;
    }

    public function do_shortcodes($value, $extension, $field = array()) {
        $element_id = isset($field['element_id']) ? $field['element_id'] : false;
        $value = apply_filters('e2pdf_extension_render_shortcodes_pre_do_shortcode', $value, $element_id, $extension->get('template_id'), $extension->get('item'), $extension->get('dataset'), $extension->get('item2'), $extension->get('dataset2'));
        $value = do_shortcode($value);
        $value = apply_filters('e2pdf_extension_render_shortcodes_after_do_shortcode', $value, $element_id, $extension->get('template_id'), $extension->get('item'), $extension->get('dataset'), $extension->get('item2'), $extension->get('dataset2'));
        return $value;
    }

    public function render($value, $extension, $field = array()) {
        $type = isset($field['type']) ? $field['type'] : false;
        $element_id = isset($field['element_id']) ? $field['element_id'] : false;
        if (false !== strpos($value, '[pdf_num]') || false !== strpos($value, '[e2pdf-num]')) {
            $replace = array(
                '[pdf_num]' => '[e2pdf-num]',
                '[e2pdf-num]' => '',
            );
            if ($extension->get('entry')) {
                if (!$extension->get('entry')->load_by_uid()) {
                    $extension->get('entry')->save();
                }
                $replace['[e2pdf-num]'] = $extension->get('entry')->get('pdf_num') + 1;
            }
            $value = str_replace(array_keys($replace), $replace, $value);
        }

        switch ($type) {
            case 'e2pdf-image':
            case 'e2pdf-signature':
                $esig = isset($field['properties']['esig']) && $field['properties']['esig'] ? true : false;
                $text = isset($field['properties']['text']) && $field['properties']['text'] ? true : false;
                if ($esig) {
                    $value = '';
                } else {
                    $value = $this->helper->load('properties')->apply($field, $value);
                    $value = $this->helper->load('translator')->translate($value, 'full');
                    $file = false;
                    if (!$text) {
                        if ($this->helper->load('pdf')->get_extension(trim($value))) {
                            $file = $this->helper->load('pdf')->get_pdf($value, $extension->info('key'));
                        } else {
                            $file = $this->helper->load('image')->get_image($value, $extension->info('key'), $field);
                        }
                    }
                    if ($file) {
                        $value = $file;
                    } elseif (!$text && $value && 0 === strpos($value, 'image/jsignature;base30,')) {
                        $options = apply_filters(
                                'e2pdf_image_sig_output_options',
                                array(
                                    'bgColour' => 'transparent',
                                    'penColour' => isset($field['properties']['text_color']) && $field['properties']['text_color'] ? $this->helper->load('convert')->to_hex_color($field['properties']['text_color']) : array(0x14, 0x53, 0x94),
                                ), $element_id, $extension->get('template_id')
                        );

                        $model_e2pdf_signature = new Model_E2pdf_Signature();
                        $value = $model_e2pdf_signature->j_signature($value, $options);
                    } elseif (isset($field['properties']['only_image']) && $field['properties']['only_image']) {
                        $value = '';
                    } else {
                        $value = $extension->strip_shortcodes($value);
                        $font = false;
                        $model_e2pdf_font = new Model_E2pdf_Font();
                        if (isset($field['properties']['text_font']) && $field['properties']['text_font']) {
                            $font = $model_e2pdf_font->get_font_path($field['properties']['text_font']);
                        } elseif ($extension instanceof Extension_E2pdf_Formidable && class_exists('FrmSigAppHelper')) {
                            if (file_exists(FrmSigAppHelper::plugin_path() . '/assets/journal.ttf')) {
                                $font = FrmSigAppHelper::plugin_path() . '/assets/journal.ttf';
                            }
                        }
                        if (!$font) {
                            $font = $model_e2pdf_font->get_font_path('Noto Sans Regular');
                        }
                        if (!$font) {
                            $font = $model_e2pdf_font->get_font_path('Noto Sans');
                        }

                        $options = apply_filters(
                                'e2pdf_image_sig_output_options',
                                array(
                                    'bgColour' => 'transparent',
                                    'penColour' => isset($field['properties']['text_color']) && $field['properties']['text_color'] ? $this->helper->load('convert')->to_hex_color($field['properties']['text_color']) : array(0x14, 0x53, 0x94),
                                    'font' => $font,
                                    'fontSize' => isset($field['properties']['text_font_size']) && $field['properties']['text_font_size'] ? $field['properties']['text_font_size'] : 150,
                                ), $element_id, $extension->get('template_id')
                        );

                        /* Formidable Forms 1.16.x compatbility filter */
                        if ($extension instanceof Extension_E2pdf_Formidable) {
                            $options = apply_filters('e2pdf_frm_sig_output_options', $options, $element_id);
                        }
                        $model_e2pdf_signature = new Model_E2pdf_Signature();
                        $value = $model_e2pdf_signature->ttf_signature($value, $options);
                    }
                }
                break;
            case 'e2pdf-qrcode':
                $value = $this->helper->load('properties')->apply($field, $value);
                $value = $this->helper->load('translator')->translate($value, 'full');
                $value = $this->helper->load('qrcode')->qrcode($extension->strip_shortcodes($value), $field);
                break;
            case 'e2pdf-barcode':
                $value = $this->helper->load('properties')->apply($field, $value);
                $value = $this->helper->load('translator')->translate($value, 'full');
                $value = $this->helper->load('qrcode')->barcode($extension->strip_shortcodes($value), $field);
                break;
            case 'e2pdf-graph':
                $value = $this->helper->load('properties')->apply($field, $value);
                $value = $this->helper->load('translator')->translate($value, 'full');
                $value = $this->helper->load('graph')->graph($extension->strip_shortcodes($value), $field);
                break;
            default:
                if (!($extension instanceof Extension_E2pdf_Formidable)) {
                    $value = $extension->convert_shortcodes($value);
                }
                $value = $this->helper->load('properties')->apply($field, $value);
                break;
        }
        return $value;
    }

    public function render_checkbox($value, $extension, $field = array(), $separator = ', ') {
        if (isset($field['type']) && $field['type'] === 'e2pdf-checkbox' && isset($field['properties']['option'])) {
            $option = $extension->render($field['properties']['option']);
            $sub_options = explode($separator, $option);
            $checked = explode($separator, $value);
            if (!array_diff($sub_options, $checked)) {
                $value = $option;
            } else {
                $value = '';
            }
        }
        return $value;
    }

    public function render_select_multiline($value, $extension, $field = array(), $separator = ', ') {
        if (isset($field['type']) && $field['type'] === 'e2pdf-select' && !empty($field['properties']['multiline']) && isset($field['properties']['options'])) {
            $options = preg_split('/\r?\n/', $extension->render($field['properties']['options']));
            $selected = array();
            $checked = explode($separator, $value);
            foreach ($options as $option) {
                $sub_options = explode($separator, $option);
                if (!array_diff($sub_options, $checked)) {
                    $selected[] = $option;
                }
            }
            $value = implode(', ', $selected);
        }
        return $value;
    }

    public function e2pdf_acf_repeater($value, $extension) {
        $shortcode_tags = array(
            'e2pdf-acf-repeater',
        );
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
        $tagnames = array_intersect($shortcode_tags, $matches[1]);
        if (!empty($tagnames)) {
            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
            foreach ($shortcodes[0] as $key => $shortcode_value) {
                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                $atts = shortcode_parse_atts($shortcode[3]);
                if ($extension instanceof Extension_E2pdf_Wordpress) {
                    if (!isset($atts['post_id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                        if ($extension->get('item') == '-3') {
                            $shortcode[3] .= ' post_id=user_' . $extension->get('cached_post')->ID . '';
                        } else {
                            $shortcode[3] .= ' post_id=' . $extension->get('cached_post')->ID . '';
                        }
                    }
                } elseif ($extension instanceof Extension_E2pdf_Woocommerce) {
                    if (!isset($atts['post_id']) && isset($extension->get('cached_post')->ID) && $extension->get('cached_post')->ID) {
                        if ($extension->get('item') == 'product_variation' && isset($extension->get('cached_post')->post_parent)) {
                            $shortcode[3] .= ' post_id=' . $extension->get('cached_post')->post_parent . '';
                        } else {
                            $shortcode[3] .= ' post_id=' . $extension->get('cached_post')->ID . '';
                        }
                    }
                }
                $value = str_replace($shortcode_value, do_shortcode_tag($shortcode), $value);
            }
        }
        return $value;
    }
}
