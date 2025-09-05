<?php

/**
 * File: /model/e2pdf-action.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Action extends Model_E2pdf_Model {

    private $extension;

    public function load($extension) {
        $this->extension = $extension;
    }

    public function process_actions($page) {
        if (!$page || !$this->extension) {
            return $page;
        }

        if (isset($page['actions']) && !empty($page['actions'])) {
            foreach ($page['actions'] as $action) {
                $page = $this->process_action($page, $action);
            }
        }

        if (isset($page['hidden']) && $page['hidden']) {  // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
        } else {
            if (!empty($page['elements'])) {
                foreach ($page['elements'] as $el_key => $el_value) {
                    if (isset($el_value['actions']) && !empty($el_value['actions'])) {
                        foreach ($el_value['actions'] as $action) {
                            $el_value = $this->process_action($el_value, $action);
                        }
                        $page['elements'][$el_key] = $el_value;
                        if ((isset($el_value['hidden']) && $el_value['hidden']) || ($el_value['page_id'] != $page['page_id'])) {
                            unset($page['elements'][$el_key]);
                        }
                    }
                }
            }
        }

        return $page;
    }

    public function process_global_actions($actions = array()) {
        if (!empty($actions) && $this->extension) {
            foreach ($actions as $action_key => $action) {
                $actions[$action_key] = $this->process_action($action, $action);
            }
        }
        return $actions;
    }

    public function process_page_id($page) {
        $elements = array();
        if ($page && $this->extension) {
            if (!empty($page['elements'])) {
                foreach ($page['elements'] as $el_key => $el_value) {
                    if (isset($el_value['actions']) && !empty($el_value['actions'])) {
                        foreach ($el_value['actions'] as $action) {
                            if ($action['action'] == 'change' && isset($action['property']) && $action['property'] == 'page_id') {
                                $el_value = $this->process_action($el_value, $action);
                            }
                        }
                        if ($el_value['page_id'] != $page['page_id']) {
                            $elements[] = $el_value;
                        }
                    }
                }
            }
        }
        return $elements;
    }

    private function process_action($element, $action) {

        $apply = false;
        if (isset($action['conditions']) && !empty($action['conditions'])) {
            foreach ($action['conditions'] as $condition) {
                $apply = $this->process_condition($condition);
                if ($action['apply'] == 'any' && $apply) {
                    break;
                } elseif ($action['apply'] == 'all' && !$apply) {
                    break;
                }
            }
        }

        if ($apply) {
            $element = $this->apply_action($element, $action);
        } else {
            $element = $this->apply_else_action($element, $action);
        }

        return $element;
    }

    private function process_condition($condition) {

        $result = false;
        $if = $this->extension->render($condition['if']);
        $value = $this->extension->render($condition['value']);

        switch ($condition['condition']) {
            case '=':
                $result = $if == $value ? true : false;
                break;
            case '!=':
                $result = $if != $value ? true : false;
                break;
            case '>':
                $result = $if > $value ? true : false;
                break;
            case '>=':
                $result = $if >= $value ? true : false;
                break;
            case '<':
                $result = $if < $value ? true : false;
                break;
            case '<=':
                $result = $if <= $value ? true : false;
                break;
            case 'like':
                if (empty($value) && empty($if)) {
                    $result = true;
                } else {
                    $result = !empty($value) && strpos($if, $value) !== false ? true : false;
                }
                break;
            case 'not_like':
                if (empty($value) && empty($if)) {
                    $result = false;
                } elseif (empty($value) && !empty($if)) {
                    $result = true;
                } else {
                    $result = !empty($value) && strpos($if, $value) === false ? true : false;
                }
                break;
            case 'in_array':
                $unserialized = false;
                if (is_serialized($value)) {
                    $unserialized = $this->helper->load('convert')->unserialize($value);
                }
                // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
                $result = is_array($unserialized) && in_array($if, $unserialized) ? true : false;
                break;
            case 'not_in_array':
                $unserialized = false;
                if (is_serialized($value)) {
                    $unserialized = $this->helper->load('convert')->unserialize($value);
                }
                // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
                $result = is_array($unserialized) && in_array($if, $unserialized) ? false : true;
                break;
            case 'in_list':
                $list_if = array_map('trim', explode(',', $if));
                $list_value = array_map('trim', explode(',', $value));
                $result = !array_diff($list_if, $list_value) ? true : false;
                break;
            case 'not_in_list':
                $list_if = array_map('trim', explode(',', $if));
                $list_value = array_map('trim', explode(',', $value));
                $result = !array_diff($list_if, $list_value) ? false : true;
                break;
            case 'array_key_exists':
                $unserialized = false;
                if (is_serialized($value)) {
                    $unserialized = $this->helper->load('convert')->unserialize($value);
                }
                $result = is_array($unserialized) && array_key_exists($if, $unserialized) ? true : false;
                break;
            case 'array_key_not_exists':
                $unserialized = false;
                if (is_serialized($value)) {
                    $unserialized = $this->helper->load('convert')->unserialize($value);
                }
                $result = is_array($unserialized) && array_key_exists($if, $unserialized) ? false : true;
                break;
        }

        return $result;
    }

    private function apply_action($element, $action) {
        if ($action['action'] == 'hide') {
            $element['hidden'] = true;
        } elseif ($action['action'] == 'show') {
            $element['hidden'] = false;
        } elseif ($action['action'] == 'merge' && $action['property']) {
            if ($action['property'] == 'value' && isset($element['element_id'])) {
                $value = isset($element[$action['property']]) ? $element[$action['property']] : '';
                $change = $value . $action['change'];
                $element[$action['property']] = $change;
            }
        } elseif ($action['action'] == 'change' && $action['property']) {

            $number_properties = array(
                'width', 'height', 'left', 'top',
            );

            $not_properties = array(
                'top', 'left', 'width', 'height', 'value', 'page_id',
            );

            if ($action['property'] == 'value') {
                if (isset($action['format']) && $action['format'] != 'replace') {
                    if ($action['format'] == 'insert_before') {
                        $value = isset($element[$action['property']]) ? $element[$action['property']] : '';
                        $change = $action['change'] . $value;
                    } elseif ($action['format'] == 'insert_after') {
                        $value = isset($element[$action['property']]) ? $element[$action['property']] : '';
                        $change = $value . $action['change'];
                    } elseif ($action['format'] == 'search') {
                        $search = isset($action['search']) ? $action['search'] : '';
                        $replace = isset($action['change']) ? $action['change'] : '';
                        if ($search) {
                            $value = isset($element[$action['property']]) ? $element[$action['property']] : '';
                            if ($value) {
                                $change = str_replace($search, $replace, $value);
                            }
                        }
                    }
                } else {
                    $change = $action['change'];
                }
            } else {
                $change = $this->extension->render($action['change']);
            }

            if ((substr($change, 0, 1) === '+' || substr($change, 0, 1) === '-') && in_array($action['property'], $number_properties, true)) {
                if (isset($element['element_id'])) {
                    $value = isset($element[$action['property']]) ? $element[$action['property']] : 0;
                } else {
                    $value = isset($element['properties'][$action['property']]) ? $element['properties'][$action['property']] : 0;
                }

                $change = $value + floatval($change);
            }

            if (in_array($action['property'], $not_properties, true) && isset($element['element_id'])) {
                $element[$action['property']] = $change;
            } else {
                $element['properties'][$action['property']] = $change;
            }
        } elseif (isset($element['action'])) {
            $element['success'] = true;
        }

        return $element;
    }

    private function apply_else_action($element, $action) {
        if ($action['action'] == 'show') {
            if (isset($action['else']) && $action['else'] == 'hide') {
                $element['hidden'] = true;
            }
        }
        return $element;
    }
}
