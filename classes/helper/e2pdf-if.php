<?php

/**
 * E2pdf Shortcode If Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.22.00
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_If {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function get_delimiters() {
        return array(
            '[&&]',
            '[||]',
        );
    }

    public function get_brackets() {
        return array(
            '[(]',
            '[)]',
        );
    }

    public function get_comparators() {
        return array(
            '[==]',
            '[!=]',
            '[>]',
            '[>=]',
            '[<]',
            '[<=]',
            '[contains]',
            '[not_contains]',
            '[is_array]',
            '[in_array]',
            '[not_in_array]',
            '[in_list]',
            '[not_in_list]',
            '[array_key_exists]',
            '[array_key_not_exists]',
            '[isset]',
            '[not_isset]',
        );
    }

    public function get_shortcodes() {
        return array_merge($this->get_delimiters(), $this->get_brackets(), $this->get_comparators());
    }

    public function do_shortcode($atts = array(), $value = '', $extension = null) {
        $tags = array(
            'e2pdf-if-condition' => 'e2pdf-if-condition',
            'e2pdf-if-do' => 'e2pdf-if-do',
            'e2pdf-if-else' => 'e2pdf-if-else'
        );
        /* Backward compatibility */
        if (false === strpos($value, '[e2pdf-if-condition')) {
            $tags = array(
                'e2pdf-if-condition' => 'e2pdf-condition',
                'e2pdf-if-do' => 'e2pdf-do',
                'e2pdf-if-else' => 'e2pdf-else'
            );
        }
        $comparators = $this->get_shortcodes();
        $sub_values = preg_split('/(\\' . str_replace(array('||', '(', ')'), array('\|\|', '\(', '\)'), implode('|\\', $comparators)) . ')/', $this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-if-condition'], $value), -1, PREG_SPLIT_DELIM_CAPTURE);
        $condition = '';
        foreach ($sub_values as $sub_value) {
            if ($sub_value && in_array($sub_value, $comparators)) {
                $condition .= $sub_value;
            } elseif ($extension && method_exists($extension, 'render')) {
                $condition .= $extension->render($sub_value, array(), false);
            }
        }
        $result = $this->apply($condition, false, $extension);
        if ($result) {
            $response = $this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-if-do'], $value);
        } else {
            $response = $this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-if-else'], $value);
        }
        return $response;
    }

    public function apply($condition, $nested = false, $extension = null) {
        while (strpos($condition, '[(]') !== false && strpos($condition, '[)]') !== false) {
            preg_match_all('/\[\(\](?:(?!\[\(\]|\[\)\]).)+\[\)\]/', $condition, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    $condition = str_replace($match, $this->apply(substr($match, 3, -3), true), $condition, $extension);
                }
            }
        }
        $delimiters = $this->get_delimiters();
        $sub_conditions = preg_split('/(\\' . str_replace('||', '\|\|', implode('|\\', $delimiters)) . ')/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);
        $final_result = false;
        $delimiter = false;
        foreach ($sub_conditions as $sub_condition) {
            $result = false;
            if ($sub_condition && in_array($sub_condition, $delimiters)) {
                $delimiter = $sub_condition;
            } else {
                $expression = false;
                foreach ($this->get_comparators() as $comparator) {
                    if (false !== strpos($sub_condition, $comparator)) {
                        $expression = $comparator;
                        break;
                    }
                }
                $if = '';
                $value = '';
                if ($expression) {
                    $comparision = explode($expression, $sub_condition);
                    if (isset($comparision[0])) {
                        $if = $comparision[0];
                    }
                    if (isset($comparision[1])) {
                        $value = $comparision[1];
                    }
                } else {
                    $if = $sub_condition;
                }
                $result = false;
                switch ($expression) {
                    case '[==]':
                        $result = $if == $value ? true : false;
                        break;
                    case '[!=]':
                        $result = $if != $value ? true : false;
                        break;
                    case '[>]':
                        $result = $if > $value ? true : false;
                        break;
                    case '[>=]':
                        $result = $if >= $value ? true : false;
                        break;
                    case '[<]':
                        $result = $if < $value ? true : false;
                        break;
                    case '[<=]':
                        $result = $if <= $value ? true : false;
                        break;
                    case '[contains]':
                        if (empty($value) && empty($if)) {
                            $result = true;
                        } else {
                            $result = !empty($value) && strpos($if, $value) !== false ? true : false;
                        }
                        break;
                    case '[not_contains]':
                        if (empty($value) && empty($if)) {
                            $result = false;
                        } elseif (empty($value) && !empty($if)) {
                            $result = true;
                        } else {
                            $result = !empty($value) && strpos($if, $value) === false ? true : false;
                        }
                        break;
                    case '[is_array]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = is_array($unserialized) ? true : false;
                        break;
                    case '[in_array]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = is_array($unserialized) && in_array($if, $unserialized) ? true : false;
                        break;
                    case '[not_in_array]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = !is_array($unserialized) || (is_array($unserialized) && !in_array($if, $unserialized)) ? true : false;
                        break;
                    case '[in_list]':
                        $list_if = array_map('trim', explode(',', $if));
                        $list_value = array_map('trim', explode(',', $value));
                        $result = !array_diff($list_if, $list_value) ? true : false;
                        break;
                    case '[not_in_list]':
                        $list_if = array_map('trim', explode(',', $if));
                        $list_value = array_map('trim', explode(',', $value));
                        $result = !array_diff($list_if, $list_value) ? false : true;
                        break;
                    case '[array_key_exists]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = is_array($unserialized) && array_key_exists($if, $unserialized) ? true : false;
                        break;
                    case '[array_key_not_exists]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = !is_array($unserialized) || (is_array($unserialized) && !array_key_exists($if, $unserialized)) ? true : false;
                        break;
                    case '[isset]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = is_array($unserialized) && isset($unserialized[$if]) ? true : false;
                        break;
                    case '[not_isset]':
                        $unserialized = false;
                        if (is_serialized($value)) {
                            $unserialized = $this->helper->load('convert')->unserialize($value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                        }
                        $result = !is_array($unserialized) || (is_array($unserialized) && !isset($unserialized[$if])) ? true : false;
                        break;
                    default:
                        $result = $if ? true : false;
                        break;
                }
                if ($delimiter == '[||]') {
                    $final_result = $final_result || $result ? true : false;
                } elseif ($delimiter == '[&&]') {
                    $final_result = $final_result && $result ? true : false;
                } else {
                    $final_result = $result;
                }
            }
        }
        if ($nested) {
            return $final_result ? '1[==]1' : '1[==]0';
        } else {
            return $final_result;
        }
    }
}
