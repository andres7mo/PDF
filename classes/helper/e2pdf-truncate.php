<?php

/**
 * E2Pdf Truncate Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.01.02
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Truncate {

    private $helper;
    private $max_length = 0;
    private $read_more = '';
    private $break_words = false;
    private $length = 0;
    private $limit = false;
    private $elements = array();

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function truncate($text = '', $max_length = 100, $read_more = '', $break_words = false, $is_html = false) {
        if (!$text || mb_strlen($text) < $max_length) {
            return $text;
        }
        if ($is_html) {
            $this->length = 0;
            $this->limit = false;
            $this->elements = array();

            $this->max_length = $max_length;
            $this->read_more = $read_more;
            $this->break_words = $break_words;
            $text = '<div>' . $text . '</div>';
            $dom = new DOMDocument();
            if (function_exists('mb_convert_encoding')) {
                $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
            } else {
                $dom->loadHTML('<?xml encoding="UTF-8">' . $text);
            }
            libxml_clear_errors();
            $remove = $this->truncate_html($dom);
            foreach ($remove as $child) {
                $child->parentNode->removeChild($child);
            }
            $container = $dom->getElementsByTagName('div')->item(0);
            $container = $container->parentNode->removeChild($container);
            while ($dom->firstChild) {
                $dom->removeChild($dom->firstChild);
            }
            while ($container->firstChild) {
                $dom->appendChild($container->firstChild);
            }
            $text = $dom->saveHTML();
        } else {
            if (!$break_words) {
                $text = rtrim($text) . ' ';
            }
            $text = mb_substr($text, 0, $max_length);
            if (!$break_words) {
                $text = mb_substr($text, 0, strrpos($text, ' '));
            }
            $text = rtrim($text) . $read_more;
        }
        return $text;
    }

    public function truncate_html($node) {
        if ($this->limit) {
            $this->elements[] = $node;
        } else {
            if ($node && $node instanceof DOMText) {
                $nodeLen = mb_strlen($node->nodeValue);
                $this->length += $nodeLen;
                if ($this->length > $this->max_length) {
                    if (!$this->break_words) {
                        $text = rtrim($node->nodeValue) . ' ';
                    } else {
                        $text = $node->nodeValue;
                    }
                    $text = mb_substr($text, 0, $nodeLen - ($this->length - $this->max_length));
                    if (!$this->break_words) {
                        $text = mb_substr($text, 0, strrpos($text, ' '));
                    }
                    $text = rtrim($text) . $this->read_more;
                    $node->nodeValue = $text;
                    $this->limit = true;
                }
            }
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    $this->truncate_html($child);
                }
            }
        }
        return $this->elements;
    }
}
