<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div id="submitbox" class="submitbox e2pdf-submitbox">
    <div id="minor-publishing">
        <div id="misc-shortcodes-actions">
            <div class="misc-pub-section">
                <div class="e2pdf-rel e2pdf-closed">
                    <a href="javascript:void(0);" class="e2pdf-link e2pdf-hidden-dropdown button e2pdf-w100 e2pdf-center <?php if ($this->get->get('action') !== 'edit') { ?>disabled<?php } ?>" <?php if ($this->get->get('action') !== 'edit') { ?>disabled="disabled"<?php } ?>><?php _e('Shortcodes', 'e2pdf') ?> <span class="toggle-indicator" aria-hidden="true"></span></a>
                    <div class="e2pdf-hidden-dropdown-content">
                        <?php if ($this->get->get('action') === 'edit') { ?>
                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-attachment id="<?php echo $this->view->template->get('ID'); ?>"]'>
                            </div>
                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-download id="<?php echo $this->view->template->get('ID'); ?>"]'>
                            </div>
                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-save id="<?php echo $this->view->template->get('ID'); ?>"]'>
                            </div>
                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-view id="<?php echo $this->view->template->get('ID'); ?>"]'>
                            </div>
                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-zapier id="<?php echo $this->view->template->get('ID'); ?>"]'>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="minor-publishing-actions">
            <div class="e2pdf-grid">
                <div class="e2pdf-ib e2pdf-w50 e2pdf-pr5 e2pdf-align-left">

                    <?php if (!$this->get->get('revision_id')) { ?>
                        <a class="e2pdf-link e2pdf-modal" data-modal="tpl-options" href="javascript:void(0);">
                            <?php _e('Options', 'e2pdf'); ?> <i class="dashicons dashicons-admin-generic"></i>
                        </a>
                    <?php } ?>
                </div><div class="e2pdf-ib e2pdf-w50 e2pdf-pl5">
                    <div id="preview-action">
                        <a form-id="e2pdf-build-form" target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'preview')); ?>" class="button e2pdf-w100 e2pdf-center e2pdf-submit-form e2pdf-link" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>"><?php _e('Preview', 'e2pdf'); ?></a>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>
        </div>
        <div class="misc-pub-section">
            <div class="e2pdf-grid">
                <div class="e2pdf-ib e2pdf-w50 e2pdf-pr5 e2pdf-align-left e2pdf-mt5">
                    <?php if ($this->view->template->get('pdf')) { ?>
                        <a class="e2pdf-link" title="<?php _e('Download Original PDF', 'e2pdf') ?>" target="_blank" href="<?php echo $this->helper->get_upload_url('pdf/' . $this->view->template->get('pdf') . '/' . $this->view->template->get('pdf') . '.pdf') ?>"><i class="dashicons dashicons-download"></i></a>
                        <?php if (!$this->get->get('revision_id')) { ?>
                            <a class="e2pdf-link e2pdf-modal" data-modal="pdf-reupload" title="<?php _e('Replace PDF', 'e2pdf') ?>" href="javascript:void(0);"><i class="dashicons dashicons-upload"></i></a>
                            <a class="e2pdf-link e2pdf-delete-pdf" title="<?php _e('Remove PDF', 'e2pdf') ?>" href="javascript:void(0);"><i class="dashicons dashicons-no"></i></a>
                        <?php } ?>
                    <?php } else { ?>
                        <?php if ($this->get->get('action') === 'edit' && !$this->get->get('revision_id')) { ?>
                            <a class="e2pdf-link e2pdf-modal" data-modal="pdf-reupload" title="<?php _e('Upload PDF', 'e2pdf') ?>" href="javascript:void(0);"><i class="dashicons dashicons-upload"></i></a>
                        <?php } ?>
                    <?php } ?>
                </div><div class="e2pdf-ib e2pdf-w50 e2pdf-pl5 e2pdf-center">
                    <a <?php if ($this->get->get('action') !== 'edit') { ?>disabled="disabled"<?php } ?> target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'view', 'id' => $this->view->template->get('ID'))); ?>" class="e2pdf-ib e2pdf-link"><?php _e('View Saved', 'e2pdf'); ?></a><br/>
                    <a <?php if (!$this->view->template->get('activated')) { ?>disabled="disabled"<?php } ?> target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf', 'id' => $this->view->template->get('ID'))); ?>" class="e2pdf-ib e2pdf-link e2pdf-mt6 e2pdf-generate-pdf-button"><?php _e('Create PDF', 'e2pdf'); ?></a>
                </div>
            </div>
        </div>
        <div id="misc-activation-actions">
            <?php if ($this->get->get('action') === 'edit') { ?>
                <div class="misc-pub-section misc-pub-e2pdf-revision">
                    <span id="e2pdf-post-revision">
                        <div class="e2pdf-grid e2pdf-w100">
                            <div class="e2pdf-ib e2pdf-w40">
                                <label for="e2pdf-revision-item" class="e2pdf-small"><?php _e('Revision', 'e2pdf'); ?>:</label>
                                <div class="e2pdf-ib e2pdf-small e2pdf-w100 "><?php echo sprintf(__("Revisions Limit: %s", 'e2pdf'), max(1, (int) get_option('e2pdf_revisions_limit', '3'))) ?></div>
                            </div><div class="e2pdf-ib e2pdf-w60 e2pdf-onload">
                                <?php
                                $this->render('field', 'select', array(
                                    'field' => array(
                                        'id' => 'e2pdf-revision',
                                        'name' => 'revision_id',
                                        'class' => 'e2pdf-w100 e2pdf-small disabled',
                                        'disabled' => 'disabled'
                                    ),
                                    'value' => $this->get->get('revision_id') ? $this->get->get('revision_id') : '0',
                                    'options' => $this->view->template->get('revisions'),
                                ));
                                ?>
                            </div>
                        </div>
                    </span>
                </div>
            <?php } ?>
            <div class="misc-pub-section misc-pub-e2pdf-tpl-hooks">
                <span id="e2pdf-post-tpl-hooks">
                    <a class="e2pdf-modal e2pdf-link" data-modal="tpl-hooks" href="javascript:void(0);"><?php _e('Integration Hooks', 'e2pdf'); ?></a>
                </span>
            </div>
            <div class="misc-pub-section misc-pub-e2pdf-tpl-actions">
                <span id="e2pdf-post-tpl-actions">
                    <a class="e2pdf-modal e2pdf-link" data-modal="tpl-actions" href="javascript:void(0);"><?php _e('Global Actions', 'e2pdf'); ?></a>
                </span>
            </div>
            <div class="misc-pub-section misc-pub-e2pdf-tpl-properties">
                <span id="e2pdf-post-tpl-properties">
                    <a class="e2pdf-modal e2pdf-link" data-modal="tpl-properties" href="javascript:void(0);"><?php _e('Global Properties', 'e2pdf'); ?></a>
                </span>
            </div>
            <div class="misc-pub-section misc-pub-e2pdf-item">
                <label for="e2pdf-post-item"><?php _e('Connection', 'e2pdf'); ?>:</label>
                <span id="e2pdf-post-item">
                    <?php if ($this->view->template->get('extension') && $this->view->template->get('item') && $this->view->template->extension()->item()) { ?>
                        <?php if ($this->view->template->get('item') == '-2') { ?>
                            <?php if ($this->view->template->get('item1')) { ?><a target="_blank" href="<?php echo $this->view->template->extension()->item($this->view->template->get('item1'))->url; ?>" class="e2pdf-link" <?php echo $this->view->template->extension()->item($this->view->template->get('item1'))->url == 'javascript:void(0);' ? 'disabled=disabled' : ''; ?>><?php echo $this->view->template->extension()->item($this->view->template->get('item1'))->name ?></a><?php } ?><?php if ($this->view->template->get('item2')) { ?><?php if ($this->view->template->get('item1')) { ?>, <?php } ?><a target="_blank" href="<?php echo $this->view->template->extension()->item($this->view->template->get('item2'))->url; ?>" class="e2pdf-link" <?php echo $this->view->template->extension()->item($this->view->template->get('item2'))->url == 'javascript:void(0);' ? 'disabled=disabled' : ''; ?>><?php echo $this->view->template->extension()->item($this->view->template->get('item2'))->name ?></a><?php } ?>
                        <?php } else { ?>
                            <a target="_blank" href="<?php echo $this->view->template->extension()->item()->url; ?>" class="e2pdf-link" <?php echo $this->view->template->extension()->item()->url == 'javascript:void(0);' ? 'disabled=disabled' : ''; ?>><?php echo $this->view->template->extension()->item()->name ?></a>
                        <?php } ?>
                    <?php } else { ?>   
                        <a href="javascript:void(0);" class="e2pdf-link e2pdf-modal" data-modal="tpl-options" <?php if ($this->get->get('revision_id')) { ?>disabled="disabled"<?php } ?> href="javascript:void(0);"><?php _e('None', 'e2pdf'); ?></a>
                    <?php } ?>

                </span>
            </div>
            <div class="misc-pub-section misc-pub-e2pdf-activation">
                <label for="e2pdf-post-activation"><?php _e('Activation', 'e2pdf'); ?>:</label>
                <span id="e2pdf-post-activation">
                    <?php if ($this->view->template->get('activated')) { ?>
                        <a class="e2pdf-color-green e2pdf-deactivate-template e2pdf-link" <?php if ($this->get->get('revision_id')) { ?>disabled="disabled"<?php } ?> data-id="<?php echo $this->view->template->get('ID'); ?>" href="javascript:void(0);" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>"><?php _e('Activated', 'e2pdf'); ?></a>
                    <?php } else { ?>
                        <a class="e2pdf-color-red e2pdf-activate-template e2pdf-link" <?php if ($this->get->get('action') !== 'edit' || $this->get->get('revision_id')) { ?>disabled="disabled"<?php } ?> data-id="<?php echo $this->view->template->get('ID'); ?>" href="javascript:void(0);" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>"><?php _e('Not Activated', 'e2pdf'); ?></a>
                    <?php } ?></span>
            </div>
        </div>
        <?php if (get_option('e2pdf_debug', '0') === '1' && defined('E2PDF_DEVELOPER')) { ?>
            <hr>
            <div class="misc-pub-section">
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w100">
                        <div id="convert-action">
                            <a form-id="e2pdf-build-form" target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'convert', 'type' => 'php')); ?>" class="e2pdf-submit-form e2pdf-link" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>">PHP</a>
                        </div>
                        <div class="clear"></div> 
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="clear"></div> 
    </div>
    <div id="major-publishing-actions">
        <?php if ($this->get->get('action') === 'edit' && !$this->get->get('revision_id')) { ?>
            <div id="delete-action">
                <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'trash', 'id' => $this->view->template->get('ID'), '_wpnonce' => wp_create_nonce('e2pdf_templates'))); ?>" onclick="return confirm('<?php _e('Trash', 'e2pdf'); ?>?')" class="submitdelete deletion"><?php _e('Trash', 'e2pdf'); ?></a>
            </div>
        <?php } ?>
        <div id="publishing-action" class="e2pdf-onload">
            <span class="spinner"></span>
            <input form-id="e2pdf-build-form" action="e2pdf_save_form" type="button" value="<?php
            if ($this->get->get('revision_id')) {
                _e('Restore', 'e2pdf');
            } else if ($this->get->get('action') === 'edit') {
                _e('Update', 'e2pdf');
            } else {
                _e('Save', 'e2pdf');
            }
            ?>" class="e2pdf-submit-form button-primary button-large disabled <?php if ($this->get->get('revision_id')) { ?>restore<?php } ?>" id="e2pdf-submit-side-top" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>">
        </div>
        <div class="clear"></div>
    </div>
</div>
