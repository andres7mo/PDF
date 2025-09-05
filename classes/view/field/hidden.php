<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<input type="hidden" <?php foreach ($this->tpl_args->get('field') as $key => $value) { ?><?php if ((($key === 'disabled' || $key === 'readonly') && $value != false) || ($key != 'disabled' && $key != 'readonly')) { ?><?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>" <?php } ?><?php } ?> value="<?php echo esc_attr($this->tpl_args->get('value')); ?>">
