<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<?php
$wrapper = '';
if ($this->get->get('action') && ($this->get->get('action') === 'create' || ($this->get->get('action') === 'edit') && $this->view->template) && get_option('e2pdf_new_edit_layout', '1')) {
    $wrapper = 'e2pdf-new-edit-layout';
}
?>
<div class='wrap js <?php echo $wrapper; ?>'>
    <?php if (!$this->get->get('action')) { ?>
        <h1><?php _e('Templates', 'e2pdf'); ?>
            <a href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'create'))); ?>" class="page-title-action">
                <?php _e('Add New', 'e2pdf') ?>
            </a>
        </h1>
        <hr class="wp-header-end">
        <?php $this->render('blocks', 'notifications'); ?>
        <?php $this->render('blocks', 'sub-filters'); ?>
        <form id="e2pdf-templates-filter" method="post">
            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_templates'); ?>">
            <p class="search-box">
                <input type="search" id="post-search-input" name="s" placeholder="<?php _e('Search...', 'e2pdf'); ?>" value="<?php echo $this->get->get('s'); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php _e('Search', 'e2pdf'); ?>">
            </p>
            <div class="tablenav top e2pdf-templates-list-tablenav">
                <?php
                if ($this->get->get('status') == 'trash') {
                    $this->render('blocks', 'bulk-actions', array(
                        'id' => 'doaction',
                        'name' => 'action',
                        'options' => array(
                            'restore' => __('Restore', 'e2pdf'),
                            'delete' => __('Delete', 'e2pdf')
                        )
                    ));
                } else {
                    $this->render('blocks', 'bulk-actions', array(
                        'id' => 'doaction',
                        'name' => 'action',
                        'options' => array(
                            'activate' => __('Activate', 'e2pdf'),
                            'deactivate' => __('Deactivate', 'e2pdf'),
                            'trash' => __('Trash', 'e2pdf'),
                        )
                    ));
                }
                ?>
                <div class="alignright actions bulkactions"><a href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'import'))); ?>" class="button action">
                        <?php _e('Import', 'e2pdf') ?>
                    </a></div>
            </div>
            <table class="wp-list-table widefat fixed striped pages e2pdf-templates-list">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'e2pdf'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                        <th scope="col" id="id" class="manage-column column-id sortable <?php if ($this->get->get('orderby') == 'id') { ?>sorted<?php } ?> <?php if ($this->get->get('orderby') == 'id' && $this->get->get('order') == 'asc') { ?>asc<?php } else { ?>desc<?php } ?>">
                            <a href="<?php
                            if ($this->get->get('orderby') == 'id' && $this->get->get('order') == 'asc') {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'id', 'order' => 'desc'));
                            } else {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'id', 'order' => 'asc'));
                            }
                            ?>">
                                <span>ID</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="title" class="manage-column column-title column-primary sortable <?php if ($this->get->get('orderby') == 'title') { ?>sorted<?php } ?> <?php if ($this->get->get('orderby') == 'title' && $this->get->get('order') == 'asc') { ?>asc<?php } else { ?>desc<?php } ?>">
                            <a href="<?php
                            if ($this->get->get('orderby') == 'title' && $this->get->get('order') == 'asc') {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'title', 'order' => 'desc'));
                            } else {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'title', 'order' => 'asc'));
                            }
                            ?>">
                                <span><?php _e('Title', 'e2pdf'); ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="item" class="manage-column column-item"><?php _e('Connection', 'e2pdf'); ?></th>
                        <th scope="col" id="shortcodes" class="manage-column column-shortcode"><?php _e('Shortcode', 'e2pdf'); ?></th>
                        <th scope="col" id="updated" class="manage-column column-updated sortable <?php if ($this->get->get('orderby') == 'updated_at') { ?>sorted<?php } ?> <?php if ($this->get->get('orderby') == 'updated_at' && $this->get->get('order') == 'asc') { ?>asc<?php } else { ?>desc<?php } ?>">
                            <a href="<?php
                            if ($this->get->get('orderby') == 'updated_at' && $this->get->get('order') == 'asc') {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'updated_at', 'order' => 'desc'));
                            } else {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'updated_at', 'order' => 'asc'));
                            }
                            ?>">
                                <span><?php _e('Updated', 'e2pdf'); ?></span>
                                <span class="sorting-indicator">
                                </span>
                            </a>
                        </th>
                        <th scope="col" id="activation" class="manage-column column-activation"><?php _e('Activation', 'e2pdf'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if ($this->is_empty($this->controller->get_templates_list($this->get->get()))) { ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="7">
                                <?php _e('No Templates Found', 'e2pdf'); ?> <a href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'create'))); ?>"><?php _e('Add New', 'e2pdf') ?></a>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($this->controller->get_templates_list($this->get->get()) as $key => $template) { ?>
                            <tr id="post-<?php echo $template->get('ID'); ?>" class="iedit author-self level-0 post-<?php echo $template->get('ID'); ?> type-page status-publish hentry">
                                <th scope="row" class="check-column">
                                    <label class="screen-reader-text" for="cb-select-2">Select Sample Page</label>
                                    <input id="cb-select-2" type="checkbox" name="post[]" value="<?php echo $template->get('ID'); ?>">
                                </th>
                                <td class="id column-id" data-colname="ID"><?php echo $template->get('ID'); ?></td>
                                <td class="title column-title has-row-actions column-primary page-title" data-colname="Title"><div class="locked-info"><span class="locked-avatar"></span><span class="locked-text"></span></div>
                                    <strong><a class="row-title" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'edit', 'id' => $template->get('ID'))); ?>"><?php echo esc_html($template->get('title')); ?></a></strong>
                                    <div class="row-actions">
                                        <?php if ($this->get->get('status') == 'trash') { ?>
                                            <span class="restore"><a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'restore', 'id' => $template->get('ID'), '_wpnonce' => wp_create_nonce('e2pdf_templates'), 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => $this->get->get('orderby'), 'order' => $this->get->get('order'))); ?>" rel="permalink"><?php _e('Restore', 'e2pdf'); ?></a>  | </span>
                                            <span class="delete"><a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'delete', 'id' => $template->get('ID'), '_wpnonce' => wp_create_nonce('e2pdf_templates'), 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => $this->get->get('orderby'), 'order' => $this->get->get('order'))); ?>" onclick="return confirm('<?php _e('Template will be removed! Continue?', 'e2pdf'); ?> ')" class="submitdelete"><?php _e('Delete', 'e2pdf'); ?></a></span>
                                        <?php } else { ?>
                                            <span class="edit"><a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'edit', 'id' => $template->get('ID'))); ?>"><?php _e('Edit', 'e2pdf'); ?></a> | </span>
                                            <span class="duplicate"><a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'duplicate', 'id' => $template->get('ID'), '_wpnonce' => wp_create_nonce('e2pdf_templates'))); ?>"><?php _e('Duplicate', 'e2pdf'); ?></a> | </span>
                                            <span class="trash"><a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'trash', 'id' => $template->get('ID'), '_wpnonce' => wp_create_nonce('e2pdf_templates'), 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => $this->get->get('orderby'), 'order' => $this->get->get('order'))); ?>" class="submitdelete"><?php _e('Trash', 'e2pdf'); ?></a> | </span>
                                            <span class="view"><a target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'view', 'id' => $template->get('ID'))); ?>" rel="permalink"><?php _e('View', 'e2pdf'); ?></a> | </span>
                                            <span class="download"><a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'download', 'id' => $template->get('ID'))); ?>" rel="permalink"><?php _e('Download', 'e2pdf'); ?></a></span>
                                            <?php if ($template->get('activated')) { ?>
                                                <span class="export"> | <a target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf', 'id' => $template->get('ID'))); ?>"><?php _e('Create PDF', 'e2pdf'); ?></a></span>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                    <button type="button" class="toggle-row"></button>
                                </td>
                                <td class="item column-item" data-colname="Item">
                                    <div>
                                        <?php if ($template->extension()->method('item') && $template->get('item')) { ?>
                                            <?php if ($template->get('item') == '-2') { ?>
                                                <?php if ($template->get('item1') && $template->extension()->item($template->get('item1'))->name) { ?><a target="_blank" href="<?php echo $template->extension()->item($template->get('item1'))->url; ?>" class="e2pdf-link" <?php echo $template->extension()->item($template->get('item1'))->url == 'javascript:void(0);' ? 'disabled=disabled' : ''; ?>><?php echo $template->extension()->item($template->get('item1'))->name ?></a><?php } ?><?php if ($template->get('item2')) { ?><?php if ($template->get('item1') && $template->extension()->item($template->get('item1'))->name) { ?>, <?php } ?><a target="_blank" href="<?php echo $template->extension()->item($template->get('item2'))->url; ?>" class="e2pdf-link" <?php echo $template->extension()->item($template->get('item2'))->url == 'javascript:void(0);' ? 'disabled=disabled' : ''; ?>><?php echo $template->extension()->item($template->get('item2'))->name ?></a><?php } ?>
                                            <?php } else { ?>
                                                <a target="_blank" href="<?php echo $template->extension()->item()->url; ?>"><?php echo $template->extension()->item()->name; ?></a>
                                            <?php } ?>
                                        <?php } else { ?>—<?php } ?></div>
                                    <div class="e2pdf-small"><?php if ($template->extension()->method('info')) { ?> <?php echo $template->extension()->info('title'); ?> <?php } ?></div>
                                </td>
                                <td class="shortcode column-shortcode" data-colname="Shortcode">
                                    <div class="e2pdf-rel e2pdf-closed">
                                        <a href="javascript:void(0);" class="e2pdf-link e2pdf-hidden-dropdown button e2pdf-w100 e2pdf-center"><?php _e('Shortcodes', 'e2pdf') ?> <span class="toggle-indicator" aria-hidden="true"></span></a>
                                        <div class="e2pdf-hidden-dropdown-content">
                                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-attachment id="<?php echo $template->get('ID'); ?>"]'>
                                            </div>
                                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-download id="<?php echo $template->get('ID'); ?>"]'>
                                            </div>
                                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-save id="<?php echo $template->get('ID'); ?>"]'>
                                            </div>
                                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-view id="<?php echo $template->get('ID'); ?>"]'>
                                            </div>
                                            <div class="misc-pub-section  misc-pub-e2pdf-shortcode">
                                                <input placeholder="<?php _e('Shortcode', 'e2pdf') ?>" class="e2pdf-center e2pdf-copy-field e2pdf-w100" type="text" readonly="readonly" value='[e2pdf-zapier id="<?php echo $template->get('ID'); ?>"]'>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="updated column-updated" data-colname="<?php _e('Updated', 'e2pdf') ?>">
                                    <?php echo $template->get('updated_at'); ?>
                                    <div class="e2pdf-small">by <strong><?php echo get_userdata($template->get('author'))->user_nicename; ?></strong></div>
                                </td>
                                <td class="author column-activation" data-colname="Activation">
                                    <span id="e2pdf-list-post-activation">
                                        <?php if ($template->get('activated')) { ?>
                                            <a class="e2pdf-color-green e2pdf-deactivate-template e2pdf-link" data-id="<?php echo $template->get('ID'); ?>" href="javascript:void(0);" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>"><?php _e('Activated', 'e2pdf'); ?></a>
                                        <?php } else { ?>
                                            <a class="e2pdf-color-red e2pdf-activate-template e2pdf-link" data-id="<?php echo $template->get('ID'); ?>" href="javascript:void(0);" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>"><?php _e('Not Activated', 'e2pdf'); ?></a>
                                        <?php } ?></span>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php echo _e('Select All', 'e2pdf'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                        <th scope="col" id="id" class="manage-column column-id sortable <?php if ($this->get->get('orderby') == 'id') { ?>sorted<?php } ?> <?php if ($this->get->get('orderby') == 'id' && $this->get->get('order') == 'asc') { ?>asc<?php } else { ?>desc<?php } ?>">
                            <a href="<?php
                            if ($this->get->get('orderby') == 'id' && $this->get->get('order') == 'asc') {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'id', 'order' => 'desc'));
                            } else {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'id', 'order' => 'asc'));
                            }
                            ?>">
                                <span>ID</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="title" class="manage-column column-title column-primary sortable <?php if ($this->get->get('orderby') == 'title') { ?>sorted<?php } ?> <?php if ($this->get->get('orderby') == 'title' && $this->get->get('order') == 'asc') { ?>asc<?php } else { ?>desc<?php } ?>">
                            <a href="<?php
                            if ($this->get->get('orderby') == 'title' && $this->get->get('order') == 'asc') {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'title', 'order' => 'desc'));
                            } else {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'title', 'order' => 'asc'));
                            }
                            ?>">
                                <span><?php _e('Title', 'e2pdf'); ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="item" class="manage-column column-item"><?php _e('Connection', 'e2pdf'); ?></th>
                        <th scope="col" id="shortcodes" class="manage-column column-shortcode"><?php _e('Shortcode', 'e2pdf'); ?></th>
                        <th scope="col" id="updated" class="manage-column column-updated sortable <?php if ($this->get->get('orderby') == 'updated_at') { ?>sorted<?php } ?> <?php if ($this->get->get('orderby') == 'updated_at' && $this->get->get('order') == 'asc') { ?>asc<?php } else { ?>desc<?php } ?>">
                            <a href="<?php
                            if ($this->get->get('orderby') == 'updated_at' && $this->get->get('order') == 'asc') {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'updated_at', 'order' => 'desc'));
                            } else {
                                echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => 'updated_at', 'order' => 'asc'));
                            }
                            ?>">
                                <span><?php _e('Updated', 'e2pdf'); ?></span>
                                <span class="sorting-indicator">
                                </span>
                            </a>
                        </th>
                        <th scope="col" id="activation" class="manage-column column-activation"><?php _e('Activation', 'e2pdf'); ?></th>
                    </tr>
                </tfoot>
            </table>
            <div class="tablenav bottom">
                <?php
                if ($this->get->get('status') == 'trash') {
                    $this->render('blocks', 'bulk-actions', array(
                        'id' => 'doaction2',
                        'name' => 'action2',
                        'options' => array(
                            'restore' => 'Restore',
                            'delete' => 'Delete'
                        )
                    ));
                } else {
                    $this->render('blocks', 'bulk-actions', array(
                        'id' => 'doaction2',
                        'name' => 'action2',
                        'options' => array(
                            'activate' => __('Activate', 'e2pdf'),
                            'deactivate' => __('Deactivate', 'e2pdf'),
                            'trash' => __('Trash', 'e2pdf'),
                        )
                    ));
                }
                $this->render('blocks', 'pagination', array(
                    'total' => $this->controller->get_templates_list($this->get->get(), true),
                    'limit' => (int) get_option('e2pdf_templates_screen_per_page', '20') > '0' ? get_option('e2pdf_templates_screen_per_page', '20') : '20',
                    'paged' => $this->get->get('paged') && (int) $this->get->get('paged') > '0' ? (int) $this->get->get('paged') : '1',
                    'url' => array('page' => 'e2pdf-templates', 'action' => $this->get->get('action'), 'status' => $this->get->get('status'), 's' => $this->get->get('s'), 'orderby' => $this->get->get('orderby'), 'order' => $this->get->get('order'))
                ));
                ?>
                <div class="clear"></div>
            </div>
        </form>
        <div class="clear"></div>
        <?php $this->render('blocks', 'debug-panel'); ?>
    <?php } elseif ($this->get->get('action')) { ?>
        <?php if ($this->get->get('action') === 'create' || ($this->get->get('action') === 'edit' && $this->view->template)) { ?>
            <?php if (!get_option('e2pdf_new_edit_layout', '1')) { ?>
                <div id="e2pdf-form-editor-header" class="e2pdf-form-editor-header">
                    <h1><?php echo $this->get->get('action') === 'edit' ? sprintf(__("ID: %d | Edit Template", 'e2pdf'), $this->view->template->get('ID')) : __('New Template', 'e2pdf'); ?>
                        <a href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'create'))); ?>" class="page-title-action">
                            <?php _e('Add New', 'e2pdf') ?>
                        </a>
                    </h1>
                    <hr class="wp-header-end">
                    <?php $this->render('blocks', 'notifications'); ?>
                </div>
            <?php } ?>
            <div id="poststuff">
                <form method="post" id="e2pdf-build-form">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content" class="e2pdf-post-body-content">
                            <div class="e2pdf-form-builder">
                                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_templates'); ?>">
                                <input type="hidden" name="sub_action" value="<?php echo $this->get->get('action') === 'create' ? 'create' : 'edit'; ?>">
                                <?php if ($this->view->template->get('ID')) { ?>
                                    <input type="hidden" name="ID" value="<?php echo $this->view->template->get('ID'); ?>">
                                    <input type="hidden" name="width" value="<?php echo $this->view->template->get('width'); ?>">
                                    <input type="hidden" name="height" value="<?php echo $this->view->template->get('height'); ?>">
                                    <input type="hidden" name="extension" value="<?php echo $this->view->template->get('extension'); ?>">
                                    <input type="hidden" name="item" value="<?php echo $this->view->template->get('item'); ?>">
                                    <input type="hidden" name="item1" value="<?php echo $this->view->template->get('item1'); ?>">
                                    <input type="hidden" name="item2" value="<?php echo $this->view->template->get('item2'); ?>">
                                    <input type="hidden" name="pdf" value="<?php echo $this->view->template->get('pdf'); ?>">
                                    <input type="hidden" name="format" value="<?php echo $this->view->template->get('format'); ?>">
                                    <input type="hidden" name="hooks" value="<?php echo $this->view->template->get('hooks'); ?>">
                                <?php } ?>
                                <div id="e2pdf-build-form-wrapper" class="e2pdf-build-form-wrapper">
                                    <?php if (get_option('e2pdf_new_edit_layout', '1')) { ?>
                                        <div id="e2pdf-form-editor-header" class="e2pdf-form-editor-header">
                                            <h1><?php echo $this->get->get('action') === 'edit' ? sprintf(__("ID: %d | Edit Template", 'e2pdf'), $this->view->template->get('ID')) : __('New Template', 'e2pdf'); ?>
                                                <a href="<?php echo ($this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'create'))); ?>" class="page-title-action">
                                                    <?php _e('Add New', 'e2pdf') ?>
                                                </a>
                                            </h1>
                                            <hr class="wp-header-end">
                                            <?php $this->render('blocks', 'notifications'); ?>
                                        </div>
                                    <?php } ?>
                                    <div id="e2pdf-form-editor-container" class="e2pdf-form-editor-container">
                                        <div id="titlediv">
                                            <?php
                                            $this->render('field', 'text', array(
                                                'field' => array(
                                                    'id' => 'e2pdf-title',
                                                    'name' => 'title',
                                                    'placeholder' => __('Template Title', 'e2pdf'),
                                                    'class' => 'e2pdf-w100'
                                                ),
                                                'value' => $this->view->template->get('title'),
                                            ));
                                            ?>
                                        </div>
                                        <?php
                                        $this->render('blocks', 'wysiwyg');
                                        ?>
                                        <div class="clear"></div>
                                    </div>
                                    <div class="e2pdf-tpl-wrapper">
                                        <div <?php echo $this->view->template->get('rtl') ? 'dir="rtl"' : ''; ?> class="e2pdf-tpl e2pdf-center" data-width="<?php echo $this->view->template->get('width'); ?>" data-height="<?php echo $this->view->template->get('height'); ?>">
                                            <div class="e2pdf-tpl-inner">
                                                <?php if ($this->get->get('action') === 'edit') { ?>
                                                    <?php $fonts = $this->view->template->get('fonts'); ?>
                                                    <?php foreach ($fonts as $font_key => $font) { ?>
                                                        <div class="e2pdf-load-font e2pdf-hide" path="<?php echo $font_key; ?>" name="<?php echo $font; ?>"></div>
                                                    <?php } ?>
                                                    <?php $pages = $this->view->template->get('pages'); ?>
                                                    <?php foreach ($pages as $page_key => $page) { ?>
                                                        <div data-page_id="<?php echo $page['page_id']; ?>" class="e2pdf-page ui-droppable e2pdf-load-page" data-width="<?php echo $page['properties']['width']; ?>" data-height="<?php echo $page['properties']['height']; ?>" style="width: <?php echo $page['properties']['width']; ?>px; height: <?php echo $page['properties']['height']; ?>px; <?php if ($this->view->template->get('pdf') && file_exists($this->helper->get('pdf_dir') . $this->view->template->get('pdf') . '/images/' . $page['page_id'] . '.png')) { ?>background-image: url('<?php echo $this->helper->get_upload_url('pdf/' . $this->view->template->get('pdf') . '/images/' . $page['page_id'] . '.png') ?>')<?php } ?>">
                                                            <div class="page-options-icons"><a href="javascript:void(0);" class="page-options-icon e2pdf-up-page e2pdf-link" <?php if ($page['page_id'] == '1' || $this->view->template->get('pdf')) { ?>disabled="disabled"<?php } ?>><i class="dashicons dashicons-arrow-up-alt2"></i></a><a href="javascript:void(0);" class="page-options-icon e2pdf-down-page e2pdf-link" <?php if ($page['page_id'] == count($pages) || $this->view->template->get('pdf')) { ?>disabled="disabled" <?php } ?>><i class="dashicons dashicons-arrow-down-alt2"></i></a><a href="javascript:void(0);" class="page-options-icon e2pdf-page-options e2pdf-modal e2pdf-link" data-modal="page-options"><i class="dashicons dashicons-admin-generic"></i></a><a href="javascript:void(0);" class="page-options-icon e2pdf-delete-page e2pdf-link"><i class="dashicons dashicons-no"></i></a></div>
                                                            <?php foreach ($page['elements'] as $key => $value) { ?>
                                                                <div class="e2pdf-load-el" data-width="<?php echo $value['width']; ?>" data-height="<?php echo $value['height']; ?>" data-top="<?php echo $value['top']; ?>"  data-left="<?php echo $value['left']; ?>" data-type="<?php echo $value['type'] ?>" data-element_id="<?php echo $value['element_id'] ?>">
                                                                    <textarea class="e2pdf-data-name"><?php echo htmlspecialchars($value['name'], ENT_QUOTES); ?></textarea>
                                                                    <textarea class="e2pdf-data-properties"><?php echo htmlspecialchars(json_encode($value['properties'], JSON_FORCE_OBJECT), ENT_QUOTES); ?></textarea>
                                                                    <textarea class="e2pdf-data-actions"><?php echo htmlspecialchars(json_encode($value['actions'], JSON_FORCE_OBJECT), ENT_QUOTES); ?></textarea>
                                                                    <textarea class="e2pdf-data-value"><?php echo htmlspecialchars($value['value'], ENT_QUOTES); ?></textarea>
                                                                </div>
                                                            <?php } ?>
                                                            <textarea class="e2pdf-data-actions e2pdf-hide"><?php echo htmlspecialchars(json_encode($page['actions'], JSON_FORCE_OBJECT), ENT_QUOTES); ?></textarea>
                                                            <textarea class="e2pdf-data-properties e2pdf-hide"><?php echo htmlspecialchars(json_encode($page['properties'], JSON_FORCE_OBJECT), ENT_QUOTES); ?></textarea>
                                                            <div class="e2pdf-guide e2pdf-guide-h"></div>
                                                            <div class="e2pdf-guide e2pdf-guide-v"></div>
                                                        </div>
                                                    <?php } ?>
                                                <?php } ?>
                                            </div>
                                            <div class="e2pdf-load-tpl">
                                                <textarea class="e2pdf-data-actions"><?php echo htmlspecialchars(json_encode($this->view->template->get('actions'), JSON_FORCE_OBJECT), ENT_QUOTES); ?></textarea>
                                                <textarea class="e2pdf-data-properties"><?php echo htmlspecialchars(json_encode($this->view->template->get('properties'), JSON_FORCE_OBJECT), ENT_QUOTES); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $this->render('blocks', 'bottom-panel'); ?>
                                </div>
                            </div>
                        </div>
                        <div id="postbox-container-1" class="postbox-container e2pdf-templates-meta-boxes">
                            <?php
                            wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
                            wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
                            ?>
                            <?php
                            do_meta_boxes(null, 'side', null);
                            ?>
                        </div>
                        <div class="clear"></div>
                    </div>
                </form>
            </div>
        <?php } elseif ($this->get->get('action') === 'import') { ?>
            <h1><?php _e('Import', 'e2pdf'); ?></h1>
            <hr class="wp-header-end">
            <?php $this->render('blocks', 'notifications'); ?>
            <div id="poststuff" class="e2pdf-view-area">
                <form enctype="multipart/form-data" method="post">
                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_templates'); ?>">
                    <ul class="e2pdf-options-list">
                        <li><div class="e2pdf-name"><?php _e('Template', 'e2pdf'); ?>:
                            </div><div class="e2pdf-value"><input name="template" type="file">
                                <div class="e2pdf-note"><?php _e('Allowed File Types', 'e2pdf'); ?>: <strong>.xml</strong></div>
                                <div class="e2pdf-note"><?php _e('Max Upload File Size', 'e2pdf'); ?>: <strong><?php echo $this->view->upload_max_filesize; ?></strong></div>
                            </div>
                        </li>
                        <?php foreach ($this->view->options as $group_key => $group) { ?>
                            <?php if (isset($group['name'])) { ?>
                                <li><h4><?php echo $group['name']; ?>:</h4></li>
                            <?php } ?>
                            <?php foreach ($group['options'] as $option_key => $option_value) { ?>
                                <li class="<?php echo isset($option_value['li']['class']) ? $option_value['li']['class'] : '' ?>" >
                                    <div class="e2pdf-name">
                                        <?php echo $option_value['name']; ?>:
                                    </div><div class="e2pdf-value">
                                        <?php
                                        if ($option_value['type'] == 'checkbox') {
                                            $this->render('field', 'checkbox', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'placeholder' => $option_value['placeholder'],
                                                    'disabled' => isset($option_value['disabled']) && $option_value['disabled'] ? 'disabled' : false,
                                                    'class' => isset($option_value['class']) ? $option_value['class'] : '',
                                                    'data-collapse' => isset($option_value['data-collapse']) ? $option_value['data-collapse'] : ''
                                                ),
                                                'value' => $option_value['value'],
                                                'checkbox_value' => 1,
                                                'default_value' => $option_value['default_value'],
                                            ));
                                        } elseif ($option_value['type'] == 'text') {
                                            $this->render('field', 'text', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'placeholder' => $option_value['placeholder'],
                                                    'class' => 'e2pdf-w100 ' . $option_value['class']
                                                ),
                                                'value' => $option_value['value'],
                                            ));
                                        } elseif ($option_value['type'] == 'textarea') {
                                            $this->render('field', 'textarea', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'style' => 'height: 100px;',
                                                    'class' => 'e2pdf-w100',
                                                    'placeholder' => $option_value['placeholder'],
                                                ),
                                                'value' => $option_value['value'],
                                            ));
                                        } elseif ($option_value['type'] == 'select') {
                                            $this->render('field', 'select', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'class' => 'e2pdf-w100'
                                                ),
                                                'value' => $option_value['value'],
                                                'options' => $option_value['options']
                                            ));
                                        } elseif ($option_value['type'] == 'radio') {
                                            $this->render('field', 'radio', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                ),
                                                'value' => $option_value['value'],
                                                'options' => $option_value['options']
                                            ));
                                        }
                                        ?>
                                    </div>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                    <p class="submit"><input type="submit" <?php if ($this->view->import_disabled) { ?>disabled="disabled"<?php } ?> name="submit" id="submit" class="button button-primary" value="<?php _e('Import', 'e2pdf'); ?>"></p>
                </form>
            </div>
        <?php } elseif ($this->get->get('action') === 'download') { ?>
            <h1><?php _e('Download', 'e2pdf'); ?></h1>
            <hr class="wp-header-end">
            <?php $this->render('blocks', 'notifications'); ?>
            <div id="poststuff" class="e2pdf-view-area">
                <form method="post">
                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf_templates'); ?>">
                    <input type="hidden" name="id" value="<?php echo $this->get->get('id'); ?>">
                    <ul class="e2pdf-options-list">
                        <li><div class="e2pdf-name"><?php _e('Extension', 'e2pdf'); ?>:
                            </div><div class="e2pdf-value">
                                <?php if ($this->view->template->extension()->method('info')) { ?>
                                    <?php echo $this->view->template->extension()->info('title'); ?>
                                <?php } ?>
                            </div>
                        </li>
                        <li><div class="e2pdf-name"><?php _e('Template', 'e2pdf'); ?>:
                            </div><div class="e2pdf-value">
                                <a target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'edit', 'id' => $this->view->template->get('ID'))); ?>"><?php echo esc_html($this->view->template->get('title')); ?></a>
                            </div>
                        </li>
                        <li><div class="e2pdf-name"><?php _e('Connection', 'e2pdf'); ?>:
                            </div><div class="e2pdf-value">
                                <?php if ($this->view->template->extension()->method('item') && $this->view->template->get('item')) { ?><a target="_blank" href="<?php echo $this->view->template->extension()->item()->url; ?>"><?php echo $this->view->template->extension()->item()->name; ?></a><?php } else { ?>—<?php } ?>
                            </div>
                        </li>
                        <?php foreach ($this->view->options as $group_key => $group) { ?>
                            <?php if (isset($group['name'])) { ?>
                                <li><h4><?php echo $group['name']; ?>:</h4></li>
                            <?php } ?>
                            <?php foreach ($group['options'] as $option_key => $option_value) { ?>
                                <li>
                                    <div class="e2pdf-name">
                                        <?php echo $option_value['name']; ?>:
                                    </div><div class="e2pdf-value">
                                        <?php
                                        if ($option_value['type'] == 'checkbox') {
                                            $this->render('field', 'checkbox', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'placeholder' => $option_value['placeholder'],
                                                    'disabled' => isset($option_value['disabled']) && $option_value['disabled'] ? 'disabled' : false
                                                ),
                                                'value' => $option_value['value'],
                                                'checkbox_value' => 1,
                                                'default_value' => $option_value['default_value'],
                                            ));
                                        } elseif ($option_value['type'] == 'text') {
                                            $this->render('field', 'text', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'placeholder' => $option_value['placeholder'],
                                                    'class' => 'e2pdf-w100 ' . $option_value['class']
                                                ),
                                                'value' => $option_value['value'],
                                            ));
                                        } elseif ($option_value['type'] == 'textarea') {
                                            $this->render('field', 'textarea', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'style' => 'height: 100px;',
                                                    'class' => 'e2pdf-w100',
                                                    'placeholder' => $option_value['placeholder'],
                                                ),
                                                'value' => $option_value['value'],
                                            ));
                                        } elseif ($option_value['type'] == 'select') {
                                            $this->render('field', 'select', array(
                                                'field' => array(
                                                    'name' => $option_value['key'],
                                                    'class' => 'e2pdf-w100'
                                                ),
                                                'value' => $option_value['value'],
                                                'options' => $option_value['options']
                                            ));
                                        }
                                        ?>
                                    </div>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                    <p class="submit"><input type="submit" <?php if ($this->view->export_disabled) { ?>disabled="disabled"<?php } ?> name="submit" id="submit" class="button button-primary" value="<?php _e('Download', 'e2pdf'); ?>"></p>
                </form>
            </div>
        <?php } ?>
    <?php } else { ?>
        <?php $this->handle_404(); ?>
    <?php } ?>
</div>
