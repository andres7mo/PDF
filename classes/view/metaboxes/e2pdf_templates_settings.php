<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}
?>
<div id="taxonomy-category" class="categorydiv e2pdf-tabs-panel">
    <ul id="e2pdf-tabs" class="category-tabs e2pdf-tabs">
        <li class="active"><a data-tab="e2pdf-template-style" href="javascript:void(0);"><?php _e('Style', 'e2pdf'); ?></a>
        </li><li>
            <a data-tab="e2pdf-template-template" href="javascript:void(0);"><?php _e('Template', 'e2pdf'); ?></a>
        </li><li>
            <a data-tab="e2pdf-template-pdf" href="javascript:void(0);">Pdf</a>
        </li><li>
            <a data-tab="e2pdf-template-meta" href="javascript:void(0);"><?php _e('Meta', 'e2pdf'); ?></a>
        </li><li>
            <a data-tab="e2pdf-template-security" href="javascript:void(0);"><?php _e('Security', 'e2pdf'); ?></a>
        </li>
        <?php do_action('e2pdf_templates_settings_tabs', $this); ?>
    </ul>
    <div class="e2pdf-rel">
        <?php if (!get_option('e2pdf_email', '') && $this->helper->get('license')->get('type') == 'FREE') { ?>
            <div class="e2pdf-email-lock e2pdf-ib">
                <div id="e2pdf-email">
                    <p class="post-attributes-label-wrapper">
                        <label><?php _e('Enter your E-mail to unlock this features', 'e2pdf'); ?>:</label>
                    </p>
                    <p class="post-attributes-label-wrapper">
                        <?php
                        $this->render('field', 'text', array(
                            'field' => array(
                                'name' => 'email',
                                'placeholder' => __('E-mail', 'e2pdf'),
                                'class' => 'e2pdf-w100 e2pdf-enter'
                            ),
                            'value' => '',
                        ));
                        ?>
                    </p>
                    <p class="post-attributes-label-wrapper">
                        <input form-id="e2pdf-email" action="e2pdf_email" type="button" class="e2pdf-submit-form button-primary button-small" value="<?php _e('Unlock', 'e2pdf'); ?>" _wpnonce="<?php echo wp_create_nonce('e2pdf_templates'); ?>">
                    </p>
                </div>
            </div>
        <?php } ?>
        <div id="e2pdf-template-style" class="tabs-panel e2pdf-rel">
            <div class="e2pdf-options-panel">
                <p class="post-attributes-label-wrapper">
                    <label><?php _e('Global Font', 'e2pdf'); ?>:</label>
                </p>
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w70 e2pdf-pr5">
                        <?php
                        $this->render('field', 'select', array(
                            'field' => array(
                                'id' => 'e2pdf-font',
                                'name' => 'font',
                                'class' => 'e2pdf-settings-style-change'
                            ),
                            'value' => $this->view->template->get('font') && array_search($this->view->template->get('font'), $this->get_fonts()) ? $this->view->template->get('font') : 'Noto Sans Regular',
                            'options' => $this->get_fonts(true),
                        ));
                        ?>
                    </div><div class="e2pdf-ib e2pdf-w30 e2pdf-pl5">
                        <?php
                        $this->render('field', 'select', array(
                            'field' => array(
                                'id' => 'e2pdf-font-size',
                                'name' => 'font_size',
                                'class' => 'e2pdf-settings-style-change'
                            ),
                            'value' => $this->view->template->get('font_size') ? $this->view->template->get('font_size') : '12',
                            'options' => $this->get_font_sizes(),
                        ));
                        ?>
                    </div>
                </div>
                <div class="e2pdf-grid">
                    <div class="e2pdf-ib e2pdf-w50 e2pdf-pr5">
                        <p class="post-attributes-label-wrapper">
                            <label><?php _e('Line Height', 'e2pdf'); ?>:</label>
                        </p>
                        <div>
                            <?php
                            $this->render('field', 'select', array(
                                'field' => array(
                                    'id' => 'e2pdf-line-height',
                                    'name' => 'line_height',
                                    'class' => 'e2pdf-settings-style-change'
                                ),
                                'value' => $this->view->template->get('line_height') ? $this->view->template->get('line_height') : '12',
                                'options' => $this->get_line_heights(),
                            ));
                            ?>
                        </div>
                    </div><div class="e2pdf-ib e2pdf-w50 e2pdf-pl5">
                        <p class="post-attributes-label-wrapper">
                            <label><?php _e('Text Align', 'e2pdf'); ?>:</label>
                        </p>
                        <div>
                            <?php
                            $this->render('field', 'select', array(
                                'field' => array(
                                    'id' => 'e2pdf-text-align',
                                    'name' => 'text_align',
                                    'class' => 'e2pdf-settings-style-change'
                                ),
                                'value' => $this->view->template->get('text_align'),
                                'options' => array(
                                    'left' => __('Left', 'e2pdf'),
                                    'center' => __('Center', 'e2pdf'),
                                    'right' => __('Right', 'e2pdf')
                                )
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                <div class="e2pdf-ib e2pdf-w100">
                    <p class="post-attributes-label-wrapper"></p>
                    <div>
                        <?php
                        $this->render('field', 'checkbox', array(
                            'field' => array(
                                'id' => 'e2pdf-rtl',
                                'name' => 'rtl',
                                'placeholder' => 'RTL',
                                'class' => 'e2pdf-settings-style-change'
                            ),
                            'value' => $this->view->template->get('rtl'),
                            'checkbox_value' => 1,
                            'default_value' => 0,
                        ));
                        ?>
                    </div>
                </div>
                <div class="e2pdf-ib e2pdf-w100">
                    <p class="post-attributes-label-wrapper">
                        <label><?php _e('Font Color', 'e2pdf'); ?>:</label>
                    </p>
                    <div class="e2pdf-colorpicker-wr">
                        <?php
                        $this->render('field', 'text', array(
                            'field' => array(
                                'id' => 'e2pdf-font-color',
                                'name' => 'font_color',
                                'class' => 'e2pdf-color-picker e2pdf-color-picker-load e2pdf-settings-style-change',
                                'data-default' => '#000000',
                            ),
                            'value' => $this->view->template->get('font_color') ? $this->view->template->get('font_color') : '#000000',
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="e2pdf-template-template" class="tabs-panel e2pdf-template-template e2pdf-rel" style="display: none;" >
            <div class="e2pdf-options-panel">
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Dataset Title', 'e2pdf'); ?>:
                    </label>
                </p>
                <div id="e2pdf-item-dataset-title" class="<?php echo $this->view->template->get('item') == '-2' ? 'e2pdf-hide' : ''; ?>">
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'dataset_title',
                            'placeholder' => __('Dataset Title', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('dataset_title'),
                    ));
                    ?>
                </div>
                <div id="e2pdf-merged-item-dataset-title" class="<?php echo $this->view->template->get('item') == '-2' ? '' : 'e2pdf-hide'; ?>">
                    <div>
                        <?php
                        $this->render('field', 'text', array(
                            'field' => array(
                                'name' => 'dataset_title1',
                                'placeholder' => __('Dataset Title', 'e2pdf') . ' #1',
                                'class' => 'e2pdf-settings-template-change'
                            ),
                            'value' => $this->view->template->get('dataset_title1'),
                        ));
                        ?>
                    </div>
                    <div class="e2pdf-mt5">
                        <?php
                        $this->render('field', 'text', array(
                            'field' => array(
                                'name' => 'dataset_title2',
                                'placeholder' => __('Dataset Title', 'e2pdf') . ' #2',
                                'class' => 'e2pdf-settings-template-change'
                            ),
                            'value' => $this->view->template->get('dataset_title2'),
                        ));
                        ?>
                    </div>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Button Title', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'button_title',
                            'placeholder' => __('Button Title', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('button_title'),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Dynamic PDF Source', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'dpdf',
                            'placeholder' => __('Dynamic PDF Source', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('dpdf'),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Format', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'name' => 'format',
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('format'),
                        'options' => array(
                            'pdf' => 'pdf',
                            'jpg' => 'jpg',
                        ),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('JPG Output Resolution', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'name' => 'resample',
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('resample'),
                        'options' => array(
                            '100' => '72dpi',
                            '125' => '90dpi',
                            '150' => '108dpi',
                            '175' => '126dpi',
                            '200' => '144dpi',
                        ),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper"></p>
                <div>
                    <?php
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'inline',
                            'placeholder' => __('Open PDF in Browser', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('inline'),
                        'checkbox_value' => 1,
                        'default_value' => 0,
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper"></p>
                <div>
                    <?php
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'auto',
                            'placeholder' => __('Auto PDF Download', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('auto'),
                        'checkbox_value' => 1,
                        'default_value' => 0,
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div id="e2pdf-template-pdf" class="tabs-panel e2pdf-template-pdf" style="display: none;" >
            <div class="e2pdf-options-panel">
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('PDF Name', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'name',
                            'placeholder' => __('PDF Name', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('name')
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('PDF Local Name', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'savename',
                            'placeholder' => __('PDF Local Name', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('savename'),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('PDF Flatten', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'name' => 'flatten',
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('flatten'),
                        'options' => array(
                            '0' => __('No', 'e2pdf'),
                            '1' => __('Yes', 'e2pdf'),
                            '2' => __('Full', 'e2pdf')
                        ),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper"></p>
                <div>
                    <?php
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'appearance',
                            'placeholder' => __('Generate appearance', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('appearance'),
                        'checkbox_value' => 1,
                        'default_value' => 0,
                    ));
                    ?>
                </div>
                <div class="e2pdf-ib e2pdf-w60 e2pdf-pr5">
                    <p class="post-attributes-label-wrapper">
                        <label>
                            <?php _e('Images', 'e2pdf'); ?>:
                        </label>
                    </p>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'id' => 'e2pdf-optimization',
                            'name' => 'optimization',
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('optimization'),
                        'options' => array(
                            '-1' => __('Not Optimized', 'e2pdf'),
                            '1' => __('Low Quality', 'e2pdf'),
                            '2' => __('Basic Quality', 'e2pdf'),
                            '3' => __('Good Quality', 'e2pdf'),
                            '4' => __('Best Quality', 'e2pdf'),
                            '5' => __('Ultra Quality', 'e2pdf'),
                        ),
                    ));
                    ?>
                </div><div class="e2pdf-ib e2pdf-w40 e2pdf-pl5">
                    <p class="post-attributes-label-wrapper">
                        <label>
                            <?php _e('Compression', 'e2pdf'); ?>:
                        </label>
                    </p>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'id' => 'e2pdf-compression',
                            'name' => 'compression',
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('compression'),
                        'options' => array(
                            '-1' => __('Default', 'e2pdf'),
                            '0' => '0',
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                            '7' => '7',
                            '8' => '8',
                            '9' => '9',
                        ),
                    ));
                    ?>
                </div>
                <div class="e2pdf-ib e2pdf-w100">
                    <p class="post-attributes-label-wrapper">
                        <label><?php _e('Font Processor', 'e2pdf'); ?>:</label>
                    </p>
                    <div>
                        <?php
                        $this->render('field', 'select', array(
                            'field' => array(
                                'id' => 'e2pdf-font-processor',
                                'name' => 'font_processor',
                                'class' => 'e2pdf-settings-template-change'
                            ),
                            'value' => $this->view->template->get('font_processor'),
                            'options' => array(
                                '0' => 'Plain Fonts',
                                '1' => 'Complex Fonts',
                            )
                        ));
                        ?>
                    </div>
                </div>
                <div>
                    <p class="post-attributes-label-wrapper">
                        <label>
                            <?php _e('PDF Tab Order', 'e2pdf'); ?>:
                        </label>
                    </p>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'name' => 'tab_order',
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('tab_order'),
                        'options' => array(
                            '0' => __('Unspecified', 'e2pdf'),
                            '1' => __('Use Row Order', 'e2pdf'),
                            '2' => __('Use Column Order', 'e2pdf'),
                            '3' => __('Use Document Structure', 'e2pdf'),
                        ),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div id="e2pdf-template-meta" class="tabs-panel e2pdf-template-meta" style="display: none;" >
            <div class="e2pdf-options-panel">
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Title', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'meta_title',
                            'placeholder' => __('Title', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('meta_title')
                    ));
                    ?>
                </div>

                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Subject', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'meta_subject',
                            'placeholder' => __('Subject', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('meta_subject')
                    ));
                    ?>
                </div>

                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Author', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'meta_author',
                            'placeholder' => __('Author', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('meta_author')
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Keywords', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'meta_keywords',
                            'placeholder' => __('Keywords', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('meta_keywords')
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Lang Code', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'select', array(
                        'field' => array(
                            'name' => 'lang_code',
                            'class' => 'e2pdf-settings-style-change'
                        ),
                        'value' => $this->view->template->get('lang_code'),
                        'options' => $this->get_lang_codes(),
                    ));
                    ?>
                </div>


            </div>
        </div>
        <div id="e2pdf-template-security" class="tabs-panel e2pdf-template-security" style="display: none;" >
            <div class="e2pdf-options-panel">
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('PDF Open Password', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'password',
                            'placeholder' => __('PDF Open Password', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('password'),
                    ));
                    ?>
                </div>

                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Permissions Owner Password', 'e2pdf'); ?>:
                    </label>

                </p>
                <div>
                    <?php
                    $this->render('field', 'text', array(
                        'field' => array(
                            'name' => 'owner_password',
                            'placeholder' => __('Permissions Owner Password', 'e2pdf'),
                            'class' => 'e2pdf-settings-template-change'
                        ),
                        'value' => $this->view->template->get('owner_password'),
                    ));
                    ?>
                </div>
                <p class="post-attributes-label-wrapper">
                    <label>
                        <?php _e('Permissions (Opened with PDF Open Password)', 'e2pdf'); ?>:
                    </label>
                </p>
                <div>
                    <?php
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Printing', 'e2pdf')
                        ),
                        'value' => in_array('printing', $this->view->template->get('permissions')) ? 'printing' : '',
                        'checkbox_value' => 'printing',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Printing (Degraded)', 'e2pdf')
                        ),
                        'value' => in_array('degraded_printing', $this->view->template->get('permissions')) ? 'degraded_printing' : '',
                        'checkbox_value' => 'degraded_printing',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Editing', 'e2pdf')
                        ),
                        'value' => in_array('modify_contents', $this->view->template->get('permissions')) ? 'modify_contents' : '',
                        'checkbox_value' => 'modify_contents',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Copying', 'e2pdf')
                        ),
                        'value' => in_array('copy', $this->view->template->get('permissions')) ? 'copy' : '',
                        'checkbox_value' => 'copy',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Commenting', 'e2pdf')
                        ),
                        'value' => in_array('modify_annotations', $this->view->template->get('permissions')) ? 'modify_annotations' : '',
                        'checkbox_value' => 'modify_annotations',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Fill-In or Signing', 'e2pdf')
                        ),
                        'value' => in_array('fill_in', $this->view->template->get('permissions')) ? 'fill_in' : '',
                        'checkbox_value' => 'fill_in',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Document Assembly', 'e2pdf')
                        ),
                        'value' => in_array('assembly', $this->view->template->get('permissions')) ? 'assembly' : '',
                        'checkbox_value' => 'assembly',
                    ));
                    $this->render('field', 'checkbox', array(
                        'field' => array(
                            'name' => 'permissions[]',
                            'class' => 'e2pdf-settings-template-change',
                            'placeholder' => __('Screen Reader Accessibility', 'e2pdf')
                        ),
                        'value' => in_array('screenreaders', $this->view->template->get('permissions')) ? 'screenreaders' : '',
                        'checkbox_value' => 'screenreaders',
                    ));
                    ?>
                </div>

            </div>
        </div>
        <?php do_action('e2pdf_templates_settings_tabs_content', $this); ?>
    </div>
</div>
