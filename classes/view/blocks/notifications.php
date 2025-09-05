<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<?php foreach ($this->get_notifications() as $key => $notify) { ?>
    <?php if ($notify['type'] === 'update') { ?>
        <div id="message" class="e2pdf-notice notice notice-success is-dismissible">
            <p><?php echo $notify['text']; ?></p>
        </div>
    <?php } elseif ($notify['type'] === 'error') { ?>
        <div id="message" class="e2pdf-notice notice notice-error is-dismissible">
            <p><b><?php echo __('[ERROR]', 'e2pdf'); ?></b> <?php echo $notify['text']; ?></p>
        </div>
    <?php } elseif ($notify['type'] === 'notice') { ?>
        <div id="message" class="e2pdf-notice notice notice-warning is-dismissible">
            <p><?php echo $notify['text']; ?></p>
        </div>
    <?php } ?>
<?php } ?>