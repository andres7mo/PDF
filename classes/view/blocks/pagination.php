<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div class="tablenav-pages e2pdf-tablenav-pages"><span class="displaying-num">
        <?php echo sprintf(__("Templates: %d", 'e2pdf'), $this->tpl_args->get('total')) ?></span>
    <span class="pagination-links">
        <?php if (($this->tpl_args->get('paged')) > 2) { ?>
            <a class="first-page button" href="<?php echo $this->helper->get_url($this->tpl_args->get('url')); ?>"><span class="screen-reader-text"><?php _e('First page', 'e2pdf') ?></span><span aria-hidden="true">«</span></a>
        <?php } else { ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
        <?php } ?>

        <?php if (($this->tpl_args->get('paged')) > 1) { ?>
            <a class="prev-page button" href="<?php echo $this->helper->get_url($this->tpl_args->get('paged') > 2 ? array_merge($this->tpl_args->get('url'), array('paged' => $this->tpl_args->get('paged') - 1)) : $this->tpl_args->get('url')); ?>"><span class="screen-reader-text"><?php _e('Prev page', 'e2pdf') ?></span><span aria-hidden="true">‹</span></a>
        <?php } else { ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
        <?php } ?>
        <span class="screen-reader-text"><?php _e('Current Page', 'e2pdf') ?></span>
        <span id="table-paging" class="paging-input">
            <span class="tablenav-paging-text"><?php echo sprintf(__("%d of <span class=\"total-pages\">%d</span>", 'e2pdf'), $this->tpl_args->get('paged'), $this->tpl_args->get('total') ? ceil($this->tpl_args->get('total') / $this->tpl_args->get('limit')) : '1'); ?></span>
        </span>
        <?php if ((ceil($this->tpl_args->get('total') / $this->tpl_args->get('limit')) - $this->tpl_args->get('paged')) >= 1) { ?>
            <a class="next-page button" href="<?php echo $this->helper->get_url(array_merge($this->tpl_args->get('url'), array('paged' => $this->tpl_args->get('paged') + 1))); ?>"><span class="screen-reader-text"><?php _e('Next page', 'e2pdf') ?></span><span aria-hidden="true">›</span></a>
        <?php } else { ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
        <?php } ?>

        <?php if ((ceil($this->tpl_args->get('total') / $this->tpl_args->get('limit')) - $this->tpl_args->get('paged')) >= 2) { ?>
            <a class="last-page button" href="<?php echo $this->helper->get_url(array_merge($this->tpl_args->get('url'), array('paged' => ceil($this->tpl_args->get('total') / $this->tpl_args->get('limit'))))); ?>"><span class="screen-reader-text"><?php _e('Last page', 'e2pdf') ?></span><span aria-hidden="true">»</span></a>
        <?php } else { ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
        <?php } ?>
    </span>
</div>
