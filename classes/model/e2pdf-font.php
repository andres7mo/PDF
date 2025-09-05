<?php

/**
 * File: /model/e2pdf-font.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Font extends Model_E2pdf_Model {

    protected $text;
    protected $ntOffset;

    public function get_font_info($font = false, $key = false, $font_path = false) {

        $font_tags = array();
        if (!$font_path) {
            $font_path = $this->helper->get('fonts_dir') . $font;
        }
        if ($font_path && file_exists($font_path) && filesize($font_path) > 0) {

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
            $fd = fopen($font_path, 'r');
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
            $this->text = fread($fd, filesize($font_path));
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
            fclose($fd);

            $number_of_tables = hexdec($this->dec2ord($this->text[4]) . $this->dec2ord($this->text[5]));

            for ($i = 0; $i < $number_of_tables; $i++) {
                $tag = $this->text[12 + $i * 16] . $this->text[12 + $i * 16 + 1] . $this->text[12 + $i * 16 + 2] . $this->text[12 + $i * 16 + 3];

                if ($tag == 'name') {
                    $this->ntOffset = hexdec(
                            $this->dec2ord($this->text[12 + $i * 16 + 8]) . $this->dec2ord($this->text[12 + $i * 16 + 8 + 1]) .
                            $this->dec2ord($this->text[12 + $i * 16 + 8 + 2]) . $this->dec2ord($this->text[12 + $i * 16 + 8 + 3])
                    );

                    $offset_storage_dec = hexdec($this->dec2ord($this->text[$this->ntOffset + 4]) . $this->dec2ord($this->text[$this->ntOffset + 5]));
                    $number_name_records_dec = hexdec($this->dec2ord($this->text[$this->ntOffset + 2]) . $this->dec2ord($this->text[$this->ntOffset + 3]));
                }
            }

            $storage_dec = $offset_storage_dec + $this->ntOffset;
            $storage_hex = strtoupper(dechex($storage_dec));

            for ($j = 0; $j < $number_name_records_dec; $j++) {
                $platform_id_dec = hexdec($this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 0]) . $this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 1]));
                $name_id_dec = hexdec($this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 6]) . $this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 7]));
                $string_length_dec = hexdec($this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 8]) . $this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 9]));
                $string_offset_dec = hexdec($this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 10]) . $this->dec2ord($this->text[$this->ntOffset + 6 + $j * 12 + 11]));

                if (!empty($name_id_dec) && empty($font_tags[$name_id_dec])) {
                    for ($l = 0; $l < $string_length_dec; $l++) {
                        if (ord($this->text[$storage_dec + $string_offset_dec + $l]) == '0') {
                            continue;
                        } else {

                            if (!isset($font_tags[$name_id_dec])) {
                                $font_tags[$name_id_dec] = '';
                            }

                            $font_tags[$name_id_dec] .= ($this->text[$storage_dec + $string_offset_dec + $l]);
                        }
                    }
                }
            }
        }

        if ($key) {
            if (!empty($font_tags) && isset($font_tags[$key])) {
                return $font_tags[$key];
            } else {
                return false;
            }
        } else {
            return $font_tags;
        }
    }

    public function get_font($font, $md5 = false) {
        $font_path = $this->helper->get('fonts_dir') . $font;

        if (file_exists($font_path)) {
            if ($md5) {
                return md5_file($font_path);
            } else {
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                return base64_encode(file_get_contents($font_path));
            }
        }
        return false;
    }

    public function get_fonts() {
        $fonts = array();
        $files = glob($this->helper->get('fonts_dir') . '*');
        if ($files) {
            foreach ($files as $key => $value) {
                if (in_array(strtolower(pathinfo($value, PATHINFO_EXTENSION)), $this->get_allowed_extensions(), true)) {
                    $font_name = $this->get_font_info(basename($value), 4);
                    if ($font_name) {
                        $fonts[basename($value)] = $font_name;
                    }
                }
            }
        }
        return $fonts;
    }

    public function get_font_path($font) {
        $fonts = $this->get_fonts();
        $c_font = array_search($font, $fonts, true);
        if ($c_font) {
            return $this->helper->get('fonts_dir') . $c_font;
        }
        return false;
    }

    public function delete_font($font) {
        $font_path = $this->helper->get('fonts_dir') . $font;

        if (file_exists($font_path)) {
            unlink($font_path);
            return true;
        }
        return false;
    }

    public function get_element_fonts($el_value, $all_fonts) {
        $fonts = array();
        if (($el_value['type'] == 'e2pdf-html' || $el_value['type'] == 'e2pdf-page-number') && isset($el_value['value']) && $el_value['value']) {
            preg_match_all('/font-family:[\s]+?["]?(.*?)["]?;/', htmlspecialchars_decode($el_value['value']), $matches);
            if (isset($matches[1]) && !empty($matches[1])) {
                foreach ($matches[1] as $font_key => $font_value) {
                    $exist = array_search($font_value, $fonts, true);
                    if (!$exist) {
                        $c_font = array_search($font_value, $all_fonts, true);
                        if ($c_font) {
                            $fonts[$c_font] = $font_value;
                        }
                    }
                }
            }

            if (isset($el_value['properties']['css']) && $el_value['properties']['css']) {
                preg_match_all('/font-family:[\s]+?["]?(.*?)["]?;/', htmlspecialchars_decode($el_value['properties']['css']), $matches);
                if (isset($matches[1]) && !empty($matches[1])) {
                    foreach ($matches[1] as $font_key => $font_value) {
                        $exist = array_search($font_value, $fonts, true);
                        if (!$exist) {
                            $c_font = array_search($font_value, $all_fonts, true);
                            if ($c_font) {
                                $fonts[$c_font] = $font_value;
                            }
                        }
                    }
                }
            }
        }

        if (isset($el_value['properties']['text_font']) && $el_value['properties']['text_font']) {
            $font_value = $el_value['properties']['text_font'];
            $exist = array_search($font_value, $fonts, true);
            if (!$exist) {
                $c_font = array_search($font_value, $all_fonts, true);
                if ($c_font) {
                    $fonts[$c_font] = $font_value;
                }
            }
        }

        //action fonts
        if (isset($el_value['actions']) && !empty($el_value['actions'])) {
            foreach ($el_value['actions'] as $action) {
                if (isset($action['property']) && $action['property'] == 'text_font' && isset($action['change']) && $action['change']) {
                    $font_value = $action['change'];
                    $exist = array_search($font_value, $fonts, true);
                    if (!$exist) {
                        $c_font = array_search($font_value, $all_fonts, true);
                        if ($c_font) {
                            $fonts[$c_font] = $font_value;
                        }
                    }
                }

                if (($el_value['type'] == 'e2pdf-html' || $el_value['type'] == 'e2pdf-page-number') && isset($action['property']) && $action['property'] == 'value' && isset($action['change']) && $action['change']) {
                    preg_match_all('/font-family:[\s]+?["]?(.*?)["]?;/', htmlspecialchars_decode($action['change']), $matches);
                    if (isset($matches[1]) && !empty($matches[1])) {
                        foreach ($matches[1] as $font_key => $font_value) {
                            $exist = array_search($font_value, $fonts, true);
                            if (!$exist) {
                                $c_font = array_search($font_value, $all_fonts, true);
                                if ($c_font) {
                                    $fonts[$c_font] = $font_value;
                                }
                            }
                        }
                    }
                }

                if (($el_value['type'] == 'e2pdf-html' || $el_value['type'] == 'e2pdf-page-number') && isset($action['property']) && $action['property'] == 'css' && isset($action['change']) && $action['change']) {
                    preg_match_all('/font-family:[\s]+?["]?(.*?)["]?;/', htmlspecialchars_decode($action['change']), $matches);
                    if (isset($matches[1]) && !empty($matches[1])) {
                        foreach ($matches[1] as $font_key => $font_value) {
                            $exist = array_search($font_value, $fonts, true);
                            if (!$exist) {
                                $c_font = array_search($font_value, $all_fonts, true);
                                if ($c_font) {
                                    $fonts[$c_font] = $font_value;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $fonts;
    }

    public function get_allowed_extensions() {
        return array(
            'ttf',
            'otf',
        );
    }

    protected function dec2ord($dec) {
        return $this->dec2hex(ord($dec));
    }

    protected function dec2hex($dec) {
        // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
        return str_repeat('0', 2 - strlen(($hex = strtoupper(dechex($dec))))) . $hex;
    }
}
