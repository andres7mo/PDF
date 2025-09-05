<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div id="e2pdf-build-bottom-panel" class="e2pdf-build-bottom-panel">
    <ul class="e2pdf-panel-options e2pdf-float-left">
        <li><a class="button e2pdf-delete-all-pages" href="javascript:void(0);"><?php _e('Delete All Pages', 'e2pdf') ?></a></li>
    </ul>
    <ul class="e2pdf-panel-options e2pdf-float-right">
        <li>
            <label><?php _e('Zoom', 'e2pdf'); ?>:</label>
            <?php
            $this->render('field', 'select', array(
                'field' => array(
                    'id' => 'e2pdf-zoom',
                ),
                'value' => '100',
                'options' => array(
                    '200' => '200%',
                    '175' => '175%',
                    '150' => '150%',
                    '125' => '125%',
                    '100' => '100%',
                    '75' => '75%',
                    '50' => '50%',
                    '25' => '25%',
                ),
            ));
            ?>
        </li>
    </ul>
    <div class="clear"></div>
</div>
