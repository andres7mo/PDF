<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<?php if (get_option('e2pdf_debug', '0')) { ?>
    <div class="e2pdf-debug-panel">
        <?php _e('Debug Mode is ON', 'e2pdf') ?> <?php echo $this->helper->get('version') ?>
        <?php if (defined('RECOVERY_MODE_EMAIL')) { ?>
            <?php _e('Recovery Mode E-mail', 'e2pdf') ?>: <?php echo esc_html(RECOVERY_MODE_EMAIL); ?>
        <?php } ?>
        <?php if (get_option('e2pdf_memory_time', '0')) { ?>
            <?php _e('Load Time', 'e2pdf') ?>: <?php echo microtime(true) - (float) $this->helper->get('time_debug'); ?> | <?php _e('Total Memory Usage', 'e2pdf') ?>: <?php echo $this->helper->load('convert')->from_bytes(memory_get_usage()); ?> | <?php _e('E2Pdf Memory Usage', 'e2pdf') ?>: <?php echo $this->helper->load('convert')->from_bytes(memory_get_usage() - (float) $this->helper->get('memory_debug')); ?>
        <?php } ?>
    </div> 
<?php } ?>