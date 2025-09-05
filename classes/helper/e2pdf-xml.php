<?php

/**
 * E2pdf Zip Helper
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

class Helper_E2pdf_Xml {

    private $xml;

    /**
     * Check if extension simplexml available
     * 
     * @return bool
     */
    public function check() {
        if (extension_loaded('simplexml')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create new XML
     * 
     * @param string $key - Wrapper of XML
     * 
     * @return object - XML Object
     */
    public function create($key = false) {
        $this->set('xml', new Helper_E2pdf_SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $key . '></' . $key . '>'));
        return $this->get('xml');
    }

    /**
     * Get XML file in Base64
     * 
     * @return string
     */
    public function get_xml() {
        if ($this->get('xml')) {
            return base64_encode($this->get('xml')->asXML());
        }
        return '';
    }

    /**
     * Set option
     * 
     * @param string $key - Key of option
     * @param string $value - Value of option
     * 
     * @return bool - Status of setting option
     */
    public function set($key, $value) {
        if (!isset($this->xml)) {
            $this->xml = new stdClass();
        }
        $this->xml->$key = $value;
    }

    /**
     * Get option by key
     * 
     * @param string $key - Key to get assigned option value
     * 
     * @return mixed
     */
    public function get($key) {
        if (isset($this->xml->$key)) {
            return $this->xml->$key;
        } else {
            return false;
        }
    }

    /**
     * Get Element Attribute Value
     * 
     * @param object $element - XML Element
     * @param string $attribute - Attribute Name
     * 
     * @return string|bool
     */
    public function get_node_value($element, $attribute = '') {
        $value = "";
        if (is_object($element) && $attribute && isset($element->attributes) && $element->attributes->getNamedItem($attribute)) {
            $value = $element->attributes->getNamedItem($attribute)->nodeValue;
        }
        return $value;
    }

    /**
     * Check Element Attribute
     * 
     * @param object $element - XML Element
     * @param string $attribute - Attribute Name
     * 
     * @return string|bool
     */
    public function check_node_value($element, $attribute = '') {
        if (is_object($element) && $attribute && isset($element->attributes) && $element->attributes->getNamedItem($attribute)) {
            return true;
        }
        return false;
    }

    /**
     * Set Element Attribute
     * 
     * @param object $element - XML Element
     * @param string $attribute - Attribute Name
     * @param string $value - Attribute Value
     * @param bool $parent - if need to modify parent node
     * 
     * @return object
     */
    public function set_node_value($element, $attribute = '', $value = '', $parent = false) {

        if ($parent) {
            if (is_object($element) && $attribute && isset($element->parentNode->attributes)) {
                if ($element->parentNode->attributes->getNamedItem($attribute)) {
                    $element->parentNode->attributes->getNamedItem($attribute)->nodeValue = $value;
                } elseif ($this->get('dom')) {
                    $attr = $this->get('dom')->createAttribute($attribute);
                    $attr->value = $value;
                    $element->parentNode->appendChild($attr);
                }
            }
        } else {
            if (is_object($element) && $attribute && isset($element->attributes)) {
                if ($element->attributes->getNamedItem($attribute)) {
                    $element->attributes->getNamedItem($attribute)->nodeValue = $value;
                } elseif ($this->get('dom')) {
                    $attr = $this->get('dom')->createAttribute($attribute);
                    $attr->value = $value;
                    $element->appendChild($attr);
                }
            }
        }

        return $element;
    }

}
