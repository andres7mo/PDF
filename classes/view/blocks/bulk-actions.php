<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div class="alignleft actions bulkactions">
    <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'e2pdf'); ?></label>
    <select name="<?php echo $this->tpl_args->get('name'); ?>" id="bulk-action-selector-top">
        <option value="-1"><?php _e('Bulk Actions', 'e2pdf'); ?></option>
        <?php foreach ($this->tpl_args->get('options') as $key => $value) { ?>
            <option value="<?php echo $key; ?>"><?php _e($value, 'e2pdf'); ?></option>
        <?php } ?>
    </select>
    <input type="submit" id="<?php echo $this->tpl_args->get('id'); ?>" class="button action" value="<?php _e('Apply', 'e2pdf'); ?>">
</div>

