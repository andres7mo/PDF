<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div class="wrap <?php echo $this->page; ?>">
    <h1><?php _e('Debug', 'e2pdf') ?></h1>
    <hr class="wp-header-end">
    <?php $this->render('blocks', 'notifications'); ?>
    <h3 class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug')); ?>" class="nav-tab <?php if (!$this->get->get('action')) { ?>nav-tab-active<?php } ?>"><?php echo _e('Debug', 'e2pdf'); ?></a>
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug', 'action' => 'connections')); ?>" class="nav-tab <?php if ($this->get->get('action') == 'connections') { ?>nav-tab-active<?php } ?>"><?php _e('Connections', 'e2pdf'); ?></a>
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug', 'action' => 'db')); ?>" class="nav-tab <?php if ($this->get->get('action') == 'db') { ?>nav-tab-active<?php } ?>"><?php _e('DB', 'e2pdf'); ?></a>
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug', 'action' => 'phpinfo')); ?>" class="nav-tab <?php if ($this->get->get('action') == 'phpinfo') { ?>nav-tab-active<?php } ?>">PHP</a>
    </h3>
    <div class="wrap">
        <?php if (!$this->get->get('action')) { ?>
            <div class="e2pdf-view-area">
                <ul class="e2pdf-options-list">
                    <li><h2><?php _e('Maintenance', 'e2pdf') ?></h2></li>
                    <li>
                        <div class="e2pdf-name">
                            <?php _e('Reinitialize Activation Hooks', 'e2pdf'); ?>:
                        </div><div class="e2pdf-value">
                            <form onsubmit="return confirm('<?php _e('Are you sure want to continue?', 'e2pdf') ?>');" method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug')); ?>">
                                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_debug'); ?>">
                                <input type="hidden" name="e2pdf_updated" value="1">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="»">
                            </form>
                        </div>
                    </li>
                </ul>
                <hr>
                <small><?php _e("* It is strongly RECOMMENDED that you backup your WEBSITE before any of these actions are applied to avoid data loss", 'e2pdf') ?></small>
            </div>
            <ul class="e2pdf-view-area">
                <li><h2><?php _e('Common', 'e2pdf') ?></h2></li>
                <li><span class="e2pdf-bold"><?php _e('Site URL', 'e2pdf') ?>:</span> <?php echo $this->helper->get_site_url(); ?></li>
                <li><span class="e2pdf-bold"><?php _e('Plugin Version', 'e2pdf') ?>:</span> <?php echo $this->helper->get('version'); ?></li>
                <li><span class="e2pdf-bold"><?php _e('DB Version', 'e2pdf') ?>:</span> <?php echo get_option('e2pdf_version'); ?></li>
                <li><span class="e2pdf-bold"><?php _e('WP Version', 'e2pdf') ?>:</span> <?php echo get_bloginfo('version'); ?></li>
                <li><span class="e2pdf-bold"><?php _e('Multisite', 'e2pdf') ?>:</span> <?php is_multisite() ? _e('Yes', 'e2pdf') : _e('No', 'e2pdf'); ?></span></li>
                <li><span class="e2pdf-bold"><?php _e('Is Main Site', 'e2pdf') ?>:</span> <?php is_main_site() ? _e('Yes', 'e2pdf') : _e('No', 'e2pdf'); ?></span></li>
                <li><h2><?php _e('Settings', 'e2pdf') ?></h2></li>
                <li><span class="e2pdf-bold"><?php _e('PDF Processor', 'e2pdf') ?>:</span> <?php echo get_option('e2pdf_processor', '0') ? 'Release Candidate (Debug Mode)' : 'Default (Stable Version)' ?></li>
                <li><span class="e2pdf-bold"><?php _e('Font Processor', 'e2pdf') ?>:</span> <?php echo get_option('e2pdf_font_processor', '0') ? 'Complex Fonts' : 'Plain Fonts' ?></li>
                <li><h2>PHP</h2></li>
                <li><span class="e2pdf-bold">PHP:</span> <?php echo phpversion(); ?></li>
                <li><span class="e2pdf-bold">post_max_size:</span> <?php echo ini_get('post_max_size'); ?></span></li>
                <li><span class="e2pdf-bold">upload_max_filesize:</span> <?php echo ini_get('upload_max_filesize'); ?></span></li>
                <li><span class="e2pdf-bold">memory_limit:</span> <?php echo ini_get('memory_limit'); ?></span></li>
                <li><span class="e2pdf-bold">max_execution_time:</span> <?php echo ini_get('max_execution_time'); ?></span></li>
                <li><h2><?php _e('Folders', 'e2pdf') ?></h2></li>
                <li><span class="e2pdf-bold"><?php _e('WP Folder', 'e2pdf') ?>:</span></li>
                <li><?php echo ABSPATH ?></li>
                <li><span class="e2pdf-bold"><?php _e('Upload Folder', 'e2pdf') ?>:</span></li>
                <li><?php echo $this->helper->get('upload_dir'); ?></li>
                <li><span class="e2pdf-bold"><?php _e('Plugin Folder', 'e2pdf') ?>:</span></li>
                <li><?php echo $this->helper->get('plugin_dir'); ?></li>
                <li><span class="e2pdf-bold"><?php _e('Folders permission', 'e2pdf') ?>:</span></li>
                <li><?php if (is_writable($this->helper->get('tmp_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('tmp_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('cache_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('cache_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('pdf_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('pdf_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('fonts_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('fonts_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('tpl_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('tpl_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('viewer_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('viewer_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('bulk_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('bulk_dir'); ?>
                </li>
                <li><?php if (is_writable($this->helper->get('wpcf7_dir'))) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> <?php echo $this->helper->get('wpcf7_dir'); ?>
                </li>
                <li><h2><?php _e('PHP Extensions', 'e2pdf') ?></h2></li>
                <li>
                    <?php if (!function_exists('curl_version')) { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span> CURL<br><small>curl_version</small>
                    <?php } elseif (in_array('curl_exec', $this->view->disabled_functions)) { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span> CURL<br><small>curl_exec</small>
                    <?php } else { ?>
                        <span class="e2pdf-color-green">[OK]</span> CURL
                    <?php } ?> 
                </li>
                <li>
                    <?php if (extension_loaded('simplexml')) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> SIMPLEXML
                </li>
                <li>
                    <?php if (extension_loaded('libxml')) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> LIBXML
                </li>
                <li>
                    <?php if (extension_loaded('Dom')) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> DOM
                </li>
                <li>
                    <?php if (extension_loaded('intl')) { ?>
                        <span class="e2pdf-color-green">[OK]</span>
                    <?php } else { ?>
                        <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                    <?php } ?> INTL
                </li>
                <?php
                if (function_exists('get_locale') && function_exists('get_available_languages')) {
                    $locales = get_available_languages();
                    if (!in_array(get_locale(), $locales)) {
                        array_unshift($locales, get_locale());
                    }
                    ?>
                    <li><h2><?php _e('Locales', 'e2pdf') ?>:</h2></li>
                    <li><?php echo implode(', ', $locales); ?></span></li>
                    <?php
                }
                ?>
                <?php
                if (function_exists('get_post_types') && function_exists('get_post_type_labels')) {
                    $post_type_objects = get_post_types(array(), 'objects');
                    $post_types = array();
                    foreach ($post_type_objects as $post_type_obj) {
                        $labels = get_post_type_labels($post_type_obj);
                        $post_types[] = esc_html($labels->name) . ' (' . esc_html($post_type_obj->name) . ')';
                    }
                    ?>
                    <li><h2><?php _e('Post Types', 'e2pdf') ?>:</h2></li>
                    <li><?php echo implode(', ', $post_types); ?></span></li>
                    <?php
                }
                ?>
                <?php
                if (function_exists('get_intermediate_image_sizes')) {
                    $image_sizes = get_intermediate_image_sizes();
                    ?>
                    <li><h2><?php _e('Image Sizes', 'e2pdf') ?>:</h2></li>
                    <li><?php echo implode(', ', $image_sizes); ?></span></li>
                    <?php
                }
                ?>
                <li><h2><?php _e('Plugins', 'e2pdf') ?>:</h2></li>
                <li>
                    <?php echo implode(", ", get_option('active_plugins')); ?>
                </li>
            </ul>
        <?php } elseif ($this->get->get('action') == 'db') { ?>
            <div class="e2pdf-view-area">
                <ul class="e2pdf-options-list">
                    <li><h2><?php _e('Maintenance', 'e2pdf') ?></h2></li>
                    <li>
                        <div class="e2pdf-name">
                            <?php _e('Repair DB', 'e2pdf'); ?>:
                        </div><div class="e2pdf-value">
                            <form onsubmit="return confirm('<?php _e('Are you sure want to continue?', 'e2pdf') ?>');" method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug', 'action' => 'db')); ?>">
                                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_debug'); ?>">
                                <input type="hidden" name="e2pdf_db_repair" value="1">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="»">
                            </form>
                        </div>
                    </li>
                    <?php if (!$this->view->db_check_collate) { ?>
                        <li>
                            <div class="e2pdf-name">
                                <?php _e('Repair DB Collate', 'e2pdf'); ?>:
                            </div><div class="e2pdf-value">
                                <form onsubmit="return confirm('<?php _e('Are you sure want to continue?', 'e2pdf') ?>');" method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug', 'action' => 'db')); ?>">
                                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_debug'); ?>">
                                    <input type="hidden" name="e2pdf_db_repair_collate" value="1">
                                    <input type="submit" name="submit" id="submit" class="button button-primary" value="»">
                                </form>
                            </div>
                        </li>
                    <?php } ?>
                    <li>
                        <div class="e2pdf-name">
                            <?php _e('Reinitialize DB Hooks', 'e2pdf'); ?>:
                        </div><div class="e2pdf-value">
                            <form onsubmit="return confirm('<?php _e('Are you sure want to continue?', 'e2pdf') ?>');" method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-debug', 'action' => 'db')); ?>">
                                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_debug'); ?>">
                                <input type="hidden" name="e2pdf_db" value="1">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="»">
                            </form>
                        </div>
                    </li>
                </ul>
                <hr>
                <small><?php _e("* It is strongly RECOMMENDED that you backup your DB before any of these actions are applied to avoid data loss", 'e2pdf') ?></small>
            </div>
            <br>
            <div class="e2pdf-view-area">
                <ul>
                    <li><h2><?php _e('Structure', 'e2pdf') ?></h2></li>
                    <?php foreach ($this->view->db_structure as $table_key => $table) { ?>
                        <li><span class="e2pdf-bold <?php echo $table['check'] ? 'e2pdf-color-green' : 'e2pdf-color-red' ?>"><?php echo $table_key; ?></span></li>
                        <li style="font-size: 8px;"><span>Table Format: <?php echo strtoupper($table['format']); ?></span></li>
                        <li style="font-size: 8px;"><span>Table Collate: <?php echo strtoupper($table['collation']); ?></span></li>
                        <li>
                            <?php foreach ($table['columns'] as $column_key => $column) { ?>
                                <span class="<?php echo isset($column['check']) && $column['check'] ? 'e2pdf-color-green' : 'e2pdf-color-red' ?>"><?php echo $column_key; ?></span>
                            <?php } ?>
                            <br><br>

                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } elseif ($this->get->get('action') == 'phpinfo') { ?>
            <div class="e2pdf-view-area">
                <div class="phpinfo_wrapper">
                    <?php echo $this->view->phpinfo; ?>
                </div>
            </div>
        <?php } elseif ($this->get->get('action') == 'connections') { ?>
            <div class="e2pdf-view-area">
                <ul>
                    <li><h2><?php _e('Connections', 'e2pdf') ?></h2></li>
                    <?php if (isset($this->view->connections['self_connection'])) { ?>
                        <li>
                            <?php if (!isset($this->view->connections['self_connection']['error'])) { ?>
                                <span class="e2pdf-color-green">[OK]</span>
                            <?php } else { ?>
                                <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                            <?php } ?> <?php _e('Back-connection', 'e2pdf') ?>
                        </li>
                    <?php } ?>
                    <?php if (isset($this->view->connections['api_connection_upload'])) { ?>
                        <li>
                            <?php if (!isset($this->view->connections['api_connection_upload']['error'])) { ?>
                                <span class="e2pdf-color-green">[OK]</span>
                            <?php } else { ?>
                                <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                            <?php } ?> <?php _e('Upload Speed', 'e2pdf') ?>: <?php echo $this->view->connections['api_connection_upload']['result']; ?>
                        </li>
                    <?php } ?>
                    <?php if (isset($this->view->connections['api_connection_download'])) { ?>
                        <li>
                            <?php if (!isset($this->view->connections['api_connection_download']['error'])) { ?>
                                <span class="e2pdf-color-green">[OK]</span>
                            <?php } else { ?>
                                <span class="e2pdf-color-red"><?php _e('[ERROR]', 'e2pdf'); ?></span>
                            <?php } ?> <?php _e('Download Speed', 'e2pdf') ?>: <?php echo $this->view->connections['api_connection_download']['result']; ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
</div>
<?php $this->render('blocks', 'debug-panel'); ?>


