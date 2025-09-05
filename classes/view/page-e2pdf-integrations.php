<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div class="wrap">
    <h1><?php _e('Integrations', 'e2pdf'); ?></h1>
    <hr class="wp-header-end">
    <?php $this->render('blocks', 'notifications'); ?>
    <h3 class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($this->view->groups as $group_key => $group) { ?>
            <?php if (isset($group['action']) && isset($group['group'])) { ?>
                <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-integrations', 'action' => $group['action'], 'group' => $group['group'])); ?>" class="nav-tab <?php if ($this->get->get('action') === $group['action'] && $this->get->get('group') === $group['group']) { ?>nav-tab-active<?php } ?>"><?php echo $group['name']; ?></a>
            <?php } elseif (isset($group['action'])) { ?>
                <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-integrations', 'action' => $group['action'])); ?>" class="nav-tab <?php if ($this->get->get('action') === $group['action']) { ?>nav-tab-active<?php } ?>"><?php echo $group['name']; ?></a>
            <?php } else { ?>
                <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-integrations')); ?>" class="nav-tab <?php if (!$this->get->get('action')) { ?>nav-tab-active<?php } ?>"><?php echo $group['name']; ?></a>
            <?php } ?>
        <?php } ?>
    </h3>
    <div class="wrap">
        <div class="e2pdf-view-area">
            <?php if (isset($group['action']) && isset($group['group'])) { ?>
                <form method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-integrations', 'action' => $this->get->get('action'), 'group' => $this->get->get('group'))); ?>">
                <?php } elseif (isset($group['action'])) { ?>
                    <form method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-integrations', 'action' => $this->get->get('action'))); ?>">
                    <?php } else { ?>
                        <form method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf-integrations')); ?>">
                        <?php } ?>
                        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_integrations'); ?>">
                        <ul class="e2pdf-options-list">
                            <?php
                            $this->render('field', 'group', array(
                                'groups' => $this->view->options
                            ));
                            ?>
                        </ul>

                        <?php if ($this->get->get('action')) { ?>
                            <?php submit_button(); ?>
                        <?php } ?>
                    </form>
                    </div>
                    </div>
                    </div>
                    <?php $this->render('blocks', 'debug-panel'); ?>