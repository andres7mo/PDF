<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div class='wrap js'>
    <h1><?php _e('License', 'e2pdf'); ?></h1>
    <hr class="wp-header-end">
    <?php $this->render('blocks', 'notifications'); ?>
    <h3 class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-license')); ?>" class="nav-tab <?php if (!$this->get->get('action')) { ?>nav-tab-active<?php } ?>"><?php echo _e('License', 'e2pdf'); ?></a>
    </h3>
    <?php if (!$this->get->get('action')) { ?>
        <ul class="e2pdf-view-area">
            <li>
                <div class="e2pdf-name"><?php _e('Site URL', 'e2pdf'); ?>:
                </div><div class="e2pdf-value">
                    <?php echo site_url(); ?>
                </div>
            </li>
            <li>
                <div class="e2pdf-name"><?php _e('Type', 'e2pdf'); ?>:
                </div><div class="e2pdf-value">
                    <?php if ($this->view->license->get('type')) { ?>
                        <a target="_blank" href="<?php echo $this->view->license->get('type') == "FREE" ? "https://e2pdf.com/price" : "https://e2pdf.com/checkout/license/upgrade/" . get_option('e2pdf_license', ''); ?>"><strong><?php echo _e($this->view->license->get('type'), 'e2pdf'); ?></strong></a>
                    <?php } ?>
                </div>
            </li>
            <li>
                <div class="e2pdf-name"><?php _e('License Key', 'e2pdf'); ?>:
                </div><div class="e2pdf-value">
                    <?php echo get_option('e2pdf_license', false) ?>
                </div>
            </li>
            <li>
                <div class="e2pdf-name"></div><div class="e2pdf-value">
                    <div class="e2pdf-ib e2pdf-pr5">
                        <a href="javascript:void(0);" data-modal="license-key" data-modal-title="<?php _e('License Key', 'e2pdf'); ?>" class="e2pdf-modal page-title-action e2pdf-simple-button"><?php _e('Change', 'e2pdf'); ?></a>
                    </div><?php if ($this->view->license->get('type') && $this->view->license->get('type') != "FREE") { ?><div class="e2pdf-ib">
                            <a id="e2pdf-unlink-license-key" class="e2pdf-link page-title-action e2pdf-simple-button" href="javascript:void(0);" _wpnonce="<?php echo wp_create_nonce('e2pdf_license'); ?>"><?php _e('Disconnect', 'e2pdf'); ?></a>
                        </div>
                    <?php } ?>
                </div>
            </li>  
            <li>
                <div class="e2pdf-name"><?php _e('Sites', 'e2pdf'); ?>:
                </div><div class="e2pdf-value">
                    <?php echo $this->view->license->get('active_sites'); ?>/<?php echo $this->view->license->get('sites_limit'); ?></div>
            </li>
            <li>
                <div class="e2pdf-name"><?php _e('Templates', 'e2pdf'); ?>:
                </div><div class="e2pdf-value">
                    <div class="e2pdf-ib e2pdf-w100"><?php echo $this->view->license->get('active_templates'); ?>/<?php echo $this->view->license->get('templates_limit'); ?></div>
                    <?php if ($this->view->license->get('templates_limit')) { ?>
                        <div class="e2pdf-ib">
                            <a class="e2pdf-link page-title-action e2pdf-simple-button" id="e2pdf-deactivate-all-templates" href="javascript:void(0);" _wpnonce="<?php echo wp_create_nonce('e2pdf_license'); ?>"><?php _e('Deactivate Templates', 'e2pdf'); ?></a>
                        </div>
                    <?php } ?>
                </div>
            </li>
            <li>
                <div class="e2pdf-name"><?php _e('Expire Date', 'e2pdf'); ?>:
                </div><div class="e2pdf-value e2pdf-license-status">
                    <?php if ($this->view->license->get('expire') != '-') { ?><a target="_blank" href="https://e2pdf.com/checkout/license/renew/<?php echo get_option('e2pdf_license', ''); ?>"  class="e2pdf-link e2pdf-valign-top"><?php } ?>
                        <span class="<?php echo $this->view->license->get('status'); ?>"><?php echo $this->view->license->get('expire'); ?></span>
                        <?php if ($this->view->license->get('expire') != '-') { ?></a><?php } ?>
                </div>
            </li>
        </ul>
    <?php } ?>
</div>
<?php $this->render('blocks', 'debug-panel'); ?>

