<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<ul class="subsubsub">
    <li class="all">
        <a <?php if (!$this->get->get('status')) { ?> class="current" <?php } ?> href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates'))); ?>">
            <?php _e('All', 'e2pdf'); ?> <span class="count">(<?php echo $this->controller->get_templates_list(array(), true); ?>)</span>
        </a> |
    </li>
    <li class="trash">
        <a <?php if ($this->get->get('status') == 'trash') { ?>class="current"<?php } ?> href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => 'trash'))); ?>">
            <?php _e('Trash', 'e2pdf'); ?> <span class="count">(<?php echo $this->controller->get_templates_list(array('status' => 'trash'), true); ?>)</span>
        </a>
    </li>
</ul>
