<?php

/**
 * E2pdf Get Helper
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

class Helper_E2pdf_Db {

    /**
     * Prepare WHERE for sql requests
     * 
     * @param string $condition - Array of conditions
     * 
     * @return array - Filtered WHERE request
     */
    public function prepare_where($condition = array(), $sub_query = false) {

        $sql = array();
        $filter = array();
        if (!$sub_query) {
            $sql[] = " WHERE '1' = '1'";
        }
        $condition = apply_filters('e2pdf_helper_db_prepare_where_condition', $condition);

        if (!empty($condition)) {
            foreach ($condition as $key => $value) {

                $sql_keys = explode('.', $key);
                foreach ($sql_keys as $sql_sub_key => $sql_key) {
                    $sql_keys[$sql_sub_key] = '`' . $sql_key . '`';
                }

                if (is_array($value['value'])) {
                    foreach ($value['value'] as $sub_value) {
                        $sql[] = " " . implode('.', $sql_keys) . " {$value['condition']} '{$value['type']}'";
                        $filter[] = $sub_value;
                    }
                } else {
                    if (!empty($value['or'])) {
                        $sub_sql = array();
                        $sub_sql[] = " " . implode('.', $sql_keys) . " {$value['condition']} '{$value['type']}'";
                        $filter[] = $value['value'];
                        foreach ($value['or'] as $or) {
                            $sub_where = $this->prepare_where($or, true);
                            $sub_sql[] = $sub_where['sql'];
                            foreach ($sub_where['filter'] as $sub_filter) {
                                $filter[] = $sub_filter;
                            }
                        }

                        $sql[] = ' (' . implode(' OR ', $sub_sql) . ')';
                    } else {
                        $sql[] = " " . implode('.', $sql_keys) . " {$value['condition']} '{$value['type']}'";
                        $filter[] = $value['value'];
                    }
                }
            }
        }

        $where = array(
            'sql' => implode(' AND', $sql),
            'filter' => $filter
        );

        return apply_filters('e2pdf_helper_db_prepare_where', $where, $condition);
    }

    /**
     * Prepare ORDER_BY for sql requests
     * 
     * @param string $condition - Array of conditions
     * 
     * @return array - Filtered ORDER_BY request
     */
    public function prepare_orderby($condition = array()) {

        $orderby = '';
        $condition = apply_filters('e2pdf_helper_db_prepare_orderby_condition', $condition);

        if (isset($condition['orderby']) && isset($condition['order'])) {
            $orderby .= " ORDER BY {$condition['orderby']} {$condition['order']}";
        }

        return apply_filters('e2pdf_helper_db_prepare_orderby', $orderby, $condition);
    }

    public function prepare_limit($condition = array()) {

        $limit = '';
        $condition = apply_filters('e2pdf_helper_db_prepare_limit_condition', $condition);

        if (isset($condition['limit']) && isset($condition['offset'])) {
            $limit .= " LIMIT {$condition['offset']}, {$condition['limit']}";
        }

        return apply_filters('e2pdf_helper_db_prepare_limit', $limit, $condition);
    }

    public function db_init($db_prefix, $maybe_row_format = true) {
        global $wpdb;

        $collate = $this->db_collate();
        if ($this->db_test_row_format($db_prefix)) {
            $collate .= ' ROW_FORMAT=DYNAMIC';
        }

        $srpk = $wpdb->get_row("SHOW VARIABLES LIKE 'sql_require_primary_key'", ARRAY_A);
        if ($srpk && isset($srpk['Value']) && $srpk['Value'] == 'ON') {
            $wpdb->query('SET SESSION sql_require_primary_key = 0;');
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_templates` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `uid` varchar(255) NOT NULL,
        `pdf` text,
        `title` text,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        `flatten` enum('0','1','2') NOT NULL DEFAULT '0',
        `tab_order` enum('0','1','2','3') NOT NULL DEFAULT '0',
        `compression` int(1) NOT NULL DEFAULT '-1',
        `optimization` int(1) NOT NULL DEFAULT '-1',
        `appearance` enum('0','1') NOT NULL DEFAULT '0',
        `width` int(11) NOT NULL DEFAULT '0',
        `height` int(11) NOT NULL DEFAULT '0',
        `extension` varchar(255) NOT NULL,
        `item` varchar(255) NOT NULL,
        `item1` varchar(255) NOT NULL,
        `item2` varchar(255) NOT NULL,
        `format` varchar(255) NOT NULL DEFAULT 'pdf',
        `resample` varchar(255) NOT NULL DEFAULT '100',
        `dataset_title` text NOT NULL,
        `dataset_title1` text NOT NULL,
        `dataset_title2` text NOT NULL,
        `button_title` text NOT NULL,
        `dpdf` text NOT NULL,
        `inline` enum('0','1') NOT NULL DEFAULT '0',
        `auto` enum('0','1') NOT NULL DEFAULT '0',
        `rtl` enum('0','1') NOT NULL DEFAULT '0',
        `font_processor` enum('0','1') NOT NULL DEFAULT '0',
        `name` text NOT NULL,
        `savename` text NOT NULL,
        `password` text NOT NULL,
        `owner_password` text NOT NULL,
        `permissions` longtext NOT NULL,
        `hooks` longtext NOT NULL,
        `meta_title` text NOT NULL,
        `meta_subject` text NOT NULL,
        `meta_author` text NOT NULL,
        `meta_keywords` text NOT NULL,
        `lang_code` varchar(255) NOT NULL,
        `font` varchar(255) NOT NULL,
        `font_size` varchar(255) NOT NULL,
        `font_color` varchar(255) NOT NULL,
        `line_height` varchar(255) NOT NULL,
        `text_align` varchar(255) NOT NULL,
        `fonts` longtext NOT NULL,
        `trash` enum('0','1') NOT NULL DEFAULT '0',
        `activated` enum('0','1') NOT NULL DEFAULT '0',
        `locked` enum('0','1') NOT NULL DEFAULT '0',
        `author` int(11) NOT NULL,
        `properties` longtext NOT NULL,
        `actions` longtext NOT NULL,
            PRIMARY KEY (`ID`)
        ) " . $collate . "");

        if ($wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'blank';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` DROP COLUMN `blank`;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'meta_title';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `meta_title` text NOT NULL AFTER password;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'meta_subject';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `meta_subject` text NOT NULL AFTER meta_title;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'meta_author';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `meta_author` text NOT NULL AFTER meta_subject;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'meta_keywords';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `meta_keywords` text NOT NULL AFTER meta_author;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'lang_code';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `lang_code` text NOT NULL AFTER meta_keywords;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'actions';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `actions` longtext NOT NULL AFTER author;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'properties';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `properties` longtext NOT NULL AFTER author;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'rtl';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `rtl` enum('0','1') NOT NULL DEFAULT '0' AFTER auto;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'font_processor';")) {
            if (get_option('e2pdf_font_processor', '0') == '1') {
                $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `font_processor` enum('0','1') NOT NULL DEFAULT '1' AFTER rtl;");
            } else {
                $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `font_processor` enum('0','1') NOT NULL DEFAULT '0' AFTER rtl;");
            }
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'text_align';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `text_align`  varchar(255) NOT NULL AFTER line_height;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'tab_order';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `tab_order` enum('0','1','2','3') NOT NULL DEFAULT '0' AFTER flatten;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'item1';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `item1`  varchar(255) NOT NULL AFTER item;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'item2';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `item2`  varchar(255) NOT NULL AFTER item1;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'dataset_title1';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `dataset_title1` text NOT NULL AFTER dataset_title;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'dataset_title2';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `dataset_title2` text NOT NULL AFTER dataset_title1;");
        }

        if ($wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` WHERE Field = 'format' and Type LIKE '%enum%';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` DROP COLUMN `format`;");
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `format` varchar(255) NOT NULL DEFAULT 'pdf' AFTER item2;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'resample';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `resample` varchar(255) NOT NULL DEFAULT '100' AFTER format;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'owner_password';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `owner_password` text NOT NULL AFTER password;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'permissions';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `permissions` longtext NOT NULL AFTER owner_password;");
        }

        $wpdb->query("UPDATE `" . $db_prefix . "e2pdf_templates` SET permissions = 'a:1:{i:0;s:8:\"printing\";}' WHERE permissions = ''");

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'hooks';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `hooks` longtext NOT NULL AFTER permissions;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'dpdf';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `dpdf` text NOT NULL AFTER button_title;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'savename';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `savename` text NOT NULL AFTER name;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'optimization';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `optimization` int(1) NOT NULL DEFAULT '-1' AFTER compression;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_templates` LIKE 'attachments';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_templates` ADD COLUMN `attachments` longtext NOT NULL AFTER fonts;");
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_entries` (
        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
        `uid` varchar(255) NOT NULL,
        `entry` longtext,
        `pdf_num` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`ID`)
        ) " . $collate . "");

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_entries` LIKE 'pdf_num';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_entries` ADD COLUMN `pdf_num` int(11) NOT NULL DEFAULT '0';");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_entries` WHERE key_name = 'uid';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `uid` ON `" . $db_prefix . "e2pdf_entries` (`uid`);");
        }

        if ($wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_entries` WHERE Field = 'ID' and Type LIKE 'int(11)';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_entries` CHANGE `ID` `ID` bigint(20) NOT NULL AUTO_INCREMENT;");
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_datasets` (
        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
        `extension` varchar(255) NOT NULL,
        `item` varchar(255) NOT NULL,
        `entry` longtext,
        `created_at` datetime NOT NULL,
            PRIMARY KEY (`ID`)
        ) " . $collate . "");

        if ($wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_datasets` WHERE Field = 'ID' and Type LIKE 'int(11)';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_datasets` CHANGE `ID` `ID` bigint(20) NOT NULL AUTO_INCREMENT;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_datasets` LIKE 'created_at';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_datasets` ADD COLUMN `created_at` datetime NOT NULL AFTER entry;");
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_pages` (
        `PID` bigint(20) NOT NULL AUTO_INCREMENT,    
        `page_id` int(11) NOT NULL DEFAULT '0',
        `template_id` int(11) NOT NULL DEFAULT '0',
        `properties` longtext NOT NULL,
        `actions` longtext NOT NULL,
        `revision_id` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`PID`)
        ) " . $collate . "");

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_pages` LIKE 'actions';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_pages` ADD COLUMN `actions` longtext NOT NULL;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_pages` LIKE 'revision_id';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_pages` ADD COLUMN `revision_id` int(11) NOT NULL DEFAULT '0' AFTER actions;");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_pages` WHERE key_name = 'page_id';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `page_id` ON `" . $db_prefix . "e2pdf_pages` (`page_id`);");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_pages` WHERE key_name = 'template_id';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `template_id` ON `" . $db_prefix . "e2pdf_pages` (`template_id`);");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_pages` WHERE key_name = 'revision_id';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `revision_id` ON `" . $db_prefix . "e2pdf_pages` (`revision_id`);");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_pages` LIKE 'PID';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_pages` ADD COLUMN `PID` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_elements` (
        `PID` bigint(20) NOT NULL AUTO_INCREMENT,    
        `page_id` int(11) NOT NULL DEFAULT '0',
        `template_id` int(11) NOT NULL DEFAULT '0',
        `element_id` int(11) NOT NULL DEFAULT '0',
        `name` text NOT NULL,
        `type` varchar(255) NOT NULL,
        `top` int(11) NOT NULL DEFAULT '0',
        `left` int(11) NOT NULL DEFAULT '0',
        `width` int(11) NOT NULL DEFAULT '0',
        `height` int(11) NOT NULL DEFAULT '0',
        `value` longtext NOT NULL,
        `properties` longtext NOT NULL,
        `actions` longtext NOT NULL,
        `revision_id` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`PID`)
        ) " . $collate . "");

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_elements` LIKE 'actions';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_elements` ADD COLUMN `actions` longtext NOT NULL;");
        }

        if ($wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_pages` LIKE 'ID';") && $wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_elements` LIKE 'ID';")) {
            $wpdb->query("UPDATE `" . $db_prefix . "e2pdf_elements` ee INNER JOIN `" . $db_prefix . "e2pdf_pages` ep ON ee.page_id = ep.ID set ee.page_id = ep.page_id;");
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_pages` DROP COLUMN `ID`;");
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_elements` DROP COLUMN `ID`;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_elements` LIKE 'name';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_elements` ADD COLUMN `name` text NOT NULL AFTER element_id;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_elements` LIKE 'revision_id';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_elements` ADD COLUMN `revision_id` int(11) NOT NULL DEFAULT '0' AFTER actions;");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_elements` WHERE key_name = 'page_id';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `page_id` ON `" . $db_prefix . "e2pdf_elements` (`page_id`);");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_elements` WHERE key_name = 'template_id'");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `template_id` ON `" . $db_prefix . "e2pdf_elements` (`template_id`); ");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_elements` WHERE key_name = 'revision_id'");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `revision_id` ON `" . $db_prefix . "e2pdf_elements` (`revision_id`); ");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_elements` LIKE 'PID';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_elements` ADD COLUMN `PID` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_revisions` (
        `PID` bigint(20) NOT NULL AUTO_INCREMENT,    
        `revision_id` int(11) NOT NULL DEFAULT '0',
        `template_id` int(11) NOT NULL DEFAULT '0',
        `pdf` text,
        `title` text,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        `flatten` enum('0','1','2') NOT NULL DEFAULT '0',
        `tab_order` enum('0','1','2','3') NOT NULL DEFAULT '0',
        `compression` int(1) NOT NULL DEFAULT '-1',
        `optimization` int(1) NOT NULL DEFAULT '-1',
        `appearance` enum('0','1') NOT NULL DEFAULT '0',
        `width` int(11) NOT NULL DEFAULT '0',
        `height` int(11) NOT NULL DEFAULT '0',
        `extension` varchar(255) NOT NULL,
        `item` varchar(255) NOT NULL,
        `item1` varchar(255) NOT NULL,
        `item2` varchar(255) NOT NULL,
        `format` varchar(255) NOT NULL DEFAULT 'pdf',
        `resample` varchar(255) NOT NULL DEFAULT '100',
        `dataset_title` text NOT NULL,
        `dataset_title1` text NOT NULL,
        `dataset_title2` text NOT NULL,
        `button_title` text NOT NULL,
        `dpdf` text NOT NULL,
        `inline` enum('0','1') NOT NULL DEFAULT '0',
        `auto` enum('0','1') NOT NULL DEFAULT '0',
        `rtl` enum('0','1') NOT NULL DEFAULT '0',
        `font_processor` enum('0','1') NOT NULL DEFAULT '0',
        `name` text NOT NULL,
        `savename` text NOT NULL,
        `password` text NOT NULL,
        `owner_password` text NOT NULL,
        `permissions` longtext NOT NULL,
        `hooks` longtext NOT NULL,
        `meta_title` text NOT NULL,
        `meta_subject` text NOT NULL,
        `meta_author` text NOT NULL,
        `meta_keywords` text NOT NULL,
        `lang_code` varchar(255) NOT NULL,
        `font` varchar(255) NOT NULL,
        `font_size` varchar(255) NOT NULL,
        `font_color` varchar(255) NOT NULL,
        `line_height` varchar(255) NOT NULL,
        `text_align` varchar(255) NOT NULL,
        `fonts` longtext NOT NULL,
        `author` int(11) NOT NULL,
        `properties` longtext NOT NULL,
        `actions` longtext NOT NULL,
            PRIMARY KEY (`PID`)
        ) " . $collate . "");

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_revisions` WHERE key_name = 'revision_id';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `revision_id` ON `" . $db_prefix . "e2pdf_revisions` (`revision_id`);");
        }

        $index = $wpdb->get_row("SHOW INDEX FROM `" . $db_prefix . "e2pdf_revisions` WHERE key_name = 'template_id';");
        if (is_null($index)) {
            $wpdb->query("CREATE INDEX `template_id` ON `" . $db_prefix . "e2pdf_revisions` (`template_id`);");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'lang_code';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `lang_code` text NOT NULL AFTER meta_keywords;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'actions';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `actions` longtext NOT NULL AFTER author;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'properties';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `properties` longtext NOT NULL AFTER author;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'rtl';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `rtl` enum('0','1') NOT NULL DEFAULT '0' AFTER auto;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'font_processor';")) {
            if (get_option('e2pdf_font_processor', '0') == '1') {
                $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `font_processor` enum('0','1') NOT NULL DEFAULT '1' AFTER rtl;");
            } else {
                $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `font_processor` enum('0','1') NOT NULL DEFAULT '0' AFTER rtl;");
            }
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'text_align';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `text_align`  varchar(255) NOT NULL AFTER line_height;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'tab_order';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `tab_order` enum('0','1','2','3') NOT NULL DEFAULT '0' AFTER flatten;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'item1';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `item1`  varchar(255) NOT NULL AFTER item;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'item2';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `item2`  varchar(255) NOT NULL AFTER item1;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'dataset_title1';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `dataset_title1` text NOT NULL AFTER dataset_title;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'dataset_title2';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `dataset_title2` text NOT NULL AFTER dataset_title1;");
        }

        if ($wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` WHERE Field = 'format' and Type LIKE '%enum%';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` DROP COLUMN `format`;");
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `format` varchar(255) NOT NULL DEFAULT 'pdf' AFTER item2;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'resample';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `resample` varchar(255) NOT NULL DEFAULT '100' AFTER format;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'owner_password';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `owner_password` text NOT NULL AFTER password;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'permissions';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `permissions` longtext NOT NULL AFTER owner_password;");
        }

        $wpdb->query("UPDATE `" . $db_prefix . "e2pdf_revisions` SET permissions = 'a:1:{i:0;s:8:\"printing\";}' WHERE permissions = ''");

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'hooks';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `hooks` longtext NOT NULL AFTER permissions;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'dpdf';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `dpdf` text NOT NULL AFTER button_title;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'savename';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `savename` text NOT NULL AFTER name;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'PID';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `PID` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'optimization';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `optimization` int(1) NOT NULL DEFAULT '-1' AFTER compression;");
        }

        if (!$wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_revisions` LIKE 'attachments';")) {
            $wpdb->query("ALTER TABLE `" . $db_prefix . "e2pdf_revisions` ADD COLUMN `attachments` longtext NOT NULL AFTER fonts;");
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_bulks` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `uid` varchar(255) NOT NULL,
        `template_id` int(11) NOT NULL DEFAULT '0',
        `count` int(11) NOT NULL DEFAULT '0',
        `total` int(11) NOT NULL DEFAULT '0',
        `dataset` longtext NOT NULL,
        `datasets` longtext NOT NULL,
        `options` longtext NOT NULL,
        `status` varchar(255) NOT NULL DEFAULT 'pending',
        `created_at` datetime NOT NULL,
            PRIMARY KEY (`ID`)
        ) " . $collate . "");

        if (!$this->db_check($db_prefix) && $maybe_row_format) {
            $this->db_row_format($db_prefix);
        } else {
            $this->db_optimize($db_prefix);
            update_option('e2pdf_font_processor', '1');
        }

        return true;
    }

    public function db_test_row_format($db_prefix) {
        global $wpdb;

        $collate = $this->db_collate() . ' ROW_FORMAT=DYNAMIC';

        $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "e2pdf_test_db` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
            PRIMARY KEY (`ID`)
        ) " . $collate . "");

        $pass = $wpdb->get_var("SHOW COLUMNS FROM `" . $db_prefix . "e2pdf_test_db` LIKE 'ID';");
        $wpdb->query("DROP TABLE IF EXISTS `" . $db_prefix . "e2pdf_test_db`;");

        if ($pass) {
            return true;
        } else {
            return false;
        }
    }

    public function db_row_format($db_prefix) {
        global $wpdb;
        if ($this->db_test_row_format($db_prefix)) {
            $db_structure = $this->db_structure($db_prefix);
            foreach ($db_structure as $table => $columns) {
                $wpdb->query('ALTER TABLE `' . $db_prefix . $table . '` ROW_FORMAT=DYNAMIC;');
                $wpdb->query('OPTIMIZE TABLE `' . $db_prefix . $table . '`;');
            }
            $this->db_init($db_prefix, false);
        }
        return true;
    }

    public function db_optimize($db_prefix) {
        global $wpdb;
        $db_structure = $this->db_structure($db_prefix);
        foreach ($db_structure as $table => $columns) {
            $wpdb->query("OPTIMIZE TABLE `" . $db_prefix . $table . "`;");
        }
        return true;
    }

    public function db_repair($db_prefix) {
        global $wpdb;
        $db_structure = $this->db_structure($db_prefix);
        foreach ($db_structure as $table => $columns) {
            $wpdb->query("REPAIR TABLE `" . $db_prefix . $table . "`;");
        }
        return true;
    }

    public function db_repair_collate($db_prefix) {
        global $wpdb;

        $db_structure = $this->db_structure($db_prefix);

        $collate = '';
        if (!empty($wpdb->charset)) {
            $collate = 'CONVERT TO CHARACTER SET ' . $wpdb->charset;
        }
        if (!empty($wpdb->collate)) {
            $collate .= ' COLLATE ' . $wpdb->collate;
        }

        if (!empty($collate)) {
            foreach ($db_structure as $table => $columns) {
                $wpdb->query('ALTER TABLE `' . $db_prefix . $table . '` ' . $collate . ';');
                $wpdb->query('OPTIMIZE TABLE `' . $db_prefix . $table . '`;');
            }
            $this->db_init($db_prefix, false);
        }
    }

    public function db_collate() {
        global $wpdb;
        return $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : 'CHARSET=utf8 COLLATE=utf8_general_ci';
    }

    public function db_check($db_prefix) {
        $db_structure = $this->db_structure($db_prefix, true);
        foreach ($db_structure as $table) {
            foreach ($table['columns'] as $column) {
                if (!isset($column['check']) || (isset($column['check']) && !$column['check'])) {
                    return false;
                }
            }
        }
        return true;
    }

    public function db_check_collate($db_prefix) {
        global $wpdb;
        $db_structure = $this->db_structure($db_prefix);
        if (!empty($wpdb->collate)) {
            foreach ($db_structure as $table => $columns) {
                $condition = array(
                    'TABLE_SCHEMA' => array(
                        'condition' => '=',
                        'value' => DB_NAME,
                        'type' => '%s'
                    ),
                    'TABLE_NAME' => array(
                        'condition' => '=',
                        'value' => $db_prefix . $table,
                        'type' => '%s'
                    ),
                    'TABLE_COLLATION' => array(
                        'condition' => '=',
                        'value' => $wpdb->collate,
                        'type' => '%s'
                    ),
                );
                $where = $this->prepare_where($condition);
                $check = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` ' . $where['sql'] . '', $where['filter']));
                if (!$check) {
                    return false;
                }
            }
        }
        return true;
    }

    public function db_structure($db_prefix, $check = false) {
        global $wpdb;

        $db_structure = array(
            'e2pdf_templates' => array(
                'format' => __('Undefined', 'e2pdf'),
                'check' => false,
                'columns' => array(
                    'ID' => array(),
                    'uid' => array(),
                    'pdf' => array(),
                    'title' => array(),
                    'created_at' => array(),
                    'updated_at' => array(),
                    'flatten' => array(),
                    'tab_order' => array(),
                    'compression' => array(),
                    'optimization' => array(),
                    'appearance' => array(),
                    'width' => array(),
                    'height' => array(),
                    'extension' => array(),
                    'item' => array(),
                    'item1' => array(),
                    'item2' => array(),
                    'format' => array(),
                    'resample' => array(),
                    'dataset_title' => array(),
                    'dataset_title1' => array(),
                    'dataset_title2' => array(),
                    'button_title' => array(),
                    'dpdf' => array(),
                    'inline' => array(),
                    'auto' => array(),
                    'rtl' => array(),
                    'font_processor' => array(),
                    'name' => array(),
                    'savename' => array(),
                    'password' => array(),
                    'owner_password' => array(),
                    'permissions' => array(),
                    'hooks' => array(),
                    'meta_title' => array(),
                    'meta_subject' => array(),
                    'meta_author' => array(),
                    'meta_keywords' => array(),
                    'lang_code' => array(),
                    'font' => array(),
                    'font_size' => array(),
                    'font_color' => array(),
                    'line_height' => array(),
                    'text_align' => array(),
                    'fonts' => array(),
                    'attachments' => array(),
                    'trash' => array(),
                    'activated' => array(),
                    'locked' => array(),
                    'author' => array(),
                    'properties' => array(),
                    'actions' => array(),
                )
            ),
            'e2pdf_entries' => array(
                'format' => __('Undefined', 'e2pdf'),
                'check' => false,
                'columns' => array(
                    'ID' => array(),
                    'uid' => array(),
                    'entry' => array(),
                    'pdf_num' => array(),
                )
            ),
            'e2pdf_datasets' => array(
                'format' => '',
                'check' => false,
                'columns' => array(
                    'ID' => array(),
                    'extension' => array(),
                    'item' => array(),
                    'entry' => array(),
                    'created_at' => array(),
                )
            ),
            'e2pdf_pages' => array(
                'format' => __('Undefined', 'e2pdf'),
                'check' => false,
                'columns' => array(
                    'PID' => array(),
                    'page_id' => array(),
                    'template_id' => array(),
                    'properties' => array(),
                    'actions' => array(),
                    'revision_id' => array(),
                )
            ),
            'e2pdf_elements' => array(
                'format' => __('Undefined', 'e2pdf'),
                'check' => false,
                'columns' => array(
                    'PID' => array(),
                    'page_id' => array(),
                    'template_id' => array(),
                    'element_id' => array(),
                    'name' => array(),
                    'type' => array(),
                    'top' => array(),
                    'left' => array(),
                    'width' => array(),
                    'height' => array(),
                    'value' => array(),
                    'properties' => array(),
                    'actions' => array(),
                    'revision_id' => array(),
                )
            ),
            'e2pdf_revisions' => array(
                'format' => __('Undefined', 'e2pdf'),
                'check' => false,
                'columns' => array(
                    'PID' => array(),
                    'revision_id' => array(),
                    'template_id' => array(),
                    'pdf' => array(),
                    'title' => array(),
                    'created_at' => array(),
                    'updated_at' => array(),
                    'flatten' => array(),
                    'tab_order' => array(),
                    'compression' => array(),
                    'optimization' => array(),
                    'appearance' => array(),
                    'width' => array(),
                    'height' => array(),
                    'extension' => array(),
                    'item' => array(),
                    'item1' => array(),
                    'item2' => array(),
                    'format' => array(),
                    'resample' => array(),
                    'dataset_title' => array(),
                    'dataset_title1' => array(),
                    'dataset_title2' => array(),
                    'button_title' => array(),
                    'dpdf' => array(),
                    'inline' => array(),
                    'auto' => array(),
                    'rtl' => array(),
                    'font_processor' => array(),
                    'name' => array(),
                    'savename' => array(),
                    'password' => array(),
                    'owner_password' => array(),
                    'permissions' => array(),
                    'hooks' => array(),
                    'meta_title' => array(),
                    'meta_subject' => array(),
                    'meta_author' => array(),
                    'meta_keywords' => array(),
                    'lang_code' => array(),
                    'font' => array(),
                    'font_size' => array(),
                    'font_color' => array(),
                    'line_height' => array(),
                    'text_align' => array(),
                    'fonts' => array(),
                    'attachments' => array(),
                    'author' => array(),
                    'properties' => array(),
                    'actions' => array(),
                )
            )
        );

        if ($check) {
            foreach ($db_structure as $table_key => $table) {
                $condition = array(
                    'TABLE_SCHEMA' => array(
                        'condition' => '=',
                        'value' => DB_NAME,
                        'type' => '%s'
                    ),
                    'TABLE_NAME' => array(
                        'condition' => '=',
                        'value' => $db_prefix . $table_key,
                        'type' => '%s'
                    ),
                );
                $where = $this->prepare_where($condition);
                $table_exists = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` ' . $where['sql'] . '', $where['filter']));
                if ($table_exists) {

                    $db_structure[$table_key]['check'] = true;
                    $table_columns = $wpdb->get_results($wpdb->prepare('SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` ' . $where['sql'] . '', $where['filter']), ARRAY_A);
                    $table_data = $wpdb->get_row($wpdb->prepare('SELECT `ROW_FORMAT`, `TABLE_COLLATION` FROM `INFORMATION_SCHEMA`.`TABLES` ' . $where['sql'] . '', $where['filter']), ARRAY_A);

                    $db_structure[$table_key]['format'] = !empty($table_data['ROW_FORMAT']) ? $table_data['ROW_FORMAT'] : '';
                    $db_structure[$table_key]['collation'] = !empty($table_data['TABLE_COLLATION']) ? $table_data['TABLE_COLLATION'] : '';

                    foreach ($table_columns as $table_column) {
                        $table_column_name = isset($table_column['COLUMN_NAME']) ? $table_column['COLUMN_NAME'] : false;
                        if ($table_column_name) {
                            if (isset($table['columns'][$table_column_name])) {
                                $db_structure[$table_key]['columns'][$table_column_name]['check'] = true;
                            } else {
                                $db_structure[$table_key]['columns'][$table_column_name]['check'] = false;
                            }
                        }
                    }
                }
            }
        }
        return $db_structure;
    }
}
