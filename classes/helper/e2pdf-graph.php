<?php

/**
 * E2pdf Graph Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.22.00
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Graph {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function graph($value = '', $field = array()) {

        if (!$value) {
            return '';
        }

        if (!class_exists('Goat1000\SVGGraph\SVGGraph')) {
            require_once $this->helper->get('plugin_dir') . 'vendors/svggraph/autoloader.php';
        }

        $width = $field['width'];
        $height = $field['height'];

        $settings = [
            'auto_fit' => false,
            'back_stroke_width' => 0,
            'back_stroke_colour' => '',
            'link_base' => '/',
            'link_target' => '_top',
            'axis_font' => $this->get_value('text_font', 'Noto Sans Regular', $field),
            'label_font' => $this->get_value('text_font', 'Noto Sans Regular', $field),
            'graph_title_font' => $this->get_value('text_font', 'Noto Sans Regular', $field),
            'legend_title_font' => $this->get_value('text_font', 'Noto Sans Regular', $field),
            'back_colour' => $this->get_value('background', 'none', $field),
            /* Graph */
            'stroke_colour' => $this->get_value('g_stroke_colour', '', $field),
            'stroke_width' => $this->get_value('g_stroke_width', '0', $field),
            'fill_under' => $this->get_boolean('g_fill_under', $field),
            'sort' => $this->get_boolean('g_sort', $field),
            'percentage' => $this->get_boolean('g_percentage', $field),
            'reverse' => $this->get_boolean('g_reverse', $field),
            /* Title */
            'graph_title_colour' => $this->get_value('g_graph_title_colour', '', $field),
            'graph_title' => $this->get_value('g_graph_title', '', $field),
            'graph_title_font_size' => $this->get_value('g_graph_title_font_size', '12', $field),
            'graph_title_position' => $this->get_value('g_graph_title_position', 'top', $field),
            'graph_title_space' => $this->get_value('g_graph_title_space', '0', $field),
            /* Labels */
            'label_colour' => $this->get_value('g_label_colour', '', $field),
            'label_font_size' => $this->get_value('g_label_font_size', '10', $field),
            'label_space' => $this->get_value('g_label_space', '0', $field),
            'label_h' => $this->get_value('g_label_h', '', $field),
            'label_v' => $this->get_value('g_label_v', '', $field),
            'data_label_type' => $this->get_value('g_data_label_type', 'plain', $field),
            /* Markers */
            'marker_type' => $this->get_value('g_marker_type', 'circle', $field),
            'marker_size' => $this->get_value('g_marker_size', '0', $field),
            'marker_colour' => $this->get_value('g_marker_colour', '', $field),
            /* Legend */
            'show_legend' => $this->get_boolean('g_show_legend', $field),
            'legend_back_colour' => $this->get_value('g_legend_back_colour', '', $field),
            'legend_stroke_colour' => $this->get_value('g_legend_stroke_colour', '', $field),
            'legend_stroke_width' => $this->get_value('g_legend_stroke_width', '0', $field),
            'legend_entry_width' => $this->get_value('g_legend_entry_width', '0', $field),
            'legend_padding_x' => $this->get_value('g_legend_padding_x', '0', $field),
            'legend_padding_y' => $this->get_value('g_legend_padding_y', '0', $field),
            'legend_colour' => $this->get_value('g_legend_colour', '', $field),
            'legend_title' => $this->get_value('g_legend_title', '', $field),
            'legend_title_font_size' => $this->get_value('g_legend_title_font_size', '', $field) === '' ? null : $this->get_value('g_legend_title_font_size', '0', $field),
            'legend_font_size' => $this->get_value('g_legend_font_size', '0', $field),
            'legend_position' => $this->get_value('g_legend_position_horizontal', 'right', $field) . ' ' . $this->get_value('g_legend_position_vertical', 'top', $field) . ' ' . $this->get_value('g_legend_position_horizontal_margin', '0', $field) . ' ' . $this->get_value('g_legend_position_vertical_margin', '0', $field) . ' ' . $this->get_value('g_legend_position_join', 'inner', $field),
            'legend_text_side' => $this->get_value('g_legend_text_side', 'left', $field),
            'legend_columns' => $this->get_value('g_legend_columns', '1', $field),
            'legend_shadow_opacity' => '0',
            /* Grid */
            'grid_colour' => $this->get_value('g_grid_colour', '', $field),
            'grid_subdivision_colour' => $this->get_value('g_grid_subdivision_colour', '', $field),
            'minimum_grid_spacing' => $this->get_value('g_minimum_grid_spacing', '0', $field),
            'minimum_grid_spacing_v' => $this->get_value('g_minimum_grid_spacing_v', '0', $field) > '0' ? $this->get_value('g_minimum_grid_spacing_v', '0', $field) : null,
            'minimum_grid_spacing_h' => $this->get_value('g_minimum_grid_spacing_h', '0', $field) > '0' ? $this->get_value('g_minimum_grid_spacing_h', '0', $field) : null,
            'grid_division_v' => $this->get_value('g_grid_division_v', '0', $field) > '0' ? $this->get_value('g_grid_division_v', '0', $field) : null,
            'grid_division_h' => $this->get_value('g_grid_division_h', '0', $field) > '0' ? $this->get_value('g_grid_division_h', '0', $field) : null,
            'show_grid' => $this->get_boolean('g_show_grid', $field),
            'show_grid_subdivisions' => $this->get_boolean('g_show_grid_subdivisions', $field),
            /* Axis */
            'axis_colour' => $this->get_value('g_axis_colour', '', $field),
            'axis_font_size' => $this->get_value('g_axis_font_size', '12', $field),
            'axis_overlap' => $this->get_value('g_axis_overlap', '0', $field),
            'show_axis_v' => $this->get_boolean('g_show_axis_v', $field),
            'show_axis_text_v' => $this->get_boolean('g_show_axis_text_v', $field),
            'show_axis_h' => $this->get_boolean('g_show_axis_h', $field),
            'show_axis_text_h' => $this->get_boolean('g_show_axis_text_h', $field),
            'show_subdivisions' => $this->get_boolean('g_show_subdivisions', $field),
            'axis_stroke_width_v' => $this->get_value('g_axis_stroke_width_v', '0', $field),
            'axis_stroke_width_h' => $this->get_value('g_axis_stroke_width_h', '0', $field),
            'axis_text_position_v' => $this->get_value('g_axis_text_position_v', 'outside', $field),
            'axis_text_position_h' => $this->get_value('g_axis_text_position_h', 'outside', $field),
            'axis_text_space_v' => $this->get_value('g_axis_text_space_v', '2', $field),
            'axis_text_space_h' => $this->get_value('g_axis_text_space_h', '2', $field),
            'axis_text_offset_x_v' => $this->get_value('g_axis_text_offset_x_v', '0', $field),
            'axis_text_offset_y_v' => $this->get_value('g_axis_text_offset_y_v', '0', $field),
            'axis_text_offset_x_h' => $this->get_value('g_axis_text_offset_x_h', '0', $field),
            'axis_text_offset_y_h' => $this->get_value('g_axis_text_offset_y_h', '0', $field),
            /* Bar Labels */
            'show_bar_labels' => $this->get_boolean('g_show_bar_labels', $field),
            'bar_label_colour' => $this->get_value('g_bar_label_colour', '', $field),
            'bar_label_font_size' => $this->get_value('g_bar_label_font_size', '0', $field),
            'bar_label_position' => $this->get_value('g_bar_label_position_join', 'outer', $field) . ' ' . $this->get_value('g_bar_label_position_horizontal', 'centre', $field) . ' ' . $this->get_value('g_bar_label_position_vertical', 'top', $field),
            'bar_label_space' => $this->get_value('g_bar_label_space', '0', $field),
            'units_x' => $this->get_value('g_units_x', '', $field),
            'units_y' => $this->get_value('g_units_y', '', $field),
            'units_label' => $this->get_value('g_units_label', '', $field),
            /* Additional */
            'bubble_scale' => $this->get_value('g_bubble_scale', '1', $field),
            'increment' => $this->get_value('g_increment', '0', $field),
            'stack_group' => $this->get_value('g_stack_group', '0', $field),
            'project_angle' => $this->get_value('g_project_angle', '0', $field),
        ];

        if (!$this->is_empty('g_axis_text_align_v', $field)) {
            $settings['axis_text_align_v'] = $this->get_value('g_axis_text_align_v', '', $field);
        }

        if (!$this->is_empty('g_axis_text_align_h', $field)) {
            $settings['axis_text_align_h'] = $this->get_value('g_axis_text_align_h', '', $field);
        }

        if (!$this->is_empty('g_line_curve', $field)) {
            $settings['line_curve'] = $this->get_value('g_line_curve', '', $field);
        }

        if (!$this->is_empty('g_line_dataset', $field)) {
            $settings['line_dataset'] = $this->get_array('g_line_dataset', '', $field);
        }

        if ($this->get_boolean('g_axis_min_max_h', $field)) {
            $settings['axis_min_h'] = $this->get_value('g_axis_min_h', '0', $field);
            $settings['axis_max_h'] = $this->get_value('g_axis_max_h', '0', $field);
        }

        if ($this->get_boolean('g_axis_min_max_v', $field)) {
            $settings['axis_min_v'] = $this->get_value('g_axis_min_v', '0', $field);
            $settings['axis_max_v'] = $this->get_value('g_axis_max_v', '0', $field);
        }

        /**
         * Padding
         */
        $settings['pad_top'] = $this->get_value('margin_top', '0', $field);
        $settings['pad_left'] = $this->get_value('margin_left', '0', $field);
        $settings['pad_right'] = $this->get_value('margin_right', '0', $field);
        $settings['pad_bottom'] = $this->get_value('margin_bottom', '0', $field);

        $separators = array(
            'key' => ' => ',
            'array' => ', ',
            'sub_array' => '|',
        );
        if (!$this->is_empty('g_key_sep', $field)) {
            $separators['key'] = $field['properties']['g_key_sep'];
        }
        if (!$this->is_empty('g_array_sep', $field)) {
            $separators['array'] = $field['properties']['g_array_sep'];
        }
        if (!$this->is_empty('g_sub_array_sep', $field)) {
            $separators['sub_array'] = $field['properties']['g_sub_array_sep'];
        }

        $data = $this->get_graph_data(apply_filters('e2pdf_helper_graph_pre_data', $value, $field), $separators, $this->get_value('g_multiline', '0', $field));
        $data = apply_filters('e2pdf_helper_graph_pre_data', $data, $field);
        if ($this->get_boolean('g_reverse_data', $field) && is_array($data)) {
            $data = array_reverse($data);
        }

        $single_array_conversion = array(
            'StackedBarGraph',
            'StackedBar3DGraph',
            'StackedCylinderGraph',
            'HorizontalStackedBarGraph',
            'HorizontalStackedBar3DGraph',
            'StackedBarAndLineGraph',
            'StackedLineGraph',
            'GroupedBarGraph',
            'GroupedBar3DGraph',
            'GroupedCylinderGraph',
            'HorizontalGroupedBarGraph',
            'HorizontalGroupedBar3DGraph',
            'StackedGroupedBarGraph',
            'StackedGroupedBar3DGraph',
            'StackedGroupedCylinderGraph',
            'BarAndLineGraph',
            'Histogram',
            'LineGraph',
            'RadarGraph',
            'ScatterGraph',
            'SteppedLineGraph',
            'MultiLineGraph',
            'MultiSteppedLineGraph',
            'MultiScatterGraph',
            'MultiRadarGraph',
            'StackedBarAndLineGraph',
            'ParetoChart',
            'PopulationPyramid'
        );
        if (in_array($this->get_value('g_type', 'BarGraph', $field), $single_array_conversion) && $this->get_value('g_multiline', '0', $field) == '2') {
            $new_data = array();
            foreach ($data as $key => $tmp) {
                if (is_array($tmp)) {
                    foreach ($tmp as $sub_key => $sub_tmp) {
                        $new_data[$sub_key][$key] = $sub_tmp;
                    }
                } else {
                    $new_data[0][$key] = $tmp;
                }
            }
            $data = $new_data;
        }

        /*
         * Structured Data
         */
        if ($this->get_value('g_structured_data', '0', $field)) {
            $settings['structured_data'] = true;
            $structure = array();
            if (!$this->is_empty('g_structure_key', $field)) {
                $structure['key'] = $this->get_value('g_structure_key', '0', $field);
            }
            if (!$this->is_empty('g_structure_value', $field)) {
                $structure['value'] = $this->get_array('g_structure_value', '0', $field);
            }
            if (!$this->is_empty('g_structure_label', $field)) {
                $structure['label'] = $this->get_array('g_structure_label', '0', $field);
                $settings['show_data_labels'] = true;
            }
            if (!$this->is_empty('g_structure_legend_text', $field)) {
                $structure['legend_text'] = $this->get_array('g_structure_legend_text', '0', $field);
            }
            if (!$this->is_empty('g_structure_area', $field)) {
                $structure['area'] = $this->get_array('g_structure_area', '0', $field);
            }
            if (!$this->is_empty('g_structure_open', $field)) {
                $structure['open'] = $this->get_array('g_structure_open', '0', $field);
            }
            if (!$this->is_empty('g_structure_end', $field)) {
                $structure['end'] = $this->get_array('g_structure_end', '0', $field);
            }
            if (!$this->is_empty('g_structure_outliers', $field)) {
                $structure['outliers'] = $this->get_array('g_structure_outliers', '0', $field);
            }
            if (!$this->is_empty('g_structure_top', $field)) {
                $structure['top'] = $this->get_array('g_structure_top', '0', $field);
            }
            if (!$this->is_empty('g_structure_bottom', $field)) {
                $structure['bottom'] = $this->get_array('g_structure_bottom', '0', $field);
            }
            if (!$this->is_empty('g_structure_wtop', $field)) {
                $structure['wtop'] = $this->get_array('g_structure_wtop', '0', $field);
            }
            if (!$this->is_empty('g_structure_wbottom', $field)) {
                $structure['wbottom'] = $this->get_array('g_structure_wbottom', '0', $field);
            }
            if (!$this->is_empty('g_structure_high', $field)) {
                $structure['high'] = $this->get_array('g_structure_high', '0', $field);
            }
            if (!$this->is_empty('g_structure_low', $field)) {
                $structure['low'] = $this->get_array('g_structure_low', '0', $field);
            }
            $settings['structure'] = $structure;
        }

        if (!$this->get_boolean('g_structured_data', $field) && $this->get_boolean('g_show_legend', $field)) {
            $legends = array();
            if ($this->get_value('g_multiline', '0', $field) == '1') {
                if (isset($data[0]) && is_array($data[0])) {
                    $legends = $data[0];
                }
            } elseif (is_array($data)) {
                $legends = $data;
            }
            $settings['legend_entries'] = array_keys($legends);
        }

        if (!$this->is_empty('g_legends', $field)) {
            $tmp_legends = explode("\r\n", $this->get_value('g_legends', '', $field));
            $legends = array();
            foreach ($tmp_legends as $legend) {
                $legends[] = implode("\n", explode('\n', $legend));
            }
            $settings['legend_entries'] = $legends;
        }

        $count = max(count($data), 1);
        foreach ($data as $sub_data) {
            if (is_array($sub_data)) {
                $count = max($count, count($sub_data));
            }
        }
        $colors = array();
        if (!$this->is_empty('g_colors', $field)) {
            $tmp_colors = explode("\r\n", $this->get_value('g_colors', '', $field));
            foreach ($tmp_colors as $color) {
                $colors[] = $this->get_array_value($color, '');
            }
        } else {
            $hue = 0;
            $color_change = 360 / $count;
            for ($i = 0; $i < $count; $i++) {
                $colors[] = array($this->get_value('g_palette', '#1e73be', $field) . '/hue(' . $hue . ')');
                $hue += $color_change;
            }
        }

        $colors = apply_filters('e2pdf_helper_graph_colors', $colors, $field);
        if ($this->get_boolean('g_stroke_dynamic_colour', $field)) {
            $settings['stroke_colour'] = apply_filters('e2pdf_helper_graph_stroke_colors', $colors, $field);
        }
        if ($this->get_boolean('g_marker_dynamic_colour', $field)) {
            $settings['marker_colour'] = apply_filters('e2pdf_helper_graph_marker_colors', $colors, $field);
        }

        $graph = new Goat1000\SVGGraph\SVGGraph($width, $height, apply_filters('e2pdf_helper_graph_settings', $settings, $field));
        $graph->colours($colors);
        $graph->values(apply_filters('e2pdf_helper_graph_data', $data, $field));
        try {
            $svg = $graph->fetch($this->get_value('g_type', 'BarGraph', $field));
            if (false !== strpos($svg, 'Goat1000')) {
                $svg = preg_replace('/Goat1000\\\(.[^\s]*)\s/', 'Graph ', $svg);
            }
            return base64_encode($svg); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        } catch (Exception $e) {
            return '';
        }
    }

    public function get_graph_data($value, $separators = array(), $multiline = false) {
        $data = array();
        if ($multiline != '0') {
            $lines = preg_split('/\r\n|\n/', $value);
            foreach ($lines as $key => $line) {
                $line_data = $this->get_line_data(trim($line), $separators);
                if ($multiline == '1') {
                    $data[] = $line_data;
                } elseif ($multiline == '2') {
                    $data = array_merge($data, $line_data);
                }
            }
        } else {
            $data = $this->get_line_data($value, $separators);
        }
        return $data;
    }

    public function get_line_data($line, $separators = array()) {
        $value = array();
        $data = explode($separators['array'], $line);
        foreach ($data as $line_data) {
            $key = null;
            if (strpos($line_data, $separators['key']) !== false) {
                list($key, $line_data) = explode($separators['key'], $line_data, 2);
            }
            if (strpos($line_data, $separators['sub_array']) !== false) {
                if ($key !== null) {
                    $value[$key] = explode($separators['sub_array'], $line_data);
                } else {
                    $value[] = explode($separators['sub_array'], $line_data);
                }
            } else {
                if ($key !== null) {
                    if ($line_data === '') {
                        $value[$key] = null;
                    } else {
                        $value[$key] = $line_data;
                    }
                } else {
                    if ($line_data === '') {
                        $value[] = null;
                    } else {
                        $value[] = $line_data;
                    }
                }
            }
        }
        return $value;
    }

    public function is_empty($key = '', $field = array()) {
        return isset($field['properties'][$key]) && $field['properties'][$key] !== '' ? false : true;
    }

    public function get_value($key = '', $default = '', $field = array()) {
        return isset($field['properties'][$key]) && $field['properties'][$key] ? $field['properties'][$key] : $default;
    }

    public function get_array($key = '', $default = '', $field = array()) {
        $value = isset($field['properties'][$key]) && $field['properties'][$key] ? $field['properties'][$key] : '';
        if ($value && (strpos($value, ',') !== false)) {
            return $value = array_map('trim', explode(',', $value));
        } else {
            return $value ? $value : $default;
        }
    }

    public function get_array_value($value = '', $default = '') {
        if ($value && (strpos($value, ',') !== false)) {
            return $value = array_map('trim', explode(',', $value));
        } else {
            return $value ? $value : $default;
        }
    }

    public function get_boolean($key = '', $field = array()) {
        return isset($field['properties'][$key]) && $field['properties'][$key] ? true : false;
    }
}
