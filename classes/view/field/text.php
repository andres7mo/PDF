<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<?php if ($this->tpl_args->get('prefield')) { ?><div class="e2pdf-prefield"><?php echo esc_html($this->tpl_args->get('prefield')); ?></div><span class="e2pdf-prefield-field"><?php } ?><input type="text" <?php foreach ($this->tpl_args->get('field') as $key => $value) { ?><?php if ((($key === 'disabled' || $key === 'readonly') && $value != false) || ($key != 'disabled' && $key != 'readonly')) { ?><?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>" <?php } ?><?php } ?> value="<?php echo esc_attr($this->tpl_args->get('value')); ?>"><?php if ($this->tpl_args->get('prefield')) { ?></span><?php } ?>
