<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>

<div id="e2pdf-build-top-panel" class="e2pdf-build-top-panel">
    <ul class="e2pdf-panel-options">
        <li>
            <a title="<?php _e('Hide/Show Hidden Elements', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button button button-small e2pdf-hidden-elements e2pdf-inactive">
                <i class="dashicons dashicons-visibility"></i>
            </a>
            <a title="<?php _e('Lock/Unlock Locked Elements', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button button button-small e2pdf-locked-elements e2pdf-inactive">
                <i class="dashicons dashicons-unlock"></i>
            </a>
            <a title="<?php _e('Enable/Disable WYSIWYG Editor', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button button button-small e2pdf-wysiwyg-source e2pdf-inactive">
                <i class="dashicons dashicons-html"></i>
            </a>
        </li>
        <li>
            <a title="<?php _e('New Page', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button button button-small e2pdf-add-page">
                <i class="dashicons dashicons-welcome-add-page"></i> 
            </a>
        </li><li>
            <!-- undo -->
            <a title="<?php _e('Undo', 'e2pdf'); ?>" href="javascript:void(0);"  class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='undo'>
                <i class="dashicons dashicons-undo"></i>
            </a><a title="<?php _e('Redo', 'e2pdf'); ?>" href="javascript:void(0);"  class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='redo'>
                <i class="dashicons dashicons-redo"></i>
            </a><a title="<?php _e('Clear Style', 'e2pdf'); ?>" href="javascript:void(0);"  class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='clear'>
                <i class="dashicons dashicons-editor-removeformatting"></i>
            </a>
        </li><li>
            <div class="e2pdf-wysiwyg-color">
                <a title="<?php _e('Color', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button button button-small e2pdf-apply-wysiwyg-color">
                    <i class="dashicons dashicons-editor-textcolor"></i>
                </a>
                <div class="e2pdf-colorpicker-wr">
                    <input data-command='color' type="text" class="e2pdf-wysiwyg-font-color e2pdf-color-picker e2pdf-color-picker-load" value="">
                </div>
            </div><a title="<?php _e('Bold', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='bold'>
                <i class="dashicons dashicons-editor-bold"></i>
            </a><a title="<?php _e('Italic', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='italic'>
                <i class="dashicons dashicons-editor-italic"></i>
            </a><a title="<?php _e('Underline', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='underline'>
                <i class="dashicons dashicons-editor-underline"></i>
            </a><a title="<?php _e('Strike Through', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='strikeThrough'>
                <i class="dashicons dashicons-editor-strikethrough"></i>
            </a>

        </li><li><a title="<?php _e('Align Left', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='justifyLeft'>
                <i class="dashicons dashicons-editor-alignleft"></i>
            </a><a title="<?php _e('Align Center', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='justifyCenter'>
                <i class="dashicons dashicons-editor-aligncenter"></i>
            </a><a title="<?php _e('Align Right', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='justifyRight'>
                <i class="dashicons dashicons-editor-alignright"></i>
            </a>
        </li><li>
            <!-- UL -->
            <a title="<?php _e('Unordered List', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='insertUnorderedList'>
                <i class="dashicons dashicons-editor-ul"></i>
            </a><a title="<?php _e('Ordered List', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='insertOrderedList'>
                <i class="dashicons dashicons-editor-ol"></i>
            </a>
        </li><li><a title="<?php _e('Heading H1', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='H1'>
                <i class="dashicons dashicons-heading"></i>1
            </a><a title="<?php _e('Heading H2', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='H2'>
                <i class="dashicons dashicons-heading"></i>2
            </a>
        </li><li><a title="<?php _e('Link', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='createlink'>
                <i class="dashicons dashicons-admin-links"></i>
            </a><a title="<?php _e('Unlink', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button e2pdf-apply-wysiwyg button button-small" data-command='unlink'>
                <i class="dashicons dashicons-editor-unlink"></i>
            </a>
        </li>
        <li><a title="<?php _e('Table', 'e2pdf'); ?>" href="javascript:void(0);" class="ed_button button button-small e2pdf-wysiwyg-table">
                <i class="dashicons dashicons-editor-table"></i>
            </a><div class="e2pdf-wysiwyg-table-grid e2pdf-hide"></div>
        </li>
        <li>
            <label><?php _e('Font Size', 'e2pdf'); ?>:</label>
            <?php
            $this->render('field', 'select', array(
                'field' => array(
                    'class' => 'e2pdf-wysiwyg-fontsize',
                    'data-command' => 'font-size'
                ),
                'value' => '',
                'empty' => '-',
                'options' => $this->controller->get_font_sizes(),
            ));
            ?>
            <label><?php _e('Font', 'e2pdf'); ?>:</label>
            <?php
            $this->render('field', 'select', array(
                'field' => array(
                    'class' => 'e2pdf-wysiwyg-font',
                    'data-command' => 'font'
                ),
                'value' => '',
                'empty' => '-',
                'options' => $this->controller->get_fonts(true),
            ));
            ?>
        </li>
    </ul>
</div>
