<?php foreach ($this->tpl_args->get('groups') as $group_key => $group) { ?>
    <?php foreach ($group['options'] as $option_key => $option_value) { ?>
        <?php if (isset($option_value['header'])) { ?>
            <li><h4><?php echo $option_value['header']; ?></h4></li>
        <?php } ?>
        <?php if ($option_value['type'] != 'hidden') { ?>
            <li>
                <div class="e2pdf-name">
                    <?php
                    if ($option_value['name']) {
                        echo $option_value['name'] . ":";
                    }
                    ?>
                </div><div class="e2pdf-value <?php echo $option_value['type'] == 'checkbox_list' ? 'checkbox_list' : '' ?>">
                    <?php
                    if ($option_value['type'] == 'checkbox') {
                        $this->render('field', 'checkbox', array(
                            'field' => array(
                                'name' => $option_value['key'],
                                'placeholder' => isset($option_value['placeholder']) ? $option_value['placeholder'] : ''
                            ),
                            'value' => isset($option_value['value']) ? $option_value['value'] : '',
                            'checkbox_value' => isset($option_value['checkbox_value']) ? $option_value['checkbox_value'] : '',
                            'default_value' => isset($option_value['default_value']) ? $option_value['default_value'] : '',
                        ));
                    } elseif ($option_value['type'] == 'text') {
                        $this->render('field', 'text', array(
                            'field' => array(
                                'name' => isset($option_value['key']) ? $option_value['key'] : '',
                                'placeholder' => isset($option_value['placeholder']) ? $option_value['placeholder'] : '',
                                'class' => isset($option_value['class']) ? 'e2pdf-w100 ' . $option_value['class'] : 'e2pdf-w100',
                                'readonly' => isset($option_value['readonly']) ? $option_value['readonly'] : false,
                            ),
                            'value' => $option_value['value'],
                            'prefield' => isset($option_value['prefield']) ? $option_value['prefield'] : '',
                        ));
                    } elseif ($option_value['type'] == 'textarea') {
                        $this->render('field', 'textarea', array(
                            'field' => array(
                                'name' => isset($option_value['key']) ? $option_value['key'] : '',
                                'style' => 'height: 100px;',
                                'class' => 'e2pdf-w100',
                                'placeholder' => isset($option_value['placeholder']) ? $option_value['placeholder'] : '',
                            ),
                            'value' => isset($option_value['value']) ? $option_value['value'] : '',
                        ));
                    } elseif ($option_value['type'] == 'select') {
                        $this->render('field', 'select', array(
                            'field' => array(
                                'name' => isset($option_value['key']) ? $option_value['key'] : '',
                                'class' => 'e2pdf-w100'
                            ),
                            'value' => isset($option_value['value']) ? $option_value['value'] : '',
                            'options' => isset($option_value['options']) ? $option_value['options'] : ''
                        ));
                    } elseif ($option_value['type'] == 'checkbox_list') {
                        $this->render('field', 'hidden', array(
                            'field' => array(
                                'name' => $option_value['key'],
                            ),
                            'value' => '',
                        ));

                        foreach ($option_value['options'] as $sub_option_key => $sub_option_value) {
                            if ($sub_option_value['type'] == 'checkbox') {
                                $this->render('field', 'checkbox', array(
                                    'field' => array(
                                        'class' => isset($sub_option_value['class']) ? $sub_option_value['class'] : '',
                                        'name' => $sub_option_value['key'],
                                        'placeholder' => isset($sub_option_value['placeholder']) ? $sub_option_value['placeholder'] : ''
                                    ),
                                    'value' => isset($sub_option_value['value']) ? $sub_option_value['value'] : '',
                                    'checkbox_value' => isset($sub_option_value['checkbox_value']) ? $sub_option_value['checkbox_value'] : '',
                                    'default_value' => isset($sub_option_value['default_value']) ? $sub_option_value['default_value'] : '',
                                ));
                            }
                        }
                    }
                    ?>
                    <?php if (isset($option_value['description'])) { ?>
                        <div class="e2pdf-small">* <?php echo $option_value['description']; ?></div>
                    <?php } ?>
                </div>
            </li>
        <?php } ?>
    <?php } ?>
<?php } ?>
