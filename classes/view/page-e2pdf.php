<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div class="wrap">
    <h1><?php _e('Create PDF', 'e2pdf'); ?></h1>
    <hr class="wp-header-end">
    <?php $this->render('blocks', 'notifications'); ?>
    <h3 class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf')); ?>" class="nav-tab <?php if (!($this->get->get('action'))) { ?>nav-tab-active<?php } ?>"><?php echo _e('Create PDF', 'e2pdf'); ?></a>
        <a href="<?php echo $this->helper->get_url(array('page' => 'e2pdf', 'action' => 'bulk')); ?>" class="nav-tab <?php if ($this->get->get('action') == 'bulk') { ?>nav-tab-active<?php } ?>"><?php _e('Create PDFs in Bulk', 'e2pdf'); ?></a>
    </h3>

    <?php if (!$this->get->get('action')) { ?>
        <div class="wrap e2pdf-view-area e2pdf-rel">
            <form id="e2pdf-export-form" method="post" target="_blank" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf', 'action' => 'export')); ?>" class="e2pdf-export-form">
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10 e2pdf-inline-radio e2pdf-align-right e2pdf-export-disposition">
                        <?php
                        $this->render('field', 'radio', array(
                            'field' => array(
                                'name' => 'disposition',
                            ),
                            'value' => 'inline',
                            'options' => array(
                                'attachment' => __('Download', 'e2pdf'),
                                'inline' => __('View', 'e2pdf')
                            )
                        ));
                        ?>
                    </div>
                </div>
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                        <?php _e('Template', 'e2pdf'); ?>:
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-ib e2pdf-w100 e2pdf-select2-wrapper">
                            <?php
                            $this->render('field', 'select', array(
                                'field' => array(
                                    'name' => 'id',
                                    'class' => 'e2pdf-export-template e2pdf-w100',
                                    '_wpnonce' => wp_create_nonce('e2pdf'),
                                ),
                                'value' => '0',
                                'options' => $this->controller->get_active_templates(),
                            ));
                            $this->render('field', 'text', array(
                                'field' => array(
                                    'class' => 'e2pdf-w100 e2pdf-select2',
                                    'placeholder' => __('--- Select ---', 'e2pdf'),
                                ),
                            ));
                            ?>
                            <div class="e2pdf-ib e2pdf-w100 e2pdf-align-right e2pdf-export-template-actions"></div>
                        </div>
                    </div>
                </div>
                <div class="e2pdf-grid e2pdf-export-item e2pdf-hide">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                        <?php _e('Dataset', 'e2pdf'); ?>:
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-ib e2pdf-w100">
                            <div class="e2pdf-ib e2pdf-w100 e2pdf-select2-wrapper e2pdf-dataset-wrapper">
                                <?php
                                $this->render('field', 'select', array(
                                    'field' => array(
                                        'name' => 'dataset',
                                        'class' => 'e2pdf-export-dataset e2pdf-w100',
                                        '_wpnonce' => wp_create_nonce('e2pdf'),
                                    ),
                                    'value' => '0',
                                    'options' => array(
                                        '' => __('--- Select ---', 'e2pdf')
                                    ),
                                ));
                                ?>
                                <?php
                                $this->render('field', 'text', array(
                                    'field' => array(
                                        'disabled' => 'disabled',
                                        'class' => 'e2pdf-w100 e2pdf-select2',
                                        'placeholder' => __('--- Select ---', 'e2pdf'),
                                    ),
                                ));
                                ?>
                                <a href="javascript:void(0);" class="e2pdf-link e2pdf-datasets-refresh" _wpnonce="<?php echo esc_attr(wp_create_nonce('e2pdf')); ?>"><i class="dashicons dashicons-update"></i></a>
                                <div class="e2pdf-ib e2pdf-w100 e2pdf-export-dataset-actions"></div>
                            </div>
                        </div>
                    </div>
                    <div class="e2pdf-ib e2pdf-w100 e2pdf-export-item-actions"></div>
                </div>
                <div class="e2pdf-grid e2pdf-export-item e2pdf-hide">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                        <?php _e('Dataset2', 'e2pdf'); ?>:
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-ib e2pdf-w100">
                            <div class="e2pdf-ib e2pdf-w100 e2pdf-select2-wrapper e2pdf-dataset-wrapper">
                                <?php
                                $this->render('field', 'select', array(
                                    'field' => array(
                                        'name' => 'dataset2',
                                        'class' => 'e2pdf-export-dataset e2pdf-w100',
                                        '_wpnonce' => wp_create_nonce('e2pdf'),
                                    ),
                                    'value' => '0',
                                    'options' => array(
                                        '' => __('--- Select ---', 'e2pdf')
                                    ),
                                ));
                                $this->render('field', 'text', array(
                                    'field' => array(
                                        'disabled' => 'disabled',
                                        'class' => 'e2pdf-w100 e2pdf-select2',
                                        'placeholder' => __('--- Select ---', 'e2pdf'),
                                    ),
                                ));
                                ?>
                                <a href="javascript:void(0);" class="e2pdf-link e2pdf-datasets-refresh" _wpnonce="<?php echo esc_attr(wp_create_nonce('e2pdf')); ?>"><i class="dashicons dashicons-update"></i></a>
                                <div class="e2pdf-ib e2pdf-w100 e2pdf-export-dataset-actions"></div>
                            </div>
                        </div>
                    </div>
                    <div class="e2pdf-ib e2pdf-w100 e2pdf-export-item-actions"></div>
                </div>
                <div class="e2pdf-export-options e2pdf-grid e2pdf-hide">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-grid">
                            <div class="e2pdf-grid e2pdf-export-shortcodes">
                                <div class='e2pdf-ib e2pdf-w100'>
                                    <h4 class="e2pdf-center"><?php _e('Shortcodes', 'e2pdf'); ?></h4>
                                </div>
                                <div class='e2pdf-ib e2pdf-w100'>
                                    <div class="e2pdf-w100 e2pdf-center"><?php _e("Shortcode for a Download Link with a Dynamic Dataset", 'e2pdf'); ?></div>
                                    <input readonly="readonly" class="e2pdf-template-shortcode e2pdf-center e2pdf-w100" type="text" value=''>
                                </div>
                                <div class='e2pdf-dataset-shortcode-wr e2pdf-ib e2pdf-w100 e2pdf-hide'>
                                    <div class="e2pdf-w100 e2pdf-center"><?php _e("Shortcode for a Download Link with the Current Dataset", 'e2pdf'); ?></div>
                                    <input readonly="readonly" name="e2pdf-dataset-shortcode" class="e2pdf-dataset-shortcode e2pdf-center e2pdf-w100" type="text" value=''>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('PDF Name', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[name]',
                                            'placeholder' => __('PDF Name', 'e2pdf'),
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '',
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('PDF Open Password', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[password]',
                                            'placeholder' => __('Password', 'e2pdf'),
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '',
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('User', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'select', array(
                                        'field' => array(
                                            'name' => 'options[user_id]',
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '0',
                                        'options' => $this->view->users,
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('Flatten', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'select', array(
                                        'field' => array(
                                            'name' => 'options[flatten]',
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '0',
                                        'options' => array(
                                            '0' => __('No', 'e2pdf'),
                                            '1' => __('Yes', 'e2pdf'),
                                            '2' => __('Full', 'e2pdf'),
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('Format', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'select', array(
                                        'field' => array(
                                            'name' => 'options[format]',
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '0',
                                        'options' => array(
                                            'pdf' => 'pdf',
                                            'jpg' => 'jpg',
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('Arguments', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[args]',
                                            'class' => 'e2pdf-hide e2pdf-w100 e2pdf-export-option'
                                        ),
                                    ));
                                    ?>
                                    <div class="e2pdf-ib e2pdf-w100 e2pdf-align-right e2pdf-mb10">
                                        <a href="javascript:void(0);" class="e2pdf-link e2pdf-argument-add"><i class="dashicons dashicons-plus"></i></a> <a href="javascript:void(0);" class="e2pdf-link e2pdf-argument-delete"><i class="dashicons dashicons-minus"></i></a>
                                    </div>
                                    <div class="e2pdf-arguments">
                                        <div class="e2pdf-mb10 e2pdf-argument">
                                            <input type="text" class="e2pdf-w100" name="options[args][]" placeholder="arg1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="e2pdf-center">
                    <input type="submit" form-id="e2pdf-export-form" disabled="disabled" class="e2pdf-export-form-submit button-primary button-large" value="<?php _e('Create PDF', 'e2pdf'); ?>">
                </div>
            </form>
        </div>
    <?php } elseif ($this->get->get('action') == 'bulk') { ?>
        <div class="wrap e2pdf-view-area e2pdf-rel">
            <form id="e2pdf-export-form" method="post" action="<?php echo $this->helper->get_url(array('page' => 'e2pdf', 'action' => 'bulk')); ?>" class="e2pdf-export-form">
                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('e2pdf'); ?>">
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                        <?php _e('Template', 'e2pdf'); ?>:
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-ib e2pdf-w100 e2pdf-select2-wrapper">
                            <?php
                            $this->render('field', 'select', array(
                                'field' => array(
                                    'name' => 'id',
                                    'class' => 'e2pdf-export-template e2pdf-w100',
                                    '_wpnonce' => wp_create_nonce('e2pdf'),
                                ),
                                'value' => '0',
                                'options' => $this->controller->get_active_templates(),
                            ));
                            $this->render('field', 'text', array(
                                'field' => array(
                                    'class' => 'e2pdf-w100 e2pdf-select2',
                                    'placeholder' => __('--- Select ---', 'e2pdf'),
                                ),
                            ));
                            ?>
                            <div class="e2pdf-ib e2pdf-w100 e2pdf-align-right e2pdf-export-template-actions"></div>
                        </div>
                    </div>
                </div>
                <div class="e2pdf-grid e2pdf-export-item e2pdf-hide">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                        <?php _e('Dataset', 'e2pdf'); ?>:
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-ib e2pdf-w100 e2pdf-mb20">
                            <div class="e2pdf-ib e2pdf-w70 e2pdf-pr5"></div><div class="e2pdf-ib e2pdf-w30 e2pdf-pl5">
                                <?php
                                $this->render('field', 'text', array(
                                    'field' => array(
                                        'name' => 'search',
                                        'class' => 'e2pdf-w100 e2pdf-export-dataset-search',
                                        'field' => 'dataset',
                                        'placeholder' => __('Filter...', 'e2pdf'),
                                    ),
                                ));
                                ?>
                            </div>
                        </div>
                        <div class="e2pdf-ib e2pdf-w100">
                            <div class="e2pdf-ib e2pdf-w100">
                                <?php
                                $this->render('field', 'fieldset', array(
                                    'field' => array(
                                        'name' => 'dataset',
                                        'class' => 'e2pdf-export-dataset e2pdf-w100'
                                    ),
                                    'value' => '0',
                                    'options' => array(
                                        '' => __('--- Select ---', 'e2pdf')
                                    ),
                                ));
                                ?>
                            </div>
                            <div class="e2pdf-ib e2pdf-w100 e2pdf-export-dataset-actions"></div>
                        </div>
                    </div>
                    <div class="e2pdf-ib e2pdf-w100 e2pdf-export-item-actions"></div>
                </div>
                <div class="e2pdf-export-options e2pdf-grid e2pdf-hide">
                    <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                    </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                        <div class="e2pdf-grid">
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('PDF Name', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[name]',
                                            'placeholder' => __('PDF Name', 'e2pdf'),
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '',
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('PDF Local Name', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[savename]',
                                            'placeholder' => __('PDF Local Name', 'e2pdf'),
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '',
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('PDF Open Password', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[password]',
                                            'placeholder' => __('Password', 'e2pdf'),
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '',
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('User', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'select', array(
                                        'field' => array(
                                            'name' => 'options[user_id]',
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '0',
                                        'options' => $this->view->users,
                                    ));
                                    ?>
                                </div>
                            </div>

                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('Flatten', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'select', array(
                                        'field' => array(
                                            'name' => 'options[flatten]',
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '0',
                                        'options' => array(
                                            '0' => __('No', 'e2pdf'),
                                            '1' => __('Yes', 'e2pdf'),
                                            '2' => __('Full', 'e2pdf'),
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('Format', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'select', array(
                                        'field' => array(
                                            'name' => 'options[format]',
                                            'class' => 'e2pdf-w100 e2pdf-export-option'
                                        ),
                                        'value' => '0',
                                        'options' => array(
                                            'pdf' => 'pdf',
                                            'jpg' => 'jpg',
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="e2pdf-grid">
                                <div class="e2pdf-ib e2pdf-w30 e2pdf-pr10">
                                    <?php _e('Arguments', 'e2pdf'); ?>:
                                </div><div class="e2pdf-ib e2pdf-w70 e2pdf-pl10">
                                    <?php
                                    $this->render('field', 'text', array(
                                        'field' => array(
                                            'name' => 'options[args]',
                                            'class' => 'e2pdf-hide e2pdf-w100 e2pdf-export-option'
                                        ),
                                    ));
                                    ?>
                                    <div class="e2pdf-ib e2pdf-w100 e2pdf-align-right e2pdf-mb10">
                                        <a href="javascript:void(0);" class="e2pdf-link e2pdf-argument-add"><i class="dashicons dashicons-plus"></i></a> <a href="javascript:void(0);" class="e2pdf-link e2pdf-argument-delete"><i class="dashicons dashicons-minus"></i></a>
                                    </div>
                                    <div class="e2pdf-arguments">
                                        <div class="e2pdf-mb10 e2pdf-argument">
                                            <input type="text" class="e2pdf-w100" name="options[args][]" placeholder="arg1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="e2pdf-center">
                    <input type="button" form-id="e2pdf-export-form"  action="e2pdf_bulk_create" disabled="disabled" class="e2pdf-submit-form e2pdf-export-form-submit button-primary button-large" value="<?php _e('Create PDFs in Bulk', 'e2pdf'); ?>" _wpnonce="<?php echo wp_create_nonce('e2pdf'); ?>">
                </div>
            </form>
        </div>

        <?php if ($this->controller->get_bulks_list()) { ?>
            <div class="e2pdf-ib wrap e2pdf-view-area e2pdf-rel e2pdf-bulks-list">
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w10">ID
                    </div><div class="e2pdf-ib e2pdf-w50">
                        <?php _e("Template", 'e2pdf'); ?></div><div class="e2pdf-ib e2pdf-w20">
                        <?php _e("Progress", 'e2pdf'); ?></div><div class="e2pdf-ib e2pdf-w10">
                        <?php _e("Actions", 'e2pdf'); ?></div><div class="e2pdf-ib e2pdf-w10">
                        <?php _e("Created", 'e2pdf'); ?></div>
                </div>
                <?php foreach ($this->controller->get_bulks_list() as $key => $bulk) { ?>
                    <div class="e2pdf-grid e2pdf-mt10 e2pdf-bulk" bulk="<?php echo $bulk->get('ID'); ?>" status="<?php echo $bulk->get('status'); ?>">
                        <div class="e2pdf-ib e2pdf-w10"><?php echo $bulk->get('ID'); ?>
                        </div><div class="e2pdf-ib e2pdf-w50">
                            <?php if ($bulk->get('template')) { ?>
                                <a target="_blank" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf-templates', 'action' => 'edit', 'id' => $bulk->get('template')->get('ID'))); ?>"><?php echo esc_html($bulk->get('template')->get('title')); ?></a></strong>
                            <?php } else { ?>
                                <?php echo $bulk->get('template_id'); ?>
                            <?php } ?>
                        </div><div class="e2pdf-ib e2pdf-w20">
                            <span class="e2pdf-bulk-count"><?php echo $bulk->get('count'); ?></span>/<?php echo $bulk->get('total'); ?></div><div class="e2pdf-ib e2pdf-w10">
                            <?php if ($bulk->get('status') == 'completed') { ?>
                                <a class="e2pdf-link" href="<?php echo $this->helper->get_url(array('page' => 'e2pdf', 'action' => 'bulk', 'uid' => $bulk->get('uid'))); ?>"><i class="dashicons dashicons-download"></i></a>
                            <?php } ?>
                            <?php if ($bulk->get('status') == 'stop') { ?>
                                <a class="e2pdf-link e2pdf-bulk-action" action="start" bulk="<?php echo $bulk->get('ID'); ?>" href="javascript:void(0)" _wpnonce="<?php echo wp_create_nonce('e2pdf'); ?>"><i class="dashicons dashicons-controls-play"></i></a>
                            <?php } elseif ($bulk->get('status') != 'completed') { ?>
                                <a class="e2pdf-link e2pdf-bulk-action" action="stop" bulk="<?php echo $bulk->get('ID'); ?>" href="javascript:void(0)" _wpnonce="<?php echo wp_create_nonce('e2pdf'); ?>"><i class="dashicons dashicons-controls-pause"></i></a> 
                            <?php } ?>
                            <a class="e2pdf-link e2pdf-bulk-action" action="delete" bulk="<?php echo $bulk->get('ID'); ?>" href="javascript:void(0)" _wpnonce="<?php echo wp_create_nonce('e2pdf'); ?>"><i class="dashicons dashicons-no"></i></a> 
                        </div><div class="e2pdf-ib e2pdf-w10">
                            <?php echo get_date_from_gmt($bulk->get('created_at')); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    <?php } ?>

</div>
<?php $this->render('blocks', 'debug-panel'); ?>



