<?php

/**
 * E2pdf Helper
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

class Helper_E2pdf_Sort {

    private function sort_by_zindex($a, $b) {
        if (!isset($a['properties']['z_index']) || (isset($a['properties']['z_index']) && !$a['properties']['z_index'])) {
            $a['properties']['z_index'] = '0';
        }

        if (!isset($b['properties']['z_index']) || (isset($b['properties']['z_index']) && !$b['properties']['z_index'])) {
            $b['properties']['z_index'] = '0';
        }

        if ($a['properties']['z_index'] == $b['properties']['z_index']) {
            return 0;
        }
        return ($a['properties']['z_index'] < $b['properties']['z_index']) ? -1 : 1;
    }

    private function sort_by_elementid($a, $b) {
        if ($a['element_id'] == $b['element_id']) {
            return 0;
        }
        return ($a['element_id'] < $b['element_id']) ? -1 : 1;
    }

    private function sort_by_pageid($a, $b) {
        if ($a['page_id'] == $b['page_id']) {
            return 0;
        }
        return ($a['page_id'] < $b['page_id']) ? -1 : 1;
    }

    public function uasort(&$array, $cmp_function) {
        uasort($array, array($this, $cmp_function));
        return;
    }

    public function stable_uasort(&$array, $cmp_function) {
        if (count($array) < 2) {
            return;
        }
        $halfway = (int) (count($array) / 2);
        $array1 = array_slice($array, 0, $halfway, true);
        $array2 = array_slice($array, $halfway, null, true);

        $this->stable_uasort($array1, $cmp_function);
        $this->stable_uasort($array2, $cmp_function);
        if (call_user_func(array($this, $cmp_function), end($array1), reset($array2)) < 1) {
            $array = $array1 + $array2;
            return;
        }
        $array = array();
        reset($array1);
        reset($array2);
        while (current($array1) && current($array2)) {
            if (call_user_func(array($this, $cmp_function), current($array1), current($array2)) < 1) {
                $array[key($array1)] = current($array1);
                next($array1);
            } else {
                $array[key($array2)] = current($array2);
                next($array2);
            }
        }
        while (current($array1)) {
            $array[key($array1)] = current($array1);
            next($array1);
        }
        while (current($array2)) {
            $array[key($array2)] = current($array2);
            next($array2);
        }
        return;
    }

}
