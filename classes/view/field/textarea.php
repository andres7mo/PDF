<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<textarea <?php foreach ($this->tpl_args->get('field') as $key => $value) { ?><?php if (($key === 'disabled' && $value != false) || $key != 'disabled') { ?><?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>" <?php } ?><?php } ?>><?php echo esc_textarea($this->tpl_args->get('value')); ?></textarea>
