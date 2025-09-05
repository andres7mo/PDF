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

/** @license
 * Based on https://gist.github.com/ircmaxell/1232629
 */
class Helper_E2pdf_Math {

    private $helper;
    protected $variables = array();
    protected $stack = array();
    protected $output = array();

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function evaluate($string) {
        try {
            $this->stack = $this->parse($string);
            return $this->run();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function parse($string) {
        $tokens = $this->tokenize($string);
        $this->output = array();
        $this->operators = array();
        foreach ($tokens as $token) {
            $token = $this->extractVariables($token);
            $expression = $this->factory($token);
            if ($expression->operator) {
                $this->parseOperator($expression);
            } elseif (isset($expression->type) && $expression->type == 'parenthesis') {
                $this->parseParenthesis($expression);
            } else {
                $this->output[] = $expression;
            }
        }
        while (($op = array_pop($this->operators))) {
            if (isset($op->type) && $op->type == 'parenthesis') {
                throw new Exception(__('Mismatched Parenthesis in Math operation', 'e2pdf'));
            }
            $this->output[] = $op;
        }
        return $this->output;
    }

    public function registerVariable($name, $value) {
        $this->variables[$name] = $value;
    }

    public function run() {
        while (($operator = array_pop($this->stack)) && $operator->operator) {
            $value = $this->operate($operator);
            if (!is_null($value)) {
                $this->stack[] = $this->factory($value);
            }
        }
        return $operator ? $operator->value : $this->render();
    }

    protected function extractVariables($token) {
        if ($token[0] == '$') {
            $key = substr($token, 1);
            return isset($this->variables[$key]) ? $this->variables[$key] : 0;
        }
        return $token;
    }

    protected function render() {
        $output = '';
        while (($el = array_pop($this->stack))) {
            $output .= $el->value;
        }
        if ($output) {
            return $output;
        }
        throw new Exception(__('Could not parse Math operation', 'e2pdf'));
    }

    protected function parseParenthesis($expression) {
        if ($expression->open) {
            $this->operators[] = $expression;
        } else {
            $clean = false;
            while (($end = array_pop($this->operators))) {
                if (isset($end->type) && $end->type == 'parenthesis') {
                    $clean = true;
                    break;
                } else {
                    $this->output[] = $end;
                }
            }
            if (!$clean) {
                throw new Exception(__('Mismatched Parenthesis in Math operation', 'e2pdf'));
            }
        }
    }

    protected function parseOperator($expression) {
        $end = end($this->operators);
        if (!$end) {
            $this->operators[] = $expression;
        } elseif ($end->operator) {
            do {
                if ($expression->operator && $expression->precidence <= $end->precidence) {
                    $this->output[] = array_pop($this->operators);
                } elseif (!$expression->operator && $expression->precidence < $end->precidence) {
                    $this->output[] = array_pop($this->operators);
                } else {
                    break;
                }
            } while (($end = end($this->operators)) && $end->operator);

            $this->operators[] = $expression;
        } else {
            $this->operators[] = $expression;
        }
    }

    protected function tokenize($string) {
        $parts = preg_split('((\b\d[\d.]*\b|\+|-|\(|\)|\*|/)|\s+)', $string, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $parts = array_map('trim', $parts);
        return $parts;
    }

    protected function factory($value) {
        $expression = new stdClass();
        $expression->open = false;
        $expression->operator = false;
        $expression->precidence = 0;
        $expression->type = 'expression';
        if (is_object($value)) {
            return $value;
        } elseif (is_numeric($value)) {
            $expression->type = 'number';
        } elseif ($value == '+') {
            $expression->operator = true;
            $expression->type = 'addition';
            $expression->precidence = 4;
        } elseif ($value == '-') {
            $expression->operator = true;
            $expression->type = 'subtraction';
            $expression->precidence = 4;
        } elseif ($value == '*') {
            $expression->operator = true;
            $expression->type = 'multiplication';
            $expression->precidence = 5;
        } elseif ($value == '/') {
            $expression->operator = true;
            $expression->type = 'division';
            $expression->precidence = 5;
        } elseif ($value == '^') {
            $expression->operator = true;
            $expression->type = 'power';
            $expression->precidence = 6;
        } elseif (in_array($value, array('(', ')'))) {
            $expression->type = 'parenthesis';
            $expression->precidence = 7;
            $expression->open = $value == '(';
        }
        $expression->value = $value;
        return $expression;
    }

    protected function operate($expression) {
        $value = null;
        if (isset($expression->type)) {
            switch ($expression->type) {
                case 'number':
                    $value = $expression->value;
                    break;
                case 'addition':
                    $value = $this->operate(array_pop($this->stack)) + $this->operate(array_pop($this->stack));
                    break;
                case 'subtraction':
                    $left = $this->operate(array_pop($this->stack));
                    $right = $this->operate(array_pop($this->stack));
                    $value = $right - $left;
                    break;
                case 'multiplication':
                    $value = $this->operate(array_pop($this->stack)) * $this->operate(array_pop($this->stack));
                    break;
                case 'division':
                    $left = $this->operate(array_pop($this->stack));
                    $right = $this->operate(array_pop($this->stack));
                    $value = $right / $left;
                    break;
                case 'power':
                    $left = $this->operate(array_pop($this->stack));
                    $right = $this->operate(array_pop($this->stack));
                    $value = pow($right, $left);
                    break;
                default:
                    break;
            }
        }
        return $value;
    }
}
