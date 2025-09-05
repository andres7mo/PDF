var e2pdf = {
    // e2pdf.helper
    helper: {
        // e2pdf.helper.color
        color: {
            close: function (el) {
                var color_panel = jQuery(el).parent();
                color_panel.find('.wp-color-result').click();
            }
        },
        // e2pdf.helper.font
        font: {
            fonts: function (field = '') {
                var options = [];
                jQuery('.e2pdf-wysiwyg-font').find('option').each(function () {
                    var option = {};
                    option[jQuery(this).attr('value')] = jQuery(this).html();
                    options.push(option);
                });
                return options;
            },
            sizes: function (field = '', properties = []) {
                var options = [];
                jQuery('.e2pdf-wysiwyg-fontsize').find('option').each(function () {
                    var option = {};
                    option[jQuery(this).attr('value')] = jQuery(this).html();
                    options.push(option);
                    if (jQuery(this).attr('value') === '' && properties.hasOwnProperty('text_font_size') && properties['text_font_size'] == '-1') {
                        option = {};
                        option['-1'] = e2pdf.lang.get('Auto');
                        options.push(option);
                    }
                });
                return options;
            },
            lines: function (field = '') {
                var options = [];
                var option = {};
                option[''] = '-';
                options.push(option);
                jQuery('#e2pdf-line-height').find('option').each(function () {
                    var option = {};
                    option[jQuery(this).attr('value')] = jQuery(this).html();
                    options.push(option);
                });
                return options;
            }
        },
        // e2pdf.helper.image
        image: {
            // e2pdf.helper.image.load
            load: function (el) {
                el.addClass('e2pdf-loader');
                var properties = e2pdf.properties.get(el);
                var value = e2pdf.helper.getString(properties['value']);
                var image = new Image();
                switch (el.data('data-type')) {
                    case 'e2pdf-qrcode':
                        value = e2pdf.url.pluginsUrl() + '/img/qrcode.svg';
                        break;
                    case 'e2pdf-barcode':
                        value = e2pdf.url.pluginsUrl() + '/img/barcode.svg';
                        break;
                    case 'e2pdf-graph':
                        value = e2pdf.url.pluginsUrl() + '/img/graph.svg';
                        break;
                    case 'e2pdf-signature':
                        if (typeof value === 'string' && (value.trim().startsWith("https://") || !value.trim().startsWith("http://"))) {
                        } else {
                            value = e2pdf.url.pluginsUrl() + '/img/signature.svg';
                        }
                        break;
                    case 'e2pdf-image':
                        if (typeof value === 'string' && (value.trim().startsWith("https://") || value.trim().startsWith("http://"))) {
                        } else {
                            value = e2pdf.url.pluginsUrl() + '/img/upload.svg';
                        }
                        break;
                }

                var children = e2pdf.element.children(el);
                children.attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
                image.onload = function ()
                {
                    el.removeClass('e2pdf-loader');
                    switch (el.data('data-type')) {
                        case 'e2pdf-qrcode':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/qrcode.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", true).data('uiResizable')._aspectRatio = true;
                            e2pdf.helper.image.aspectRatio(el, this, properties);
                            break;
                        case 'e2pdf-barcode':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/barcode.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            break;
                        case 'e2pdf-graph':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/graph.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            break;
                        case 'e2pdf-image':
                        case 'e2pdf-signature':
                            el.removeClass('e2pdf-aspect-ratio e2pdf-align-left e2pdf-align-center e2pdf-align-bottom e2pdf-valign-left e2pdf-valign-middle e2pdf-valign-bottom');
                            if (value === e2pdf.url.pluginsUrl() + '/img/upload.svg') {
                                children.attr('src', e2pdf.url.pluginsUrl() + '/img/upload.svg').addClass('e2pdf-image-blank');
                                el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            } else if (value === e2pdf.url.pluginsUrl() + '/img/signature.svg') {
                                children.attr('src', e2pdf.url.pluginsUrl() + '/img/signature.svg').addClass('e2pdf-image-blank');
                                el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            } else {
                                children.attr('src', value);
                                if (e2pdf.helper.getCheckbox(properties['dimension']) == '1') {
                                    el.addClass('e2pdf-aspect-ratio');
                                    switch (e2pdf.helper.getString(properties['horizontal'])) {
                                        case 'center':
                                            el.addClass('e2pdf-align-center');
                                            break;
                                        case 'right':
                                            el.addClass('e2pdf-align-right');
                                            break;
                                        default:
                                            el.addClass('e2pdf-align-left');
                                            break;
                                    }
                                    switch (e2pdf.helper.getString(properties['vertical'])) {
                                        case 'top':
                                            el.addClass('e2pdf-valign-top');
                                            break;
                                        case 'middle':
                                            el.addClass('e2pdf-valign-middle');
                                            break;
                                        default:
                                            el.addClass('e2pdf-valign-bottom');
                                            break;
                                    }
                                    if (e2pdf.helper.getCheckbox(properties['block_dimension']) == '1') {
                                        e2pdf.helper.image.aspectRatio(el, this, properties);
                                        el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = true;
                                    } else {
                                        el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                                    }

                                } else {
                                    el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                                }
                                children.removeClass('e2pdf-image-blank');
                            }
                            break;
                    }
                };
                image.onerror = function ()
                {
                    el.removeClass('e2pdf-loader');
                    switch (el.data('data-type')) {
                        case 'e2pdf-qrcode':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/qrcode.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", true).data('uiResizable')._aspectRatio = true;
                            break;
                        case 'e2pdf-barcode':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/barcode.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            break;
                        case 'e2pdf-graph':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/graph.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            break;
                        case 'e2pdf-signature':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/signature.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            break;
                        case 'e2pdf-image':
                            children.attr('src', e2pdf.url.pluginsUrl() + '/img/upload.svg').addClass('e2pdf-image-blank');
                            el.resizable("option", "aspectRatio", false).data('uiResizable')._aspectRatio = false;
                            break;
                    }
                };
                image.src = value;
            },
            // e2pdf.helper.image.aspectRatio
            aspectRatio: function (el, img, properties) {

                var padding_top = e2pdf.helper.getFloat(el.css('padding-top'));
                var padding_left = e2pdf.helper.getFloat(el.css('padding-left'));
                var padding_right = e2pdf.helper.getFloat(el.css('padding-right'));
                var padding_bottom = e2pdf.helper.getFloat(el.css('padding-bottom'));
                var border_top = e2pdf.helper.getFloat(el.css('border-top-width'));
                var border_left = e2pdf.helper.getFloat(el.css('border-left-width'));
                var border_right = e2pdf.helper.getFloat(el.css('border-right-width'));
                var border_bottom = e2pdf.helper.getFloat(el.css('border-bottom-width'));
                var maxWidth = el.width();
                var maxHeight = el.height();
                var ratio = 0;
                var width = img.naturalWidth * 100000;
                var height = img.naturalHeight * 100000;
                if (width > maxWidth) {
                    ratio = maxWidth / width;
                    height = height * ratio;
                    width = width * ratio;
                }
                if (height > maxHeight) {
                    ratio = maxHeight / height;
                    width = width * ratio;
                    height = height * ratio;
                }
                switch (e2pdf.helper.getString(properties['vertical'])) {
                    case 'middle':
                        if (el.height() > height) {
                            var top = Math.max(0, (((e2pdf.helper.getFloat(el.css('top'))) + (el.height()) / 2)) - (height / 2));
                            e2pdf.properties.set(el, 'top', top);
                            el.css('top', top);
                        }
                        break;
                    case 'bottom':
                        if (el.height() > height) {
                            var top = Math.max(0, e2pdf.helper.getFloat(el.css('top')) + (el.height() - height));
                            e2pdf.properties.set(el, 'top', top);
                            el.css('top', top);
                        }
                        break;
                }
                switch (e2pdf.helper.getString(properties['horizontal'])) {
                    case  'center':
                        if (el.width() > width) {
                            var left = Math.max(0, (((e2pdf.helper.getFloat(el.css('left'))) + (el.width()) / 2)) - (width / 2));
                            e2pdf.properties.set(el, 'left', left);
                            el.css('left', left);
                        }
                        break;
                    case 'right':
                        if (el.width() > width) {
                            var left = Math.max(0, e2pdf.helper.getFloat(el.css('left')) + (el.width() - width));
                            e2pdf.properties.set(el, 'left', left);
                            el.css('left', left);
                        }
                        break;
                }
                e2pdf.properties.set(el, 'width', width + border_left + border_right + padding_left + padding_right);
                e2pdf.properties.set(el, 'height', height + border_top + border_bottom + padding_top + padding_bottom);
                el.width(width);
                el.height(height);
            }
        },
        getFloat: function (value, def = 0, units = '') {
            const val = parseFloat(value);
            if (units) {
                return isNaN(val) ? def : val + units;
            } else {
                return isNaN(val) ? def : val;
        }
        },
        getInt: function (value, def = 0, units = '') {
            const val = parseInt(value);
            if (units) {
                return isNaN(val) ? def : val + units;
            } else {
                return isNaN(val) ? def : val;
        }

        },
        getString: function (value, def = '', units = '') {
            const val = value;
            if (units) {
                return typeof val === 'undefined' ? def : val + units;
            } else {
                return typeof val === 'undefined' ? def : val;
        }
        },
        getCheckbox: function (value, def = '', units = '') {
            const val = value;
            if (units) {
                return typeof val === 'undefined' || val != '1' ? def : val + units;
            } else {
                return typeof val === 'undefined' || val != '1' ? def : val;
        }
        },
        // e2pdf.helper.toHtml
        toHtml: function (value) {
            var div = document.createElement('div');
            div.innerHTML = value;
            return (div.innerHTML);
        },
        stripHTML: function (html, ...args) {
            return html.replace(/<(\/?)(\w+)[^>]*\/?>/g, (_, endMark, tag) => {
                return args.includes(tag) ? '<' + endMark + tag + '>' : '';
            }).replace(/<!--.*?-->/g, '').replace(/<!--\[if(.|\n)*?<!\[endif\]-->/g, '').replace(/\s*style=(["'])(.*?)\1/gmi, '').replace(/([ \t]*\n){3,}/g, "\n\n");
        },
        // e2pdf.helper.sizeToFloat
        sizeToFloat: function (value, width) {
            if (typeof value === 'string' && value.search("%") > 0) {
                return parseFloat(width) * (parseFloat(value.replace('%', '')) / 100);
            } else {
                return parseFloat(value);
            }
        },
        // e2pdf.helper.ajaxurl
        ajaxurl: function (action, _wpnonce) {
            if (action) {
                var url = ajaxurl + '?action=' + action + '&e2pdf_check=true&_wpnonce=' + _wpnonce;
            } else {
                var url = ajaxurl + '?e2pdf_check=true&_wpnonce=' + _wpnonce;
            }
            return url;
        },
        // e2pdf.helper.css
        css: function (element_id, css) {
            var css = css.replace(/([^{}]+?{[^}]+?})/g, function (match) {
                return '.e2pdf-element[data-element_id="' + element_id + '"] .e2pdf-html ' + match;
            });
            var style = jQuery('#e2pdf-html-css-' + element_id);
            if (style.length !== 0) {
                style.remove();
            }
            if (css) {
                jQuery('<style>', {id: 'e2pdf-html-css-' + element_id}).html(css).appendTo('head');
            }
        },
        // e2pdf.helper.cssGlobal
        cssGlobal: function (css) {
            var css = css.replace(/([^{}]+?{[^}]+?})/g, function (match) {
                return '.e2pdf-element .e2pdf-html ' + match;
            });
            var style = jQuery('#e2pdf-html-css');
            if (style.length !== 0) {
                style.remove();
            }
            if (css) {
                jQuery('<style>', {id: 'e2pdf-html-css'}).html(css).appendTo('head');
            }
        }
    },
    // e2pdf.select2
    select2: {
        // e2pdf.select2.init
        init: function (el) {
            el.val('');
            var val = el.closest('.e2pdf-select2-wrapper').find('select').find('option:selected').val();
            var placeholder = e2pdf.lang.get('--- Select ---');
            if (val) {
                placeholder = el.closest('.e2pdf-select2-wrapper').find('select').find('option:selected').text();
            }
            el.attr('placeholder', placeholder);
            var select2 = document.createElement('div');
            select2.className = 'e2pdf-select2-dropdown';
            var wrapper = el.closest('.e2pdf-select2-wrapper').get(0);
            if (wrapper) {
                var select = wrapper.querySelector('select');
                if (select) {
                    Array.from(select.children).forEach(function (option) {
                        var optionDiv = document.createElement('div');
                        optionDiv.setAttribute('value', option.value);
                        optionDiv.innerHTML = option.textContent;
                        select2.appendChild(optionDiv);
                    });
                }
                el.get(0).insertAdjacentElement('afterend', select2);
            }
        },
        // e2pdf.select2.filter
        filter: function (el) {
            var search = el.val();
            var wrapper = el.closest('.e2pdf-select2-wrapper').get(0);
            if (wrapper) {
                var dropdownItems = wrapper.querySelectorAll('.e2pdf-select2-dropdown > div');
                dropdownItems.forEach(function (item) {
                    if (!search) {
                        item.style.display = "block";
                    } else {
                        var textContent = item.textContent.toLowerCase();
                        var valueAttr = item.getAttribute('value') ? item.getAttribute('value').toLowerCase() : '';
                        if (textContent.includes(search.toLowerCase()) || valueAttr.includes(search.toLowerCase()) || valueAttr === '') {
                            item.style.display = "block";
                        } else {
                            item.style.display = "none";
                        }
                    }
                });
            }
        },
        // e2pdf.select2.update
        update: function (el) {
            var val = el.closest('.e2pdf-select2-wrapper').find('select').find('option:selected').val();
            var text = '';
            if (val) {
                text = el.closest('.e2pdf-select2-wrapper').find('select').find('option:selected').text();
            } else {
                el.closest('.e2pdf-select2-wrapper').find('.e2pdf-select2').first().attr('placeholder', e2pdf.lang.get('--- Select ---'));
            }
            el.closest('.e2pdf-select2-wrapper').find('.e2pdf-select2').first().val(text);
        },
        // e2pdf.select2.click
        click: function (el) {
            el.closest('.e2pdf-select2-wrapper').find('select').first().val(el.attr('value')).trigger('change');
            e2pdf.select2.close(el);
        },
        // e2pdf.select2.val
        val: function (el, value) {
            el.closest('.e2pdf-select2-wrapper').find('select').first().val(value).trigger('change');
            e2pdf.select2.update(el);
        },
        // e2pdf.select2.disable
        disable: function (el) {
            el.closest('.e2pdf-select2-wrapper').find('.e2pdf-select2').first().attr('disabled', 'disabled');
        },
        // e2pdf.select2.enable
        enable: function (el) {
            el.closest('.e2pdf-select2-wrapper').find('.e2pdf-select2').first().attr('disabled', false);
        },
        // e2pdf.select2.close
        close: function (el) {
            e2pdf.select2.update(el);
            el.closest('.e2pdf-select2-wrapper').find('.e2pdf-select2-dropdown').remove();
        }
    },
    // e2pdf.hooks
    hooks: {
        // e2pdf.hooks.get
        get: function () {
            var hooks = {};
            switch (e2pdf.pdf.settings.get('extension')) {
                case 'formidable':
                    hooks = {
                        'hook_formidable_entry_view': e2pdf.lang.get('WP Admin Entry View'),
                        'hook_formidable_entry_edit': e2pdf.lang.get('WP Admin Entry Edit'),
                        'hook_formidable_row_actions': e2pdf.lang.get('WP Admin Entry Row Actions')
                    };
                    break;
                case 'gravity':
                    hooks = {
                        'hook_gravity_entry_view': e2pdf.lang.get('WP Admin Entry View'),
                        'hook_gravity_row_actions': e2pdf.lang.get('WP Admin Entry Row Actions')
                    };
                    break;
                case 'jetformbuilder':
                    hooks = {
                        'hook_jetformbuilder_entry_view': e2pdf.lang.get('WP Admin Entry View')
                    };
                    break;
                case 'woocommerce':
                    if (e2pdf.pdf.settings.get('item') == 'shop_order') {
                        hooks = {
                            'hook_woocommerce_order_edit': e2pdf.lang.get('WP Admin Order Details'),
                            'hook_woocommerce_order_row_actions': e2pdf.lang.get('WP Admin Order List Actions'),
                            'hook_woocommerce_order_row_column': e2pdf.lang.get('WP Admin Order List Column')
                        };
                    }
                    break;
                case 'wordpress':
                    if (e2pdf.pdf.settings.get('item') == '-3') {
                        hooks = {
                            'hook_wordpress_row_actions': e2pdf.lang.get('WP Admin User Row Actions')
                        };
                    } else {
                        hooks = {
                            'hook_wordpress_page_edit': e2pdf.lang.get('WP Admin Page Edit'),
                            'hook_wordpress_row_actions': e2pdf.lang.get('WP Admin Page Row Actions')
                        };
                    }
                    break;
                case 'wpforms':
                    hooks = {
                        'hook_wpforms_entry_view': e2pdf.lang.get('WP Admin Entry View'),
                        'hook_wpforms_entry_edit': e2pdf.lang.get('WP Admin Entry Edit'),
                        'hook_wpforms_row_actions': e2pdf.lang.get('WP Admin Entry Row Actions')
                    };
                    break;
                case 'everest':
                    hooks = {
                        'hook_everest_entry_view': e2pdf.lang.get('WP Admin Entry View'),
                        'hook_everest_row_actions': e2pdf.lang.get('WP Admin Entry Row Actions')
                    };
                    break;
                case 'metform':
                    hooks = {
                        'hook_metform_entry_view': e2pdf.lang.get('WP Admin Entry View'),
                        'hook_metform_row_actions': e2pdf.lang.get('WP Admin Entry Row Actions'),
                        'hook_metform_entry_row_column': e2pdf.lang.get('WP Admin Entry List Column')
                    };
                    break;
                default:
                    break;
            }
            return hooks;
        },
        getChecked: function () {
            var hooks = [];
            if (e2pdf.pdf.settings.get('hooks')) {
                hooks = e2pdf.pdf.settings.get('hooks').split(',');
            }
            return hooks;
        }
    },
    // e2pdf.lang
    lang: {
        // e2pdf.lang.get
        get: function (key) {
            if (typeof e2pdf_lang[key] === 'undefined') {
                return key;
            } else {
                return e2pdf_lang[key];
            }
        }
    },
    // e2pdf.url
    url: {
        // e2pdf.url.change
        change: function (page, path) {
            if (window.history && window.history.pushState) {
                var url = window.location.pathname;
                if (page) {
                    url += '?page=' + page;
                }

                if (path) {
                    url += '&' + path;
                }
                history.pushState({urlPath: url}, "", url);
            }
        },
        // e2pdf.url.build
        build: function (page, path, _wpnonce) {
            var url = window.location.pathname;
            if (page) {
                url += '?page=' + page;
            }
            if (path) {
                url += '&' + path;
            }
            if (_wpnonce) {
                url += '&_wpnonce=' + _wpnonce;
            }
            return url;
        },
        // e2pdf.url.get
        get: function (name, url) {
            if (!url) {
                url = window.location.href;
            }
            if (!name) {
                return url;
            }
            name = name.replace(/[\[\]]/g, "\\$&");
            var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);
            if (!results) {
                return null;
            }
            if (!results[2]) {
                return '';
            }
            return decodeURIComponent(results[2].replace(/\+/g, " "));
        },
        pluginsUrl: function () {
            return e2pdf_params['plugins_url'];
        },
        uploadUrl: function () {
            return e2pdf_params['upload_url'];
        }
    },
    // e2pdf.guide
    guide: {
        // e2pdf.guide.calc
        calc: function (el, pos, w, h, g) {
            if (el != null) {
                if (g) {
                    w = parseFloat(jQuery(el).css('width')) * e2pdf.zoom.zoom;
                    h = parseFloat(jQuery(el).css('height')) * e2pdf.zoom.zoom;
                    pos = jQuery(el).offset();
                    if (jQuery(el).hasClass('e2pdf-page')) {
                        pos = {
                            left: pos.left + 1,
                            top: pos.top + 1
                        };
                    }
                } else {
                    w = parseFloat(jQuery(el).css('width'));
                    h = parseFloat(jQuery(el).css('height'));
                    if (jQuery(el).hasClass('e2pdf-page')) {
                        pos = {
                            left: 0,
                            top: 0
                        };
                    } else {
                        pos = {
                            left: e2pdf.properties.getValue(jQuery(el), 'left', 'float'),
                            top: e2pdf.properties.getValue(jQuery(el), 'top', 'float')
                        };
                    }
                }
            }

            return [
                {type: "h", left: pos.left, top: pos.top},
                {type: "h", left: pos.left, top: pos.top + h},
                {type: "v", left: pos.left, top: pos.top},
                {type: "v", left: pos.left + w, top: pos.top},
                {type: "h", left: pos.left, top: pos.top + h / 2},
                {type: "v", left: pos.left + w / 2, top: pos.top}
            ];
        }
    },
    // e2pdf.bulk
    bulk: {
        // e2pdf.bulk.progress
        progress: function () {
            setTimeout(function () {
                var data = {};
                data['bulks'] = [];
                jQuery('.e2pdf-bulk[status="pending"],.e2pdf-bulk[status="busy"]').each(function () {
                    data['bulks'].push(jQuery(this).attr('bulk'));
                });
                e2pdf.request.submitRequest('e2pdf_bulk_progress', jQuery(this), data);
                e2pdf.bulk.progress();
            }, 5000);
        }
    },
    // e2pdf.pdf
    pdf: {
        // e2pdf.pdf.settings
        settings: {
            options: [],
            // e2pdf.pdf.settings.change
            change: function (key, value) {
                e2pdf.pdf.settings.options[key] = value;
                if (jQuery('.e2pdf-form-builder > input[name="' + key + '"]').length > 0) {
                    jQuery('.e2pdf-form-builder > input[name="' + key + '"]').val(value);
                } else {
                    var input = jQuery('<input>',
                            {
                                'type': 'hidden',
                                'name': key,
                                'value': value
                            });
                    jQuery('.e2pdf-form-builder').append(input);
                }

                if (key == 'item') {
                    var data = {};
                    data['extension'] = e2pdf.pdf.settings.get('extension');
                    data['item'] = e2pdf.pdf.settings.get('item');
                    e2pdf.request.submitRequest('e2pdf_get_styles', jQuery('.e2pdf-submit-form'), data);
                }
            },
            // e2pdf.pdf.settings.set
            set: function (key, value) {
                e2pdf.pdf.settings.options[key] = value;
                if (key == 'item') {
                    var data = {};
                    data['extension'] = e2pdf.pdf.settings.get('extension');
                    data['item'] = e2pdf.pdf.settings.get('item');
                    e2pdf.request.submitRequest('e2pdf_get_styles', jQuery('.e2pdf-submit-form'), data);
                }
            },
            // e2pdf.pdf.settings.get
            get: function (key) {
                if (typeof e2pdf.pdf.settings.options[key] === 'undefined') {
                    return null;
                } else {
                    return e2pdf.pdf.settings.options[key];
                }
            }
        }
    },
    // e2pdf.static
    static: {
        // e2pdf.static.unsaved
        unsaved: false,
        // e2pdf.static.mediaUploader
        mediaUploader: false,
        // e2pdf.static.autoloadExport
        autoloadExport: false,
        // e2pdf.static.selectionRange
        selectionRange: null,
        // e2pdf.static.observer
        observer: null,
        // e2pdf.static.vm
        vm: {
            // e2pdf.static.vm.hidden
            hidden: false,
            // e2pdf.static.vm.replace
            replace: true,
            // e2pdf.static.vm.close
            close: true
        },
        // e2pdf.static.guide
        guide: {
            // e2pdf.static.guide.guides
            guides: [],
            // e2pdf.static.guide.distance
            distance: 5,
            x: 0,
            y: 0
        },
        // e2pdf.static.drag
        drag: {
            // e2pdf.static.drag.min_top
            min_top: 0,
            // e2pdf.static.drag.max_top
            max_top: 0,
            // e2pdf.static.drag.min_left
            min_left: 0,
            // e2pdf.static.drag.max_left
            max_left: 0,
            // e2pdf.static.drag.page
            page: null
        }
    },
    // e2pdf.event
    event: {
        // e2pdf.event.fire
        fire: function (event, action, el) {
            if (
                    event === 'after.pages.deletePage' ||
                    event === 'after.createPdf' ||
                    event === 'after.pages.createPage.newpage' ||
                    event === 'after.element.create' ||
                    event === 'after.element.delete' ||
                    event === 'after.settings.style.change' ||
                    event === 'after.settings.template.change' ||
                    event === 'after.wysiwyg.apply' ||
                    event === 'after.request.submitLocal' ||
                    event === 'after.mediaUploader.select' ||
                    event === 'after.element.moved' ||
                    event === 'after.pages.movePage'
                    ) {
                e2pdf.static.unsaved = true;
            }

            if (event === 'before.request.submitForm') {
                e2pdf.static.unsaved = false;
            }

            if (event === 'after.dialog.create' || event === 'after.actions.change') {
                jQuery('.e2pdf-color-picker-load').each(function () {
                    jQuery(this).wpColorPicker(
                            {
                                defaultColor: function () {
                                    if (jQuery(this).attr('data-default')) {
                                        return jQuery(this).attr('data-default');
                                    } else {
                                        return;
                                    }
                                },
                                change: function (event, ui) {
                                    jQuery(this).val(ui.color.toString()).change();
                                }
                            }
                    ).removeClass('e2pdf-color-picker-load');
                });
            }

            if (event === 'after.pages.deletePage' || event === 'after.pages.createPage.newpage') {
                jQuery('#e2pdf-zoom').trigger('change');
            }

            if (event === 'before.request.submitRequest') {
                el.attr('disabled', 'disabled');
                switch (action) {
                    case 'e2pdf_auto':
                        el.closest('form').find('.e2pdf-submit, .e2pdf-extension, .e2pdf-items').attr('disabled', 'disabled');
                        break;
                    case 'e2pdf_extension':
                        el.closest('form').find('.e2pdf-create-pdf, .e2pdf-items, #auto_form_label').attr('disabled', 'disabled');
                        break;
                    case 'e2pdf_templates':
                        jQuery('.e2pdf-export-form-submit').attr('disabled', 'disabled');
                        jQuery('.e2pdf-export-options, .e2pdf-export-item, .e2pdf-dataset-shortcode-wr').hide();
                        jQuery('.e2pdf-export-template-actions, .e2pdf-export-dataset-actions').empty();
                        jQuery('.e2pdf-export-dataset').data('options', []).empty();
                        jQuery('.e2pdf-export-dataset-search').val('');
                        e2pdf.select2.disable(el);
                        break;
                    case 'e2pdf_dataset':
                        jQuery('.e2pdf-export-form-submit').attr('disabled', 'disabled');
                        jQuery('.e2pdf-dataset-shortcode-wr').hide();
                        el.closest('.e2pdf-export-item').find('.e2pdf-export-dataset-actions').empty();
                        e2pdf.select2.disable(el);
                        break;
                    case 'e2pdf_datasets_refresh':
                        el.closest('.e2pdf-select2-wrapper').find('.e2pdf-export-dataset').data('options', []).empty();
                        e2pdf.select2.disable(el);
                        break;
                    case 'e2pdf_delete_item':
                    case 'e2pdf_delete_items':
                        e2pdf.select2.disable(el);
                        break;
                    default:
                        break;
                }
            }

            if (event === 'after.request.submitRequest.error' || event === 'after.request.submitRequest.success') {
                if (action !== 'e2pdf_deactivate_all_templates' && action !== 'e2pdf_license_key') {
                    el.attr('disabled', false);
                }
                switch (action) {
                    case 'e2pdf_auto':
                        el.closest('form').find('.e2pdf-submit, .e2pdf-extension, .e2pdf-items').attr('disabled', false);
                        break;
                    case 'e2pdf_extension':
                        el.closest('form').find('.e2pdf-items').find('option').remove();
                        el.closest('form').find('.e2pdf-create-pdf, .e2pdf-items, #auto_form_label').attr('disabled', false);
                        break;
                    case 'e2pdf_get_styles':
                        jQuery('link[id^="e2pdf-dynamic-style-"]').remove();
                        jQuery('script[id^="e2pdf-dynamic-script-"]').remove();
                        break;
                    case 'e2pdf_templates':
                    case 'e2pdf_delete_item':
                    case 'e2pdf_delete_items':
                        e2pdf.select2.enable(el);
                        break;
                    default:
                        break;
                }
            }

            if (event === 'before.request.upload') {
                el.closest('form').find('.e2pdf-submit, .e2pdf-extension, .e2pdf-items, #auto_form_label').attr('disabled', 'disabled');
            }

            if (event === 'after.request.upload.error' || event === 'after.request.upload.success') {
                if (event === 'after.request.upload.error') {
                    el.closest('form').find('.e2pdf-submit, .e2pdf-extension, .e2pdf-items, #auto_form_label').attr('disabled', false);
                }
                el.closest('form').find('.e2pdf-upload-pdf').replaceWith(
                        jQuery('<input>', {'type': 'file', 'name': 'pdf', 'class': 'e2pdf-upload-pdf e2pdf-hide'})
                        );
                el.closest('form').find('.e2pdf-reupload-pdf').replaceWith(
                        jQuery('<input>', {'type': 'file', 'name': 'pdf', 'class': 'e2pdf-reupload-pdf e2pdf-hide'})
                        );
            }
        }
    },
    // e2pdf.form
    form: {
        // e2pdf.form.serializeObject
        serializeObject: function (form) {

            var o = {};
            var a = form.serializeArray();
            jQuery.each(a, function () {
                if (this.name.endsWith('[]')) {

                    var name = this.name;
                    name = name.substring(0, this.name.length - 2);
                    if (!(name in o)) {
                        o[name] = [];
                    }
                    o[name].push(this.value);
                } else if (this.name.endsWith(']')) {

                    var name = this.name;
                    var path = name.split(/[\[\]]+/);
                    var curItem = o;
                    for (var j = 0; j < path.length - 2; j++)
                    {
                        if (!(path[j] in curItem))
                        {
                            curItem[path[j]] = {};
                        }
                        curItem = curItem[path[j]];
                    }

                    curItem[path[j]] = this.value || '';
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        },
        // e2pdf.form.serializeElements
        serializeElements: function () {
            var o = {};
            jQuery('.e2pdf-element').each(
                    function (index) {
                        var el = {};
                        el.type = jQuery(this).data('data-type');
                        el.properties = e2pdf.properties.get(jQuery(this));
                        el.actions = e2pdf.actions.get(jQuery(this));
                        el.top = jQuery(this).css('top');
                        el.left = jQuery(this).css('left');
                        el.width = jQuery(this).css('width');
                        el.height = jQuery(this).css('height');
                        switch (jQuery(this).data('data-type')) {
                            case 'e2pdf-html':
                                if (jQuery(this).find('.e2pdf-html').is('textarea')) {
                                    el.value = jQuery(this).find('.e2pdf-html').val();
                                } else {
                                    el.value = jQuery(this).find('.e2pdf-html').html();
                                }
                                break;
                            case 'e2pdf-input':
                                el.value = jQuery(this).find('.e2pdf-input').val();
                                break;
                            case 'e2pdf-textarea':
                                el.value = jQuery(this).find('.e2pdf-textarea').val();
                                break;
                            case 'e2pdf-page-number':
                            case 'e2pdf-checkbox':
                            case 'e2pdf-radio':
                            case 'e2pdf-select':
                            case 'e2pdf-image':
                            case 'e2pdf-qrcode':
                            case 'e2pdf-barcode':
                            case 'e2pdf-graph':
                            case 'e2pdf-link':
                            case 'e2pdf-signature':
                                el.value = e2pdf.helper.getString(el.properties['value']);
                                break;
                            default:
                                el.value = jQuery(this).html();
                                break;
                        }
                        el.name = e2pdf.helper.getString(el.properties['name']);
                        el.page_id = jQuery(this).closest('.e2pdf-page').attr('data-page_id');
                        el.element_id = jQuery(this).attr('data-element_id');
                        delete el.properties['width'];
                        delete el.properties['height'];
                        delete el.properties['value'];
                        delete el.properties['top'];
                        delete el.properties['left'];
                        delete el.properties['name'];
                        delete el.properties['element_type'];
                        delete el.properties['element_id'];
                        delete el.properties['page_id'];
                        o[index] = el;
                    });
            return o;
        }
    },
    // e2pdf.font
    font: {
        // e2pdf.font.load
        load: function (el) {
            if (el.is('select')) {
                var name = el.val();
                var value = el.find('option:selected').attr('path');
            } else if (el.is('div')) {
                var name = el.attr('name');
                var value = el.attr('path');
            }

            if (jQuery("head").find('style[name="' + name + '"]').length === 0) {
                jQuery("head").append("<style name='" + name + "' type='text/css'>@font-face {font-family: " + name + "; src: url('" + e2pdf.url.uploadUrl() + "/fonts/" + value + "')}</style>");
            }
        },
        // e2pdf.font.apply
        apply: function (el, font) {
            var font_name = font.find('option:selected').html();
            if (font_name) {
                el.css('font-family', font_name);
            } else {
                el.css('font-family', '');
            }
        },
        // e2pdf.font.size
        size: function (el, size) {
            var font_size = size.val();
            el.css({'font-size': font_size + "px"});
        },
        // e2pdf.font.line
        line: function (el, height) {
            var line_height = height.val();
            el.css({'line-height': line_height + "px"});
        },
        // e2pdf.font.color
        fontcolor: function (el, color) {
            var font_color = color.val();
            el.css({'color': font_color});
        },
        // e2pdf.font.delete
        delete: function (el) {
            var font = el.attr('data-font');
            e2pdf.request.submitRequest('e2pdf_delete_font', el, font);
        }
    },
    // e2pdf.request
    request: {
        // e2pdf.request.upload
        upload: function (action, el) {
            if (el.attr('disabled')) {
                return;
            }
            var data = new FormData(el.closest('form')[0]);
            e2pdf.event.fire('before.request.upload', action, el);
            jQuery('html').addClass('e2pdf-loading');
            jQuery.ajax({
                url: e2pdf.helper.ajaxurl(action, e2pdf_params['nonce']['e2pdf_templates']),
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.redirect !== undefined) {
                        e2pdf.event.fire('after.request.upload.success', action, el);
                        e2pdf.static.unsaved = false;
                        location.href = response.redirect;
                    } else if (response.error !== undefined) {
                        jQuery('html').removeClass('e2pdf-loading');
                        e2pdf.event.fire('after.request.upload.error', action, el);
                        alert(response.error);
                    } else {
                        jQuery('html').removeClass('e2pdf-loading');
                        e2pdf.event.fire('after.request.upload.error', action, el);
                    }
                },
                error: function (response) {
                    jQuery('html').removeClass('e2pdf-loading');
                    e2pdf.event.fire('after.request.upload.error', action, el);
                }
            });
        },
        // e2pdf.request.submitForm
        submitForm: function (el) {
            if (el.attr('disabled')) {
                return;
            }
            el.attr('disabled', 'disabled');
            jQuery('html').addClass('e2pdf-loading');
            e2pdf.event.fire('before.request.submitForm');
            var form_id = el.attr('form-id');
            var form = jQuery(document.getElementById(form_id));
            var data = e2pdf.form.serializeObject(form);
            var action = el.attr('action');
            var _wpnonce = el.attr('_wpnonce');
            switch (form_id) {
                case 'e2pdf-build-form':
                    var elements = e2pdf.form.serializeElements();
                    data.pages = {};
                    jQuery('.e2pdf-page').each(function () {
                        var page_id = jQuery(this).attr('data-page_id');
                        data.pages[page_id] = {};
                        var properties = e2pdf.properties.get(jQuery(this));
                        properties['width'] = jQuery(this).attr('data-width');
                        properties['height'] = jQuery(this).attr('data-height');
                        delete properties['page_id'];
                        delete properties['element_type'];
                        delete properties['preset'];
                        var page_elements = [];
                        data.pages[page_id]['properties'] = properties;
                        data.pages[page_id]['actions'] = e2pdf.actions.get(jQuery(this));
                        data.pages[page_id]['elements'] = page_elements;
                    });
                    for (var key in elements) {
                        data.pages[elements[key].page_id]['elements'].push(elements[key]);
                    }
                    data.actions = e2pdf.actions.get(jQuery('.e2pdf-tpl'));
                    data.properties = e2pdf.properties.get(jQuery('.e2pdf-tpl'));
                    data = JSON.stringify(data);
                    break;
                case 'e2pdf-export-form':
                    data = JSON.stringify(data);
                    break;
                case 'e2pdf-email':
                    jQuery('.e2pdf-email-lock .e2pdf-form-loader').removeClass('e2pdf-hidden-loader');
                    data = {};
                    data['email'] = form.find('input[name="email"]').val();
                    if (form.find('input[name="email_code"]').length > 0) {
                        data['email_code'] = form.find('input[name="email_code"]').val();
                    }
                    break;
            }
            jQuery('html').addClass('e2pdf-loading');
            if (el.attr('target') === '_blank') {
                var post_form = jQuery('<form>', {'target': '_blank', 'method': 'POST', 'action': el.attr('href')}).append(
                        jQuery('<textarea>', {'name': 'preview'}).val(data)
                        ).hide();
                jQuery('body').append(post_form);
                post_form.submit();
                post_form.remove();
                jQuery('html').removeClass('e2pdf-loading');
                el.attr('disabled', false);
                return false;
            }
            jQuery.ajax({
                type: 'POST', url: e2pdf.helper.ajaxurl(false, _wpnonce),
                data: {action: action, data: data},
                success: function (response) {
                    if (response.redirect !== undefined) {
                        location.href = response.redirect;
                    } else {
                        el.attr('disabled', false);
                        if (response.error !== undefined) {
                            jQuery('html').removeClass('e2pdf-loading');
                            alert(response.error);
                        } else if (response.content) {
                            e2pdf.request.callBack(action, response.content, el);
                            jQuery('html').removeClass('e2pdf-loading');
                        } else {
                            jQuery('html').removeClass('e2pdf-loading');
                        }
                    }
                },
                error: function (response) {
                    jQuery('html').removeClass('e2pdf-loading');
                    el.attr('disabled', false);
                }
            });
        },
        // e2pdf.request.submitRequest
        submitRequest: function (action, el, value) {
            if (el.attr('disabled')) {
                return;
            }
            jQuery('html').addClass('e2pdf-loading');
            if (!value) {
                var value = el.val();
            }

            var _wpnonce = el.attr('_wpnonce');
            if (action == 'e2pdf_bulk_progress') {
                _wpnonce = e2pdf_params['nonce']['e2pdf'];
            }

            e2pdf.event.fire('before.request.submitRequest', action, el);
            jQuery.ajax({
                type: 'POST', url: e2pdf.helper.ajaxurl(false, _wpnonce),
                data: {action: action, data: value},
                success: function (response) {
                    e2pdf.event.fire('after.request.submitRequest.success', action, el);
                    if (response.redirect !== undefined) {
                        location.href = response.redirect;
                    } else if (response.error !== undefined) {
                        jQuery('html').removeClass('e2pdf-loading');
                        alert(response.error);
                    } else if (response.content) {
                        e2pdf.request.callBack(action, response.content, el);
                        jQuery('html').removeClass('e2pdf-loading');
                    } else {
                        jQuery('html').removeClass('e2pdf-loading');
                    }
                },
                error: function (response) {
                    jQuery('html').removeClass('e2pdf-loading');
                    e2pdf.event.fire('after.request.submitRequest.error', action, el);
                }
            });
        },
        // e2pdf.request.submitLocal
        submitLocal: function (el, noclose = false) {
            var form_id = el.attr('form-id');
            var form = jQuery(document.getElementById(form_id));
            var data = e2pdf.form.serializeObject(form);
            switch (form_id) {
                case 'e2pdf-page-options':
                    jQuery('.e2pdf-action').removeClass('e2pdf-action-error');
                    var action_error = false;
                    for (var action in data['actions']) {
                        for (var condition in data['actions'][action]['conditions']) {
                            if (data['actions'][action]['conditions'][condition]['if'].trim() === '' && data['actions'][action]['conditions'][condition]['value'].trim() == '') {
                                let action_element = jQuery(".e2pdf-action[data-action_id='" + action + "']").first();
                                action_element.addClass('e2pdf-action-error');
                                action_error = true;
                            }
                        }
                    }
                    if (action_error) {
                        setTimeout(function () {
                            alert(e2pdf.lang.get('Error: Empty "if" and "value" detected in action condition'));
                        }, 0);
                        return;
                    }
                    var width = data['width'];
                    var height = data['height'];
                    var page = jQuery('.e2pdf-page[data-page_id="' + data['page_id'] + '"]');
                    e2pdf.actions.apply(page, data['actions']);
                    e2pdf.pages.changePageSize(page, width, height);
                    e2pdf.properties.apply(page, data);
                    e2pdf.event.fire('after.request.submitLocal', false, page);
                    break;
                case 'e2pdf-tpl-actions':
                    jQuery('.e2pdf-action').removeClass('e2pdf-action-error');
                    var action_error = false;
                    for (var action in data['actions']) {
                        for (var condition in data['actions'][action]['conditions']) {
                            if (data['actions'][action]['conditions'][condition]['if'].trim() === '' && data['actions'][action]['conditions'][condition]['value'].trim() == '') {
                                let action_element = jQuery(".e2pdf-action[data-action_id='" + action + "']").first();
                                action_element.addClass('e2pdf-action-error');
                                action_error = true;
                            }
                        }
                    }
                    if (action_error) {
                        setTimeout(function () {
                            alert(e2pdf.lang.get('Error: Empty "if" and "value" detected in action condition'));
                        }, 0);
                        return;
                    }
                    e2pdf.actions.apply(jQuery('.e2pdf-tpl'), data['actions']);
                    break;
                case 'e2pdf-tpl-properties':
                    e2pdf.properties.apply(jQuery('.e2pdf-tpl'), data);
                    e2pdf.helper.cssGlobal(e2pdf.helper.getString(data['css']));
                    break;
                case 'e2pdf-tpl-hooks':
                    var hooks = data['hooks'];
                    if (hooks) {
                        e2pdf.pdf.settings.change('hooks', hooks.join(','));
                    } else {
                        e2pdf.pdf.settings.change('hooks', '');
                    }
                    break;
                default:
                    var element = jQuery(".e2pdf-element[data-element_id='" + data.element_id + "']").first();
                    jQuery('.e2pdf-action').removeClass('e2pdf-action-error');
                    var action_error = false;
                    for (var action in data['actions']) {
                        for (var condition in data['actions'][action]['conditions']) {
                            if (data['actions'][action]['conditions'][condition]['if'].trim() === '' && data['actions'][action]['conditions'][condition]['value'].trim() == '') {
                                let action_element = jQuery(".e2pdf-action[data-action_id='" + action + "']").first();
                                action_element.addClass('e2pdf-action-error');
                                action_error = true;
                            }
                        }
                    }
                    if (action_error) {
                        setTimeout(function () {
                            alert(e2pdf.lang.get('Error: Empty "if" and "value" detected in action condition'));
                        }, 0);
                        return;
                    }
                    e2pdf.actions.apply(element, data['actions']);
                    delete data['actions'];
                    e2pdf.properties.apply(element, data);
                    e2pdf.properties.render(element);
                    e2pdf.event.fire('after.request.submitLocal', false, el);
                    break;
            }
            if (!noclose) {
                e2pdf.dialog.close();
        }
        },
        // e2pdf.request.callBack
        callBack: function (action, result, el) {
            switch (action) {
                case 'e2pdf_email':
                    if (result === 'subscribed') {
                        jQuery('.e2pdf-email-lock').remove();
                    } else {
                        jQuery('.e2pdf-email-lock .e2pdf-form-loader').addClass('e2pdf-hidden-loader');
                        var form_id = el.attr('form-id');
                        var form = jQuery(document.getElementById(form_id));
                        form.find('label').html(e2pdf.lang.get('Confirmation Code') + ":");
                        form.find('input[name="email"]').attr('type', 'hidden');
                        jQuery('<input>', {'type': 'text', 'name': 'email_code', 'class': 'e2pdf-w100 e2pdf-enter', 'placeholder': e2pdf.lang.get('Code')}).insertAfter(form.find('input[name="email"]'));
                    }
                    break;
                case 'e2pdf_extension':
                    for (var key in result) {
                        var option = jQuery('<option>',
                                {
                                    'value': result[key]['id']
                                }).html(result[key]['name']);
                        if (e2pdf.pdf.settings.get('item') === result[key]['id']) {
                            option.attr('selected', 'selected');
                        }
                        option.data('data-item', result[key]);
                        jQuery('.e2pdf-item').append(option);
                    }
                    for (var key in result) {
                        if (result[key]['id'] != '-1' && result[key]['id'] != '-2') {
                            var option = jQuery('<option>',
                                    {
                                        'value': result[key]['id']
                                    }).html(result[key]['name']);
                            if (e2pdf.pdf.settings.get('item1') === result[key]['id']) {
                                option.attr('selected', 'selected');
                            }
                            option.data('data-item', result[key]);
                            jQuery('.e2pdf-item1').append(option);
                        }
                    }
                    for (var key in result) {
                        if (result[key]['id'] != '-1' && result[key]['id'] != '-2') {
                            var option = jQuery('<option>',
                                    {
                                        'value': result[key]['id']
                                    }).html(result[key]['name']);
                            if (e2pdf.pdf.settings.get('item2') === result[key]['id']) {
                                option.attr('selected', 'selected');
                            }
                            option.data('data-item', result[key]);
                            jQuery('.e2pdf-item2').append(option);
                        }
                    }
                    el.closest('form').find('.e2pdf-item').trigger('change');
                    break;
                case 'e2pdf_get_styles':
                    for (var key in result) {
                        if (result[key].split('.').pop() == 'js') {
                            jQuery('<script>', {'id': 'e2pdf-dynamic-script-' + key + '-js', 'type': 'text/javascript', 'href': result[key]}).appendTo('head');
                        } else {
                            jQuery('<link>', {'id': 'e2pdf-dynamic-style-' + key + '-css', 'type': 'text/css', 'rel': 'stylesheet', 'href': result[key]}).appendTo('head');
                        }
                    }
                    break;
                case 'e2pdf_visual_mapper':
                    el.html(result);
                    var height = el.outerHeight();
                    e2pdf.visual.mapper.markup();
                    if (window.ResizeObserver) {
                        e2pdf.static.observer = new ResizeObserver((mutationsList, observer) => {
                            for (var mutation of mutationsList) {
                                if (el.outerHeight() != height) {
                                    e2pdf.visual.mapper.rebuild();
                                }
                            }
                        });
                        e2pdf.static.observer.observe(el[0]);
                    }
                    var images = el.find('img');
                    var counter = 0;
                    counter = images.length;
                    images.each(function () {
                        var img = new Image();
                        img.onload = function () {
                            counter--;
                            if (counter === 0) {
                                e2pdf.visual.mapper.rebuild();
                            }
                        };
                        img.onerror = function () {
                            counter--;
                            if (counter === 0) {
                                e2pdf.visual.mapper.rebuild();
                            }
                        };
                        img.src = jQuery(this).attr('src');
                    });
                    break;
                case 'e2pdf_templates':
                    var template_id = result['id'];
                    if (template_id) {
                        jQuery('.e2pdf-template-shortcode').val('[e2pdf-download id="' + template_id + '"]');
                        for (var key in result['datasets']) {
                            var options = [];
                            var dataset_field = jQuery('.e2pdf-export-dataset[name="' + key + '"]');
                            dataset_field.closest('.e2pdf-export-item').show();
                            for (var subkey in result['datasets'][key]) {
                                var option = {
                                    key: result['datasets'][key][subkey]['key'].toString(),
                                    value: result['datasets'][key][subkey]['value'].toString()
                                };
                                options.push(option);
                                if (e2pdf.url.get('action') == 'bulk') {
                                    dataset_field.append(jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(jQuery('<label>').html(option.value).prepend(jQuery('<input>', {'name': key + '[]', 'type': 'checkbox', 'value': option.key}))));
                                } else {
                                    dataset_field.append(jQuery('<option>', {'value': option.key}).html(option.value));
                                }
                            }
                            dataset_field.data('options', options);
                            if (e2pdf.url.get('action') == 'bulk') {
                                dataset_field.find('input[type="checkbox"][value=""]').prop('checked', true).trigger('change');
                                if (Object.keys(result['datasets'][key]).length > 1) {
                                    dataset_field.attr('disabled', false);
                                    jQuery('.e2pdf-export-form-submit').attr('disabled', false);
                                } else {
                                    dataset_field.attr('disabled', 'disabled');
                                    jQuery('.e2pdf-export-form-submit').attr('disabled', 'disabled');
                                }
                            } else {
                                dataset_field.val('');
                                e2pdf.select2.update(dataset_field);
                                e2pdf.select2.enable(dataset_field);
                            }
                        }
                        if (result['actions']) {
                            var ul = jQuery('<ul>', {'class': 'e2pdf-inline-links'});
                            for (var key in result['actions']) {
                                ul.append(jQuery('<li>').html(result['actions'][key]));
                            }
                            jQuery('.e2pdf-export-template-actions').append(ul);
                        }
                        jQuery('.e2pdf-export-option').each(function () {
                            jQuery(this).closest('.e2pdf-grid').addClass('e2pdf-hide');
                            var key = jQuery(this).attr('name').replace('options[', '').replace(']', '');
                            if (result['options'].hasOwnProperty(key)) {
                                jQuery(this).closest('.e2pdf-grid').removeClass('e2pdf-hide');
                                if (result['options'][key]) {
                                    jQuery(this).val(result['options'][key]);
                                } else {
                                    if (e2pdf.url.get('action') == 'bulk' && key == 'name') {
                                        jQuery(this).val('[e2pdf-dataset]');
                                    } else {
                                        if (key == 'args') {
                                            jQuery(this).closest('.e2pdf-grid').find('.e2pdf-argument').first().find('input').val('');
                                            jQuery(this).closest('.e2pdf-grid').find('.e2pdf-argument').not(':first').remove();
                                        }
                                        jQuery(this).val('');
                                    }
                                }
                            } else {
                                jQuery(this).val('');
                            }
                        });
                        if (e2pdf.static.autoloadExport) {
                            var datasets = [
                                'dataset',
                                'dataset2'
                            ];
                            for (var key in datasets) {
                                if (e2pdf.url.get(datasets[key])) {
                                    jQuery('.e2pdf-export-dataset[name="' + datasets[key] + '"]').val(e2pdf.url.get(datasets[key]));
                                }
                            }
                            for (var key in datasets) {
                                if (e2pdf.url.get(datasets[key])) {
                                    e2pdf.select2.val(jQuery('.e2pdf-export-dataset[name="' + datasets[key] + '"]'), e2pdf.url.get(datasets[key]));
                                }
                            }
                            e2pdf.static.autoloadExport = false;
                        } else {
                            var url = '';
                            if (e2pdf.url.get('action')) {
                                url += 'action=' + e2pdf.url.get('action') + '&';
                            }
                            url += 'id=' + template_id;
                            e2pdf.url.change('e2pdf', url);
                        }

                        if (e2pdf.url.get('action') == 'bulk') {
                            jQuery('.e2pdf-export-form').attr('action', e2pdf.url.build('e2pdf', 'action=bulk&id=' + template_id));
                        }
                        jQuery('.e2pdf-export-options').slideDown();
                    } else {
                        if (e2pdf.url.get('action') == 'bulk') {
                            e2pdf.url.change('e2pdf', 'action=bulk');
                        } else {
                            e2pdf.url.change('e2pdf');
                        }
                    }
                    break;
                case 'e2pdf_dataset':
                    var template_id = result['id'];
                    var url = 'id=' + template_id;
                    var shortcode = '[e2pdf-download id="' + template_id + '"';
                    if (result['datasets']) {
                        for (var key in result['datasets']) {
                            var dataset_field = jQuery('.e2pdf-export-dataset[name="' + key + '"]');
                            var actions = dataset_field.closest('.e2pdf-export-item').find('.e2pdf-export-dataset-actions');
                            e2pdf.select2.enable(dataset_field);
                            if (result['datasets'][key]['id'] == '') {
                                dataset_field.val('');
                                e2pdf.select2.update(dataset_field);
                            } else {
                                url += '&' + key + '=' + result['datasets'][key]['id'];
                                shortcode += ' ' + key + '="' + result['datasets'][key]['id'] + '"';
                            }
                            if (result['datasets'][key]['actions']) {
                                var ul = jQuery('<ul>', {'class': 'e2pdf-inline-links'});
                                for (var dkey in result['datasets'][key]['actions']) {
                                    ul.append(jQuery('<li>').html(result['datasets'][key]['actions'][dkey]));
                                }
                                actions.empty().append(ul);
                            }
                        }
                        jQuery('.e2pdf-export-form').attr('action', e2pdf.url.build('e2pdf', 'action=export&' + url));
                    }
                    shortcode += "]";
                    if (result['export']) {
                        jQuery('.e2pdf-dataset-shortcode').val(shortcode);
                        jQuery('.e2pdf-export-form-submit').attr('disabled', false);
                        jQuery('.e2pdf-dataset-shortcode-wr').show();
                    }
                    e2pdf.url.change('e2pdf', url);
                    break;
                case 'e2pdf_datasets_refresh':
                    var options = [];
                    var dataset_field = el.closest('.e2pdf-select2-wrapper').find('select').first();
                    for (var key in result['datasets']) {
                        var option = {
                            key: result['datasets'][key]['key'].toString(),
                            value: result['datasets'][key]['value'].toString()
                        };
                        options.push(option);
                        if (e2pdf.url.get('action') == 'bulk') {
                            dataset_field.append(jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(jQuery('<label>').html(option.value).prepend(jQuery('<input>', {'name': key + '[]', 'type': 'checkbox', 'value': option.key}))));
                        } else {
                            dataset_field.append(jQuery('<option>', {'value': option.key}).html(option.value));
                        }
                    }
                    dataset_field.data('options', options);
                    e2pdf.select2.val(dataset_field, result['dataset']);
                    break;
                case 'e2pdf_delete_item':
                case 'e2pdf_delete_items':
                    jQuery('.e2pdf-export-template').trigger('change');
                    break;
                case 'e2pdf_activate_template':
                case 'e2pdf_deactivate_template':
                    if (result == 'activated') {
                        el.removeClass('e2pdf-activate-template e2pdf-color-red').addClass('e2pdf-deactivate-template e2pdf-color-green').text(e2pdf.lang.get('Activated'));
                        if (el.parent().attr('id') === 'e2pdf-post-activation') {
                            el.closest("#minor-publishing").find('.e2pdf-generate-pdf-button').attr('disabled', false);
                        }
                    } else {
                        el.removeClass('e2pdf-deactivate-template e2pdf-color-green').addClass('e2pdf-activate-template e2pdf-color-red').text(e2pdf.lang.get('Not Activated'));
                        if (el.parent().attr('id') === 'e2pdf-post-activation') {
                            el.closest("#minor-publishing").find('.e2pdf-generate-pdf-button').attr('disabled', 'disabled');
                        }
                    }
                    break;
                case  'e2pdf_auto':
                    var hidden_elements = [];
                    var elements = result.elements;
                    var width = parseFloat(e2pdf.pdf.settings.get('width'));
                    var height = parseFloat(e2pdf.pdf.settings.get('height'));
                    e2pdf.pages.changeTplSize(width, height);
                    e2pdf.pages.createPage();
                    jQuery('.ui-dialog-content').dialog('close');
                    var i = 1;
                    var page = jQuery('.e2pdf-page').last();
                    var auto = {
                        'block': {
                            'top': 0,
                            'left': 0,
                            'right': 0,
                            'bottom': 0,
                            'width': 0,
                            'page': page.attr('data-page_id')
                        },
                        'element': {
                            'top': 0,
                            'left': 0,
                            'right': 0,
                            'bottom': 0,
                            'width': 0,
                            'properties': {}
                        },
                        page: {
                            'top': typeof result.page.top !== 'undefined' ? parseFloat(result.page.top) : 0,
                            'left': typeof result.page.left !== 'undefined' ? parseFloat(result.page.left) : 0,
                            'right': typeof result.page.right !== 'undefined' ? parseFloat(result.page.right) : 0,
                            'bottom': typeof result.page.bottom !== 'undefined' ? parseFloat(result.page.bottom) : 0
                        }
                    };
                    for (var key in elements) {
                        var element = elements[key];
                        var type = element.type;
                        var properties = {};
                        element['properties'] = typeof element.properties === 'undefined' ? {} : element.properties;
                        element['block'] = typeof element.block === 'undefined' ? false : element.block;
                        element['float'] = typeof element.float === 'undefined' ? false : element.float;
                        element['hidden'] = typeof element.hidden === 'undefined' ? false : element.hidden;
                        element.properties['width'] = typeof element.properties.width === 'undefined' ? '0' : element.properties.width;
                        element.properties['height'] = typeof element.properties.height === 'undefined' ? '0' : element.properties.height;
                        element.properties['top'] = typeof element.properties.top === 'undefined' ? '0' : element.properties.top;
                        element.properties['left'] = typeof element.properties.left === 'undefined' ? '0' : element.properties.left;
                        element.properties['right'] = typeof element.properties.right === 'undefined' ? '0' : element.properties.right;
                        for (var property in element.properties) {
                            properties[property] = element.properties[property];
                        }

                        if (element.block) {
                            properties['width'] = Math.floor(e2pdf.helper.sizeToFloat(element.properties.width, width) - parseFloat(element.properties.left) - parseFloat(element.properties.right));
                            if (element.float && auto.block.width > 0 && (auto.block.right + Math.floor(e2pdf.helper.sizeToFloat(properties.width, width) - parseFloat(properties.left) - parseFloat(properties.right)) <= width - auto.page.right)) {
                                page = jQuery('.e2pdf-page[data-page_id="' + auto.block.page + '"]');
                                properties['left'] = auto.block.right + parseFloat(properties.left);
                                properties['top'] = auto.block.top;
                            } else {
                                page = jQuery('.e2pdf-page').last();
                                auto.block['bottom'] = 0;
                                page.find('.e2pdf-element').each(function () {
                                    auto.block['bottom'] = Math.max(auto.block['bottom'], e2pdf.properties.getValue(jQuery(this), 'top') + e2pdf.properties.getValue(jQuery(this), 'height'));
                                });
                                properties['left'] = parseFloat(properties.left);
                                properties['top'] = auto.block.bottom + parseFloat(properties.top);
                            }
                        } else {
                            if (element.float) {
                                if (element.properties.width !== 'auto') {
                                    properties['width'] = Math.floor(e2pdf.helper.sizeToFloat(properties.width, auto.block.width) - e2pdf.helper.sizeToFloat(properties.left, auto.block.width) - e2pdf.helper.sizeToFloat(properties.right, auto.block.width));
                                    if (auto.element.properties.width === 'auto') {
                                        properties['width'] = properties['width'] - auto.element.width;
                                    }
                                }
                                properties['left'] = auto.element.right + e2pdf.helper.sizeToFloat(properties.left, auto.block.width);
                                properties['top'] = auto.element.top;
                            } else {
                                if (element.properties.width !== 'auto') {
                                    properties['width'] = Math.floor(e2pdf.helper.sizeToFloat(properties.width, auto.block.width) - e2pdf.helper.sizeToFloat(properties.left, auto.block.width) - e2pdf.helper.sizeToFloat(properties.right, auto.block.width));
                                }
                                properties['left'] = auto.block.left + e2pdf.helper.sizeToFloat(properties.left, auto.block.width);
                                properties['top'] = auto.block.bottom + parseFloat(properties.top);
                            }
                        }

                        var obj = e2pdf.element.create(type, page, properties, false, true);
                        e2pdf.properties.render(obj);
                        page.append(obj);
                        if (typeof element.actions !== 'undefined' && Object.keys(element.actions).length !== 0) {
                            e2pdf.actions.apply(obj, element.actions);
                        }

                        if (element.properties.height === 'auto') {
                            e2pdf.properties.set(obj, 'height', obj.height());
                            e2pdf.properties.render(obj);
                        } else if (element.properties.height === 'max') {
                            e2pdf.properties.set(obj, 'height', height - auto.page.bottom - e2pdf.properties.getValue(obj, 'top'));
                            e2pdf.properties.render(obj);
                        }

                        if (!element.block
                                && element.float
                                && auto.block.width > 0
                                && e2pdf.properties.getValue(obj, 'left') + e2pdf.properties.getValue(obj, 'width') > auto.block.left + auto.block.width) {
                            e2pdf.properties.set(obj, 'left', auto.block.left);
                            e2pdf.properties.set(obj, 'top', auto.element.bottom + 1);
                            e2pdf.properties.render(obj);
                        }

                        if (e2pdf.properties.getValue(obj, 'top') + e2pdf.properties.getValue(obj, 'height') + auto.page.bottom > height) {
                            if (page.is(':last-child')) {
                                if (e2pdf_params['license_type'] == 'FREE') {
                                    obj.remove();
                                    alert(e2pdf.lang.get('Only single-page PDFs are allowed with the "FREE" license type'));
                                    return;
                                }

                                if (page.find('.e2pdf-element').not(obj).length > 0) {
                                    e2pdf.pages.createPage();
                                    page = jQuery('.e2pdf-page').last();
                                }
                            } else {
                                if (page.find('.e2pdf-element').not(obj).length > 0) {
                                    page = page.next('.e2pdf-page');
                                }
                            }

                            e2pdf.element.delete(obj);
                            if (element.properties.height === 'auto') {
                                properties['height'] = element.properties.height;
                            }

                            auto.block['bottom'] = auto.page.top;
                            properties['top'] = auto.block.bottom;
                            obj = e2pdf.element.create(type, page, properties, false, true);
                            e2pdf.properties.render(obj);
                            page.append(obj);
                            if (element.properties.height === 'auto') {
                                e2pdf.properties.set(obj, 'height', obj.height());
                                e2pdf.properties.render(obj);
                            }

                            if (e2pdf.properties.getValue(obj, 'top') + e2pdf.properties.getValue(obj, 'height') + auto.page.bottom > height) {
                                e2pdf.properties.set(obj, 'height', height - e2pdf.properties.getValue(obj, 'top') - auto.page.bottom);
                                e2pdf.properties.render(obj);
                                e2pdf.properties.set(obj, 'top', properties['top']);
                                e2pdf.properties.render(obj);
                            }
                        }

                        auto.element = {
                            'top': e2pdf.properties.getValue(obj, 'top'),
                            'left': e2pdf.properties.getValue(obj, 'left'),
                            'right': e2pdf.properties.getValue(obj, 'left') + e2pdf.properties.getValue(obj, 'width') + e2pdf.helper.sizeToFloat(properties.right, auto.block.width),
                            'bottom': e2pdf.properties.getValue(obj, 'top') + e2pdf.properties.getValue(obj, 'height'),
                            'width': e2pdf.properties.getValue(obj, 'width'),
                            'properties': element.properties
                        };
                        if (element.block) {
                            auto.block = {
                                'top': e2pdf.properties.getValue(obj, 'top'),
                                'left': e2pdf.properties.getValue(obj, 'left'),
                                'right': e2pdf.properties.getValue(obj, 'left') + e2pdf.properties.getValue(obj, 'width') + parseFloat(properties.right),
                                'bottom': e2pdf.properties.getValue(obj, 'top') + e2pdf.properties.getValue(obj, 'height'),
                                'width': e2pdf.properties.getValue(obj, 'width'),
                                'page': page.attr('data-page_id')
                            };
                        } else {
                            auto.block['bottom'] = e2pdf.properties.getValue(obj, 'top') + e2pdf.properties.getValue(obj, 'height');
                        }
                        if (element.hidden) {
                            hidden_elements.push(obj);
                        }
                        i++;
                    }
                    hidden_elements.forEach(function (element) {
                        e2pdf.element.delete(element);
                    });
                    break;
                case  'e2pdf_bulk_action':
                    if (result.action == 'delete') {
                        el.closest('.e2pdf-bulk').remove();
                        if (jQuery('.e2pdf-bulks-list .e2pdf-bulk').length == 0) {
                            jQuery('.e2pdf-bulks-list').remove();
                        }
                    } else if (result.action == 'stop' && el.closest('.e2pdf-bulk').attr('status') != 'completed') {
                        el.closest('.e2pdf-bulk').attr('status', 'stop');
                        el.attr('action', 'start').html(jQuery('<i>', {'class': 'dashicons dashicons-controls-play'}));
                    } else if (result.action == 'start' && el.closest('.e2pdf-bulk').attr('status') != 'completed') {
                        el.closest('.e2pdf-bulk').attr('status', 'pending');
                        el.attr('action', 'stop').html(jQuery('<i>', {'class': 'dashicons dashicons-controls-pause'}));
                    }
                    break;
                case 'e2pdf_bulk_progress':
                    for (var key in result.bulks) {
                        var bulk = result.bulks[key];
                        jQuery('.e2pdf-bulk[bulk="' + bulk['ID'] + '"]').find('.e2pdf-bulk-count').html(bulk['count']);
                        if (bulk['status'] == 'completed') {
                            jQuery('.e2pdf-bulk[bulk="' + bulk['ID'] + '"]').attr('status', 'completed');
                            jQuery('.e2pdf-bulk[bulk="' + bulk['ID'] + '"]').find('.e2pdf-bulk-action:not([action="delete"])').replaceWith(
                                    jQuery('<a>', {'class': 'e2pdf-link', 'href': e2pdf.url.build('e2pdf', 'action=bulk&uid=' + bulk['uid'])}).append(
                                    jQuery('<i>', {'class': 'dashicons dashicons-download'})
                                    ));
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    },
    // e2pdf.dialog
    dialog: {
        top: null,
        left: null,
        center: true,
        // e2pdf.dialog.create
        create: function (el) {
            e2pdf.dialog.close();
            var modal = el.attr('data-modal');
            var title = el.attr('data-modal-title');
            var noclose = false;
            var width = '600';
            var height = '600';
            if (modal === 'license-key') {
                width = '400';
                var content = jQuery('<div>', {'class': 'e2pdf-rel'}).append(
                        jQuery('<form>', {'id': 'license_key', 'class': 'e2pdf-license-key e2pdf-rel'}).append(
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ),
                        jQuery('<ul>').append(
                        jQuery('<li>').append(
                        jQuery('<label>', {'class': 'e2pdf-mb5'}).html(e2pdf.lang.get('License Key') + ':'),
                        jQuery('<input>', {'type': 'text', 'name': 'license_key', 'class': 'e2pdf-w100 e2pdf-enter'})
                        ),
                        jQuery('<li>', {'class': 'e2pdf-center'}).append(
                        jQuery('<input>', {'form-id': "license_key", 'action': 'e2pdf_license_key', 'type': 'button', 'class': 'e2pdf-submit-form button-primary button-small', 'value': e2pdf.lang.get('Apply'), '_wpnonce': e2pdf_params['nonce']['e2pdf_license']})
                        )
                        )
                        )
                        );
            } else if (modal === 'properties') {
                for (var key in e2pdf.element.selected) {
                    var title = e2pdf.lang.get('Properties');
                    var selected = e2pdf.element.selected[key];
                    var fields = e2pdf.properties.renderFields(selected);
                    var content = jQuery('<div>').append(
                            jQuery('<form>', {'id': 'e2pdf-properties'}).append(
                            jQuery('<div>', {'class': 'e2pdf-el-properties e2pdf-popup-inner'}).append(
                            fields
                            )
                            )
                            );
                }
            } else if (modal === 'page-options') {
                var title = e2pdf.lang.get('Page Options');
                var page = el.closest('.e2pdf-page');
                var readonly_size = false;
                if (e2pdf.pdf.settings.get('pdf')) {
                    readonly_size = true;
                }

                var content = jQuery('<div>', {'class': 'e2pdf-page-options'}).append(jQuery('<form>', {'id': 'e2pdf-page-options', 'class': 'e2pdf-rel'}).append(
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-center'}).append(
                        jQuery('<h3>').html(e2pdf.lang.get('Page Options'))
                        )),
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ));
                var fields = e2pdf.properties.renderFields(page);
                content.find('form').append(jQuery('<div>', {'class': 'e2pdf-el-properties e2pdf-popup-inner'}).append(
                        fields
                        ));
            } else if (modal === 'tpl-actions') {
                width = '600';
                var title = e2pdf.lang.get('Global Actions');
                var fields = e2pdf.properties.renderFields(jQuery('.e2pdf-tpl'));
                var content = jQuery('<div>').append(
                        jQuery('<form>', {'id': 'e2pdf-tpl-actions'}).append(
                        jQuery('<div>', {'class': 'e2pdf-el-properties e2pdf-popup-inner'}).append(
                        fields
                        )
                        )
                        );
            } else if (modal === 'tpl-properties') {
                width = '600';
                var title = e2pdf.lang.get('Global Properties');
                var fields = e2pdf.properties.renderFields(jQuery('.e2pdf-tpl'), false);
                var content = jQuery('<div>').append(
                        jQuery('<form>', {'id': 'e2pdf-tpl-properties'}).append(
                        jQuery('<div>', {'class': 'e2pdf-el-properties e2pdf-popup-inner'}).append(
                        fields
                        )
                        )
                        );
            } else if (modal === 'tpl-options') {
                width = '500';
                var title = e2pdf.lang.get('Options');
                var readonly_size = false;
                if (e2pdf.pdf.settings.get('pdf')) {
                    readonly_size = true;
                }
                var sizes = jQuery('<select>', {'name': 'preset', 'class': 'e2pdf-preset e2pdf-w100', 'disabled': readonly_size ? 'disabled' : false}).append(
                        jQuery('<option>',
                                {
                                    'value': ''
                                }).html(e2pdf.lang.get('--- Select ---')).attr('selected', 'selected')
                        );
                for (var size in e2pdf_params['template_sizes']) {
                    var option = jQuery('<option>',
                            {
                                'value': size
                            }).html(size + ' (' + e2pdf_params['template_sizes'][size]['width'] + 'x' + e2pdf_params['template_sizes'][size]['height'] + ')');
                    sizes.append(option);
                }

                var extensions = jQuery('<select>', {'name': 'extension', 'class': 'e2pdf-extension e2pdf-w100', '_wpnonce': e2pdf_params['nonce']['e2pdf_templates']});
                for (var extension in e2pdf_params['extensions']) {
                    var option = jQuery('<option>',
                            {
                                'value': extension
                            }).html(e2pdf_params['extensions'][extension]);
                    if (e2pdf.pdf.settings.get('extension') === extension) {
                        option.attr('selected', 'selected');
                    }
                    extensions.append(option);
                }

                var content = jQuery('<div>', {'class': 'e2pdf-welcome'}).append(jQuery('<form>', {'id': 'e2pdf-tpl-options', 'class': 'e2pdf-rel'}).append(
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-center'}).append(
                        jQuery('<h3>').html(e2pdf.lang.get('Options'))
                        )),
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ));
                var default_width = '595';
                if (e2pdf.pdf.settings.get('width')) {
                    default_width = e2pdf.pdf.settings.get('width');
                }

                var default_height = '842';
                if (e2pdf.pdf.settings.get('height')) {
                    default_height = e2pdf.pdf.settings.get('height');
                }

                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).html(e2pdf.lang.get('Width') + ':'),
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).html(e2pdf.lang.get('Height') + ':')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).append(
                        jQuery('<input>', {'class': 'e2pdf-numbers e2pdf-w100', 'id': 'e2pdf-width', 'type': 'text', 'name': 'width', 'readonly': readonly_size ? 'readonly' : false, 'value': default_width})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).append(
                        jQuery('<input>', {'class': 'e2pdf-numbers e2pdf-w100', 'id': 'e2pdf-height', 'type': 'text', 'name': 'height', 'readonly': readonly_size ? 'readonly' : false, 'value': default_height})
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<label>').html(e2pdf.lang.get('Size') + ':'),
                        sizes);
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid e2pdf-mt5'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w60 e2pdf-small e2pdf-pr10'}).html(e2pdf.lang.get('Font') + ':'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-small e2pdf-pl10 e2pdf-pr10'}).html(e2pdf.lang.get('Size') + ':'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-small e2pdf-pl10'}).html(e2pdf.lang.get('Line Height') + ':')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w60 e2pdf-pr10'}).append(
                        jQuery('#e2pdf-font').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-font').val())
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20  e2pdf-pl10 e2pdf-pr10'}).append(
                        jQuery('#e2pdf-font-size').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-font-size').val())
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-pl10'}).append(
                        jQuery('#e2pdf-line-height').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-line-height').val())
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid e2pdf-mt5'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-small e2pdf-pr10'}).html(e2pdf.lang.get('Text Align') + ':'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-small e2pdf-pl10'}).html('')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-pr10'}).append(
                        jQuery('#e2pdf-text-align').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-text-align').val())
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-pr10 e2pdf-mt5'}).append(
                        jQuery('#e2pdf-rtl').clone().removeAttr('id').val(jQuery('#e2pdf-rtl').val()),
                        e2pdf.lang.get('RTL')
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<label>', {'class': 'e2pdf-mt5'}).html(e2pdf.lang.get('Extension') + ':'),
                        extensions,
                        jQuery('<label>').html(e2pdf.lang.get('Connection') + ':'),
                        jQuery('<select>', {'name': 'item', 'class': 'e2pdf-item e2pdf-items e2pdf-w100'})
                        );
                content.find('form').append(
                        jQuery('<div>', {'id': 'e2pdf-item-options', 'class': 'e2pdf-hide'}).append(
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib'}).html(e2pdf.lang.get('Labels') + ':')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(
                        jQuery('<select>', {'id': 'auto_form_label', 'class': 'e2pdf-w100', 'name': 'auto_form_label'}).append(
                        jQuery('<option>', {'value': '0'}).text(e2pdf.lang.get('None')),
                        jQuery('<option>', {'value': 'value'}).text(e2pdf.lang.get('Field Values')),
                        jQuery('<option>', {'value': 'name'}).text(e2pdf.lang.get('Field Names'))
                        ).val('0'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100 e2pdf-align-right e2pdf-mt5'}).append(
                        jQuery('<label>', {'class': 'e2pdf-label e2pdf-small e2pdf-wauto'}).append(
                        jQuery('<input>', {'type': 'checkbox', 'class': 'e2pdf-ib', 'name': 'auto_form_shortcode', 'value': '1'}),
                        e2pdf.lang.get('Shortcodes')
                        )
                        )
                        )
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-item-merged e2pdf-hide'}).append(
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).html(e2pdf.lang.get('Connection') + ' #1:'),
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).html(e2pdf.lang.get('Connection') + ' #2:')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).append(
                        jQuery('<select>', {'class': 'e2pdf-w100 e2pdf-item1 e2pdf-items', 'type': 'text', 'name': 'item1'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).append(
                        jQuery('<select>', {'class': 'e2pdf-w100 e2pdf-item2 e2pdf-items', 'type': 'text', 'name': 'item2'})
                        )
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<input>', {'type': 'file', 'name': 'pdf', 'class': 'e2pdf-upload-pdf e2pdf-hide'})
                        );
                if (e2pdf.pdf.settings.get('pdf')) {
                    content.find('form').append(
                            jQuery('<ul>', {'class': 'e2pdf-mb0 e2pdf-mt15'}).append(
                            jQuery('<li>'),
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'data-action': 'apply', 'class': 'e2pdf-w-apply e2pdf-create-pdf e2pdf-submit button-primary button-large e2pdf-link'}).html(e2pdf.lang.get('Apply'))
                            ),
                            jQuery('<li>')
                            )
                            );
                } else {
                    content.find('form').append(
                            jQuery('<ul>', {'class': 'e2pdf-mb0 e2pdf-mt15'}).append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'data-action': 'apply', 'class': 'e2pdf-w-apply e2pdf-create-pdf e2pdf-submit button-primary button-large e2pdf-link'}).html(e2pdf.lang.get('Apply'))
                            ),
                            jQuery('<li>'),
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'data-action': 'auto', 'class': 'e2pdf-w-auto e2pdf-create-pdf e2pdf-submit button-primary button-large e2pdf-link', '_wpnonce': e2pdf_params['nonce']['e2pdf_templates']}).html(e2pdf.lang.get('Auto PDF'))
                            )
                            )
                            );
                }

            } else if (modal === 'tpl-hooks') {
                width = '600';
                var title = e2pdf.lang.get('Integration Hooks');
                var hooks = e2pdf.hooks.get();
                var checked = e2pdf.hooks.getChecked();
                var fields = jQuery('<div>');
                fields.append(jQuery('<p>', {'class': 'e2pdf-bold'}).html(e2pdf.lang.get('Display PDF Download') + ':'));
                for (var hook in hooks) {
                    var option = jQuery('<label>', {'class': 'e2pdf-label e2pdf-w50'}).append(
                            jQuery(
                                    '<input>', {
                                        'type': 'checkbox', 'name': 'hooks[]',
                                        'class': 'e2pdf-ib',
                                        'value': hook,
                                        'checked': jQuery.inArray(hook, checked) !== -1 ? 'checked' : false
                                    }
                            )
                            ).append(hooks[hook]);
                    fields.append(option);
                }
                if (Object.keys(hooks).length === 0) {
                    fields.append(jQuery('<p>').html(e2pdf.lang.get('No hooks are available for this extension')));
                }
                var content = jQuery('<div>').append(
                        jQuery('<form>', {'id': 'e2pdf-tpl-hooks'}).append(
                        jQuery('<div>', {'class': 'e2pdf-el-properties e2pdf-popup-inner'}).append(
                        fields
                        )
                        )
                        );
            } else if (modal === 'welcome-screen') {
                var noclose = true;
                width = '500';
                var title = e2pdf.lang.get('Create PDF');
                var sizes = jQuery('<select>', {'id': 'e2pdf-preset', 'name': 'preset', 'class': 'e2pdf-preset e2pdf-w100'}).append(
                        jQuery('<option>',
                                {
                                    'value': ''
                                }).html(e2pdf.lang.get('--- Select ---')).attr('selected', 'selected')
                        );
                for (var size in e2pdf_params['template_sizes']) {
                    var option = jQuery('<option>',
                            {
                                'value': size,
                                'data-width': e2pdf_params['template_sizes'][size]['width'],
                                'data-height': e2pdf_params['template_sizes'][size]['height']
                            }).html(size + ' (' + e2pdf_params['template_sizes'][size]['width'] + 'x' + e2pdf_params['template_sizes'][size]['height'] + ')');
                    sizes.append(option);
                }

                var extensions = jQuery('<select>', {'name': 'extension', 'class': 'e2pdf-extension e2pdf-w100', '_wpnonce': e2pdf_params['nonce']['e2pdf_templates']});
                for (var extension in e2pdf_params['extensions']) {
                    var option = jQuery('<option>',
                            {
                                'value': extension
                            }).html(e2pdf_params['extensions'][extension]);
                    if (e2pdf.pdf.settings.get('extension') === extension) {
                        option.attr('selected', 'selected');
                    }
                    extensions.append(option);
                }

                var content = jQuery('<div>', {'class': 'e2pdf-welcome'}).append(jQuery('<form>', {'id': 'e2pdf-welcome-screen', 'class': 'e2pdf-rel'}).append(
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-center'}).append(
                        jQuery('<h3>').html(e2pdf.lang.get('Options'))
                        )),
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ));
                var default_width = '595';
                if (e2pdf.pdf.settings.get('width')) {
                    var default_width = e2pdf.pdf.settings.get('width');
                }

                var default_height = '842';
                if (e2pdf.pdf.settings.get('height')) {
                    var default_height = e2pdf.pdf.settings.get('height');
                }

                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w100'}).html(e2pdf.lang.get('Title') + ':'),
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(
                        jQuery('<input>', {'id': 'e2pdf-title', 'type': 'text', 'name': 'title', 'class': 'e2pdf-w100', 'value': jQuery('#e2pdf-build-form').find('input[name="title"]').val()})
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid e2pdf-mt5'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).html(e2pdf.lang.get('Width') + ':'),
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).html(e2pdf.lang.get('Height') + ':')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).append(
                        jQuery('<input>', {'class': 'e2pdf-numbers e2pdf-w100', 'id': 'e2pdf-width', 'type': 'text', 'name': 'width', 'value': default_width})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).append(
                        jQuery('<input>', {'class': 'e2pdf-numbers e2pdf-w100', 'id': 'e2pdf-height', 'type': 'text', 'name': 'height', 'value': default_height})
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<label>').html(e2pdf.lang.get('Size') + ':'),
                        sizes);
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid e2pdf-mt5'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w60 e2pdf-small e2pdf-pr10'}).html(e2pdf.lang.get('Font') + ':'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-small e2pdf-pl10 e2pdf-pr10'}).html(e2pdf.lang.get('Size') + ':'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-small e2pdf-pl10'}).html(e2pdf.lang.get('Line Height') + ':'),
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w60 e2pdf-pr10'}).append(
                        jQuery('#e2pdf-font').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-font').val())
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20  e2pdf-pl10 e2pdf-pr10'}).append(
                        jQuery('#e2pdf-font-size').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-font-size').val())
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-pl10'}).append(
                        jQuery('#e2pdf-line-height').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-line-height').val())
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-grid e2pdf-mt5'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-small e2pdf-pr10'}).html(e2pdf.lang.get('Text Align') + ':'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-small e2pdf-pl10'}).html('')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-pr10'}).append(
                        jQuery('#e2pdf-text-align').clone().removeAttr('id').attr('class', 'e2pdf-w100').val(jQuery('#e2pdf-text-align').val())
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-pr10 e2pdf-mt5'}).append(
                        jQuery('#e2pdf-rtl').clone().removeAttr('id').val(jQuery('#e2pdf-rtl').val()),
                        e2pdf.lang.get('RTL')
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<label>', {'class': 'e2pdf-mt5'}).html(e2pdf.lang.get('Extension') + ':'),
                        extensions,
                        jQuery('<label>').html(e2pdf.lang.get('Connection') + ':'),
                        jQuery('<select>', {'name': 'item', 'class': 'e2pdf-item e2pdf-items e2pdf-w100'})
                        );
                content.find('form').append(
                        jQuery('<div>', {'id': 'e2pdf-item-options', 'class': 'e2pdf-hide'}).append(
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib'}).html(e2pdf.lang.get('Labels') + ':')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(
                        jQuery('<select>', {'id': 'auto_form_label', 'class': 'e2pdf-w100', 'name': 'auto_form_label'}).append(
                        jQuery('<option>', {'value': '0'}).text(e2pdf.lang.get('None')),
                        jQuery('<option>', {'value': 'value'}).text(e2pdf.lang.get('Field Values')),
                        jQuery('<option>', {'value': 'name'}).text(e2pdf.lang.get('Field Names'))
                        ).val('0'),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100 e2pdf-align-right e2pdf-mt5'}).append(
                        jQuery('<label>', {'class': 'e2pdf-label e2pdf-small e2pdf-wauto'}).append(
                        jQuery('<input>', {'type': 'checkbox', 'class': 'e2pdf-ib', 'name': 'auto_form_shortcode', 'value': '1'}),
                        e2pdf.lang.get('Shortcodes')
                        )
                        )
                        )
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<div>', {'class': 'e2pdf-item-merged e2pdf-hide'}).append(
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).html(e2pdf.lang.get('Connection') + ' #1:'),
                        jQuery('<label>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).html(e2pdf.lang.get('Connection') + ' #2:')
                        ),
                        jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr10'}).append(
                        jQuery('<select>', {'class': 'e2pdf-w100 e2pdf-item1 e2pdf-items', 'type': 'text', 'name': 'item1'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl10'}).append(
                        jQuery('<select>', {'class': 'e2pdf-w100 e2pdf-item2 e2pdf-items', 'type': 'text', 'name': 'item2'})
                        )
                        )
                        )
                        );
                content.find('form').append(
                        jQuery('<input>', {'type': 'file', 'name': 'pdf', 'class': 'e2pdf-upload-pdf e2pdf-hide'})
                        );
                content.find('form').append(
                        jQuery('<input>', {'type': 'hidden', 'name': 'pdf', 'value': ''})
                        );
                if (e2pdf.pdf.settings.get('ID')) {
                    content.find('form').append(
                            jQuery('<input>', {'id': 'template_id', 'type': 'hidden', 'name': 'template_id', 'value': e2pdf.pdf.settings.get('ID')})
                            );
                }

                content.find('form').append(
                        jQuery('<ul>', {'class': 'e2pdf-mb0 e2pdf-mt15'}).append(
                        jQuery('<li>').append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'data-action': 'empty', 'class': 'e2pdf-w-empty e2pdf-create-pdf e2pdf-submit button-primary button-large e2pdf-link'}).html(e2pdf.lang.get('Empty PDF'))
                        ),
                        jQuery('<li>').append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'data-action': 'upload', 'class': 'e2pdf-w-upload e2pdf-create-pdf e2pdf-submit button-primary button-large e2pdf-link'}).html(e2pdf.lang.get('Upload PDF')).append(jQuery('<span>').html(e2pdf.lang.get('Max Upload File Size') + ": " + e2pdf_params['upload_max_filesize']))
                        ),
                        jQuery('<li>').append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'data-action': 'auto', 'class': 'e2pdf-w-auto e2pdf-create-pdf e2pdf-submit button-primary button-large e2pdf-link', '_wpnonce': e2pdf_params['nonce']['e2pdf_templates']}).html(e2pdf.lang.get('Auto PDF'))
                        )
                        )
                        );
            } else if (modal === 'visual-mapper') {
                var title = e2pdf.lang.get('Visual Mapper');
                var content = jQuery('<div>', {'class': 'visual-mapper'}).append(
                        jQuery('<form>', {'class': 'e2pdf-rel'}).append(
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        )).append(
                        jQuery('<div>', {'class': 'e2pdf-popup-inner'}).append(
                        jQuery('<div>', {'class': 'e2pdf-vm-content e2pdf-rel', '_wpnonce': e2pdf_params['nonce']['e2pdf_templates']}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        )
                        )
                        ));
            } else if (modal === 'pdf-reupload') {
                var title = e2pdf.lang.get('PDF Upload');
                width = '500';
                var content = jQuery('<div>', {'class': 'e2pdf-welcome'}).append(
                        jQuery('<form>', {'id': 'e2pdf-reupload-pdf-form', 'class': 'e2pdf-rel'}).append(
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-center'}).append(
                        jQuery('<h3>').html(e2pdf.lang.get('Options'))
                        )),
                        jQuery('<div>', {'class': 'e2pdf-form-loader'}).append(
                        jQuery('<span>', {'class': 'spinner'})
                        ));
                content.find('form').append(
                        jQuery('<input>', {'type': 'file', 'name': 'pdf', 'class': 'e2pdf-reupload-pdf e2pdf-hide'})
                        );
                if (e2pdf.pdf.settings.get('ID')) {
                    content.find('form').append(
                            jQuery('<input>', {'id': 'template_id', 'type': 'hidden', 'name': 'template_id', 'value': e2pdf.pdf.settings.get('ID')})
                            );
                }

                var pages = jQuery('<div>', {'class': 'e2pdf-reupload-pages'});
                pages.append(
                        jQuery('<div>', {'class': 'e2pdf-grid e2pdf-reupload-pages-header'}).append(
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-pr10 e2pdf-center'}).append(
                        jQuery('<label>').text(e2pdf.lang.get('Page ID'))
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w30 e2pdf-pl10 e2pdf-pr10 e2pdf-center'}).append(
                        jQuery('<label>').text(e2pdf.lang.get('Page ID inside Upload PDF'))
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w25 e2pdf-pl10 e2pdf-center'}).append(
                        jQuery('<label>').text(e2pdf.lang.get('Render Fields from Upload PDF'))
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w25 e2pdf-pl10 e2pdf-pr10 e2pdf-center'}).append(
                        jQuery('<label>').text(e2pdf.lang.get('Delete created E2Pdf Fields'))
                        )));
                jQuery('.e2pdf-page').each(function () {
                    var page_id = jQuery(this).attr('data-page_id');
                    pages.append(
                            jQuery('<div>', {'class': 'e2pdf-grid'}).append(
                            jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w20 e2pdf-pr10 e2pdf-center'}).text(page_id),
                            jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w5 e2pdf-center'}).append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-delete-reupload-page'}).append(
                            jQuery('<i>', {'class': 'dashicons dashicons-no'})
                            )
                            ),
                            jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w25 e2pdf-pl10 e2pdf-pr10 e2pdf-center'}).append(
                            jQuery('<input>', {'name': 'positions[' + page_id + ']', 'type': 'text', 'class': 'e2pdf-numbers e2pdf-center e2pdf-w100', 'value': page_id})
                            ),
                            jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w25 e2pdf-pl10 e2pdf-center'}).append(
                            jQuery('<input>', {'name': 'new[]', 'type': 'checkbox', 'value': page_id})
                            ),
                            jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w25 e2pdf-pl10 e2pdf-pr10 e2pdf-center'}).append(
                            jQuery('<input>', {'name': 'flush[]', 'type': 'checkbox', 'value': page_id})
                            )
                            )
                            );
                });
                content.find('form').append(pages);
                content.find('form').append(
                        jQuery('<ul>').append(
                        jQuery('<li>').append(
                        ),
                        jQuery('<li>').append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-w-reupload e2pdf-submit button-primary button-large e2pdf-link'}).html(e2pdf.lang.get('Upload PDF'))
                        ),
                        jQuery('<li>').append(
                        )
                        )
                        );
            } else {
                var content = jQuery('<div>');
            }

            if (typeof content !== 'undefined') {

                var dialog_class = 'e2pdf-dialog';
                if (modal === 'visual-mapper') {
                    dialog_class += ' e2pdf-dialog-visual-mapper';
                } else if (modal === 'properties') {
                    dialog_class += ' e2pdf-dialog-element-properties';
                    dialog_class += ' for-' + selected.data('data-type');
                }
                content.dialog({
                    title: title,
                    dialogClass: dialog_class,
                    modal: true,
                    width: width,
                    height: Math.min(height, jQuery(window).height() - 200),
                    resizable: modal === 'visual-mapper' || modal === 'properties' || modal === 'page-options' || modal === 'tpl-actions' || modal === 'tpl-properties' ? true : false,
                    minWidth: 200,
                    closeText: '',
                    my: 'center',
                    at: 'center',
                    of: window,
                    open: function (event, ui) {
                        jQuery('html').css('overflow', 'hidden');
                        jQuery('.e2pdf-dialog').css('min-height', '150');
                        if (noclose) {
                            jQuery(".ui-dialog-titlebar-close", ui.dialog | ui).off().click(function (e) {
                                location.href = e2pdf.url.build('e2pdf-templates');
                                e.preventDefault();
                            });
                        } else {
                            jQuery('.ui-widget-overlay').bind('click', function ()
                            {
                                if (!confirm(e2pdf.lang.get('Changes will not be saved! Continue?'))) {
                                    return;
                                }
                                content.dialog('close');
                            });
                        }

                        if (modal === 'properties') {
                            jQuery(".ui-dialog-titlebar").after(
                                    jQuery('<div>', {'class': 'e2pdf-dialog-over e2pdf-align-right'}).append(
                                    jQuery('<input>', {'form-id': "e2pdf-properties", 'type': 'button', 'class': 'e2pdf-submit-local button-primary button-small e2pdf-noclose', 'value': e2pdf.lang.get('Apply')}),
                                    jQuery('<input>', {'form-id': "e2pdf-properties", 'type': 'button', 'class': 'e2pdf-submit-local button-primary button-small e2pdf-ml5', 'value': e2pdf.lang.get('Save')})
                                    )
                                    );
                        } else if (modal === 'tpl-actions') {
                            jQuery(".ui-dialog-titlebar").after(
                                    jQuery('<div>', {'class': 'e2pdf-dialog-over e2pdf-align-right'}).append(
                                    jQuery('<input>', {'form-id': "e2pdf-tpl-actions", 'type': 'button', 'class': 'e2pdf-submit-local button-primary button-small', 'value': e2pdf.lang.get('Save')})
                                    )
                                    );
                        } else if (modal === 'tpl-properties') {
                            jQuery(".ui-dialog-titlebar").after(
                                    jQuery('<div>', {'class': 'e2pdf-dialog-over e2pdf-align-right'}).append(
                                    jQuery('<input>', {'form-id': "e2pdf-tpl-properties", 'type': 'button', 'class': 'e2pdf-submit-local button-primary button-small', 'value': e2pdf.lang.get('Save')})
                                    )
                                    );
                        } else if (modal === 'tpl-hooks') {
                            jQuery(".ui-dialog-titlebar").after(
                                    jQuery('<div>', {'class': 'e2pdf-dialog-over e2pdf-align-right'}).append(
                                    jQuery('<input>', {'form-id': "e2pdf-tpl-hooks", 'type': 'button', 'class': 'e2pdf-submit-local button-primary button-small', 'value': e2pdf.lang.get('Save')})
                                    )
                                    );
                        } else if (modal === 'visual-mapper') {
                            jQuery(".ui-dialog-titlebar").after(
                                    jQuery('<div>', {'class': 'e2pdf-dialog-over'}).append(
                                    jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(
                                    jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w40 e2pdf-pr10'}).append(
                                    jQuery('<input>', {'type': 'text', 'name': 'vm_search', 'class': 'e2pdf-ib e2pdf-w100 e2pdf-hide', 'value': '', 'placeholder': e2pdf.lang.get('Filter...')})
                                    ),
                                    jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w60 e2pdf-align-right'}).append(
                                    jQuery('<label>', {'class': 'e2pdf-label e2pdf-wauto e2pdf-pr10'}).append(
                                    jQuery('<input>', {'type': 'checkbox', 'name': 'vm_hidden', 'class': 'e2pdf-ib', 'value': '1', 'checked': e2pdf.static.vm.hidden ? 'checked' : false})
                                    ).append(e2pdf.lang.get('Hidden Fields')),
                                    jQuery('<label>', {'class': 'e2pdf-label e2pdf-wauto e2pdf-pl10 e2pdf-pr10'}).append(
                                    jQuery('<input>', {'type': 'checkbox', 'name': 'vm_replace', 'class': 'e2pdf-ib', 'value': '1', 'checked': e2pdf.static.vm.replace ? 'checked' : false})
                                    ).append(e2pdf.lang.get('Replace Value')),
                                    jQuery('<label>', {'class': 'e2pdf-label e2pdf-wauto e2pdf-pl10'}).append(
                                    jQuery('<input>', {'type': 'checkbox', 'name': 'vm_close', 'class': 'e2pdf-ib', 'value': '1', 'checked': e2pdf.static.vm.close ? 'checked' : false})
                                    ).append(e2pdf.lang.get('Auto-Close'))
                                    )
                                    )
                                    )
                                    );
                        } else if (modal === 'page-options') {
                            jQuery(".ui-dialog-titlebar").after(
                                    jQuery('<div>', {'class': 'e2pdf-dialog-over e2pdf-align-right'}).append(
                                    jQuery('<input>', {'form-id': "e2pdf-page-options", 'type': 'button', 'class': 'e2pdf-submit-local button-primary button-small', 'value': e2pdf.lang.get('Save')})
                                    )
                                    );
                        }
                        jQuery('.e2pdf-dialog').find('.ui-dialog-content').css({'max-height': Math.min(height, jQuery(window).height() - 200)});
                    },
                    closeOnEscape: modal === 'welcome-screen' || modal === 'tpl-options' ? false : true,
                    beforeClose: function (event, ui) {
                        e2pdf.dialog.center = true;
                    },
                    dragStop: function (event, ui) {
                        e2pdf.dialog.center = false;
                    },
                    resizeStart: function (event, ui) {
                    },
                    resize: function (event, ui) {
                        var max_height = jQuery('.e2pdf-dialog').height();
                        var min_height = 0;
                        if (jQuery('.e2pdf-dialog').find('.ui-dialog-titlebar').length > 0) {
                            min_height += jQuery('.e2pdf-dialog').find('.ui-dialog-titlebar').outerHeight();
                        }
                        if (jQuery('.e2pdf-dialog').find('.e2pdf-dialog-over').length > 0) {
                            min_height += jQuery('.e2pdf-dialog').find('.e2pdf-dialog-over').outerHeight();
                        }
                        jQuery('.e2pdf-dialog').css('min-height', Math.max(150, min_height + 50));
                        jQuery('.e2pdf-dialog').find('.ui-dialog-content').css({'max-height': max_height - min_height});
                    },
                    resizeStop: function (event, ui) {
                        e2pdf.dialog.center = false;
                    },
                    close: function (event, ui)
                    {
                        jQuery('html').css('overflow', 'auto');
                        if (jQuery(this).find('.wp-color-result.wp-picker-open').length > 0) {
                            jQuery(this).find('.wp-color-result.wp-picker-open').each(function () {
                                jQuery(this).click();
                            });
                        }
                        e2pdf.visual.mapper.selected = null;
                        jQuery(this).remove();
                    }
                });
            }

            if (modal === 'welcome-screen' || modal === 'tpl-options') {
                jQuery('.e2pdf-extension').trigger('change');
            } else if (modal === 'visual-mapper') {
                var data = {};
                data['extension'] = e2pdf.pdf.settings.get('extension');
                data['item'] = e2pdf.pdf.settings.get('item');
                data['item1'] = e2pdf.pdf.settings.get('item1');
                data['item2'] = e2pdf.pdf.settings.get('item2');
                e2pdf.request.submitRequest('e2pdf_visual_mapper', jQuery('.e2pdf-vm-content'), data);
            }

            if (modal !== 'visual-mapper') {
                e2pdf.dialog.rebuild();
            }

            e2pdf.event.fire('after.dialog.create');
        },
        // e2pdf.dialog.close
        close: function () {
            if (jQuery('.e2pdf-dialog').length > 0) {
                jQuery('.ui-dialog-content').dialog('close');
            }
        },
        // e2pdf.dialog.rebuild

        rebuild: function () {
            if (jQuery('.e2pdf-dialog').not('.ui-dialog-resizing').length > 0) {
                if (jQuery('.e2pdf-dialog').width() > jQuery(window).width()) {
                    jQuery('.e2pdf-dialog').width(jQuery(window).width() - 100);
                }
                if (e2pdf.dialog.center) {
                    jQuery('.e2pdf-dialog').find('.ui-dialog-content').dialog('option', 'position', {my: 'center', at: 'center', of: window});
                }
                e2pdf.visual.mapper.rebuild();
            }
        }
    },
    // e2pdf.mediaUploader
    mediaUploader: {
        // e2pdf.mediaUploader.init
        init: function (el) {
            var mediaUploader;
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: e2pdf.lang.get('Media Library'),
                button: {
                    text: e2pdf.lang.get('Select')
                }, multiple: false});
            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                e2pdf.properties.set(el, 'value', attachment.url);
                e2pdf.properties.render(el);
                e2pdf.event.fire('after.mediaUploader.select');
            });
            mediaUploader.open();
        }
    },
    // e2pdf.actions
    actions: {
        // e2pdf.actions.add
        add: function (el, target, action) {
            var last_id = -1;
            target.find('.e2pdf-action').each(function () {
                var num_id = parseInt(jQuery(this).attr("data-action_id"));
                if (num_id > last_id) {
                    last_id = num_id;
                }
            });
            var action_id = parseInt(last_id + 1);
            if (action) {
                target.append(e2pdf.actions.renderField(el, action_id, action));
            } else {
                target.append(e2pdf.actions.renderField(el, action_id));
            }

        },
        // e2pdf.actions.change
        change: function (action, trigger) {
            var action_id = action.attr("data-action_id");
            var form = action.closest('form');
            var data = e2pdf.form.serializeObject(form);
            var el = false;
            if (form.attr('id') === 'e2pdf-page-options') {
                el = jQuery('.e2pdf-page[data-page_id="' + data.page_id + '"]');
            } else if (form.attr('id') === 'e2pdf-tpl-actions') {
                el = jQuery('.e2pdf-tpl');
            } else {
                el = jQuery(".e2pdf-element[data-element_id='" + data.element_id + "']").first();
            }

            var formatRegex = new RegExp('actions\\[\\d+\\]\\[format\\]');
            if (!formatRegex.test(trigger.attr('name'))) {
                data['actions'][action_id]['change'] = '';
            }

            action.replaceWith(e2pdf.actions.renderField(el, action_id, data['actions'][action_id]));
            e2pdf.event.fire('after.actions.change');
        },
        // e2pdf.actions.duplicate
        duplicate: function (action) {
            var actions = action.closest('.e2pdf-actions-wrapper').find('.e2pdf-actions');
            var action_id = action.attr("data-action_id");
            var form = action.closest('form');
            var data = e2pdf.form.serializeObject(form);
            var el = false;
            if (form.attr('id') === 'e2pdf-page-options') {
                el = jQuery('.e2pdf-page[data-page_id="' + data.page_id + '"]');
            } else if (form.attr('id') === 'e2pdf-tpl-actions') {
                el = jQuery('.e2pdf-tpl');
            } else {
                el = jQuery(".e2pdf-element[data-element_id='" + data.element_id + "']").first();
            }
            e2pdf.actions.add(el, actions, data['actions'][action_id]);
            e2pdf.event.fire('after.actions.change');
        },
        // e2pdf.actions.delete

        delete: function (action) {
            action.remove();
        },
        // e2pdf.actions.conditions
        conditions: {
            // e2pdf.actions.conditions.add
            add: function (el, target) {
                var action = target.closest('.e2pdf-action');
                var action_id = action.attr('data-action_id');
                var last_id = -1;
                target.find('.e2pdf-condition').each(function () {
                    var num_id = parseInt(jQuery(this).attr("data-condition_id"));
                    if (num_id > last_id) {
                        last_id = num_id;
                    }
                });
                var condition_id = parseInt(last_id + 1);
                action.find('.e2pdf-conditions').append(
                        e2pdf.actions.conditions.renderField(el, action_id, condition_id)
                        );
            },
            // e2pdf.actions.conditions.getFields
            getFields: function (el, action_id, condition_id, condition) {
                var obj = {
                    'condition': {
                        'fields': [
                            e2pdf.actions.conditions.getField('if', el, action_id, condition_id, condition),
                            e2pdf.actions.conditions.getField('condition', el, action_id, condition_id, condition),
                            e2pdf.actions.conditions.getField('value', el, action_id, condition_id, condition)
                        ],
                        'classes': [
                            'e2pdf-ib e2pdf-w30 e2pdf-pr5',
                            'e2pdf-ib e2pdf-w25 e2pdf-pl5 e2pdf-pr5',
                            'e2pdf-ib e2pdf-w35 e2pdf-pl5 e2pdf-pr5'
                        ]
                    }
                };
                return obj;
            },
            // e2pdf.actions.conditions.getField
            getField: function (field, el, action_id, condition_id, condition) {
                var obj = false;
                switch (field) {
                    case 'if':
                        var value = condition ? condition.if : '';
                        obj = {
                            'name': e2pdf.lang.get('If'),
                            'key': 'actions[' + action_id + '][conditions][' + condition_id + '][if]',
                            'type': 'textarea',
                            'value': value,
                            'atts': []
                        };
                        break;
                    case 'condition':
                        var value = condition ? condition.condition : '=';
                        obj = {
                            'name': e2pdf.lang.get('Condition'),
                            'key': 'actions[' + action_id + '][conditions][' + condition_id + '][condition]',
                            'type': 'select',
                            'value': value,
                            'options': [
                                {'=': '='},
                                {'!=': '!='},
                                {'>': '>'},
                                {'>=': '>='},
                                {'<': '<'},
                                {'<=': '<='},
                                {'like': e2pdf.lang.get('Contains')},
                                {'not_like': e2pdf.lang.get('Not Contains')},
                                {'in_array': e2pdf.lang.get('In Array')},
                                {'not_in_array': e2pdf.lang.get('Not In Array')},
                                {'in_list': e2pdf.lang.get('In List')},
                                {'not_in_list': e2pdf.lang.get('Not In List')},
                                {'array_key_exists': e2pdf.lang.get('Array Key Exists')},
                                {'array_key_not_exists': e2pdf.lang.get('Array Key Not Exists')}
                            ],
                            'atts': []
                        };
                        break;
                    case 'value':
                        var value = condition ? condition.value : '';
                        obj = {
                            'name': e2pdf.lang.get('Value'),
                            'key': 'actions[' + action_id + '][conditions][' + condition_id + '][value]',
                            'type': 'textarea',
                            'value': value,
                            'atts': []
                        };
                        break;
                }
                return obj;
            },
            // e2pdf.actions.conditions.delete
            delete: function (condition) {
                condition.remove();
            },
            // e2pdf.actions.conditions.renderField
            renderField: function (el, action_id, condition_id, condition) {
                var groups = e2pdf.actions.conditions.getFields(el, action_id, condition_id, condition);
                if (groups) {
                    for (var group_key in groups) {

                        var group = groups[group_key];
                        var grid = jQuery('<div>', {'class': 'e2pdf-grid'});
                        for (var field_key in group.fields) {
                            var group_field = group.fields[field_key];
                            var classes = '';
                            if (group.classes) {
                                if (group.classes[field_key]) {
                                    classes = group.classes[field_key];
                                }
                            }

                            var field = '';
                            var label = '';
                            var wrap = '';
                            if (group_field.type === 'text') {
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<input>', {'type': 'text', 'class': 'e2pdf-w100', 'name': group_field.key, 'value': group_field.value});
                            } else if (group_field.type === 'hidden') {
                                field = jQuery('<input>', {'type': 'hidden', 'name': group_field.key, 'value': group_field.value});
                            } else if (group_field.type === 'textarea') {
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<textarea>', {'name': group_field.key, 'class': 'e2pdf-w100', 'rows': '5'}).val(group_field.value);
                            } else if (group_field.type === 'checkbox') {
                                label = jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-small e2pdf-pr10 e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<input>', {'type': 'checkbox', 'class': 'e2pdf-ib e2pdf-w50 e2pdf-small e2pdf-pl10', 'name': group_field.key, 'value': group_field.option});
                                if (group_field.value == group_field.option) {
                                    field.prop('checked', true);
                                }
                            } else if (group_field.type === 'color') {
                                wrap = jQuery('<div>', {'class': 'e2pdf-colorpicker-wr'});
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<input>', {'class': 'e2pdf-color-picker e2pdf-color-picker-load e2pdf-w100', 'type': 'text', 'name': group_field.key, 'value': group_field.value});
                            } else if (group_field.type === 'select') {
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<select>', {'class': 'e2pdf-w100', 'name': group_field.key});
                                for (var option_key in group_field.options) {
                                    field.append(jQuery('<option>', {'value': Object.keys(group_field.options[option_key])[0]}).html(Object.values(group_field.options[option_key])[0]));
                                }
                                field.val(group_field.value);
                            }

                            if (!wrap) {
                                wrap = field;
                            } else {
                                wrap.prepend(field);
                            }

                            grid.append(jQuery('<div>', {'class': 'e2pdf-ib ' + classes}).append(label, wrap));
                        }
                    }
                }

                grid.append(jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w10 e2pdf-pl5 e2pdf-mt23 e2pdf-center'}).append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-action-condition-add'}).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-plus'})
                        ),
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-action-condition-delete'}).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-minus'})
                        )));
                var new_condition = jQuery("<div>", {'class': 'e2pdf-ib e2pdf-condition e2pdf-w100', 'data-condition_id': condition_id}).append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-action-duplicate'}).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-admin-page'})
                        ),
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-action-delete'}).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-no'})
                        ),
                        jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(
                        grid
                        )
                        );
                return new_condition;
            }
        },
        // e2pdf.actions.get
        get: function (el) {
            var actions = [];
            if (typeof el.data('data-actions') !== 'undefined') {
                actions = JSON.parse(el.data('data-actions'));
                actions = Object.values(actions).sort(function (a, b) {
                    if (parseInt(a.order) === parseInt(b.order)) {
                        return 0;
                    }
                    return parseInt(a.order) < parseInt(b.order) ? -1 : 1;
                });
            }
            return actions;
        },
        // e2pdf.actions.apply
        apply: function (el, data) {
            if (!data) {
                data = [];
            }
            el.data('data-actions', JSON.stringify(data));
        },
        // e2pdf.actions.getField
        getField: function (field, el, action_id, action) {
            var obj = false;
            switch (field) {
                case 'order':
                    var value = action ? action.order : '0';
                    obj = {
                        'name': e2pdf.lang.get('Sort'),
                        'key': 'actions[' + action_id + '][order]',
                        'type': 'text',
                        'value': value,
                        'atts': ['number']
                    };
                    break;
                case 'format':
                    var value = action && typeof action.format !== 'undefined' ? action.format : 'replace';
                    obj = {
                        'name': e2pdf.lang.get('Format'),
                        'key': 'actions[' + action_id + '][format]',
                        'type': 'select',
                        'value': value,
                        'options': [
                            {'insert_before': e2pdf.lang.get('Insert Before')},
                            {'insert_after': e2pdf.lang.get('Insert After')},
                            {'replace': e2pdf.lang.get('Full Replacement')},
                            {'search': e2pdf.lang.get('Search / Replace')},
                        ],
                        'atts': []
                    };
                    break;
                case 'search':
                    var value = action && typeof action.search !== 'undefined' ? action.search : '';
                    obj = {
                        'name': 'Search',
                        'key': 'actions[' + action_id + '][search]',
                        'type': 'textarea',
                        'value': value,
                        'atts': []
                    };
                    break;
                case 'action':
                    if (el.data('data-type') == 'e2pdf-tpl') {
                        var value = action ? action.action : 'access_by_url';
                        var options = [
                            {'access_by_url': e2pdf.lang.get('Allow PDF Access By URL')},
                            {'restrict_access_by_url': e2pdf.lang.get('Restrict PDF Access By URL')},
                            {'process_shortcodes': e2pdf.lang.get('Process Shortcodes')},
                            {'restrict_process_shortcodes': e2pdf.lang.get('Restrict Process Shortcodes')}
                        ];
                    } else if (el.data('data-type') == 'e2pdf-page') {
                        var value = action ? action.action : 'hide';
                        var options = [
                            {'hide': e2pdf.lang.get('Hide Page')},
                            {'show': e2pdf.lang.get('Show Page')},
                            {'change': e2pdf.lang.get('Change Property')}
                        ];
                    } else {
                        var value = action ? action.action : 'hide';
                        var options = [
                            {'hide': e2pdf.lang.get('Hide Element')},
                            {'show': e2pdf.lang.get('Show Element')},
                            {'change': e2pdf.lang.get('Change Property')}
                        ];
                    }
                    obj = {
                        'name': e2pdf.lang.get('Action'),
                        'key': 'actions[' + action_id + '][action]',
                        'type': 'select',
                        'value': value,
                        'options': options,
                        'atts': []
                    };
                    break;
                case 'error_message':
                    if (el.data('data-type') == 'e2pdf-tpl') {
                        var value = action ? action.error_message : '';
                        obj = {
                            'name': e2pdf.lang.get('Error Message'),
                            'key': 'actions[' + action_id + '][error_message]',
                            'type': 'textarea',
                            'value': value,
                            'atts': []
                        };
                    }
                    break;
                case 'else':
                    if (el.data('data-type') == 'e2pdf-page') {
                        var options = [
                            {'': '-'},
                            {'hide': e2pdf.lang.get('Hide Page')}
                        ];
                    } else {
                        var options = [
                            {'': '-'},
                            {'hide': e2pdf.lang.get('Hide Element')}
                        ];
                    }

                    var value = action && typeof action.else !== 'undefined' ? action.else : 'hide';
                    obj = {
                        'name': e2pdf.lang.get('Else'),
                        'key': 'actions[' + action_id + '][else]',
                        'type': 'select',
                        'options': options,
                        'value': value,
                        'atts': []
                    };
                    break;
                case 'property':
                    var value = action ? action.property : '';
                    if (action && action.action === 'change') {
                        var options = [];
                        var option = {
                            '': e2pdf.lang.get('--- Select ---')
                        };
                        options.push(option);
                        var groups = e2pdf.properties.getFields(el);
                        for (var group_key in groups) {
                            var group = groups[group_key];
                            for (var field_key in group.fields) {
                                var group_field = group.fields[field_key];
                                if (jQuery.inArray('uneditable', group_field.atts) === -1 && (jQuery.inArray('readonly', group_field.atts) === -1 || (jQuery.inArray('readonly', group_field.atts) !== -1 && jQuery.inArray('editable', group_field.atts) !== -1))) {
                                    var option = {};

                                    if (group_field.key == 'g_multiline') {
                                        option[group_field.key] = e2pdf.lang.get('Type');
                                    } else if (group.classes && group.classes[field_key] && group.classes[field_key].includes("e2pdf-hide-label") && group_field.key != 'html_worker') {
                                        option[group_field.key] = group.name;
                                    } else {
                                        option[group_field.key] = group.name + ' ' + group_field.name;
                                    }
                                    options.push(option);
                                }
                            }
                        }

                        obj = {
                            'name': e2pdf.lang.get('Property'),
                            'key': 'actions[' + action_id + '][property]',
                            'type': 'select',
                            'value': value,
                            'options': options,
                            'atts': []
                        };
                    }
                    break;
                case 'change':
                    var value = action ? action.change : '';
                    if (
                            action
                            && typeof action.action !== 'undefined'
                            && action.action === 'change'
                            && typeof action.property !== 'undefined'
                            && action.property !== ''
                            ) {

                        var property = e2pdf.properties.getField(action.property, el);
                        if (property) {
                            if (action.property == 'value'
                                    && typeof action.format !== 'undefined'
                                    && (action.format == 'insert_before' || action.format == 'insert_after')) {
                                property.name = e2pdf.lang.get('Value');
                            } else if (property.type != 'checkbox') {
                                property.name = e2pdf.lang.get('Change to');
                            }
                            property.key = 'actions[' + action_id + '][change]';
                            if (jQuery.inArray('readonly', property.atts) !== -1 && jQuery.inArray('editable', property.atts) !== -1) {
                                property.atts.splice(jQuery.inArray('readonly', property.atts), 1);
                            }
                            if (jQuery.inArray('readonly', property.atts) === -1) {
                                if (property.type === 'select') {
                                    for (var key in property.options) {
                                        if (Object.keys(property.options[key])[0] == value) {
                                            property.value = value;
                                            break;
                                        }
                                    }
                                } else if (jQuery.inArray('number', property.atts) !== -1) {
                                    if (value === '') {
                                        property.value = 0;
                                    } else {
                                        property.value = value;
                                    }
                                } else {
                                    property.value = value;
                                }
                            }
                            obj = property;
                        }
                    }
                    break;
                case 'apply':
                    var value = action ? action.apply : 'any';
                    obj = {
                        'name': e2pdf.lang.get('Apply If'),
                        'key': 'actions[' + action_id + '][apply]',
                        'type': 'select',
                        'value': value,
                        'options': [
                            {'any': e2pdf.lang.get('Any')},
                            {'all': e2pdf.lang.get('All')}
                        ],
                        'atts': []
                    };
                    break;
            }
            return obj;
        },
        // e2pdf.actions.getFields
        getFields: function (el, action_id, action) {
            var obj = {
                'action': {
                    'fields': [
                        e2pdf.actions.getField('order', el, action_id, action),
                        e2pdf.actions.getField('action', el, action_id, action),
                        e2pdf.actions.getField('property', el, action_id, action),
                        e2pdf.actions.getField('apply', el, action_id, action)
                    ],
                    'classes': [
                        'e2pdf-w10 e2pdf-pr5 e2pdf-action-order',
                        'e2pdf-w35 e2pdf-pr5 e2pdf-action-action',
                        'e2pdf-w40 e2pdf-pl5 e2pdf-pr5 e2pdf-action-property',
                        'e2pdf-w15 e2pdf-pl5'
                    ]
                }
            };
            if (
                    action
                    && typeof action.action !== 'undefined'
                    && action.action === 'change'
                    && typeof action.property !== 'undefined'
                    && action.property !== ''
                    ) {
                if (action.property == 'value' && typeof action.format !== 'undefined' && action.format == 'search') {
                    obj.action.fields.push(e2pdf.actions.getField('search', el, action_id, action));
                    obj.action.classes.push('e2pdf-w100');
                }

                obj.action.fields.push(e2pdf.actions.getField('change', el, action_id, action));
                obj.action.classes.push('e2pdf-w100 e2pdf-action-change');
                if (action.property == 'value') {
                    obj.action.fields.push(e2pdf.actions.getField('format', el, action_id, action));
                    obj.action.classes.push('e2pdf-w100 e2pdf-action-format');
                }
            }

            if (action && action.action == 'show') {
                obj.action.fields.push(e2pdf.actions.getField('else', el, action_id, action));
                obj.action.classes.push('e2pdf-w100');
            }

            if (el.data('data-type') == 'e2pdf-tpl') {
                if (action && (action.action == 'process_shortcodes' || action.action == 'restrict_process_shortcodes')) {

                } else {
                    obj.action.fields.push(e2pdf.actions.getField('error_message', el, action_id, action));
                    obj.action.classes.push('e2pdf-w100 e2pdf-action-message');
                }
            }

            return obj;
        },
        // e2pdf.actions.renderField
        renderField: function (el, action_id, action) {
            var conditions = jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100 e2pdf-conditions'});
            if (action) {
                // Backward Compatibility for Merge option
                if (typeof action.action !== 'undefined' && action.action == 'merge') {
                    action.action = 'change';
                    action.format = 'insert_after';
                }
                for (var condition in action.conditions) {
                    conditions.append(e2pdf.actions.conditions.renderField(el, action_id, condition, action.conditions[condition]));
                }
            } else {
                conditions.append(e2pdf.actions.conditions.renderField(el, action_id, 1));
            }

            var groups = e2pdf.actions.getFields(el, action_id, action);
            if (groups) {
                for (var group_key in groups) {

                    var group = groups[group_key];
                    var grid = jQuery('<div>', {'class': 'e2pdf-grid'});
                    for (var field_key in group.fields) {
                        var group_field = group.fields[field_key];
                        var classes = '';
                        if (group.classes) {
                            if (group.classes[field_key]) {
                                classes = group.classes[field_key];
                            }
                        }

                        var field = '';
                        var label = '';
                        var wrap = '';
                        var placeholder = group_field.placeholder ? group_field.placeholder : '';
                        if (group_field.type === 'text') {
                            label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                            field = jQuery('<input>', {'type': 'text', 'class': 'e2pdf-w100', 'name': group_field.key, 'value': group_field.value});
                        } else if (group_field.type === 'hidden') {
                            field = jQuery('<input>', {'type': 'hidden', 'name': group_field.key, 'value': group_field.value});
                        } else if (group_field.type === 'textarea') {
                            label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                            field = jQuery('<textarea>', {'name': group_field.key, 'class': 'e2pdf-w100', 'rows': '5', 'placeholder': placeholder}).val(group_field.value);
                        } else if (group_field.type === 'checkbox') {
                            wrap = jQuery('<label>', {'class': 'e2pdf-label e2pdf-small e2pdf-mt10'}).html(group_field.name);
                            field = jQuery('<input>', {'type': 'checkbox', 'class': 'e2pdf-ib', 'name': group_field.key, 'value': group_field.option});
                            if (group_field.value == group_field.option) {
                                field.prop('checked', true);
                            }
                        } else if (group_field.type === 'color') {
                            wrap = jQuery('<div>', {'class': 'e2pdf-colorpicker-wr'});
                            label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                            field = jQuery('<input>', {'class': 'e2pdf-color-picker e2pdf-color-picker-load e2pdf-w100', 'type': 'text', 'name': group_field.key, 'value': group_field.value});
                        } else if (group_field.type === 'select') {
                            label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                            field = jQuery('<select>', {'class': 'e2pdf-w100', 'name': group_field.key});
                            for (var option_key in group_field.options) {
                                field.append(jQuery('<option>', {'value': Object.keys(group_field.options[option_key])[0]}).html(Object.values(group_field.options[option_key])[0]));
                            }
                            field.val(group_field.value);
                        }

                        for (var att_key in group_field.atts) {
                            var att = group_field.atts[att_key];
                            switch (att) {
                                case 'readonly':
                                    field.attr('readonly', 'readonly');
                                    break;
                                case 'number':
                                    field.addClass('e2pdf-numbers e2pdf-number-negative e2pdf-number-positive');
                                    break;
                                case 'autocomplete':
                                    wrap = jQuery('<div>', {'class': 'e2pdf-rel e2pdf-w100'});
                                    field.addClass('e2pdf-autocomplete-cl');
                                    field.autocomplete({
                                        source: group_field.source,
                                        minLength: 0,
                                        appendTo: wrap,
                                        open: function () {
                                            jQuery(this).autocomplete("widget").addClass("e2pdf-autocomplete");
                                        },
                                        classes: {
                                            "ui-autocomplete": "e2pdf-autocomplete"
                                        }
                                    });
                                    break;
                            }
                        }

                        if (!wrap) {
                            wrap = field;
                        } else {
                            wrap.prepend(field);
                        }

                        grid.append(jQuery('<div>', {'class': 'e2pdf-ib ' + classes}).append(label, wrap));
                    }
                }
            }

            var new_action = jQuery("<div>", {'class': 'e2pdf-ib e2pdf-rel e2pdf-w100 e2pdf-action', 'data-action_id': action_id}).append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-action-duplicate'}).append(
                    jQuery('<i>', {'class': 'dashicons dashicons-admin-page'})
                    ),
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-link e2pdf-action-delete'}).append(
                    jQuery('<i>', {'class': 'dashicons dashicons-no'})
                    ),
                    jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(
                    grid
                    ),
                    conditions
                    );
            return new_action;
        },
        // e2pdf.actions.renderFields
        renderFields: function (el) {

            var add_action = jQuery('<div>', {'class': 'e2pdf-action-add-wrapper'}).append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'button-primary button-small e2pdf-action-add'}).html(e2pdf.lang.get('Add Action'))
                    );
            var block = jQuery('<div>', {'class': 'e2pdf-actions-wrapper e2pdf-mt5 e2pdf-w100'});
            var action_wrapper = jQuery('<div>', {'class': 'e2pdf-actions'});
            var actions = e2pdf.actions.get(el);
            for (var action in actions) {
                // Backward compatiability Show Element / Page
                if (actions[action].action == 'show') {
                    if (typeof actions[action].else === 'undefined') {
                        actions[action].else = '';
                    }
                }
                action_wrapper.append(e2pdf.actions.renderField(el, action, actions[action]));
            }

            block.append(action_wrapper, add_action);
            return block;
        }
    },
    // e2pdf.properties
    properties: {
        // e2pdf.properties.getLink
        getLink: function (title, href, classes, collapse) {
            obj = {
                'name': title,
                'key': 'link',
                'type': 'link',
                'value': href,
                'classes': classes,
                'collapse': collapse,
                'atts': [
                    'readonly',
                    'collapse'
                ]
            };
            return obj;
        },
        // e2pdf.properties.getField
        getField: function (field, el) {

            var obj = false;
            var properties = e2pdf.properties.get(el);
            var children = e2pdf.element.children(el);
            switch (field) {
                case 'page_id':
                    obj = {
                        'name': e2pdf.lang.get('Page ID'),
                        'type': 'text',
                        'value': el.data('data-type') === 'e2pdf-page' ? e2pdf.helper.getInt(el.attr('data-page_id')) : e2pdf.helper.getInt(el.closest('.e2pdf-page').attr('data-page_id')),
                        'atts': [
                            'readonly',
                            'number'
                        ]
                    };
                    if (el.data('data-type') !== 'e2pdf-page') {
                        obj.atts.push('editable');
                    }
                    break;
                case 'element_id':
                    obj = {
                        'name': 'ID',
                        'type': 'text',
                        'value': e2pdf.helper.getInt(el.attr('data-element_id')),
                        'atts': ['readonly']
                    };
                    break;
                case 'element_type':
                    obj = {
                        'name': e2pdf.lang.get('Type'),
                        'type': 'text',
                        'value': el.data('data-type'),
                        'atts': ['readonly']
                    };
                    break;
                case 'width':
                    obj = {
                        'name': e2pdf.lang.get('Width'),
                        'type': 'text',
                        'value': el.data('data-type') == 'e2pdf-page' ? e2pdf.helper.getFloat(el.attr('data-width')) : e2pdf.helper.getFloat(el.css('width')),
                        'atts': ['number']
                    };
                    if (el.data('data-type') == 'e2pdf-page' && e2pdf.pdf.settings.get('pdf')) {
                        obj.atts.push('readonly');
                    }
                    break;
                case 'height':
                    obj = {
                        'name': e2pdf.lang.get('Height'),
                        'type': 'text',
                        'value': el.data('data-type') == 'e2pdf-page' ? e2pdf.helper.getFloat(el.attr('data-height')) : e2pdf.helper.getFloat(el.css('height')),
                        'atts': ['number']
                    };
                    if (el.data('data-type') == 'e2pdf-page' && e2pdf.pdf.settings.get('pdf')) {
                        obj.atts.push('readonly');
                    }
                    break;
                case 'top':
                    obj = {
                        'name': e2pdf.lang.get('Position Top'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(el.css('top')),
                        'atts': ['number']
                    };
                    break;
                case 'left':
                    obj = {
                        'name': e2pdf.lang.get('Position Left'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(el.css('left')),
                        'atts': ['number']
                    };
                    break;
                case 'name':
                    obj = {
                        'name': e2pdf.lang.get('Field Name'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'field_name':
                    obj = {
                        'name': e2pdf.lang.get('As Field Name'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'dynamic_height':
                    obj = {
                        'name': e2pdf.lang.get('Dynamic Height'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'multipage':
                    obj = {
                        'name': e2pdf.lang.get('Multipage'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'hide_if_empty':
                    obj = {
                        'name': e2pdf.lang.get('Hide (If Empty)'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'hide_page_if_empty':
                    obj = {
                        'name': e2pdf.lang.get('Hide Page (If Empty)'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'nl2br':
                    obj = {
                        'name': e2pdf.lang.get('New Lines to BR'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'preload_img':
                    obj = {
                        'name': e2pdf.lang.get('Preload Images'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'text_color':
                    obj = {
                        'name': e2pdf.lang.get('Font Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'color':
                    obj = {
                        'name': e2pdf.lang.get('Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'text_type':
                    obj = {
                        'name': e2pdf.lang.get('Type'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'check'),
                        'options': [
                            {'check': 'Check'},
                            {'circle': 'Circle'},
                            {'cross': 'Cross'},
                            {'diamond': 'Diamond'},
                            {'square': 'Square'},
                            {'star': 'Star'}
                        ]
                    };
                    break;
                case 'text_font':
                    obj = {
                        'name': e2pdf.lang.get('Font'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.fonts(field)
                    };
                    break;
                case 'text_auto_font_size':
                    if ((el.data('data-type') == 'e2pdf-textarea' || el.data('data-type') == 'e2pdf-input' || el.data('data-type') == 'e2pdf-select') && e2pdf.helper.getString(properties['text_font_size']) == '-1') {
                        var value = '1';
                    } else {
                        var value = e2pdf.helper.getCheckbox(properties[field]);
                    }
                    obj = {
                        'name': e2pdf.lang.get('Auto Font Size'),
                        'type': 'checkbox',
                        'value': value,
                        'option': '1'
                    };
                    break;
                case 'text_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field, properties)
                    };
                    break;
                case 'text_line_height':
                    obj = {
                        'name': e2pdf.lang.get('Line Height'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.lines(field)
                    };
                    break;
                case 'text_letter_spacing':
                    obj = {
                        'name': e2pdf.lang.get('Char Spacing'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'z_index':
                    obj = {
                        'name': 'Z-index',
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'background':
                    obj = {
                        'name': e2pdf.lang.get('Background'),
                        'key': 'background',
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'border':
                    obj = {
                        'name': e2pdf.lang.get('Border'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'border_top':
                    obj = {
                        'name': e2pdf.lang.get('Border Top'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'border_left':
                    obj = {
                        'name': e2pdf.lang.get('Border Left'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'border_right':
                    obj = {
                        'name': e2pdf.lang.get('Border Right'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'border_bottom':
                    obj = {
                        'name': e2pdf.lang.get('Border Bottom'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'border_radius':
                    obj = {
                        'name': e2pdf.lang.get('Border Radius'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'border_color':
                    obj = {
                        'name': e2pdf.lang.get('Border Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field], '#000000'),
                    };
                    break;
                case 'padding_top':
                    obj = {
                        'name': e2pdf.lang.get('Padding Top'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'padding_left':
                    obj = {
                        'name': e2pdf.lang.get('Padding Left'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'padding_right':
                    obj = {
                        'name': e2pdf.lang.get('Padding Right'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'padding_bottom':
                    obj = {
                        'name': e2pdf.lang.get('Padding Bottom'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'margin_top':
                    obj = {
                        'name': e2pdf.lang.get('Margin Top'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    if (el.data('data-type') == 'e2pdf-barcode') {
                        obj.atts.push('keep');
                    }
                    break;
                case 'margin_left':
                    obj = {
                        'name': e2pdf.lang.get('Margin Left'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    if (el.data('data-type') == 'e2pdf-barcode') {
                        obj.atts.push('keep');
                    }
                    break;
                case 'margin_right':
                    obj = {
                        'name': e2pdf.lang.get('Margin Right'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    if (el.data('data-type') == 'e2pdf-barcode') {
                        obj.atts.push('keep');
                    }
                    break;
                case 'margin_bottom':
                    obj = {
                        'name': e2pdf.lang.get('Margin Bottom'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    if (el.data('data-type') == 'e2pdf-barcode') {
                        obj.atts.push('keep');
                    }
                    break;
                case 'length':
                    obj = {
                        'name': e2pdf.lang.get('Length'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'comb':
                    obj = {
                        'name': e2pdf.lang.get('Comb'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'rtl':
                    obj = {
                        'name': e2pdf.lang.get('Direction'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options':
                                [
                                    {'': '-'},
                                    {'0': 'LTR'},
                                    {'1': 'RTL'}
                                ]
                    };
                    break;
                case 'required':
                    obj = {
                        'name': e2pdf.lang.get('Required'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'readonly':
                    obj = {
                        'name': e2pdf.lang.get('Read-Only'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'pass':
                    obj = {
                        'name': e2pdf.lang.get('Password'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'multiline':
                    obj = {
                        'name': e2pdf.lang.get('Multiline'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'dimension':
                    obj = {
                        'name': e2pdf.lang.get('Keep Image Ratio'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'block_dimension':
                    obj = {
                        'name': e2pdf.lang.get('Lock Aspect Ratio'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1',
                        'atts': []
                    };
                    if (el.data('data-type') === 'e2pdf-image') {
                        obj.atts.push('keep');
                    }
                    break;
                case 'keep_lower_size':
                    obj = {
                        'name': e2pdf.lang.get('Keep Lower Size'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'fill_image':
                    obj = {
                        'name': e2pdf.lang.get('Fill Image'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'pdf_page':
                    obj = {
                        'name': e2pdf.lang.get('Page'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'pdf_resample':
                    obj = {
                        'name': e2pdf.lang.get('Resolution'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], '100'),
                        'options':
                                [
                                    {'100': '72dpi'},
                                    {'125': '90dpi'},
                                    {'150': '108dpi'},
                                    {'175': '126dpi'},
                                    {'200': '144dpi'}
                                ]
                    };
                    break;
                case 'pdf_append':
                    obj = {
                        'name': e2pdf.lang.get('Append'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'vertical'),
                        'options':
                                [
                                    {'vertical': e2pdf.lang.get('Vertical')},
                                    {'horizontal': e2pdf.lang.get('Horizontal')},
                                    {'grid': e2pdf.lang.get('Grid')}
                                ]
                    };
                    break;
                case 'pdf_space':
                    obj = {
                        'name': e2pdf.lang.get('Space'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'pdf_border':
                    obj = {
                        'name': e2pdf.lang.get('Border'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'hl':
                    obj = {
                        'name': e2pdf.lang.get('Hide Label'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'only_image':
                    obj = {
                        'name': e2pdf.lang.get('Disable Text to Image'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'esig':
                    obj = {
                        'name': e2pdf.lang.get('E-Signature'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'esig_contact':
                    obj = {
                        'name': e2pdf.lang.get('Contact'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'esig_location':
                    obj = {
                        'name': e2pdf.lang.get('Location'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'esig_reason':
                    obj = {
                        'name': e2pdf.lang.get('Reason'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'placeholder':
                    obj = {
                        'name': e2pdf.lang.get('Placeholder'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'horizontal':
                    obj = {
                        'name': e2pdf.lang.get('Horizontal Align'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'left'),
                        'options':
                                [
                                    {'left': e2pdf.lang.get('Left')},
                                    {'center': e2pdf.lang.get('Center')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    break;
                case 'vertical':
                    obj = {
                        'name': e2pdf.lang.get('Vertical Align'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'top'),
                        'options':
                                [
                                    {'top': e2pdf.lang.get('Top')},
                                    {'middle': e2pdf.lang.get('Middle')},
                                    {'bottom': e2pdf.lang.get('Bottom')}
                                ]
                    };
                    break;
                case 'scale':
                    obj = {
                        'name': e2pdf.lang.get('Scale'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], '0'),
                        'options':
                                [
                                    {'0': e2pdf.lang.get('Auto')},
                                    {'1': e2pdf.lang.get('Width&Height')},
                                    {'2': e2pdf.lang.get('Width')},
                                    {'3': e2pdf.lang.get('Height')}
                                ]
                        ,
                        'atts': ['scale']
                    };
                    break;
                case 'text_align':
                    obj = {
                        'name': e2pdf.lang.get('Text Align'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options':
                                [
                                    {'': '-'},
                                    {'left': e2pdf.lang.get('Left')},
                                    {'center': e2pdf.lang.get('Center')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    if (el.data('data-type') === 'e2pdf-textarea' || el.data('data-type') === 'e2pdf-html' || el.data('data-type') === 'e2pdf-page-number') {
                        obj.options.push(
                                {'justify': e2pdf.lang.get('Justify')}
                        );
                    }
                    break;
                case 'page_number':
                    obj = {
                        'name': e2pdf.lang.get('Adjust Page Number'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'page_total':
                    obj = {
                        'name': e2pdf.lang.get('Adjust Page Total'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'precision':
                    obj = {
                        'name': e2pdf.lang.get('Precision'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'qrl'),
                        'options':
                                [
                                    {'qrl': e2pdf.lang.get('L - Smallest')},
                                    {'qrm': e2pdf.lang.get('M - Medium')},
                                    {'qrq': e2pdf.lang.get('Q - High')},
                                    {'qrh': e2pdf.lang.get('H - Best')}
                                ]
                    };
                    break;
                case 'wq':
                    obj = {
                        'name': e2pdf.lang.get('Quiet Zone Size'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'keep']
                    };
                    break;
                case 'format':
                    obj = {
                        'name': e2pdf.lang.get('Format'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'upc-a'),
                        'options':
                                [
                                    {'upc-a': 'UPC-A'},
                                    {'upc-e': 'UPC-E'},
                                    {'ean-8': 'EAN-8'},
                                    {'ean-13': 'EAN-13'},
                                    {'ean-13-pad': 'EAN-13 PAD'},
                                    {'ean-13-nopad': 'EAN-13 NOPAD'},
                                    {'ean-128': 'EAN-128'},
                                    {'code-39': 'CODE-39'},
                                    {'code-39-ascii': 'CODE-39 ASCII'},
                                    {'code-93': 'CODE-93'},
                                    {'code-93-ascii': 'CODE-93 ASCII'},
                                    {'code-128': 'CODE-128'},
                                    {'codabar': 'CODEBAR'},
                                    {'itf': 'ITF'},
                                    {'dmtx': 'DMTX'},
                                    {'dmtx-s': 'DMTX S'},
                                    {'dmtx-r': 'DMTX R'},
                                    {'gs1-dmtx': 'GS1 DMTX'},
                                    {'gs1-dmtx-s': 'GS1 DMTX S'},
                                    {'gs1-dmtx-r': 'GS1 DMTX R'}
                                ]
                    };
                    break;
                case 'rotation':
                    obj = {
                        'name': e2pdf.lang.get('Rotation'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], '0'),
                        'options':
                                [
                                    {'0': '0'},
                                    {'90': '90'},
                                    {'180': '180'},
                                    {'270': '270'}
                                ]
                    };
                    break;
                case 'quality':
                    obj = {
                        'name': e2pdf.lang.get('Optimization'),
                        'type': 'select',
                        'value': e2pdf.helper.getInt(properties[field], '0'),
                        'options':
                                [
                                    {'0': e2pdf.lang.get('Inherit')},
                                    {'-1': e2pdf.lang.get('Not Optimized')},
                                    {'1': e2pdf.lang.get('Low Quality')},
                                    {'2': e2pdf.lang.get('Basic Quality')},
                                    {'3': e2pdf.lang.get('Good Quality')},
                                    {'4': e2pdf.lang.get('Best Quality')},
                                    {'5': e2pdf.lang.get('Ultra Quality')},
                                ]
                    };
                    break;
                case 'opacity':
                    obj = {
                        'name': e2pdf.lang.get('Opacity'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(properties[field]),
                        'atts': ['number', 'keep']
                    };
                    break;
                case 'parent':
                    var options = [];
                    var option = {
                        '': e2pdf.lang.get('--- Select ---')
                    };
                    options.push(option);
                    if (el.data('data-type') === 'e2pdf-html') {
                        jQuery('.e2pdf-tpl').find('.e2pdf-html').each(function () {
                            if (!jQuery(this).is(children)) {
                                var parent = jQuery(this).parent().attr('data-element_id');
                                var option = {};
                                option[parent] = parent;
                                options.push(option);
                            }
                        });
                    }
                    obj = {
                        'name': e2pdf.lang.get('Parent'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': options
                    };
                    break;
                case 'group':
                    var source = [];
                    jQuery('.e2pdf-tpl').find('.e2pdf-radio').each(function () {
                        if (!jQuery(this).is(children)) {
                            var radio = jQuery(this).parent();
                            var group = e2pdf.properties.getValue(radio, 'group', 'string');
                            if (source.indexOf(group) === -1) {
                                source.push(group);
                            }
                        }
                    });
                    obj = {
                        'name': e2pdf.lang.get('Group'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field]),
                        'source': source,
                        'atts': ['autocomplete']
                    };
                    break;
                case 'option':
                    obj = {
                        'name': e2pdf.lang.get('Option'),
                        'type': 'textarea',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'options':
                    obj = {
                        'name': e2pdf.lang.get('Options'),
                        'type': 'textarea',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'value':
                    if (el.data('data-type') === 'e2pdf-html') {
                        if (children.is('textarea')) {
                            var value = children.val();
                        } else {
                            var value = children.html();
                        }
                    } else if (el.data('data-type') === 'e2pdf-input' || el.data('data-type') === 'e2pdf-textarea') {
                        var value = children.val();
                    } else {
                        var value = properties.hasOwnProperty('value') ? properties['value'] : '';
                    }
                    var name = e2pdf.lang.get('Value');
                    if (el.data('data-type') === 'e2pdf-image') {
                        name = e2pdf.lang.get('Image');
                    } else if (el.data('data-type') === 'e2pdf-link') {
                        name = e2pdf.lang.get('Link URL');
                    }
                    obj = {
                        'name': name,
                        'type': 'textarea',
                        'value': value
                    };
                    break;
                case 'css':
                    obj = {
                        'name': 'CSS',
                        'type': 'textarea',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'preset':
                    var options = [];
                    var option = {
                        '': e2pdf.lang.get('--- Select ---')
                    };
                    options.push(option);
                    for (var size in e2pdf_params['template_sizes']) {
                        var option = {};
                        option[size] = size + ' (' + e2pdf_params['template_sizes'][size]['width'] + 'x' + e2pdf_params['template_sizes'][size]['height'] + ')';
                        options.push(option);
                    }
                    obj = {
                        'name': e2pdf.lang.get('Size'),
                        'type': 'select',
                        'value': '',
                        'options': options,
                        'atts': [
                            'uneditable'
                        ]
                    };
                    if (e2pdf.pdf.settings.get('pdf')) {
                        obj.atts.push('disabled');
                    }
                    break;
                case 'highlight':
                    obj = {
                        'name': e2pdf.lang.get('Highlight'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'none'),
                        'options': [
                            {'none': e2pdf.lang.get('None')},
                            {'invert': e2pdf.lang.get('Invert')},
                            {'outline': e2pdf.lang.get('Outline')},
                            {'push': e2pdf.lang.get('Push')}
                        ]
                    };
                    break;
                case 'link_url':
                    obj = {
                        'name': e2pdf.lang.get('Link URL'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field], ''),
                    };
                    break;
                case 'link_type':
                    obj = {
                        'name': e2pdf.lang.get('Link Type'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], ''),
                        'options': [
                            {'': e2pdf.lang.get('Url')},
                            {'attachment': e2pdf.lang.get('Attachment')},
                        ]
                    };
                    break;
                case 'link_label':
                    obj = {
                        'name': e2pdf.lang.get('Link Label'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field], ''),
                    };
                    break;
                case 'underline':
                    obj = {
                        'name': e2pdf.lang.get('Underline'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'underline':
                    obj = {
                        'name': e2pdf.lang.get('Underline'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'preg_pattern':
                    obj = {
                        'name': e2pdf.lang.get('Preg Replace Pattern'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'preg_replacement':
                    obj = {
                        'name': e2pdf.lang.get('Preg Replace Replacement'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'preg_match_all_pattern':
                    obj = {
                        'name': e2pdf.lang.get('Preg Match All Pattern'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'preg_match_all_output':
                    obj = {
                        'name': e2pdf.lang.get('Preg Match All Output'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'css_priority':
                    obj = {
                        'name': e2pdf.lang.get('CSS Priority'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'css_style':
                    var options = [];
                    var option = {
                        '': '--- ' + e2pdf.lang.get('CSS Style') + ' ---'
                    };
                    options.push(option);
                    e2pdf_params['css_styles'].forEach(function (css_style) {
                        var option = {};
                        option[css_style] = css_style;
                        options.push(option);
                    });
                    obj = {
                        'name': e2pdf.lang.get('CSS Style'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], ''),
                        'options': options
                    };
                    break;
                case 'html_worker':
                    obj = {
                        'name': 'HTML Worker',
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], ''),
                        'options': [
                            {'': 'HTML Worker: v1'},
                            {'1': 'HTML Worker: v2'},
                            {'3': 'HTML Worker: v3 (BETA)'}
                        ]
                    };
                    break;
                case 'wysiwyg_disable':
                    obj = {
                        'name': e2pdf.lang.get('Disable WYSIWYG Editor'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_type':
                    obj = {
                        'name': e2pdf.lang.get('Type'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'BarGraph'),
                        'options':
                                [
                                    {'BarGraph': 'BarGraph'},
                                    {'Bar3DGraph': 'Bar3DGraph'},
                                    {'BarAndLineGraph': 'BarAndLineGraph'},
                                    {'BoxAndWhiskerGraph': 'BoxAndWhiskerGraph'},
                                    {'BubbleGraph': 'BubbleGraph'},
                                    {'CylinderGraph': 'CylinderGraph'},
                                    {'CandlestickGraph': 'CandlestickGraph'},
                                    {'DonutGraph': 'DonutGraph'},
                                    {'Donut3DGraph': 'Donut3DGraph'},
                                    {'ExplodedPieGraph': 'ExplodedPieGraph'},
                                    {'ExplodedPie3DGraph': 'ExplodedPie3DGraph'},
                                    {'ExplodedDonutGraph': 'ExplodedDonutGraph'},
                                    {'ExplodedDonut3DGraph': 'ExplodedDonut3DGraph'},
                                    {'ExplodedSemiDonutGraph': 'ExplodedSemiDonutGraph'},
                                    {'ExplodedSemiDonut3DGraph': 'ExplodedSemiDonut3DGraph'},
                                    {'FloatingBarGraph': 'FloatingBarGraph'},
                                    {'GroupedBarGraph': 'GroupedBarGraph'},
                                    {'GroupedBar3DGraph': 'GroupedBar3DGraph'},
                                    {'GroupedCylinderGraph': 'GroupedCylinderGraph'},
                                    {'Histogram': 'Histogram'},
                                    {'HorizontalBarGraph': 'HorizontalBarGraph'},
                                    {'HorizontalBar3DGraph': 'HorizontalBar3DGraph'},
                                    {'HorizontalStackedBarGraph': 'HorizontalStackedBarGraph'},
                                    {'HorizontalStackedBar3DGraph': 'HorizontalStackedBar3DGraph'},
                                    {'HorizontalGroupedBarGraph': 'HorizontalGroupedBarGraph'},
                                    {'HorizontalGroupedBar3DGraph': 'HorizontalGroupedBar3DGraph'},
                                    {'HorizontalFloatingBarGraph': 'HorizontalFloatingBarGraph'},
                                    {'LineGraph': 'LineGraph'},
                                    {'MultiLineGraph': 'MultiLineGraph'},
                                    {'MultiRadarGraph': 'MultiRadarGraph'},
                                    {'MultiScatterGraph': 'MultiScatterGraph'},
                                    {'MultiSteppedLineGraph': 'MultiSteppedLineGraph'},
                                    {'ParetoChart': 'ParetoChart'},
                                    {'PieGraph': 'PieGraph'},
                                    {'Pie3DGraph': 'Pie3DGraph'},
                                    {'PolarAreaGraph': 'PolarAreaGraph'},
                                    {'PolarArea3DGraph': 'PolarArea3DGraph'},
                                    {'PopulationPyramid': 'PopulationPyramid'},
                                    {'RadarGraph': 'RadarGraph'},
                                    {'ScatterGraph': 'ScatterGraph'},
                                    {'SemiDonutGraph': 'SemiDonutGraph'},
                                    {'SemiDonut3DGraph': 'SemiDonut3DGraph'},
                                    {'SteppedLineGraph': 'SteppedLineGraph'},
                                    {'StackedBarGraph': 'StackedBarGraph'},
                                    {'StackedBar3DGraph': 'StackedBar3DGraph'},
                                    {'StackedBarAndLineGraph': 'StackedBarAndLineGraph'},
                                    {'StackedCylinderGraph': 'StackedCylinderGraph'},
                                    {'StackedLineGraph': 'StackedLineGraph'},
                                    {'StackedGroupedBarGraph': 'StackedGroupedBarGraph'},
                                    {'StackedGroupedBar3DGraph': 'StackedGroupedBar3DGraph'},
                                    {'StackedGroupedCylinderGraph': 'StackedGroupedCylinderGraph'}
                                ]
                    };
                    break;
                case 'g_palette':
                    obj = {
                        'name': e2pdf.lang.get('Palette'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_stroke_colour':
                    obj = {
                        'name': e2pdf.lang.get('Line / Stroke Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_stroke_width':
                    obj = {
                        'name': e2pdf.lang.get('Width'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_graph_title':
                    obj = {
                        'name': e2pdf.lang.get('Title'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_graph_title_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field)
                    };
                    break;
                case 'g_label_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field)
                    };
                    break;
                case 'g_graph_title_space':
                    obj = {
                        'name': e2pdf.lang.get('Space'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_label_space':
                    obj = {
                        'name': e2pdf.lang.get('Space'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_graph_title_colour':
                    obj = {
                        'name': e2pdf.lang.get('Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_label_colour':
                    obj = {
                        'name': e2pdf.lang.get('Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_graph_title_position':
                    obj = {
                        'name': e2pdf.lang.get('Position'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'top'),
                        'options':
                                [
                                    {'top': e2pdf.lang.get('Top')},
                                    {'left': e2pdf.lang.get('Left')},
                                    {'right': e2pdf.lang.get('Right')},
                                    {'bottom': e2pdf.lang.get('Bottom')}
                                ]
                    };
                    break;
                case 'g_label_v':
                    obj = {
                        'name': e2pdf.lang.get('Vertical Label'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_label_h':
                    obj = {
                        'name': e2pdf.lang.get('Horizontal Label'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_data_label_type':
                    obj = {
                        'name': e2pdf.lang.get('Type'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'plain'),
                        'options':
                                [
                                    {'plain': 'Plain'},
                                    {'box': 'Box'},
                                    {'bubble': 'Bubble'},
                                    {'line': 'Line'},
                                    {'circle': 'Circle'},
                                    {'square': 'Square'},
                                    {'linecircle': 'LineCircle'},
                                    {'linebox': 'LineBox'},
                                    {'linesquare': 'LineSquare'},
                                    {'line2': 'Line2'}
                                ]
                    };
                    break;
                case 'g_key_sep':
                    obj = {
                        'name': 'Key Separator',
                        'type': 'text',
                        'value': properties.hasOwnProperty('g_key_sep') && properties['g_key_sep'] ? properties['g_key_sep'] : ' => '
                    };
                    break;
                case 'g_array_sep':
                    obj = {
                        'name': 'Array Separator',
                        'type': 'text',
                        'value': properties.hasOwnProperty('g_array_sep') && properties['g_array_sep'] ? properties['g_array_sep'] : ', '
                    };
                    break;
                case 'g_sub_array_sep':
                    obj = {
                        'name': 'Sub Array Separator',
                        'type': 'text',
                        'value': properties.hasOwnProperty('g_sub_array_sep') && properties['g_sub_array_sep'] ? properties['g_sub_array_sep'] : '|'
                    };
                    break;
                case 'g_structure_key':
                    obj = {
                        'name': 'Key',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_value':
                    obj = {
                        'name': 'Value',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_colour':
                    obj = {
                        'name': 'Color',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_axis_text':
                    obj = {
                        'name': 'Axis Text',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_legend_text':
                    obj = {
                        'name': 'Legend Text',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_label':
                    obj = {
                        'name': 'Label',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_area':
                    obj = {
                        'name': 'Area',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_open':
                    obj = {
                        'name': 'Open',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_end':
                    obj = {
                        'name': 'End',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_outliers':
                    obj = {
                        'name': 'Outliers',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_top':
                    obj = {
                        'name': 'Top',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_bottom':
                    obj = {
                        'name': 'Bottom',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_wtop':
                    obj = {
                        'name': 'Wtop',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_wbottom':
                    obj = {
                        'name': 'Wbottom',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_high':
                    obj = {
                        'name': 'High',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structure_low':
                    obj = {
                        'name': 'Low',
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_structured_data':
                    obj = {
                        'name': 'Structured Data',
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_axis_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'key': 'g_axis_font_size',
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field)
                    };
                    break;
                case 'g_axis_overlap':
                    obj = {
                        'name': e2pdf.lang.get('Axis Overlap'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_axis_stroke_width_v':
                    obj = {
                        'name': e2pdf.lang.get('Width'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_axis_stroke_width_h':
                    obj = {
                        'name': e2pdf.lang.get('Width'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_minimum_grid_spacing':
                    obj = {
                        'name': e2pdf.lang.get('Grid Spacing'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_minimum_grid_spacing_v':
                    obj = {
                        'name': e2pdf.lang.get('Grid Spacing (V)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_minimum_grid_spacing_h':
                    obj = {
                        'name': e2pdf.lang.get('Grid Spacing (H)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_grid_division_v':
                    obj = {
                        'name': e2pdf.lang.get('Grid Division (V)'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_grid_division_h':
                    obj = {
                        'name': e2pdf.lang.get('Grid Division (H)'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_axis_colour':
                    obj = {
                        'name': e2pdf.lang.get('Axis Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_grid_colour':
                    obj = {
                        'name': e2pdf.lang.get('Grid Color'),
                        'key': 'g_grid_colour',
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_bar_label_colour':
                    obj = {
                        'name': e2pdf.lang.get('Bar Label Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_bar_label_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field)
                    };
                    break;
                case 'g_bar_label_space':
                    obj = {
                        'name': e2pdf.lang.get('Space'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_bar_label_position_vertical':
                    obj = {
                        'name': e2pdf.lang.get('Position (V)'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'top'),
                        'options':
                                [
                                    {'top': e2pdf.lang.get('Top')},
                                    {'centre': e2pdf.lang.get('Middle')},
                                    {'bottom': e2pdf.lang.get('Bottom')}
                                ]
                    };
                    break;
                case 'g_bar_label_position_horizontal':
                    obj = {
                        'name': e2pdf.lang.get('Position (H)'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'centre'),
                        'options':
                                [
                                    {'left': e2pdf.lang.get('Left')},
                                    {'centre': e2pdf.lang.get('Center')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    break;
                case 'g_bar_label_position_join':
                    obj = {
                        'name': e2pdf.lang.get('Position'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'outer'),
                        'options':
                                [
                                    {'inner': 'Inner'},
                                    {'outer': 'Outer'}
                                ]
                    };
                    break;
                case 'g_show_bar_labels':
                    obj = {
                        'name': e2pdf.lang.get('Bar Labels'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_marker_type':
                    obj = {
                        'name': e2pdf.lang.get('Marker'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'circle'),
                        'options':
                                [
                                    {'circle': 'Circle'},
                                    {'square': 'Square'},
                                    {'triangle': 'Triangle'},
                                    {'cross': 'Cross'},
                                    {'x': 'X'},
                                    {'pentagon': 'Pentagon'},
                                    {'diamond': 'Diamond'},
                                    {'hexagon': 'Hexagon'},
                                    {'octagon': 'Octagon'},
                                    {'asterisk': 'Asterisk'},
                                    {'star': 'Star'},
                                    {'threestar': 'Threestar'},
                                    {'fourstar': 'Fourstar'},
                                    {'eightstar': 'Eightstar'}
                                ]
                    };
                    break;
                case 'g_marker_colour':
                    obj = {
                        'name': e2pdf.lang.get('Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_marker_size':
                    obj = {
                        'name': e2pdf.lang.get('Size'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_marker_dynamic_colour':
                    obj = {
                        'name': e2pdf.lang.get('Dynamic Marker Color'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_grid_subdivision_colour':
                    obj = {
                        'name': e2pdf.lang.get('Grid Subdivision Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_show_subdivisions':
                    obj = {
                        'name': e2pdf.lang.get('Sub Divisions'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_show_grid':
                    obj = {
                        'name': e2pdf.lang.get('Grid'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_show_axis_v':
                    obj = {
                        'name': e2pdf.lang.get('Axis (V)'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_show_axis_text_v':
                    obj = {
                        'name': e2pdf.lang.get('Text'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_show_axis_h':
                    obj = {
                        'name': e2pdf.lang.get('Axis (H)'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_show_axis_text_h':
                    obj = {
                        'name': e2pdf.lang.get('Text'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_axis_min_h':
                    obj = {
                        'name': e2pdf.lang.get('Min'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_axis_max_h':
                    obj = {
                        'name': e2pdf.lang.get('Max'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_axis_min_max_h':
                    obj = {
                        'name': e2pdf.lang.get('Enable'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_axis_min_v':
                    obj = {
                        'name': e2pdf.lang.get('Min'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_axis_max_v':
                    obj = {
                        'name': e2pdf.lang.get('Max'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_axis_min_max_v':
                    obj = {
                        'name': e2pdf.lang.get('Enable'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_show_grid_subdivisions':
                    obj = {
                        'name': e2pdf.lang.get('Sub Divisions'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_fill_under':
                    obj = {
                        'name': e2pdf.lang.get('Fill Under'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_reverse':
                    obj = {
                        'name': e2pdf.lang.get('Reverse'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_stroke_dynamic_colour':
                    obj = {
                        'name': e2pdf.lang.get('Dynamic Line / Stroke Color'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_sort':
                    obj = {
                        'name': e2pdf.lang.get('Sort'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_percentage':
                    obj = {
                        'name': e2pdf.lang.get('Percentage'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_legend_title':
                    obj = {
                        'name': e2pdf.lang.get('Title'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_legend_title_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field)
                    };
                    break;
                case 'g_legend_font_size':
                    obj = {
                        'name': e2pdf.lang.get('Font Size'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options': e2pdf.helper.font.sizes(field)
                    };
                    break;
                case 'g_legend_position_vertical':
                    obj = {
                        'name': e2pdf.lang.get('Position (V)'),
                        'key': 'g_legend_position_vertical',
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'top'),
                        'options':
                                [
                                    {'top': e2pdf.lang.get('Top')},
                                    {'middle': e2pdf.lang.get('Middle')},
                                    {'bottom': e2pdf.lang.get('Bottom')}
                                ]
                    };
                    break;
                case 'g_legend_position_horizontal':
                    obj = {
                        'name': e2pdf.lang.get('Position (H)'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'right'),
                        'options':
                                [
                                    {'left': e2pdf.lang.get('Left')},
                                    {'center': e2pdf.lang.get('Center')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    break;
                case 'g_legend_position_join':
                    obj = {
                        'name': e2pdf.lang.get('Position'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'inner'),
                        'options':
                                [
                                    {'inner': e2pdf.lang.get('Inner')},
                                    {'outer': e2pdf.lang.get('Outer')}
                                ]
                    };
                    break;
                case 'g_legend_position_vertical_margin':
                    obj = {
                        'name': e2pdf.lang.get('Margin (V)'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_legend_position_horizontal_margin':
                    obj = {
                        'name': e2pdf.lang.get('Margin (H)'),
                        'type': 'text',
                        'value': e2pdf.helper.getFloat(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_legend_text_side':
                    obj = {
                        'name': e2pdf.lang.get('Legend Text Side'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'left'),
                        'options':
                                [
                                    {'left': e2pdf.lang.get('Left')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    break;
                case 'g_legend_columns':
                    obj = {
                        'name': e2pdf.lang.get('Columns'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field], 1),
                        'atts': ['number']
                    };
                    break;
                case 'g_legend_entry_width':
                    obj = {
                        'name': e2pdf.lang.get('Width'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_legend_padding_x':
                    obj = {
                        'name': e2pdf.lang.get('Padding (X)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_legend_padding_y':
                    obj = {
                        'name': e2pdf.lang.get('Padding (Y)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_legend_stroke_colour':
                    obj = {
                        'name': e2pdf.lang.get('Stroke Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_legend_stroke_width':
                    obj = {
                        'name': e2pdf.lang.get('Stroke Width'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_legend_back_colour':
                    obj = {
                        'name': e2pdf.lang.get('Background'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_legend_colour':
                    obj = {
                        'name': e2pdf.lang.get('Color'),
                        'type': 'color',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_show_legend':
                    obj = {
                        'name': e2pdf.lang.get('Legend'),
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_bubble_scale':
                    obj = {
                        'name': e2pdf.lang.get('Bubble Scale'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]) ? e2pdf.helper.getInt(properties[field]) : '1',
                        'atts': ['number']
                    };
                    break;
                case 'g_increment':
                    obj = {
                        'name': e2pdf.lang.get('Increment'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_stack_group':
                    obj = {
                        'name': e2pdf.lang.get('Stack Group'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_line_dataset':
                    obj = {
                        'name': e2pdf.lang.get('Line Dataset'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_project_angle':
                    obj = {
                        'name': e2pdf.lang.get('Project Angle'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number']
                    };
                    break;
                case 'g_line_curve':
                    obj = {
                        'name': e2pdf.lang.get('Line Curve'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_units_y':
                    obj = {
                        'name': e2pdf.lang.get('Units'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_units_x':
                    obj = {
                        'name': e2pdf.lang.get('Units'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_axis_text_position_v':
                    obj = {
                        'name': e2pdf.lang.get('Position'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'outside'),
                        'options':
                                [
                                    {'outside': e2pdf.lang.get('Outer')},
                                    {'inside': e2pdf.lang.get('Inner')}
                                ]
                    };
                    break;
                case 'g_axis_text_position_h':
                    obj = {
                        'name': e2pdf.lang.get('Position'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], 'outside'),
                        'options':
                                [
                                    {'outside': e2pdf.lang.get('Outer')},
                                    {'inside': e2pdf.lang.get('Inner')}
                                ]
                    };
                    break;
                case 'g_axis_text_align_v':
                    obj = {
                        'name': e2pdf.lang.get('Align'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], ''),
                        'options':
                                [
                                    {'': e2pdf.lang.get('Auto')},
                                    {'left': e2pdf.lang.get('Left')},
                                    {'centre': e2pdf.lang.get('Center')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    break;
                case 'g_axis_text_align_h':
                    obj = {
                        'name': e2pdf.lang.get('Align'),
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field], ''),
                        'options':
                                [
                                    {'': e2pdf.lang.get('Auto')},
                                    {'left': e2pdf.lang.get('Left')},
                                    {'centre': e2pdf.lang.get('Center')},
                                    {'right': e2pdf.lang.get('Right')}
                                ]
                    };
                    break;
                case 'g_axis_text_space_v':
                    obj = {
                        'name': e2pdf.lang.get('Space'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field], 2),
                        'atts': ['number']
                    };
                    break;
                case 'g_axis_text_space_h':
                    obj = {
                        'name': e2pdf.lang.get('Space'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field], 2),
                        'atts': ['number']
                    };
                    break;
                case 'g_axis_text_offset_x_v':
                    obj = {
                        'name': e2pdf.lang.get('Offset (X)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_axis_text_offset_y_v':
                    obj = {
                        'name': e2pdf.lang.get('Offset (Y)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;

                case 'g_axis_text_offset_x_h':
                    obj = {
                        'name': e2pdf.lang.get('Offset (X)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_axis_text_offset_y_h':
                    obj = {
                        'name': e2pdf.lang.get('Offset (Y)'),
                        'type': 'text',
                        'value': e2pdf.helper.getInt(properties[field]),
                        'atts': ['number', 'number-negative']
                    };
                    break;
                case 'g_units_label':
                    obj = {
                        'name': e2pdf.lang.get('Units Label'),
                        'type': 'text',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_reverse_data':
                    obj = {
                        'name': 'Reverse Data',
                        'type': 'checkbox',
                        'value': e2pdf.helper.getCheckbox(properties[field]),
                        'option': '1'
                    };
                    break;
                case 'g_multiline':
                    obj = {
                        'name': 'Lines',
                        'type': 'select',
                        'value': e2pdf.helper.getString(properties[field]),
                        'options':
                                [
                                    {'0': 'Unsorted Data'},
                                    {'2': 'Single Array Data'},
                                    {'1': 'Multi Array Data'}
                                ]
                    };
                    break;
                case 'g_legends':
                    obj = {
                        'name': e2pdf.lang.get('Legends'),
                        'type': 'textarea',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
                case 'g_colors':
                    obj = {
                        'name': e2pdf.lang.get('Colors'),
                        'type': 'textarea',
                        'value': e2pdf.helper.getString(properties[field])
                    };
                    break;
            }
            if (!obj.hasOwnProperty('key')) {
                obj['key'] = field;
            }
            if (!obj.hasOwnProperty('atts')) {
                obj['atts'] = [];
            }
            return obj;
        },
        // e2pdf.properties.getFields
        getFields: function (el, actions) {

            if (el.data('data-type') === 'e2pdf-tpl') {
                var obj = {};
            } else if (el.data('data-type') === 'e2pdf-page') {
                var obj = {
                    'page_id': {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('page_id', el),
                            e2pdf.properties.getField('element_type', el)
                        ],
                        'position': 'top',
                        'classes': [
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pl10'
                        ]
                    },
                    'size': {
                        'name': e2pdf.lang.get('Size'),
                        'fields': [
                            e2pdf.properties.getField('width', el),
                            e2pdf.properties.getField('height', el),
                            e2pdf.properties.getField('preset', el)
                        ],
                        'position': 'top',
                        'classes': [
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100'
                        ]
                    }
                };
            } else {
                var obj = {
                    'element': {
                        'name': e2pdf.lang.get('Element'),
                        'fields': [
                            e2pdf.properties.getField('element_id', el),
                            e2pdf.properties.getField('page_id', el),
                            e2pdf.properties.getField('element_type', el),
                            e2pdf.properties.getField('left', el),
                            e2pdf.properties.getField('top', el),
                            e2pdf.properties.getField('width', el),
                            e2pdf.properties.getField('height', el)
                        ],
                        'position': 'top',
                        'classes': [
                            'e2pdf-w25',
                            'e2pdf-w25 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w25',
                            'e2pdf-w25 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w25 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w25'
                        ]
                    }
                };
                if (el.data('data-type') === 'e2pdf-input') {

                    obj['element'].fields.push(e2pdf.properties.getField('name', el));
                    obj['element'].fields.push(e2pdf.properties.getField('field_name', el));
                    obj['element'].classes.push('e2pdf-w75 e2pdf-pr10');
                    obj['element'].classes.push('e2pdf-w25 e2pdf-mt-label');
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_letter_spacing', el),
                            e2pdf.properties.getField('text_auto_font_size', el),
                            e2pdf.properties.getField('text_align', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('length', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('comb', el),
                            e2pdf.properties.getField('required', el),
                            e2pdf.properties.getField('readonly', el),
                            e2pdf.properties.getField('pass', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': e2pdf.lang.get('Value'),
                        'fields': [
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-hide-label',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-textarea') {

                    obj['element'].fields.push(e2pdf.properties.getField('name', el));
                    obj['element'].fields.push(e2pdf.properties.getField('field_name', el));
                    obj['element'].classes.push('e2pdf-w75 e2pdf-pr10');
                    obj['element'].classes.push('e2pdf-w25 e2pdf-mt-label');
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_letter_spacing', el),
                            e2pdf.properties.getField('text_auto_font_size', el),
                            e2pdf.properties.getField('text_line_height', el),
                            e2pdf.properties.getField('text_align', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('length', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('comb', el),
                            e2pdf.properties.getField('required', el),
                            e2pdf.properties.getField('readonly', el),
                            e2pdf.properties.getField('pass', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': e2pdf.lang.get('Value'),
                        'fields': [
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-hide-label',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-checkbox') {

                    obj['element'].fields.push(e2pdf.properties.getField('name', el));
                    obj['element'].fields.push(e2pdf.properties.getField('field_name', el));
                    obj['element'].classes.push('e2pdf-w75 e2pdf-pr10');
                    obj['element'].classes.push('e2pdf-w25 e2pdf-mt-label');
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_type', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('required', el),
                            e2pdf.properties.getField('readonly', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('option', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-strong-label',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-radio') {

                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_type', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('required', el),
                            e2pdf.properties.getField('readonly', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('group', el),
                            e2pdf.properties.getField('field_name', el),
                            e2pdf.properties.getField('option', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w75 e2pdf-strong-label e2pdf-pr10',
                            'e2pdf-w25 e2pdf-mt-label',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-select') {

                    obj['element'].fields.push(e2pdf.properties.getField('name', el));
                    obj['element'].fields.push(e2pdf.properties.getField('field_name', el));
                    obj['element'].classes.push('e2pdf-w75 e2pdf-pr10');
                    obj['element'].classes.push('e2pdf-w25 e2pdf-mt-label');
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_letter_spacing', el),
                            e2pdf.properties.getField('text_auto_font_size', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('multiline', el),
                            e2pdf.properties.getField('required', el),
                            e2pdf.properties.getField('readonly', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('options', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-strong-label',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-signature') {

                    obj['element'].fields.push(e2pdf.properties.getField('name', el));
                    obj['element'].fields.push(e2pdf.properties.getField('field_name', el));
                    obj['element'].classes.push('e2pdf-w75 e2pdf-pr10');
                    obj['element'].classes.push('e2pdf-w25 e2pdf-mt-label');
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('placeholder', el),
                            e2pdf.properties.getField('esig', el),
                            e2pdf.properties.getField('horizontal', el),
                            e2pdf.properties.getField('vertical', el),
                            e2pdf.properties.getField('dimension', el),
                            e2pdf.properties.getField('block_dimension', el),
                            e2pdf.properties.getField('keep_lower_size', el),
                            e2pdf.properties.getField('fill_image', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w60 e2pdf-pr10',
                            'e2pdf-w40',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_radius', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': e2pdf.lang.get('Value'),
                        'fields': [
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getField('only_image', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-hide-label',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-html') {
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_letter_spacing', el),
                            e2pdf.properties.getField('text_line_height', el),
                            e2pdf.properties.getField('text_align', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('vertical', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('multipage', el),
                            e2pdf.properties.getField('nl2br', el),
                            e2pdf.properties.getField('preload_img', el),
                            e2pdf.properties.getField('hide_if_empty', el),
                            e2pdf.properties.getField('hide_page_if_empty', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    
                    if (e2pdf.properties.getValue(el, 'dynamic_height', 'checkbox')) {
                        obj['field'].fields.push(e2pdf.properties.getField('dynamic_height', el));
                        obj['field'].classes.push('e2pdf-pr10');
                    }

                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('parent', el),
                            e2pdf.properties.getField('css', el),
                            e2pdf.properties.getField('css_priority', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getField('html_worker', el),
                            e2pdf.properties.getField('wysiwyg_disable', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-strong-label',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w20 e2pdf-pr10',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w30 e2pdf-pr10 e2pdf-hide-label',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w20 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-page-number') {
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_letter_spacing', el),
                            e2pdf.properties.getField('text_line_height', el),
                            e2pdf.properties.getField('text_align', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('vertical', el),
                            e2pdf.properties.getField('rtl', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10'
                        ]
                    };
                    obj['page_number'] = {
                        'name': e2pdf.lang.get('Page Number'),
                        'fields': [
                            e2pdf.properties.getField('page_number', el),
                            e2pdf.properties.getField('page_total', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('css', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getField('html_worker', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                            'e2pdf-w30 e2pdf-pr10'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-image') {

                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('horizontal', el),
                            e2pdf.properties.getField('vertical', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('opacity', el),
                            e2pdf.properties.getField('quality', el),
                            e2pdf.properties.getField('dimension', el),
                            e2pdf.properties.getField('block_dimension', el),
                            e2pdf.properties.getField('keep_lower_size', el),
                            e2pdf.properties.getField('fill_image', el),
                            e2pdf.properties.getField('hide_page_if_empty', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['pdf'] = {
                        'name': 'Pdf',
                        'fields': [
                            e2pdf.properties.getField('pdf_page', el),
                            e2pdf.properties.getField('pdf_resample', el),
                            e2pdf.properties.getField('pdf_append', el),
                            e2pdf.properties.getField('pdf_space', el),
                            e2pdf.properties.getField('pdf_border', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_radius', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('link_url', el),
                            e2pdf.properties.getField('link_type', el),
                            e2pdf.properties.getField('highlight', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getField('only_image', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w25 e2pdf-pr10',
                            'e2pdf-w25',
                            'e2pdf-w100',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-qrcode') {

                    obj['qrcode'] = {
                        'name': e2pdf.lang.get('QR Code'),
                        'fields': [
                            e2pdf.properties.getField('color', el),
                            e2pdf.properties.getField('precision', el),
                            e2pdf.properties.getField('wq', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('margin_top', el),
                            e2pdf.properties.getField('margin_left', el),
                            e2pdf.properties.getField('margin_right', el),
                            e2pdf.properties.getField('margin_bottom', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': e2pdf.lang.get('Value'),
                        'fields': [
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-hide-label',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-barcode') {
                    obj['barcode'] = {
                        'name': e2pdf.lang.get('Barcode'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_line_height', el),
                            e2pdf.properties.getField('color', el),
                            e2pdf.properties.getField('format', el),
                            e2pdf.properties.getField('wq', el),
                            e2pdf.properties.getField('horizontal', el),
                            e2pdf.properties.getField('vertical', el),
                            e2pdf.properties.getField('scale', el),
                            e2pdf.properties.getField('rotation', el),
                            e2pdf.properties.getField('dimension', el),
                            e2pdf.properties.getField('hl', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w60 e2pdf-pr10',
                            'e2pdf-w40',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('margin_top', el),
                            e2pdf.properties.getField('margin_left', el),
                            e2pdf.properties.getField('margin_right', el),
                            e2pdf.properties.getField('margin_bottom', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100'
                        ]
                    };
                    obj['value'] = {
                        'name': e2pdf.lang.get('Value'),
                        'fields': [
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-hide-label',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-graph') {
                    obj['graph'] = {
                        'name': e2pdf.lang.get('Graph'),
                        'fields': [
                            e2pdf.properties.getField('g_type', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('g_palette', el),
                            e2pdf.properties.getField('g_stroke_colour', el),
                            e2pdf.properties.getField('g_stroke_width', el),
                            e2pdf.properties.getField('g_stroke_dynamic_colour', el),
                            e2pdf.properties.getField('g_sort', el),
                            e2pdf.properties.getField('g_fill_under', el),
                            e2pdf.properties.getField('g_reverse', el),
                            e2pdf.properties.getField('g_percentage', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                            'e2pdf-pr10',
                        ]
                    };
                    obj['title'] = {
                        'name': e2pdf.lang.get('Title'),
                        'fields': [
                            e2pdf.properties.getField('g_graph_title_colour', el),
                            e2pdf.properties.getField('g_graph_title', el),
                            e2pdf.properties.getField('g_graph_title_font_size', el),
                            e2pdf.properties.getField('g_graph_title_position', el),
                            e2pdf.properties.getField('g_graph_title_space', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w60 e2pdf-pr10',
                            'e2pdf-w40',
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                        ]
                    };
                    obj['labels'] = {
                        'name': e2pdf.lang.get('Labels'),
                        'fields': [
                            e2pdf.properties.getField('g_label_colour', el),
                            e2pdf.properties.getField('g_label_font_size', el),
                            e2pdf.properties.getField('g_label_space', el),
                            e2pdf.properties.getField('g_label_v', el),
                            e2pdf.properties.getField('g_label_h', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                        ]
                    };
                    obj['marker'] = {
                        'name': e2pdf.lang.get('Markers'),
                        'fields': [
                            e2pdf.properties.getField('g_marker_colour', el),
                            e2pdf.properties.getField('g_marker_size', el),
                            e2pdf.properties.getField('g_marker_dynamic_colour', el),
                            e2pdf.properties.getField('g_marker_type', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                            'e2pdf-w100',
                            'e2pdf-w100'
                        ]
                    };
                    obj['legend'] = {
                        'name': e2pdf.lang.get('Legend'),
                        'fields': [
                            e2pdf.properties.getField('g_legend_back_colour', el),
                            e2pdf.properties.getField('g_legend_stroke_colour', el),
                            e2pdf.properties.getField('g_legend_stroke_width', el),
                            e2pdf.properties.getField('g_legend_entry_width', el),
                            e2pdf.properties.getField('g_legend_padding_x', el),
                            e2pdf.properties.getField('g_legend_padding_y', el),
                            e2pdf.properties.getField('g_legend_colour', el),
                            e2pdf.properties.getField('g_legend_title', el),
                            e2pdf.properties.getField('g_legend_title_font_size', el),
                            e2pdf.properties.getField('g_legend_font_size', el),
                            e2pdf.properties.getField('g_legend_position_horizontal', el),
                            e2pdf.properties.getField('g_legend_position_vertical', el),
                            e2pdf.properties.getField('g_legend_position_join', el),
                            e2pdf.properties.getField('g_legend_position_horizontal_margin', el),
                            e2pdf.properties.getField('g_legend_position_vertical_margin', el),
                            e2pdf.properties.getField('g_legend_text_side', el),
                            e2pdf.properties.getField('g_legend_columns', el),
                            e2pdf.properties.getField('g_show_legend', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w60 e2pdf-pr10',
                            'e2pdf-w40',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                            'e2pdf-w100',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['additional'] = {
                        'name': 'Additional',
                        'fields': [
                            e2pdf.properties.getField('g_bubble_scale', el),
                            e2pdf.properties.getField('g_increment', el),
                            e2pdf.properties.getField('g_stack_group', el),
                            e2pdf.properties.getField('g_line_dataset', el),
                            e2pdf.properties.getField('g_project_angle', el),
                            e2pdf.properties.getField('g_line_curve', el)
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50'
                        ]
                    };
                    obj['grid'] = {
                        'name': e2pdf.lang.get('Grid'),
                        'fields': [
                            e2pdf.properties.getField('g_grid_colour', el),
                            e2pdf.properties.getField('g_grid_subdivision_colour', el),
                            e2pdf.properties.getField('g_minimum_grid_spacing', el),
                            e2pdf.properties.getField('g_minimum_grid_spacing_h', el),
                            e2pdf.properties.getField('g_minimum_grid_spacing_v', el),
                            e2pdf.properties.getField('g_grid_division_h', el),
                            e2pdf.properties.getField('g_grid_division_v', el),
                            e2pdf.properties.getField('g_show_grid', el),
                            e2pdf.properties.getField('g_show_grid_subdivisions', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                            'e2pdf-pr10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['axis'] = {
                        'name': e2pdf.lang.get('Axis'),
                        'fields': [
                            e2pdf.properties.getField('g_axis_colour', el),
                            e2pdf.properties.getField('g_axis_font_size', el),
                            e2pdf.properties.getField('g_axis_overlap', el),
                            e2pdf.properties.getField('g_show_subdivisions', el),
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w60 e2pdf-pr10',
                            'e2pdf-w40',
                            'e2pdf-w100',
                            'e2pdf-w50 e2pdf-pr10',
                        ]
                    };

                    obj['axis_v'] = {
                        'name': e2pdf.lang.get('Axis (V)'),
                        'fields': [
                            e2pdf.properties.getField('g_axis_stroke_width_v', el),
                            e2pdf.properties.getField('g_show_axis_v', el),
                            e2pdf.properties.getField('g_show_axis_text_v', el),
                            e2pdf.properties.getField('g_axis_min_v', el),
                            e2pdf.properties.getField('g_axis_max_v', el),
                            e2pdf.properties.getField('g_axis_min_max_v', el),
                            e2pdf.properties.getField('g_axis_text_position_v', el),
                            e2pdf.properties.getField('g_axis_text_align_v', el),
                            e2pdf.properties.getField('g_axis_text_space_v', el),
                            e2pdf.properties.getField('g_axis_text_offset_x_v', el),
                            e2pdf.properties.getField('g_axis_text_offset_y_v', el),
                            e2pdf.properties.getField('g_units_x', el),
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33 e2pdf-sublabel',
                            'e2pdf-w33 e2pdf-pl10 e2pdf-sublabel',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10 e2pdf-sublabel',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                        ]
                    };

                    obj['axis_h'] = {
                        'name': e2pdf.lang.get('Axis (H)'),
                        'fields': [
                            e2pdf.properties.getField('g_axis_stroke_width_h', el),
                            e2pdf.properties.getField('g_show_axis_h', el),
                            e2pdf.properties.getField('g_show_axis_text_h', el),
                            e2pdf.properties.getField('g_axis_min_h', el),
                            e2pdf.properties.getField('g_axis_max_h', el),
                            e2pdf.properties.getField('g_axis_min_max_h', el),
                            e2pdf.properties.getField('g_axis_text_position_h', el),
                            e2pdf.properties.getField('g_axis_text_align_h', el),
                            e2pdf.properties.getField('g_axis_text_space_h', el),
                            e2pdf.properties.getField('g_axis_text_offset_x_h', el),
                            e2pdf.properties.getField('g_axis_text_offset_y_h', el),
                            e2pdf.properties.getField('g_units_y', el),
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33 e2pdf-sublabel',
                            'e2pdf-w33 e2pdf-pl10 e2pdf-sublabel',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10 e2pdf-sublabel',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                        ]
                    };
                    obj['barlabel'] = {
                        'name': 'Bar Label',
                        'fields': [
                            e2pdf.properties.getField('g_bar_label_colour', el),
                            e2pdf.properties.getField('g_bar_label_font_size', el),
                            e2pdf.properties.getField('g_units_label', el),
                            e2pdf.properties.getField('g_data_label_type', el),
                            e2pdf.properties.getField('g_bar_label_space', el),
                            e2pdf.properties.getField('g_bar_label_position_horizontal', el),
                            e2pdf.properties.getField('g_bar_label_position_vertical', el),
                            e2pdf.properties.getField('g_bar_label_position_join', el),
                            e2pdf.properties.getField('g_show_bar_labels', el),
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w70 e2pdf-pr10',
                            'e2pdf-w30',
                            'e2pdf-w100',
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-pr10'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('margin_top', el),
                            e2pdf.properties.getField('margin_left', el),
                            e2pdf.properties.getField('margin_right', el),
                            e2pdf.properties.getField('margin_bottom', el),
                            e2pdf.properties.getField('padding_top', el),
                            e2pdf.properties.getField('padding_left', el),
                            e2pdf.properties.getField('padding_right', el),
                            e2pdf.properties.getField('padding_bottom', el),
                            e2pdf.properties.getField('border_color', el),
                            e2pdf.properties.getField('border_top', el),
                            e2pdf.properties.getField('border_left', el),
                            e2pdf.properties.getField('border_right', el),
                            e2pdf.properties.getField('border_bottom', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-pr10 e2pdf-fnl',
                            'e2pdf-w25 e2pdf-fnl',
                            'e2pdf-w100'
                        ]
                    };
                    obj['structure'] = {
                        'name': e2pdf.lang.get('Structure'),
                        'fields': [
                            e2pdf.properties.getField('g_structure_key', el),
                            e2pdf.properties.getField('g_structure_value', el),
                            e2pdf.properties.getField('g_structure_colour', el),
                            e2pdf.properties.getField('g_structure_axis_text', el),
                            e2pdf.properties.getField('g_structure_legend_text', el),
                            e2pdf.properties.getField('g_structure_label', el),
                            e2pdf.properties.getField('g_structure_area', el),
                            e2pdf.properties.getField('g_structure_open', el),
                            e2pdf.properties.getField('g_structure_end', el),
                            e2pdf.properties.getField('g_structure_outliers', el),
                            e2pdf.properties.getField('g_structure_top', el),
                            e2pdf.properties.getField('g_structure_bottom', el),
                            e2pdf.properties.getField('g_structure_wtop', el),
                            e2pdf.properties.getField('g_structure_wbottom', el),
                            e2pdf.properties.getField('g_structure_high', el),
                            e2pdf.properties.getField('g_structure_low', el),
                            e2pdf.properties.getField('g_structured_data', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33 e2pdf-sublabel'
                        ]
                    };
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('g_key_sep', el),
                            e2pdf.properties.getField('g_array_sep', el),
                            e2pdf.properties.getField('g_sub_array_sep', el),
                            e2pdf.properties.getField('g_legends', el),
                            e2pdf.properties.getField('g_colors', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getField('g_multiline', el),
                            e2pdf.properties.getField('g_reverse_data', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w33 e2pdf-pr10',
                            'e2pdf-w33',
                            'e2pdf-w33 e2pdf-pl10',
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w100 e2pdf-strong-label',
                            'e2pdf-w30 e2pdf-pr10 e2pdf-hide-label',
                            'e2pdf-w30 e2pdf-pr10',
                            'e2pdf-w40 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-rectangle') {
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('background', el),
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pl10'
                        ]
                    };
                }

                if (el.data('data-type') === 'e2pdf-link') {
                    obj['field'] = {
                        'name': e2pdf.lang.get('Field'),
                        'fields': [
                            e2pdf.properties.getField('text_color', el),
                            e2pdf.properties.getField('text_font', el),
                            e2pdf.properties.getField('text_font_size', el),
                            e2pdf.properties.getField('text_letter_spacing', el),
                            e2pdf.properties.getField('text_line_height', el),
                            e2pdf.properties.getField('text_align', el),
                            e2pdf.properties.getField('vertical', el),
                            e2pdf.properties.getField('rtl', el),
                            e2pdf.properties.getField('underline', el),
                        ],
                        'position': 'left',
                        'classes': [
                            'e2pdf-w100',
                            'e2pdf-w100',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w50',
                            'e2pdf-w50 e2pdf-pl10',
                            'e2pdf-w70',
                            'e2pdf-w30 e2pdf-pl10',
                            'e2pdf-w33'
                        ]
                    };
                    obj['style'] = {
                        'name': e2pdf.lang.get('Style'),
                        'fields': [
                            e2pdf.properties.getField('z_index', el)
                        ],
                        'position': 'right',
                        'classes': [
                            'e2pdf-w100'
                        ]
                    };

                    obj['value'] = {
                        'name': e2pdf.lang.get('Value'),
                        'fields': [
                            e2pdf.properties.getField('link_label', el),
                            e2pdf.properties.getField('link_type', el),
                            e2pdf.properties.getField('highlight', el),
                            e2pdf.properties.getField('value', el),
                            e2pdf.properties.getLink("+ " + e2pdf.lang.get('Preg Filters'), 'javascript:void(0);', 'e2pdf-collapse e2pdf-link', 'e2pdf-preg-filters'),
                            e2pdf.properties.getField('preg_pattern', el),
                            e2pdf.properties.getField('preg_replacement', el),
                            e2pdf.properties.getField('preg_match_all_pattern', el),
                            e2pdf.properties.getField('preg_match_all_output', el)
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w50 e2pdf-pr10',
                            'e2pdf-w25 e2pdf-pr10',
                            'e2pdf-w25',
                            'e2pdf-w100',
                            'e2pdf-w100 e2pdf-align-right e2pdf-small e2pdf-mt6 e2pdf-pl10 e2pdf-pr10',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pr10 e2pdf-preg-filters e2pdf-hide',
                            'e2pdf-w50 e2pdf-pl10 e2pdf-preg-filters e2pdf-hide'
                        ]
                    };
                }
            }

            // tpl-properties
            if (el.data('data-type') === 'e2pdf-tpl') {
                if (actions) {
                    if (actions) {
                        obj['actions'] = {
                            'name': e2pdf.lang.get('Actions'),
                            'fields': e2pdf.actions.renderFields(el),
                            'position': 'bottom'
                        };
                    }
                } else {
                    obj['value'] = {
                        'name': '',
                        'fields': [
                            e2pdf.properties.getField('css', el),
                        ],
                        'position': 'bottom',
                        'classes': [
                            'e2pdf-w100 e2pdf-strong-label e2pdf-mt10',
                        ]
                    };
                }
            } else {
                if (actions) {
                    obj['actions'] = {
                        'name': e2pdf.lang.get('Actions'),
                        'fields': e2pdf.actions.renderFields(el),
                        'position': 'bottom'
                    };
                }
            }
            return obj;
        },
        // e2pdf.properties.renderFields
        renderFields: function (el, actions = true) {
            var fields = jQuery('<div>', {'class': ' e2pdf-grid'}).append();
            var fields_top = jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100 e2pdf-top'});
            var fields_left = jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pr5 e2pdf-left'});
            var fields_right = jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w50 e2pdf-pl5 e2pdf-right'});
            var fields_bottom = jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100 e2pdf-bottom'});
            if (el.data('data-type') == 'e2pdf-tpl') {
                fields_top = '';
                fields_left = '';
                fields_right = '';
            } else if (el.data('data-type') == 'e2pdf-page') {
                fields_left = '';
                fields_right = '';
            }

            var groups = e2pdf.properties.getFields(el, actions);
            if (groups) {
                for (var group_key in groups) {
                    if (group_key === 'actions') {
                        var group = groups[group_key];
                        var block = jQuery('<div>');
                        if (group.name) {
                            block.append(jQuery('<label>').html(group.name + ':'));
                        }
                        var grid = jQuery('<div>', {'class': 'e2pdf-grid'});
                        grid.append(group.fields);
                        block.append(grid);
                        if (group.position === 'top') {
                            fields_top.append(block);
                        } else if (group.position === 'left') {
                            fields_left.append(block);
                        } else if (group.position === 'right') {
                            fields_right.append(block);
                        } else {
                            fields_bottom.append(block);
                        }
                    } else {
                        var group = groups[group_key];
                        var block = jQuery('<div>');
                        if (group.name) {
                            block.append(jQuery('<label>').html(group.name + ':'));
                        }
                        var grid = jQuery('<div>', {'class': 'e2pdf-grid'});
                        for (var field_key in group.fields) {

                            var group_field = group.fields[field_key];
                            var classes = '';
                            if (group.classes) {
                                if (group.classes[field_key]) {
                                    classes = group.classes[field_key];
                                }
                            }
                            var field = '';
                            var label = '';
                            var wrap = '';
                            if (group_field.type === 'text') {
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<input>', {'type': 'text', 'class': 'e2pdf-w100', 'name': group_field.key, 'value': group_field.value});
                            } else if (group_field.type === 'hidden') {
                                field = jQuery('<input>', {'type': 'hidden', 'name': group_field.key, 'value': group_field.value});
                            } else if (group_field.type === 'textarea') {
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                var rows = '5';
                                if (group_field.key == 'g_legends' || group_field.key == 'g_colors') {
                                    rows = '3';
                                }
                                field = jQuery('<textarea>', {'name': group_field.key, 'class': 'e2pdf-w100', 'rows': rows}).val(group_field.value);
                            } else if (group_field.type === 'checkbox') {
                                wrap = jQuery('<label>', {'class': 'e2pdf-label e2pdf-small e2pdf-wauto'});
                                label = group_field.name;
                                field = jQuery('<input>', {'type': 'checkbox', 'class': 'e2pdf-ib', 'name': group_field.key, 'value': group_field.option});
                                if (group_field.value == group_field.option) {
                                    field.prop('checked', true);
                                }
                            } else if (group_field.type === 'color') {
                                wrap = jQuery('<div>', {'class': 'e2pdf-colorpicker-wr'});
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<input>', {'class': 'e2pdf-color-picker e2pdf-color-picker-load e2pdf-w100', 'type': 'text', 'name': group_field.key, 'value': group_field.value});
                            } else if (group_field.type === 'select') {
                                label = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html(group_field.name + ":");
                                field = jQuery('<select>', {'class': 'e2pdf-w100', 'name': group_field.key});
                                for (var option_key in group_field.options) {
                                    field.append(jQuery('<option>', {'value': Object.keys(group_field.options[option_key])[0]}).html(Object.values(group_field.options[option_key])[0]));
                                }
                                field.val(group_field.value);
                            } else if (group_field.type === 'link') {
                                field = jQuery('<a>', {'href': group_field.value, 'class': group_field.classes}).append(group_field.name);
                            }
                            for (var att_key in group_field.atts) {
                                var att = group_field.atts[att_key];
                                switch (att) {
                                    case 'readonly':
                                        field.attr('readonly', 'readonly');
                                        break;
                                    case 'disabled':
                                        field.attr('disabled', 'disabled');
                                        break;
                                    case 'number':
                                        field.addClass('e2pdf-numbers');
                                        break;
                                    case 'number-negative':
                                        field.addClass('e2pdf-number-negative');
                                        break;
                                    case 'autocomplete':
                                        wrap = jQuery('<div>', {'class': 'e2pdf-rel e2pdf-w100'});
                                        field.addClass('e2pdf-autocomplete-cl');
                                        field.autocomplete({
                                            source: group_field.source,
                                            minLength: 0,
                                            appendTo: wrap,
                                            open: function () {
                                                jQuery(this).autocomplete("widget").addClass("e2pdf-autocomplete");
                                            },
                                            classes: {
                                                "ui-autocomplete": "e2pdf-autocomplete"
                                            }
                                        });
                                        break;
                                    case 'collapse':
                                        field.attr('data-collapse', group_field.collapse);
                                        break;
                                }
                            }
                            if (!wrap) {
                                wrap = field;
                            } else {
                                wrap.prepend(field);
                            }
                            if (group_field.type === 'checkbox') {
                                wrap.append(" " + label);
                                if (classes.includes('e2pdf-sublabel')) {
                                    var sublabel = jQuery('<div>', {'class': 'e2pdf-small e2pdf-label'}).html("&nbsp;");
                                    grid.append(jQuery('<div>', {'class': 'e2pdf-ib ' + classes}).append(sublabel, wrap));
                                } else {
                                    grid.append(jQuery('<div>', {'class': 'e2pdf-ib ' + classes}).append(wrap));
                                }
                            } else {
                                grid.append(jQuery('<div>', {'class': 'e2pdf-ib ' + classes}).append(label, wrap));
                            }
                        }
                        block.append(grid);
                        if (group.position === 'top') {
                            fields_top.append(block);
                        } else if (group.position === 'left') {
                            fields_left.append(block);
                        } else if (group.position === 'right') {
                            fields_right.append(block);
                        } else {
                            fields_bottom.append(block);
                        }
                    }
                }
            }
            fields.append(fields_top, fields_left, fields_right, fields_bottom);
            return fields;
        },
        // e2pdf.properties.apply
        apply: function (el, data, onload) {
            if (onload !== true) {
                var groups = e2pdf.properties.getFields(el);
                for (var group_key in groups) {
                    var group = groups[group_key];
                    for (var field_key in group.fields) {
                        var group_field = group.fields[field_key];
                        var property = group_field['key'];
                        if (jQuery.inArray('keep', group_field['atts']) === -1 && data.hasOwnProperty(property)) {
                            switch (group_field['type']) {
                                case 'text':
                                    if ((jQuery.inArray('number', group_field['atts']) !== -1 && data[property] == '0') || !data[property]) {
                                        if (property !== 'left' && property !== 'top') {
                                            delete data[property];
                                        }
                                    }
                                    break;
                                case 'color':
                                case 'select':
                                case 'textarea':
                                    if (!data[property]) {
                                        delete data[property];
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
            el.data('data-properties', JSON.stringify(data));
        },
        // e2pdf.properties.set
        set: function (el, key, value) {
            var properties = e2pdf.properties.get(el);
            properties[key] = value;
            e2pdf.properties.apply(el, properties);
        },
        // e2pdf.properties.getValue
        getValue: function (el, key, type = '') {
            var properties = e2pdf.properties.get(el);
            if (type) {
                if (type == 'string') {
                    return e2pdf.helper.getString(properties[key]);
                } else if (type == 'checkbox') {
                    return e2pdf.helper.getCheckbox(properties[key]);
                } else if (type == 'int') {
                    return e2pdf.helper.getInt(properties[key]);
                } else if (type == 'float') {
                    return e2pdf.helper.getFloat(properties[key]);
                } else {
                    return '';
                }
            } else if (properties.hasOwnProperty(key)) {
                if (el.data('data-type') === 'e2pdf-page-number' && key == 'value') {
                    return e2pdf.helper.toHtml(properties[key]);
                } else {
                    return properties[key];
                }
            } else {
                return '';
        }
        },
        // e2pdf.properties.get
        get: function (el) {
            var properties = [];
            if (typeof el.data('data-properties') !== 'undefined') {
                properties = JSON.parse(el.data('data-properties'));
                if (el.data('data-type') !== 'e2pdf-page' && el.data('data-type') !== 'e2pdf-tpl') {
                    properties['width'] = properties.hasOwnProperty('width') ? parseFloat(properties['width']) : parseFloat(el.css('width'));
                    properties['height'] = properties.hasOwnProperty('height') ? parseFloat(properties['height']) : parseFloat(el.css('height'));
                    properties['top'] = properties.hasOwnProperty('top') ? parseFloat(properties['top']) : parseFloat(el.css('top'));
                    properties['left'] = properties.hasOwnProperty('left') ? parseFloat(properties['left']) : parseFloat(el.css('left'));
                }
            }
            return properties;
        },
        // e2pdf.properties.render
        render: function (el) {
            var properties = e2pdf.properties.get(el);
            var children = e2pdf.element.children(el);
            if (properties.hasOwnProperty('options')) {
                if (el.data('data-type') === 'e2pdf-select') {
                    children.find('option').remove();
                    var options = properties['options'].split("\n");
                    if (typeof options !== 'undefined' && options.length > 0) {
                        for (var key in options) {
                            children.append(
                                    jQuery('<option>', {'value': options[key].trim()}).html(options[key].trim())
                                    );
                        }
                    }
                }
            }
            switch (el.data('data-type')) {
                case 'e2pdf-html':
                    var wysiwyg_source = !jQuery('.e2pdf-wysiwyg-source').hasClass('e2pdf-inactive');
                    if (children.is('textarea') && e2pdf.helper.getCheckbox(properties['wysiwyg_disable']) != '1' && !wysiwyg_source) {
                        children.replaceWith(jQuery('<div>', {'contenteditable': true, 'class': 'content e2pdf-html e2pdf-inner-element'}));
                        children = e2pdf.element.children(el);
                    } else if (children.is('div') && (e2pdf.helper.getCheckbox(properties['wysiwyg_disable']) == '1' || wysiwyg_source)) {
                        children.replaceWith(jQuery('<textarea>', {'contenteditable': true, 'class': 'content e2pdf-html e2pdf-inner-element'}));
                        children = e2pdf.element.children(el);
                    }
                    if (e2pdf.helper.getCheckbox(properties['wysiwyg_disable']) == '1' || wysiwyg_source) {
                        e2pdf.helper.css(e2pdf.helper.getInt(el.attr('data-element_id')), '');
                        children.val(e2pdf.helper.getString(properties['value']));
                    } else {
                        e2pdf.helper.css(e2pdf.helper.getInt(el.attr('data-element_id')), e2pdf.helper.getString(properties['css']));
                        children.html(e2pdf.helper.getString(properties['value']));
                    }
                    break;
                case 'e2pdf-page-number':
                    children.html(e2pdf.helper.getString(properties['value']).replace('[e2pdf-page-number]', '1').replace('[e2pdf-page-total]', '2'));
                    break;
                case 'e2pdf-link':
                    children.text(e2pdf.helper.getString(properties['link_label']));
                    break;
                case 'e2pdf-input':
                case 'e2pdf-textarea':
                case 'e2pdf-select':
                    children.val(e2pdf.helper.getString(properties['value']));
                    break;
                case 'e2pdf-checkbox':
                    if (e2pdf.helper.getString(properties['value']) == e2pdf.helper.getString(properties['option'])) {
                        children.prop('checked', true);
                    } else {
                        children.prop('checked', false);
                    }
                    break;
                case 'e2pdf-radio':
                    jQuery('.e2pdf-tpl').find('.e2pdf-radio').each(function () {
                        var radio = jQuery(this).parent();
                        var radio_properties = e2pdf.properties.get(radio);
                        if (e2pdf.helper.getString(radio_properties['group']) === e2pdf.helper.getString(properties['group'])) {
                            e2pdf.properties.set(radio, 'readonly', e2pdf.helper.getCheckbox(properties['readonly']));
                            e2pdf.properties.set(radio, 'required', e2pdf.helper.getCheckbox(properties['required']));
                            e2pdf.properties.set(radio, 'value', e2pdf.helper.getString(properties['value']));
                            if (e2pdf.helper.getString(properties['value']) === e2pdf.helper.getString(radio_properties['option'])) {
                                jQuery(this).prop('checked', true);
                            } else {
                                jQuery(this).prop('checked', false);
                            }
                        }
                    });
                    break;
                case 'e2pdf-image':
                case 'e2pdf-qrcode':
                case 'e2pdf-barcode':
                case 'e2pdf-signature':
                case 'e2pdf-graph':
                    e2pdf.helper.image.load(el);
                    break;
            }

            if (el.data('data-type') === 'e2pdf-select') {
                children.attr('multiple', e2pdf.helper.getCheckbox(properties['multiline']) == '1' ? true : false);
            }

            if (el.data('data-type') === 'e2pdf-image') {
                children.css('opacity', e2pdf.helper.getFloat(properties['opacity']));
            }

            el.css('z-index', e2pdf.helper.getInt(properties['z_index']));
            if (el.data('data-type') === 'e2pdf-select' ||
                    el.data('data-type') === 'e2pdf-input' ||
                    el.data('data-type') === 'e2pdf-textarea' ||
                    el.data('data-type') === 'e2pdf-html' ||
                    el.data('data-type') === 'e2pdf-page-number') {
                if (e2pdf.helper.getString(properties['rtl']) != '') {
                    children.attr('dir', e2pdf.helper.getString(properties['rtl']) == '0' ? 'ltr' : 'rtl');
                } else {
                    children.attr('dir', false);
                }
            }

            if (el.data('data-type') === 'e2pdf-radio' || el.data('data-type') === 'e2pdf-checkbox') {
                children.css('background', e2pdf.helper.getString(properties['background']));
            } else {
                el.css('background', e2pdf.helper.getString(properties['background']));
            }

            if (el.data('data-type') === 'e2pdf-input' ||
                    el.data('data-type') === 'e2pdf-textarea' ||
                    el.data('data-type') === 'e2pdf-select' ||
                    el.data('data-type') === 'e2pdf-radio' ||
                    el.data('data-type') === 'e2pdf-checkbox'
                    ) {
                children.css('border', e2pdf.helper.getInt(properties['border']) > 0 ? e2pdf.helper.getInt(properties['border']) + 'px solid ' + e2pdf.helper.getString(properties['border_color'], '#000000') : '');
            } else {
                el.css('border-top', e2pdf.helper.getInt(properties['border_top']) + 'px solid ' + e2pdf.helper.getString(properties['border_color'], '#000000'));
                el.css('border-left', e2pdf.helper.getInt(properties['border_left']) + 'px solid' + e2pdf.helper.getString(properties['border_color'], '#000000'));
                el.css('border-right', e2pdf.helper.getInt(properties['border_right']) + 'px solid' + e2pdf.helper.getString(properties['border_color'], '#000000'));
                el.css('border-bottom', e2pdf.helper.getInt(properties['border_bottom']) + 'px solid' + e2pdf.helper.getString(properties['border_color'], '#000000'));
            }

            el.css('padding-top', e2pdf.helper.getInt(properties['padding_top']) + 'px');
            el.css('padding-left', e2pdf.helper.getInt(properties['padding_left']) + 'px');
            el.css('padding-right', e2pdf.helper.getInt(properties['padding_right']) + 'px');
            el.css('padding-bottom', e2pdf.helper.getInt(properties['padding_bottom']) + 'px');
            if (el.data('data-type') === 'e2pdf-html' || el.data('data-type') === 'e2pdf-page-number') {
                el.css('color', e2pdf.helper.getString(properties['text_color']));
            } else if (el.data('data-type') === 'e2pdf-input' ||
                    el.data('data-type') === 'e2pdf-textarea' ||
                    el.data('data-type') === 'e2pdf-select' ||
                    el.data('data-type') === 'e2pdf-radio' ||
                    el.data('data-type') === 'e2pdf-checkbox'
                    ) {
                children.css('color', e2pdf.helper.getString(properties['text_color']));
            }

            if (e2pdf.helper.getString(properties['text_font'])) {
                var path = jQuery('.e2pdf-wysiwyg-font').find("[value='" + e2pdf.helper.getString(properties['text_font']) + "']").attr('path');
                if (typeof path === 'undefined') {
                    el.css('font-family', '');
                } else {
                    var tmp = jQuery('<div>', {'name': e2pdf.helper.getString(properties['text_font']), 'path': path});
                    e2pdf.font.load(tmp);
                    el.css('font-family', e2pdf.helper.getString(properties['text_font']));
                }
            } else {
                el.css('font-family', '');
            }

            el.css('font-size', e2pdf.helper.getString(properties['text_font_size'], '', 'px'));
            el.css('letter-spacing', e2pdf.helper.getString(properties['text_letter_spacing']), '', 'px');
            children.css('text-align', e2pdf.helper.getString(properties['text_align']) ? e2pdf.helper.getString(properties['text_align']) : jQuery('#e2pdf-text-align').val());
            if (el.data('data-type') === 'e2pdf-textarea') {
                children.css('line-height', e2pdf.helper.getString(properties['text_line_height']) && e2pdf.helper.getString(properties['text_line_height']) != '0' ? e2pdf.helper.getString(properties['text_line_height']) + 'px' : '');
            } else {
                el.css('line-height', e2pdf.helper.getString(properties['text_line_height']) && e2pdf.helper.getString(properties['text_line_height']) != '0' ? e2pdf.helper.getString(properties['text_line_height']) + 'px' : '');
            }

            if (el.closest('.e2pdf-page').length > 0) {
                var page_w = e2pdf.helper.getFloat(el.closest('.e2pdf-page').css('width'));
                var page_h = e2pdf.helper.getFloat(el.closest('.e2pdf-page').css('height'));
                var height = e2pdf.helper.getFloat(properties['top']) + e2pdf.helper.getFloat(el.css('height'));
                if (height > page_h) {
                    var top = page_h - e2pdf.helper.getFloat(el.css('height'));
                    el.css('top', top + 'px');
                } else {
                    el.css('top', e2pdf.helper.getFloat(properties['top'], 0, 'px'));
                }

                var width = e2pdf.helper.getFloat(properties['left']) + e2pdf.helper.getFloat(el.css('width'));
                if (width > page_w) {
                    var left = page_w - e2pdf.helper.getFloat(el.css('width'));
                    el.css('left', left + "px");
                } else {
                    el.css('left', e2pdf.helper.getFloat(properties['left'], 0, 'px'));
                }

                var width = e2pdf.helper.getFloat(properties['width']);
                var left = e2pdf.helper.getFloat(el.css('left'));
                if (width > 0) {
                    if (width > page_w - left) {
                        el.css('width', page_w - left);
                        e2pdf.properties.set(el, 'width', page_w - left);
                    } else {
                        el.css('width', e2pdf.helper.getFloat(properties['width'], 0, 'px'));
                    }
                }

                var height = e2pdf.helper.getFloat(properties['height']);
                var top = e2pdf.helper.getFloat(el.css('top'));
                if (height > 0) {
                    if (height > page_h - top) {
                        el.css('height', page_h - top);
                        e2pdf.properties.set(el, 'height', page_h - top);
                    } else {
                        el.css('height', e2pdf.helper.getFloat(properties['height'], 0, 'px'));
                    }
                }
            }

            if (e2pdf.helper.getCheckbox(properties['locked']) == '1') {
                el.addClass('e2pdf-locked');
            } else {
                el.removeClass('e2pdf-locked');
            }
        }

    },
    // e2pdf.welcomeScreen
    welcomeScreen: function () {
        if (jQuery('.e2pdf-page').length === 0) {
            var el = jQuery('<div>', {'data-modal': 'welcome-screen'});
            e2pdf.dialog.create(el);
        }
    },
    // e2pdf.createPdf
    createPdf: function (el) {
        var item = false;
        var item1 = false;
        var item2 = false;
        var action = el.attr('data-action');
        var data = e2pdf.form.serializeObject(el.closest('form'));
        var disabled_settings = [
            'title', 'preset', 'font', 'font_size', 'line_height'
        ];
        for (var key in data) {
            if (jQuery.inArray(key, disabled_settings) === -1) {
                if (key == 'activated') {
                    e2pdf.pdf.settings.change(key, data[key]);
                } else {
                    e2pdf.pdf.settings.change(key, data[key]);
                }
            }

            if (key === 'font') {
                jQuery('#e2pdf-font').val(data[key]);
            }

            if (key === 'font_size') {
                jQuery('#e2pdf-font-size').val(data[key]);
            }

            if (key === 'line_height') {
                jQuery('#e2pdf-line-height').val(data[key]);
            }

            if (key === 'title') {
                jQuery('#e2pdf-title').val(data[key]);
            }

            if (key === 'text_align') {
                jQuery('#e2pdf-text-align').val(data[key]).trigger('change');
            }

            if (key === 'rtl') {
                jQuery('#e2pdf-rtl').prop('checked', true).trigger('change');
            }
        }

        if (!data['rtl']) {
            jQuery('#e2pdf-rtl').prop('checked', false).trigger('change');
        }

        if (action === 'apply') {
            var width = parseFloat(el.closest('form').find('input[name="width"]').val());
            var height = parseFloat(el.closest('form').find('input[name="height"]').val());
            var option = el.closest('form').find('.e2pdf-item option:selected');
            if (option && typeof option.data('data-item') !== 'undefined') {
                item = option.data('data-item');
            }

            if (item && item.id == '-1') {
                if (!confirm(e2pdf.lang.get('All Field Values will be overwritten! Continue?'))) {
                    return false;
                }
                el.attr('form-id', 'e2pdf-build-form');
                el.attr('action', 'e2pdf_save_form');
                e2pdf.static.unsaved = false;
                e2pdf.request.submitForm(el);
                return;
            } else {
                if (item && item.id == '-2') {
                    var option1 = el.closest('form').find('.e2pdf-item1 option:selected');
                    if (option1 && typeof option1.data('data-item') !== 'undefined') {
                        item1 = option1.data('data-item');
                        if (item1.id == '-1') {
                            item1 = false;
                        }
                    }

                    var option2 = el.closest('form').find('.e2pdf-item2 option:selected');
                    if (option2 && typeof option2.data('data-item') !== 'undefined') {
                        item2 = option2.data('data-item');
                        if (item2.id == '-1') {
                            item2 = false;
                        }
                    }
                }

                e2pdf.pages.changeTplSize(width, height);
                jQuery('.ui-dialog-content').dialog('close');
            }
        } else if (action === 'empty') {
            var width = parseFloat(el.closest('form').find('input[name="width"]').val());
            var height = parseFloat(el.closest('form').find('input[name="height"]').val());
            var option = el.closest('form').find('.e2pdf-item option:selected');
            if (option && typeof option.data('data-item') !== 'undefined') {
                item = option.data('data-item');
                if (item.id == '-1') {
                    item = false;
                }
            }
            if (item && item.id == '-2') {
                var option1 = el.closest('form').find('.e2pdf-item1 option:selected');
                if (option1 && typeof option1.data('data-item') !== 'undefined') {
                    item1 = option1.data('data-item');
                    if (item1.id == '-1') {
                        item1 = false;
                    }
                }

                var option2 = el.closest('form').find('.e2pdf-item2 option:selected');
                if (option2 && typeof option2.data('data-item') !== 'undefined') {
                    item2 = option2.data('data-item');
                    if (item2.id == '-1') {
                        item2 = false;
                    }
                }
            }
            e2pdf.pages.changeTplSize(width, height);
            e2pdf.pages.createPage();
            jQuery('.ui-dialog-content').dialog('close');
        } else if (action === 'auto') {
            var extension = el.closest('form').find('.e2pdf-extension').val();
            var option = el.closest('form').find('.e2pdf-item option:selected');
            if (option && typeof option.data('data-item') !== 'undefined') {
                item = option.data('data-item');
                if (item.id == '-1') {
                    item = false;
                }
            }
            if (item && item.id == '-2') {
                var option1 = el.closest('form').find('.e2pdf-item1 option:selected');
                if (option1 && typeof option1.data('data-item') !== 'undefined') {
                    item1 = option1.data('data-item');
                    if (item1.id == '-1') {
                        item1 = false;
                    }
                }
                var option2 = el.closest('form').find('.e2pdf-item2 option:selected');
                if (option2 && typeof option2.data('data-item') !== 'undefined') {
                    item2 = option2.data('data-item');
                    if (item2.id == '-1') {
                        item2 = false;
                    }
                }
            }
            var data = {};
            data['extension'] = extension;
            data['item'] = item ? item.id : '';
            data['item1'] = item1 ? item1.id : '';
            data['item2'] = item2 ? item2.id : '';
            data['font_size'] = el.closest('form').find('select[name="font_size"]').val();
            data['line_height'] = el.closest('form').find('select[name="line_height"]').val();
            e2pdf.request.submitRequest('e2pdf_auto', el, data);
        } else if (action === 'upload') {
            if (e2pdf.pdf.settings.get('ID')) {
                if (!confirm(e2pdf.lang.get('Saved Template will be overwritten! Continue?'))) {
                    return false;
                }
            }
            jQuery('.e2pdf-upload-pdf').click();
        }
        if (action === 'apply' || action === 'empty' || action === 'auto') {
            var link = jQuery('<a>', {
                'href': 'javascript:void(0);',
                'class': 'e2pdf-link e2pdf-modal',
                'data-modal': 'tpl-options'
            }).html(e2pdf.lang.get('None'));
            if (item && item.id) {
                if (item.id == '-2') {
                    if (item1 || item2) {

                        var link = jQuery('<span>');
                        if (item1 && item1.id) {
                            link.append(jQuery('<a>', {
                                'target': '_blank',
                                'href': item1.url,
                                'class': 'e2pdf-link'
                            }).html(item1.name));
                        }
                        if (item2 && item2.id) {
                            if (item1 && item1.id) {
                                link.append(', ');
                            }
                            link.append(jQuery('<a>', {
                                'target': '_blank',
                                'href': item2.url,
                                'class': 'e2pdf-link'
                            }).html(item2.name));
                        }
                    }

                } else {
                    link = jQuery('<a>', {
                        'target': '_blank',
                        'href': item.url,
                        'class': 'e2pdf-link'
                    }).html(item.name);
                }
            }
            jQuery('#e2pdf-post-item').html(link);
        }

        jQuery('.e2pdf-tpl').data('data-type', 'e2pdf-tpl');
        e2pdf.font.load(jQuery('#e2pdf-font'));
        e2pdf.font.apply(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font'));
        e2pdf.font.size(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font-size'));
        e2pdf.font.line(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-line-height'));
        e2pdf.font.fontcolor(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font-color'));
        e2pdf.event.fire('after.createPdf');
    },
    // e2pdf.pages
    pages: {
        // e2pdf.pages.rebuildPages
        rebuildPages: function () {
            jQuery('.e2pdf-page').each(function (index) {
                if (!e2pdf.pdf.settings.get('pdf')) {
                    if (index + 1 === 1) {
                        jQuery(this).find('.e2pdf-up-page').attr('disabled', 'disabled');
                    } else {
                        jQuery(this).find('.e2pdf-up-page').attr('disabled', false);
                    }

                    if (index + 1 === jQuery('.e2pdf-page').length) {
                        jQuery(this).find('.e2pdf-down-page').attr('disabled', 'disabled');
                    } else {
                        jQuery(this).find('.e2pdf-down-page').attr('disabled', false);
                    }
                }
                jQuery(this).attr('data-page_id', index + 1);
            });
            e2pdf.welcomeScreen();
        },
        // e2pdf.pages.createPage
        createPage: function (page, properties, actions, onload) {
            e2pdf.pages.rebuildPages();
            var newpage = true;
            if (page) {
                var newpage = false;
            }

            if (!properties) {
                var properties = {};
            }

            if (!actions) {
                var actions = {};
            }

            if (newpage) {
                var page_id = parseInt(jQuery('.e2pdf-page').length) + 1;
                var page = jQuery('<div>', {
                    'class': 'e2pdf-page ui-droppable',
                    'width': jQuery('.e2pdf-tpl').attr('data-width'),
                    'height': jQuery('.e2pdf-tpl').attr('data-height'),
                    'data-width': jQuery('.e2pdf-tpl').attr('data-width'),
                    'data-height': jQuery('.e2pdf-tpl').attr('data-height')
                }).attr('data-page_id', page_id).append(
                        jQuery('<div>', {'class': 'page-options-icons'}).append(
                        jQuery('<a>', {
                            'href': 'javascript:void(0);',
                            'class': 'page-options-icon e2pdf-up-page e2pdf-link'
                        }).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-arrow-up-alt2'})
                        ),
                        jQuery('<a>', {
                            'href': 'javascript:void(0);',
                            'class': 'page-options-icon e2pdf-down-page e2pdf-link'
                        }).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-arrow-down-alt2'})
                        ),
                        jQuery('<a>', {
                            'href': 'javascript:void(0);',
                            'class': 'page-options-icon e2pdf-page-options e2pdf-modal e2pdf-link',
                            'data-modal': 'page-options'
                        }).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-admin-generic'})
                        ),
                        jQuery('<a>', {
                            'href': 'javascript:void(0);',
                            'class': 'page-options-icon e2pdf-delete-page e2pdf-link'
                        }).append(
                        jQuery('<i>', {'class': 'dashicons dashicons-no'})
                        )
                        ),
                        jQuery('<div>', {'class': 'e2pdf-guide e2pdf-guide-h'}),
                        jQuery('<div>', {'class': 'e2pdf-guide e2pdf-guide-v'})
                        );
            }

            page.data('data-type', 'e2pdf-page');
            e2pdf.properties.apply(page, properties, onload);
            e2pdf.actions.apply(page, actions);
            page.droppable({
                over: function (ev, ui) {
                    e2pdf.static.drag.page = jQuery(this);
                    e2pdf.static.guide.guides = [];
                    if ((jQuery(ui.draggable).attr('data-type') == 'e2pdf-qrcode' || jQuery(ui.draggable).attr('data-type') == 'e2pdf-barcode' || jQuery(ui.draggable).attr('data-type') == 'e2pdf-graph' || jQuery(ui.draggable).attr('data-type') == 'e2pdf-signature' || jQuery(ui.draggable).attr('data-type') == 'e2pdf-image') && !jQuery(ui.helper).data('original-width') && (jQuery(ui.helper).width() > e2pdf.static.drag.page.width() || jQuery(ui.helper).height() > e2pdf.static.drag.page.height())) {
                        jQuery(ui.helper).data('original-width', jQuery(ui.helper).width());
                        jQuery(ui.helper).data('original-height', jQuery(ui.helper).height());
                        var coeff = 1;
                        if (jQuery(ui.helper).width() > e2pdf.static.drag.page.width()) {
                            coeff = e2pdf.static.drag.page.width() / jQuery(ui.helper).width();
                        } else if (jQuery(ui.helper).height() > e2pdf.static.drag.page.height()) {
                            coeff = e2pdf.static.drag.page.height() / jQuery(ui.helper).height();
                        }
                        jQuery(ui.helper).width(jQuery(ui.helper).width() * coeff);
                        jQuery(ui.helper).height(jQuery(ui.helper).height() * coeff);
                    }

                    if (ui.draggable.hasClass('e2pdf-clone')) {
                        jQuery(this).find('.e2pdf-element').each(function () {
                            e2pdf.static.guide.guides = jQuery.merge(e2pdf.static.guide.guides, e2pdf.guide.calc(jQuery(this), null, null, null, true));
                        });
                        e2pdf.static.guide.guides = jQuery.merge(e2pdf.static.guide.guides, e2pdf.guide.calc(e2pdf.static.drag.page, null, null, null, true));
                    } else {
                        e2pdf.static.guide.guides = jQuery.map(jQuery(this).find('.e2pdf-element').not('.e2pdf-selected'), e2pdf.guide.calc);
                        e2pdf.static.guide.guides = jQuery.merge(e2pdf.static.guide.guides, e2pdf.guide.calc(e2pdf.static.drag.page, null, null, null, false));
                    }
                },
                out: function (ev, ui) {
                    e2pdf.static.drag.page = null;
                    e2pdf.static.guide.guides = [];
                    if (jQuery(ui.helper).data('original-width')) {
                        jQuery(ui.helper).width(jQuery(ui.helper).data('original-width'));
                        jQuery(ui.helper).height(jQuery(ui.helper).data('original-height'));
                        jQuery(ui.helper).removeData('original-width');
                        jQuery(ui.helper).removeData('original-height');
                    }

                },
                deactivate: function (ev) {
                    e2pdf.static.drag.page = null;
                    e2pdf.static.guide.guides = [];
                },
                drop: function (ev, ui) {
                    if (ui.draggable.hasClass('e2pdf-clone')) {
                        var type = jQuery(ui.draggable).attr('data-type');
                        var page = jQuery(this).closest('.e2pdf-page');
                        var pos = {
                            top: Math.max(0, (jQuery(ui.helper).offset().top - jQuery(this).offset().top) / e2pdf.zoom.zoom - 1),
                            left: Math.max(0, (jQuery(ui.helper).offset().left - jQuery(this).offset().left) / e2pdf.zoom.zoom - 1),
                            right: Math.min(0, ((parseFloat(jQuery(ui.helper).css('width')) + jQuery(ui.helper).offset().left - 2) - (jQuery(this).offset().left + parseFloat(jQuery(this).css('width')))) / e2pdf.zoom.zoom)
                        };
                        if (pos.left < 0 || pos.right > 0 || pos.top < 0) {
                            return false;
                        }

                        var properties = {};
                        properties['width'] = jQuery(ui.helper).css('width');
                        properties['height'] = jQuery(ui.helper).css('height');
                        properties['top'] = pos.top;
                        properties['left'] = pos.left;
                        var el = e2pdf.element.create(type, page, properties, false, true);
                        jQuery(this).append(el);
                        e2pdf.properties.render(el);
                    }
                    e2pdf.static.drag.page = null;
                }
            });
            page.contextmenu(function (e) {
                if (jQuery(e.target).hasClass('e2pdf-page')) {
                    e2pdf.contextMenu(e, page);
                    e.preventDefault();
                }
            });
            page.selectable(
                    {
                        filter: '.e2pdf-element',
                        cancel: 'a,.e2pdf-element',
                        distance: 10,
                        selecting: function (event, ui) {
                            if (jQuery('html').hasClass('e2pdf-unlock-all-elements') || !jQuery(ui.selecting).hasClass('e2pdf-locked')) {
                                jQuery(ui.selecting).addClass('e2pdf-selected');
                            }
                        },
                        unselecting: function (event, ui) {
                            jQuery(ui.unselecting).removeClass('e2pdf-selected');
                        },
                        selected: function (event, ui) {
                            if (jQuery('html').hasClass('e2pdf-unlock-all-elements') || !jQuery(ui.selected).hasClass('e2pdf-locked')) {
                                e2pdf.element.select(jQuery(ui.selected));
                            }
                        },
                        unselected: function (event, ui) {
                            e2pdf.element.unselect(jQuery(ui.unselected));
                        }
                    });
            if (newpage) {
                jQuery('.e2pdf-tpl .e2pdf-tpl-inner').append(page);
                e2pdf.pages.rebuildPages();
                e2pdf.event.fire('after.pages.createPage.newpage');
                return true;
            } else {
                return false;
            }
        },
        // e2pdf.pages.movePage
        movePage: function (el, direction) {
            if (e2pdf.pdf.settings.get('pdf')) {
                return false;
            }
            if (direction === 'up') {
                el.closest('.e2pdf-page').insertBefore(el.closest('.e2pdf-page').prev('.e2pdf-page'));
            } else if (direction === 'down') {
                el.closest('.e2pdf-page').insertAfter(el.closest('.e2pdf-page').next('.e2pdf-page'));
            }
            e2pdf.event.fire('after.pages.movePage');
            e2pdf.pages.rebuildPages();
        },
        // e2pdf.pages.deletePage
        deletePage: function (el) {
            el.closest('.e2pdf-page').remove();
            e2pdf.event.fire('after.pages.deletePage');
            e2pdf.pages.rebuildPages();
        },
        // e2pdf.pages.changeTplSize
        changeTplSize: function (width, height) {
            jQuery('.e2pdf-tpl').attr('data-width', width).attr('data-height', height);
        },
        changePageSize: function (el, width, height) {
            var prev_width = parseFloat(el.css('width'));
            var prev_height = parseFloat(el.css('height'));
            var width_diff = width / prev_width;
            var height_diff = height / prev_height;
            el.find(".e2pdf-element").each(function () {
                jQuery(this).css('left', parseFloat(jQuery(this).css('left')) * width_diff);
                jQuery(this).css('top', parseFloat(jQuery(this).css('top')) * height_diff);
                if (jQuery(this).data('data-type') === 'e2pdf-qrcode' || (e2pdf.properties.getValue(jQuery(this), 'dimension', 'checkbox') == '1' && (jQuery(this).data('data-type') === 'e2pdf-image' || jQuery(this).data('data-type') === 'e2pdf-barcode' || jQuery(this).data('data-type') === 'e2pdf-graph' || jQuery(this).data('data-type') === 'e2pdf-signature'))) {
                    jQuery(this).css('width', parseFloat(jQuery(this).css('width')) * width_diff);
                    jQuery(this).css('height', parseFloat(jQuery(this).css('height')) * width_diff);
                } else {
                    jQuery(this).css('width', parseFloat(jQuery(this).css('width')) * width_diff);
                    jQuery(this).css('height', parseFloat(jQuery(this).css('height')) * height_diff);
                }
            });
            el.css('width', width);
            el.css('height', height);
            el.attr('data-width', width);
            el.attr('data-height', height);
        }
    },
    // e2pdf.contextMenu
    contextMenu: function (e, el) {
        e2pdf.delete('.e2pdf-context');
        jQuery('.e2pdf-page').css('z-index', '');
        var menu = jQuery('<div>', {'class': 'e2pdf-context'});
        if (el.hasClass('e2pdf-page')) {
            var parent = el;
            menu.append(jQuery('<ul>', {'class': 'e2pdf-context-menu'}));
            menu.find('ul.e2pdf-context-menu').append(
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-paste e2pdf-link', 'type': 'elements', 'disabled': e2pdf.storage.get('elements') !== null ? false : 'disabled'}).html(e2pdf.lang.get('Paste'))
                    ),
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-paste e2pdf-link', 'type': 'elements-in-place', 'disabled': e2pdf.storage.get('elements') !== null ? false : 'disabled'}).html(e2pdf.lang.get('Paste in Place'))
                    ),
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-page-options e2pdf-modal', 'data-modal': 'page-options'}).html(e2pdf.lang.get('Properties'))
                    )
                    );
        } else {
            if (!el.hasClass('e2pdf-selected')) {
                e2pdf.element.unselect();
                e2pdf.element.select(el);
            }

            var parent = el.closest('.e2pdf-page');
            menu.append(jQuery('<ul>', {'class': 'e2pdf-context-menu'}));
            if (Object.keys(e2pdf.element.selected).length == 1 && el.data('data-type') !== 'e2pdf-rectangle' && el.data('data-type') !== 'e2pdf-page-number') {
                if (el.hasClass('e2pdf-focused') && (el.data('data-type') === 'e2pdf-input' || el.data('data-type') === 'e2pdf-textarea' || el.data('data-type') === 'e2pdf-html')) {
                    menu.find('ul.e2pdf-context-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-visual'}).html(e2pdf.lang.get('Insert Mapped'))
                            ));
                } else {
                    menu.find('ul.e2pdf-context-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-visual'}).html(e2pdf.lang.get('Map Field'))
                            ));
                }
            }

            if (Object.keys(e2pdf.element.selected).length == 1 && (el.data('data-type') === 'e2pdf-image' || el.data('data-type') === 'e2pdf-link' || (el.data('data-type') === 'e2pdf-signature' && !e2pdf.properties.getValue(el, 'esig', 'checkbox')))) {
                menu.find('ul.e2pdf-context-menu').append(
                        jQuery('<li>').append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-upload'}).html(e2pdf.lang.get('Media Library'))
                        ));
            }

            menu.find('ul.e2pdf-context-menu').append(
                    jQuery('<li>', {'class': 'e2pdf-inner-context-menu'}).append(
                    jQuery('<a>', {'href': 'javascript:void(0);'}).append(jQuery('<span>').html(e2pdf.lang.get('Lock / Hide')), jQuery('<span>', {'class': 'e2pdf-inner-context-arrow'}))
                    ,
                    jQuery('<ul>', {'class': 'e2pdf-sub-context-menu'}).append(
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': el.hasClass('e2pdf-locked') ? 'e2pdf-unlock' : 'e2pdf-lock'}).html(el.hasClass('e2pdf-locked') ? e2pdf.lang.get('Unlock') : e2pdf.lang.get('Lock'))
                    ),
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': el.hasClass('e2pdf-hide') ? 'e2pdf-unhidden' : 'e2pdf-hidden'}).html(el.hasClass('e2pdf-hide') ? e2pdf.lang.get('Unhide') : e2pdf.lang.get('Hide'))
                    ))
                    ));
            menu.find('ul.e2pdf-context-menu').append(
                    jQuery('<li>', {'class': 'e2pdf-inner-context-menu'}).append(
                    jQuery('<a>', {'href': 'javascript:void(0);'}).append(jQuery('<span>').html(e2pdf.lang.get('Copy')), jQuery('<span>', {'class': 'e2pdf-inner-context-arrow'}))
                    ,
                    jQuery('<ul>', {'class': 'e2pdf-sub-context-menu e2pdf-copy-menu'}).append(
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-copy', 'type': 'elements'}).html(Object.keys(e2pdf.element.selected).length > 1 ? e2pdf.lang.get('Elements') : e2pdf.lang.get('Element'))
                    ))
                    ),
                    jQuery('<li>', {'class': 'e2pdf-inner-context-menu e2pdf-paste-menu e2pdf-hide'}).append(
                    jQuery('<a>', {'href': 'javascript:void(0);'}).append(jQuery('<span>').html(e2pdf.lang.get('Paste')), jQuery('<span>', {'class': 'e2pdf-inner-context-arrow'}))
                    ,
                    jQuery('<ul>', {'class': 'e2pdf-sub-context-menu e2pdf-paste-menu'})
                    ),
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-resize'}).html(e2pdf.lang.get('Resize'))
                    ),
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-cut'}).html(e2pdf.lang.get('Cut'))
                    ),
                    jQuery('<li>').append(
                    jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-delete'}).html(e2pdf.lang.get('Delete'))
                    ));
            if (Object.keys(e2pdf.element.selected).length == 1 || e2pdf.storage.get('style') !== null) {
                if (Object.keys(e2pdf.element.selected).length == 1) {
                    menu.find('ul.e2pdf-copy-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-copy', 'type': 'style'}).html(e2pdf.lang.get('Style'))
                            ));
                }
                if (e2pdf.storage.get('style') !== null) {
                    menu.find('li.e2pdf-paste-menu').removeClass('e2pdf-hide');
                    menu.find('ul.e2pdf-paste-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-paste', 'type': 'style'}).html(e2pdf.lang.get('Style'))
                            ));
                }
            }

            if (Object.keys(e2pdf.element.selected).length == 1 || e2pdf.storage.get('width') != null) {
                if (Object.keys(e2pdf.element.selected).length == 1) {
                    menu.find('ul.e2pdf-copy-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-copy', 'type': 'width'}).html(e2pdf.lang.get('Width'))
                            ));
                }
                if (e2pdf.storage.get('width') != null) {
                    menu.find('li.e2pdf-paste-menu').removeClass('e2pdf-hide');
                    menu.find('ul.e2pdf-paste-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-paste', 'type': 'width'}).html(e2pdf.lang.get('Width'))
                            ));
                }
            }

            if (Object.keys(e2pdf.element.selected).length == 1 || e2pdf.storage.get('height') != null) {

                if (Object.keys(e2pdf.element.selected).length == 1) {
                    menu.find('ul.e2pdf-copy-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-copy', 'type': 'height'}).html(e2pdf.lang.get('Height'))
                            ));
                }

                if (e2pdf.storage.get('height') != null) {
                    menu.find('li.e2pdf-paste-menu').removeClass('e2pdf-hide');
                    menu.find('ul.e2pdf-paste-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-paste', 'type': 'height'}).html(e2pdf.lang.get('Height'))
                            ));
                }
            }

            if (Object.keys(e2pdf.element.selected).length == 1 || e2pdf.storage.get('actions') !== null) {

                if (Object.keys(e2pdf.element.selected).length == 1) {
                    menu.find('ul.e2pdf-copy-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-copy', 'type': 'actions'}).html(e2pdf.lang.get('Actions'))
                            ));
                }

                if (e2pdf.storage.get('actions') !== null) {
                    menu.find('li.e2pdf-paste-menu').removeClass('e2pdf-hide');
                    menu.find('ul.e2pdf-paste-menu').append(
                            jQuery('<li>').append(
                            jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-paste', 'type': 'actions'}).html(e2pdf.lang.get('Actions'))
                            ));
                }
            }

            if (Object.keys(e2pdf.element.selected).length == 1) {
                menu.find('ul.e2pdf-context-menu').append(
                        jQuery('<li>').append(
                        jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-properties e2pdf-modal', 'data-modal': 'properties'}).html(e2pdf.lang.get('Properties'))
                        ));
            }
        }
        parent.css('z-index', '1');
        menu.hide().appendTo(parent);
        var pos_x = (e.pageX - parent.offset().left) / e2pdf.zoom.zoom;
        if ((parent.closest('.e2pdf-tpl').width() - 20 < e.pageX - parent.closest('.e2pdf-tpl').offset().left + (menu.width() * e2pdf.zoom.zoom * 2)) && (e.pageX - parent.closest('.e2pdf-tpl').offset().left > (menu.width() * e2pdf.zoom.zoom * 2))) {
            pos_x = pos_x - menu.width();
            menu.find('ul.e2pdf-context-menu').addClass('e2pdf-context-right');
        }
        var pos_y = (e.pageY - parent.offset().top) / e2pdf.zoom.zoom;
        if ((parent.closest('.e2pdf-tpl').height() - 20 < e.pageY - parent.closest('.e2pdf-tpl').offset().top + (menu.height() * e2pdf.zoom.zoom * 2)) && (e.pageY - parent.closest('.e2pdf-tpl').offset().top > (menu.height() * e2pdf.zoom.zoom * 2))) {
            pos_y = pos_y - menu.height();
            menu.addClass('e2pdf-context-bottom');
        }
        menu.css({top: pos_y + "px", left: pos_x + "px"});
        menu.show();
    },
    // e2pdf.delete
    delete: function (el) {
        jQuery(el).remove();
    },
    // e2pdf.element
    element: {
        // e2pdf.element.selected
        selected: [],
        // e2pdf.element.init
        init: function (el) {
            if (el.data('data-type') === 'e2pdf-html') {
                if (e2pdf.properties.getValue(el, 'wysiwyg_disable', 'checkbox') == '1') {
                    e2pdf.properties.set(el, 'value', el.find('.e2pdf-html').val());
                } else {
                    e2pdf.properties.set(el, 'value', el.find('.e2pdf-html').html());
                }
            } else if (el.data('data-type') === 'e2pdf-input') {
                e2pdf.properties.set(el, 'value', el.find('.e2pdf-input').val());
            } else if (el.data('data-type') === 'e2pdf-textarea') {
                e2pdf.properties.set(el, 'value', el.find('.e2pdf-textarea').val());
            }
            e2pdf.properties.set(el, 'width', parseFloat(el.css('width')));
            e2pdf.properties.set(el, 'height', parseFloat(el.css('height')));
        },
        // e2pdf.element.create
        create: function (type, page, properties, actions, default_properties, onload, element_id) {
            var size = parseFloat(jQuery('#e2pdf-line-height').val()) + 4;
            var min_height = 2;
            var min_width = 2;
            if (!properties) {
                properties = {};
            }
            if (!actions) {
                actions = {};
            }
            switch (type) {
                case 'e2pdf-input':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = '200';
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = size;
                        }
                        if (!properties.hasOwnProperty('border')) {
                            properties['border'] = '1';
                        }
                        if (!properties.hasOwnProperty('border_color')) {
                            properties['border_color'] = '#000000';
                        }
                    }
                    var element =
                            jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<input>', {'type': 'text', 'class': 'e2pdf-input e2pdf-inner-element'}).val(properties['value'] ? properties['value'] : ''),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-textarea':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = '200';
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = '100';
                        }
                        if (!properties.hasOwnProperty('border')) {
                            properties['border'] = '1';
                        }
                        if (!properties.hasOwnProperty('border_color')) {
                            properties['border_color'] = '#000000';
                        }
                    }

                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<textarea>', {'type': 'text', 'class': 'e2pdf-textarea e2pdf-inner-element'}).val(properties['value'] ? properties['value'] : ''),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-checkbox':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = size;
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = size;
                        }
                        if (!properties.hasOwnProperty('option')) {
                            properties['option'] = 'option';
                        }
                        if (!properties.hasOwnProperty('border')) {
                            properties['border'] = '1';
                        }
                        if (!properties.hasOwnProperty('border_color')) {
                            properties['border_color'] = '#000000';
                        }
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<input>', {'type': 'checkbox', 'class': 'e2pdf-checkbox e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-radio':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = size;
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = size;
                        }
                        if (!properties.hasOwnProperty('group')) {
                            properties['group'] = 'group';
                        }
                        if (!properties.hasOwnProperty('option')) {
                            properties['option'] = 'option';
                        }
                        if (!properties.hasOwnProperty('border')) {
                            properties['border'] = '1';
                        }
                        if (!properties.hasOwnProperty('border_color')) {
                            properties['border_color'] = '#000000';
                        }
                        if (!properties.hasOwnProperty('text_type')) {
                            properties['text_type'] = 'circle';
                        }

                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<input>', {'type': 'radio', 'class': 'e2pdf-radio e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-select':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = '200';
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = size;
                        }
                        if (!properties.hasOwnProperty('options')) {
                            properties['options'] = '';
                        }
                        if (!properties.hasOwnProperty('border')) {
                            properties['border'] = '1';
                        }
                        if (!properties.hasOwnProperty('border_color')) {
                            properties['border_color'] = '#000000';
                        }
                    }

                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<select>', {'class': 'e2pdf-select e2pdf-inner-element'}).append(
                            ),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-signature':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = '200';
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = '75';
                        }
                        if (!properties.hasOwnProperty('vertical')) {
                            properties['vertical'] = 'bottom';
                        }
                        if (!properties.hasOwnProperty('block_dimension')) {
                            properties['block_dimension'] = '1';
                        }
                        if (!properties.hasOwnProperty('dimension')) {
                            properties['dimension'] = '1';
                        }
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-loader e2pdf-resizable'}).append(
                            jQuery('<img>', {'class': 'e2pdf-signature e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-html':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width')) {
                            properties['width'] = '200';
                        } else if (properties['width'] === 'auto') {
                            properties['width'] = size;
                        }
                        if (!properties.hasOwnProperty('height')) {
                            properties['height'] = size;
                        } else if (properties['height'] === 'auto') {
                            delete properties['height'];
                        }
                        if (!properties.hasOwnProperty('html_worker')) {
                            properties['html_worker'] = '1';
                        }
                        if (!properties.hasOwnProperty('css_priority')) {
                            properties['css_priority'] = '1';
                        }
                    }
                    if (properties['wysiwyg_disable'] == '1') {
                        var element =
                                jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                                jQuery('<textarea>', {'class': 'content e2pdf-html e2pdf-inner-element'}).html(properties['value'] ? properties['value'] : ''),
                                jQuery('<i>', {'class': 'e2pdf-drag'})
                                );
                    } else {
                        var element =
                                jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                                jQuery('<div>', {'contenteditable': true, 'class': 'content e2pdf-html e2pdf-inner-element'}).html(properties['value'] ? properties['value'] : ''),
                                jQuery('<i>', {'class': 'e2pdf-drag'})
                                );
                    }
                    break;
                case 'e2pdf-image':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('dimension')) {
                            properties['dimension'] = '1';
                        }
                        if (!properties.hasOwnProperty('opacity')) {
                            properties['opacity'] = '1';
                        }
                        if (!properties.hasOwnProperty('block_dimension')) {
                            properties['block_dimension'] = '1';
                        }
                        if (!properties.hasOwnProperty('vertical')) {
                            properties['vertical'] = 'bottom';
                        }
                    } else {
                        // Backward Compatibility
                        if (!properties.hasOwnProperty('block_dimension') && properties.hasOwnProperty('scale') && (properties['scale'] == '1' || properties['scale'] == '2')) {
                            properties['block_dimension'] = '1';
                        }
                        if (!properties.hasOwnProperty('opacity')) {
                            properties['opacity'] = '1';
                        }
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-loader e2pdf-resizable', 'width': '100px', height: '100px'}).append(
                            jQuery('<img>', {'class': 'e2pdf-image e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-rectangle':
                    min_height = 1;
                    min_width = 1;
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = '200';
                        }
                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = '5';
                        }
                        if (!properties.hasOwnProperty('background')) {
                            properties['background'] = '#000000';
                        }
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<div>', {'class': 'content e2pdf-rectangle e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-link':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width') || properties['width'] === 'auto') {
                            properties['width'] = '200';
                        }

                        if (!properties.hasOwnProperty('height') || properties['height'] === 'auto') {
                            properties['height'] = size;
                        }
                    }
                    var element =
                            jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<div>', {'class': 'content e2pdf-link e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-qrcode':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('color')) {
                            properties['color'] = '#000000';
                        }
                        if (!properties.hasOwnProperty('background')) {
                            properties['background'] = '#ffffff';
                        }
                        if (!properties.hasOwnProperty('wq')) {
                            properties['wq'] = '1';
                        }
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-loader e2pdf-resizable', 'width': '100px', height: '100px'}).append(
                            jQuery('<img>', {'class': 'e2pdf-qrcode e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-barcode':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('color')) {
                            properties['color'] = '#000000';
                        }
                        if (!properties.hasOwnProperty('text_color')) {
                            properties['text_color'] = '#000000';
                        }
                        if (!properties.hasOwnProperty('vertical')) {
                            properties['vertical'] = 'middle';
                        }
                        if (!properties.hasOwnProperty('horizontal')) {
                            properties['horizontal'] = 'center';
                        }
                        if (!properties.hasOwnProperty('wq')) {
                            properties['wq'] = '1';
                        }
                        if (!properties.hasOwnProperty('dimension')) {
                            properties['dimension'] = '1';
                        }
                        if (!properties.hasOwnProperty('background')) {
                            properties['background'] = '#ffffff';
                        }
                        if (!properties.hasOwnProperty('margin_top')) {
                            properties['margin_top'] = '10';
                        }
                        if (!properties.hasOwnProperty('margin_left')) {
                            properties['margin_left'] = '10';
                        }
                        if (!properties.hasOwnProperty('margin_right')) {
                            properties['margin_right'] = '10';
                        }
                        if (!properties.hasOwnProperty('margin_bottom')) {
                            properties['margin_bottom'] = '10';
                        }
                    } else {
                        // Backward Compatibility
                        if (!properties.hasOwnProperty('scale')) {
                            properties['scale'] = '1';
                        }
                        if (!properties.hasOwnProperty('margin_top')) {
                            properties['margin_top'] = '10';
                        }
                        if (!properties.hasOwnProperty('margin_left')) {
                            properties['margin_left'] = '10';
                        }
                        if (!properties.hasOwnProperty('margin_right')) {
                            properties['margin_right'] = '10';
                        }
                        if (!properties.hasOwnProperty('margin_bottom')) {
                            properties['margin_bottom'] = '10';
                        }
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-loader e2pdf-resizable', 'width': '200px', height: '75px'}).append(
                            jQuery('<img>', {'class': 'e2pdf-barcode e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-page-number':
                    if (default_properties) {
                        if (!properties.hasOwnProperty('width')) {
                            properties['width'] = '100';
                        } else if (properties['width'] === 'auto') {
                            properties['width'] = '100';
                        }
                        if (!properties.hasOwnProperty('height')) {
                            properties['height'] = size;
                        } else if (properties['height'] === 'auto') {
                            delete properties['height'];
                        }
                        if (!properties.hasOwnProperty('text_align')) {
                            properties['text_align'] = 'center';
                        }
                        if (!properties.hasOwnProperty('html_worker')) {
                            properties['html_worker'] = '1';
                        }
                        if (!properties.hasOwnProperty('value')) {
                            properties['value'] = '[e2pdf-page-number] / [e2pdf-page-total]';
                        }
                    }
                    var value = properties['value'] ? properties['value'] : '';
                    var element =
                            jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-resizable'}).append(
                            jQuery('<div>', {'class': 'content e2pdf-page-number e2pdf-inner-element'}).html(value.replace('[e2pdf-page-number]', '1').replace('[e2pdf-page-total]', '2')),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                case 'e2pdf-graph':
                    if (default_properties) {
                        // Graph
                        properties['g_palette'] = '#1e73be';
                        properties['g_stroke_colour'] = '#000000';
                        properties['g_stroke_width'] = '1';
                        properties['g_sort'] = '1';
                        // Titles
                        properties['g_graph_title_colour'] = '#000000';
                        properties['g_graph_title_space'] = '10';
                        // Labels
                        properties['g_label_colour'] = '#000000';
                        properties['g_label_font_size'] = '10';
                        properties['g_label_space'] = '10';
                        // Markers
                        properties['g_marker_colour'] = '#000000';
                        properties['g_marker_size'] = '3';
                        // Legend
                        properties['g_legend_back_colour'] = '#ffffff';
                        properties['g_legend_stroke_colour'] = '#000000';
                        properties['g_legend_stroke_width'] = '1';
                        properties['g_legend_entry_width'] = '20';
                        properties['g_legend_padding_x'] = '5';
                        properties['g_legend_padding_y'] = '5';
                        properties['g_legend_colour'] = '#000000';
                        properties['g_legend_font_size'] = '10';
                        properties['g_show_legend'] = '1';
                        // Grid
                        properties['g_grid_colour'] = '#eeeeee';
                        properties['g_grid_subdivision_colour'] = '#eeeeee';
                        properties['g_minimum_grid_spacing'] = '20';
                        properties['g_show_grid'] = '1';
                        properties['g_show_grid_subdivisions'] = '1';
                        // Axis
                        properties['g_axis_colour'] = '#000000';
                        properties['g_axis_font_size'] = '12';
                        properties['g_show_axis_v'] = '1';
                        properties['g_show_axis_text_v'] = '1';
                        properties['g_show_axis_h'] = '1';
                        properties['g_show_axis_text_h'] = '1';
                        properties['g_show_subdivisions'] = '1';
                        properties['g_axis_overlap'] = '5';
                        properties['g_axis_stroke_width_v'] = '2';
                        properties['g_axis_stroke_width_h'] = '2';
                        // Bar Label
                        properties['g_bar_label_colour'] = '#000000';
                        properties['g_bar_label_font_size'] = '10';
                        properties['g_bar_label_space'] = '3';
                        properties['g_show_bar_labels'] = '1';
                        properties['g_project_angle'] = '45';
                        properties['margin_top'] = '10';
                        properties['margin_left'] = '10';
                        properties['margin_right'] = '10';
                        properties['margin_bottom'] = '10';
                        properties['g_multiline'] = '2';
                    }
                    var element = jQuery('<div>', {'class': 'e2pdf-el-wrapper e2pdf-loader e2pdf-resizable', 'width': '250px', height: '150px'}).append(
                            jQuery('<img>', {'class': 'e2pdf-graph e2pdf-inner-element'}),
                            jQuery('<i>', {'class': 'e2pdf-drag'})
                            );
                    break;
                default:
                    break;
            }

            if (typeof element !== 'undefined') {
                element.contextmenu(function (e) {
                    e2pdf.contextMenu(e, element);
                    e.preventDefault();
                });
                if (!element_id) {
                    var last_id = 0;
                    jQuery('.e2pdf-tpl .e2pdf-element').each(function () {
                        var num_id = parseInt(jQuery(this).attr("data-element_id"));
                        if (num_id > last_id) {
                            last_id = num_id;
                        }
                    });
                    element_id = parseInt(last_id + 1);
                }
                element.addClass('e2pdf-element');
                element.attr('data-element_id', element_id);
                element.data('data-type', type);
                if (properties.hasOwnProperty('width')) {
                    element.css({'width': properties['width']});
                }
                if (properties.hasOwnProperty('height')) {
                    element.css({'height': properties['height']});
                }
                if (properties.hasOwnProperty('top') && properties.hasOwnProperty('left')) {
                    element.css({'top': properties['top'], 'left': properties['left']});
                }
                element.css({"position": "absolute"});
                e2pdf.properties.apply(element, properties, onload);
                e2pdf.actions.apply(element, actions);
                element.draggable({
                    cancel: '.no-drag',
                    handle: ".e2pdf-drag",
                    containment: jQuery(page),
                    stop: function (ev, ui) {
                        var page = jQuery(this).closest('.e2pdf-page');
                        for (var key in e2pdf.element.selected) {
                            var selected = e2pdf.element.selected[key];
                            if (selected.hasClass('e2pdf-width-auto')) {
                                selected.css({'width': 'auto'});
                            }
                            if (selected.hasClass('e2pdf-height-auto')) {
                                selected.css({'height': 'auto'});
                            }

                            e2pdf.properties.set(selected, 'top', Math.max(0, e2pdf.helper.getFloat(selected.css('top'))));
                            e2pdf.properties.set(selected, 'left', Math.max(0, e2pdf.helper.getFloat(selected.css('left'))));
                        }

                        jQuery('.page-options-icons').css('z-index', '');
                        e2pdf.event.fire('after.element.moved');
                        e2pdf.element.unselect();
                        jQuery(".e2pdf-guide-v, .e2pdf-guide-h").hide();
                    },
                    drag: function (ev, ui) {
                        var left = (ev.clientX - e2pdf.zoom.click.x + ui.originalPosition.left) / e2pdf.zoom.zoom;
                        var top = (ev.clientY - e2pdf.zoom.click.y + ui.originalPosition.top) / e2pdf.zoom.zoom;
                        left = Math.min(left, e2pdf.static.drag.max_left);
                        top = Math.min(top, e2pdf.static.drag.max_top);
                        ui.position = {
                            left: Math.max(e2pdf.static.drag.min_left, left),
                            top: Math.max(e2pdf.static.drag.min_top, top)
                        };
                        var diff_top = ui.position.top - e2pdf.properties.getValue(jQuery(this), 'top', 'float');
                        var diff_left = ui.position.left - e2pdf.properties.getValue(jQuery(this), 'left', 'float');
                        for (var key in e2pdf.element.selected) {
                            var selected = e2pdf.element.selected[key];
                            if (!selected.is(jQuery(this))) {
                                selected.finish().animate({
                                    left: e2pdf.properties.getValue(selected, 'left', 'float') + diff_left,
                                    top: e2pdf.properties.getValue(selected, 'top', 'float') + diff_top
                                }, 0);
                            }
                        }

                        var guides = {top: {dist: e2pdf.static.guide.distance + 1}, left: {dist: e2pdf.static.guide.distance + 1}};
                        var w = parseFloat(jQuery(this).css('width'));
                        var h = parseFloat(jQuery(this).css('height'));
                        var el_guides = e2pdf.guide.calc(null, ui.position, w, h);
                        jQuery.each(e2pdf.static.guide.guides, function (i, guide) {
                            jQuery.each(el_guides, function (i, elemGuide) {
                                if (guide.type == elemGuide.type) {
                                    var prop = guide.type == "h" ? "top" : "left";
                                    var d = Math.abs(elemGuide[prop] - guide[prop]);
                                    if (d < guides[prop].dist) {
                                        guides[prop].dist = d;
                                        guides[prop].offset = elemGuide[prop] - ui.position[prop];
                                        guides[prop].guide = guide;
                                    }
                                }
                            });
                        });
                        if (guides.top.dist <= e2pdf.static.guide.distance) {
                            jQuery(this).closest('.e2pdf-page').find(".e2pdf-guide-h").css("top", guides.top.guide.top).show();
                            var snap_top = guides.top.guide.top - guides.top.offset;
                            if (e2pdf.static.drag.max_top >= snap_top && snap_top >= e2pdf.static.drag.min_top) {
                                ui.position.top = snap_top;
                                var guide_diff_top = ui.position.top - e2pdf.properties.getValue(jQuery(this), 'top', 'float');
                                for (var key in e2pdf.element.selected) {
                                    var selected = e2pdf.element.selected[key];
                                    if (!selected.is(jQuery(this))) {
                                        selected.finish().animate({
                                            top: e2pdf.properties.getValue(selected, 'top', 'float') + guide_diff_top
                                        }, 0);
                                    }
                                }
                            }
                        } else {
                            jQuery(".e2pdf-guide-h").hide();
                        }

                        if (guides.left.dist <= e2pdf.static.guide.distance) {
                            jQuery(this).closest('.e2pdf-page').find(".e2pdf-guide-v").css("left", guides.left.guide.left).show();
                            var snap_left = guides.left.guide.left - guides.left.offset;
                            if (e2pdf.static.drag.max_left >= snap_left && snap_left >= e2pdf.static.drag.min_left) {
                                ui.position.left = snap_left;
                                var guide_diff_left = ui.position.left - e2pdf.properties.getValue(jQuery(this), 'left', 'float');
                                for (var key in e2pdf.element.selected) {
                                    var selected = e2pdf.element.selected[key];
                                    if (!selected.is(jQuery(this))) {
                                        selected.finish().animate({
                                            left: e2pdf.properties.getValue(selected, 'left', 'float') + guide_diff_left
                                        }, 0);
                                    }
                                }
                            }
                        } else {
                            jQuery(".e2pdf-guide-v").hide();
                        }

                    },
                    start: function (ev, ui) {
                        e2pdf.element.select(jQuery(this));
                        e2pdf.static.drag.min_left = 0;
                        e2pdf.static.drag.max_left = jQuery(this).closest('.e2pdf-page').width();
                        e2pdf.static.drag.min_top = 0;
                        e2pdf.static.drag.max_top = jQuery(this).closest('.e2pdf-page').height();
                        for (var key in e2pdf.element.selected) {
                            var selected = e2pdf.element.selected[key];
                            if (selected.hasClass('e2pdf-width-auto')) {
                                selected.css({"width": "auto"});
                            }

                            if (selected.hasClass('e2pdf-height-auto')) {
                                selected.css({"height": "auto"});
                            }

                            var padding_top = e2pdf.helper.getFloat(selected.css('padding-top'));
                            var padding_left = e2pdf.helper.getFloat(selected.css('padding-left'));
                            var padding_right = e2pdf.helper.getFloat(selected.css('padding-right'));
                            var padding_bottom = e2pdf.helper.getFloat(selected.css('padding-bottom'));
                            var border_top = e2pdf.helper.getFloat(selected.css('border-top-width'));
                            var border_left = e2pdf.helper.getFloat(selected.css('border-left-width'));
                            var border_right = e2pdf.helper.getFloat(selected.css('border-right-width'));
                            var border_bottom = e2pdf.helper.getFloat(selected.css('border-bottom-width'));
                            e2pdf.static.drag.min_left = Math.max(e2pdf.properties.getValue(jQuery(this), 'left', 'float') - e2pdf.properties.getValue(selected, 'left', 'float'), e2pdf.static.drag.min_left);
                            e2pdf.static.drag.min_top = Math.max(e2pdf.properties.getValue(jQuery(this), 'top', 'float') - e2pdf.properties.getValue(selected, 'top', 'float'), e2pdf.static.drag.min_top);
                            e2pdf.static.drag.max_left = Math.min(selected.closest('.e2pdf-page').width() - selected.width() - padding_left - padding_right - border_left - border_right + (e2pdf.properties.getValue(jQuery(this), 'left', 'float') - e2pdf.properties.getValue(selected, 'left', 'float')), e2pdf.static.drag.max_left);
                            e2pdf.static.drag.max_top = Math.min(selected.closest('.e2pdf-page').height() - selected.height() - padding_top - padding_bottom - border_top - border_bottom + (e2pdf.properties.getValue(jQuery(this), 'top', 'float') - e2pdf.properties.getValue(selected, 'top', 'float')), e2pdf.static.drag.max_top);
                        }

                        e2pdf.zoom.click.x = ev.clientX;
                        e2pdf.zoom.click.y = ev.clientY;
                        jQuery('.page-options-icons').css('z-index', -1);
                    }
                });
                if (element.hasClass('e2pdf-resizable')) {
                    element.resizable({
                        handles: 'n, e, s, w, ne, se, sw, nw',
                        aspectRatio: false,
                        minHeight: min_height,
                        minWidth: min_width,
                        start: function (ev, ui) {
                            var _process = function (el, resize) {

                                var left = e2pdf.helper.getFloat(el.css('left'));
                                var top = e2pdf.helper.getFloat(el.css('top'));
                                var page_width = e2pdf.helper.getFloat(el.closest('.e2pdf-page').css('width'));
                                var page_height = e2pdf.helper.getFloat(el.closest('.e2pdf-page').css('height'));
                                var padding_top = e2pdf.helper.getFloat(el.css('padding-top'));
                                var padding_left = e2pdf.helper.getFloat(el.css('padding-left'));
                                var padding_right = e2pdf.helper.getFloat(el.css('padding-right'));
                                var padding_bottom = e2pdf.helper.getFloat(el.css('padding-bottom'));
                                var border_top = e2pdf.helper.getFloat(el.css('border-top-width'));
                                var border_left = e2pdf.helper.getFloat(el.css('border-left-width'));
                                var border_right = e2pdf.helper.getFloat(el.css('border-right-width'));
                                var border_bottom = e2pdf.helper.getFloat(el.css('border-bottom-width'));
                                var width = e2pdf.helper.getFloat(el.css('width'));
                                var height = e2pdf.helper.getFloat(el.css('height'));
                                el.resizable("option", "maxWidth", page_width - left);
                                el.resizable("option", "maxHeight", page_height - top);
                                if (jQuery(ev.originalEvent.target).hasClass('ui-resizable-w') || jQuery(ev.originalEvent.target).hasClass('ui-resizable-sw')) {
                                    el.resizable("option", "maxWidth", left + width);
                                } else if (jQuery(ev.originalEvent.target).hasClass('ui-resizable-n') || jQuery(ev.originalEvent.target).hasClass('ui-resizable-ne')) {
                                    el.resizable("option", "maxHeight", top + height);
                                } else if (jQuery(ev.originalEvent.target).hasClass('ui-resizable-nw')) {
                                    el.resizable("option", "maxWidth", left + width);
                                    el.resizable("option", "maxHeight", top + height);
                                }

                                if (resize) {
                                    ui.originalSize.width = ui.originalSize.width + padding_left + padding_right + border_left + border_right;
                                    ui.originalSize.height = ui.originalSize.height + padding_top + padding_bottom + border_top + border_bottom;
                                }
                            };
                            _process(jQuery(this), true);
                            e2pdf.zoom.click.x = ev.clientX;
                            e2pdf.zoom.click.y = ev.clientY;
                            jQuery('.e2pdf-selected').not(jQuery(this)).each(function () {
                                var el = jQuery(this);
                                var width = e2pdf.helper.getFloat(el.css('width'));
                                var height = e2pdf.helper.getFloat(el.css('height'));
                                el.data("ui-resizable-alsoresize", {
                                    width: width,
                                    height: height,
                                    left: e2pdf.helper.getFloat(el.css('left')),
                                    top: e2pdf.helper.getFloat(el.css('top'))
                                });
                                _process(jQuery(this), false);
                            });
                        },
                        resize: function (ev, ui) {
                            if (jQuery(this).data('uiResizable')._aspectRatio && ui.element.data("ui-resizable") && typeof ui.element.data("ui-resizable").axis != 'undefined') {
                                var axis = ui.element.data("ui-resizable").axis;
                                if (axis != 'nw' && axis != 'sw') {
                                    ui.size.width += jQuery(ui.element).outerWidth() - jQuery(ui.element).width();
                                    ui.size.height += jQuery(ui.element).outerHeight() - jQuery(ui.element).height();
                                }
                            }

                            var delta = {
                                height: (jQuery(ui.element).outerHeight() - ui.originalSize.height) || 0,
                                width: (jQuery(ui.element).outerWidth() - ui.originalSize.width) || 0,
                                top: (ui.position.top - ui.originalPosition.top) || 0,
                                left: (ui.position.left - ui.originalPosition.left) || 0
                            };
                            jQuery('.e2pdf-selected').not(jQuery(this)).each(function () {
                                var el = jQuery(this), start = jQuery(this).data("ui-resizable-alsoresize");
                                var style = {};
                                var css = ["width", "height", "top", "left"];
                                jQuery.each(css, function (i, prop) {
                                    var sum = (start[prop] || 0) + (delta[prop] || 0);
                                    if (sum) {
                                        if (prop == 'width') {
                                            if (sum >= 0 && sum <= el.resizable("option", "maxWidth")) {
                                                style[prop] = sum;
                                            }
                                        } else if (prop == 'height') {
                                            if (sum >= 0 && sum <= el.resizable("option", "maxHeight")) {
                                                style[prop] = sum;
                                            }
                                        } else if (prop == 'left') {
                                            if (sum >= 0) {
                                                style[prop] = sum;
                                            }
                                        } else if (prop == 'top') {
                                            if (sum >= 0) {
                                                style[prop] = sum;
                                            }
                                        }
                                    }
                                });
                                el.css(style);
                            });
                        },
                        stop: function (event, ui) {
                            var _process = function (el, width, height) {
                                if (el.data('data-type') === 'e2pdf-signature' || el.data('data-type') === 'e2pdf-image' || el.data('data-type') === 'e2pdf-qrcode' || el.data('data-type') === 'e2pdf-graph') {
                                    width += el.outerWidth() - el.width();
                                    height += el.outerWidth() - el.width();
                                }
                                e2pdf.properties.set(el, 'width', width);
                                e2pdf.properties.set(el, 'height', height);
                                e2pdf.properties.set(el, 'top', Math.max(0, e2pdf.helper.getFloat(el.css('top'))));
                                e2pdf.properties.set(el, 'left', Math.max(0, e2pdf.helper.getFloat(el.css('left'))));
                                if (el.data('data-type') === 'e2pdf-signature' || el.data('data-type') === 'e2pdf-image' || el.data('data-type') === 'e2pdf-qrcode' || el.data('data-type') === 'e2pdf-graph') {
                                    e2pdf.properties.render(el);
                                }
                            };
                            _process(jQuery(this), jQuery(this).width(), jQuery(this).height());
                            jQuery('.e2pdf-selected').not(jQuery(this)).each(function () {
                                _process(jQuery(this), jQuery(this).width(), jQuery(this).height());
                                jQuery(this).removeData("resizable-alsoresize");
                            });
                        }
                    });
                }
                if (!onload) {
                    e2pdf.event.fire('after.element.create');
                }
                return element;
            } else {
                return false;
            }
        },
        // e2pdf.element.children
        children: function (el) {
            var children = el.find('.' + el.data('data-type'));
            return children;
        },
        // e2pdf.element.select
        select: function (el) {
            var selected = false;
            for (var key in e2pdf.element.selected) {
                if (e2pdf.element.selected[key].is(el)) {
                    selected = true;
                }
            }
            if (!selected) {
                el.addClass('e2pdf-selected');
                e2pdf.element.selected.push(el);
            }
        },
        // e2pdf.element.unselect
        unselect: function (el) {
            if (!el) {
                jQuery('.e2pdf-selected').removeClass('e2pdf-selected');
                e2pdf.element.selected = [];
            } else {
                for (var key in e2pdf.element.selected) {
                    if (e2pdf.element.selected[key].is(el)) {
                        el.removeClass('e2pdf-selected');
                        delete e2pdf.element.selected[key];
                    }
                }
            }
        },
        // e2pdf.element.unfocus
        unfocus: function (el) {
            e2pdf.wysiwyg.helper.dropSelection();
            if (!el) {
                jQuery('.e2pdf-focused').removeClass('e2pdf-focused');
                jQuery('.e2pdf-el-wrapper').find('.e2pdf-inner-element:focus').each(function () {
                    jQuery(this).blur();
                });
            } else {
                el.find('.e2pdf-inner-element').blur();
                el.removeClass('e2pdf-focused');
            }
        },
        // e2pdf.element.focus
        focus: function (el) {
            var el_inner = el.find('.e2pdf-inner-element');
            el_inner.focus();
            el.addClass('e2pdf-focused');
        },
        // e2pdf.element.hide
        hide: function (el) {
            el.addClass('e2pdf-hide');
        },
        // e2pdf.element.show
        show: function (el) {
            el.removeClass('e2pdf-hide');
        },
        // e2pdf.element.delete
        delete: function (el) {
            el.remove();
            e2pdf.event.fire('after.element.delete');
        }
    },
    // e2pdf.storage
    storage: {
        get: function (key) {
            return localStorage.getItem('e2pdf_' + key) !== null ? JSON.parse(localStorage.getItem('e2pdf_' + key)) : null;
        },
        set: function (key, data) {
            localStorage.setItem('e2pdf_' + key, JSON.stringify(data));
        },
        delete: function (key) {
            localStorage.removeItem('e2pdf_' + key);
        }
    },
    // e2pdf.copy
    copy: function (key, el) {
        e2pdf.element.init(el);
        switch (key) {
            case 'style':
                var data = {
                    'type': el.data('data-type'),
                    'fields': e2pdf.properties.getFields(el)
                };
                e2pdf.storage.set(key, data);
                break;
            case 'width':
                var data = {
                    'width': e2pdf.properties.getValue(el, 'width', 'float')
                };
                e2pdf.storage.set(key, data);
                break;
            case 'height':
                var data = {
                    'height': e2pdf.properties.getValue(el, 'height', 'float')
                };
                e2pdf.storage.set(key, data);
                break;
            case 'elements':
                var elements = e2pdf.storage.get('elements');
                if (elements == null) {
                    elements = [];
                }
                var data = {
                    'type': el.data('data-type'),
                    'top': el.css('top'),
                    'left': el.css('left'),
                    'width': el.css('width'),
                    'height': el.css('height'),
                    'fields': e2pdf.properties.getFields(el),
                    'properties': e2pdf.properties.get(el),
                    'actions': e2pdf.actions.get(el)
                };
                elements.push(data);
                e2pdf.storage.set(key, elements);
                break;
            case 'actions':
                var data = {
                    'actions': e2pdf.actions.get(el)
                };
                e2pdf.storage.set(key, data);
                break;
        }
    },
    // e2pdf.paste
    paste: function (key, el) {
        if (e2pdf.storage.get(key) !== null || (key == 'elements-in-place' && e2pdf.storage.get('elements') !== null)) {
            switch (key) {
                case 'style':
                    if (e2pdf.storage.get('style').type == el.data('data-type')) {
                        e2pdf.element.init(el);
                        var groups = e2pdf.storage.get('style').fields;
                        for (var group_key in groups) {
                            var group = groups[group_key];
                            for (var field_key in group.fields) {
                                var group_field = group.fields[field_key];
                                if (group_field.type != 'link') {
                                    if (jQuery.inArray(group_field.key, [
                                        'page_id',
                                        'element_id',
                                        'element_type',
                                        'width',
                                        'height',
                                        'top',
                                        'left',
                                        'name',
                                        'field_name',
                                        'z_index',
                                        'group',
                                        'option',
                                        'options',
                                        'css',
                                        'parent',
                                        'value',
                                        'preg_pattern',
                                        'preg_replacement',
                                        'preg_match_all_pattern',
                                        'preg_match_all_output',
                                        'wysiwyg_disable',
                                        'multipage',
                                        'dynamic_height',
                                        'nl2br',
                                        'hide_if_empty',
                                        'hide_page_if_empty',
                                        'css_priority',
                                        'pdf_resample',
                                        'pdf_append',
                                        'html_worker',
                                        'esig',
                                        'dimension',
                                        'block_dimension',
                                        'keep_lower_size',
                                        'fill_image',
                                        'only_image',
                                        'hl',
                                        'placeholder',
                                        'pdf_page',
                                        'format',
                                        'g_type',
                                        'g_structure_key',
                                        'g_structure_value',
                                        'g_structure_colour',
                                        'g_structure_axis_text',
                                        'g_structure_legend_text',
                                        'g_structure_label',
                                        'g_structure_area',
                                        'g_structure_open',
                                        'g_structure_end',
                                        'g_structure_outliers',
                                        'g_structure_top',
                                        'g_structure_bottom',
                                        'g_structure_wtop',
                                        'g_structure_wbottom',
                                        'g_structure_high',
                                        'g_structure_low',
                                        'g_structured_data',
                                        'g_key_sep',
                                        'g_array_sep',
                                        'g_sub_array_sep',
                                        'g_graph_title',
                                        'g_label_v',
                                        'g_label_h',
                                        'g_legend_title'
                                    ]) === -1) {
                                        e2pdf.properties.set(el, group_field.key, group_field.value);
                                    }
                                }
                            }
                        }
                        e2pdf.properties.render(el);
                    }
                    break;
                case 'elements':
                case 'elements-in-place':
                    e2pdf.element.unselect();
                    var context = jQuery('.e2pdf-context');
                    var page = context.closest('.e2pdf-page');
                    var min_top = 99999999;
                    var min_left = 99999999;
                    var left_correction = 0;
                    var top_correction = 0;
                    if (key !== 'elements-in-place') {
                        for (var element in e2pdf.storage.get('elements')) {
                            var buffered = e2pdf.storage.get('elements')[element];
                            var properties = buffered.properties;
                            min_top = Math.min(parseFloat(properties['top']), min_top);
                            min_left = Math.min(parseFloat(properties['left']), min_left);
                        }
                        for (var element in e2pdf.storage.get('elements')) {
                            var buffered = e2pdf.storage.get('elements')[element];
                            var properties = buffered.properties;
                            var top = parseFloat(e2pdf.helper.getFloat(context.css('top')) + (parseFloat(properties['top'] - min_top)));
                            if (context.hasClass('e2pdf-context-bottom')) {
                                top += context.height();
                            }
                            var left = parseFloat(e2pdf.helper.getFloat(context.css('left')) + (parseFloat(properties['left']) - min_left));
                            if ((left + parseFloat(properties['width'])) > parseFloat(page.css('width'))) {
                                var correction = left - (parseFloat(page.css('width')) - parseFloat(properties['width']));
                                left_correction = Math.max(correction, left_correction);
                            }
                            if ((top + parseFloat(properties['height'])) > parseFloat(page.css('height'))) {
                                var correction = top - (parseFloat(page.css('height')) - parseFloat(properties['height']));
                                top_correction = Math.max(correction, top_correction);
                            }
                        }
                    }
                    for (var element in e2pdf.storage.get('elements')) {
                        var buffered = e2pdf.storage.get('elements')[element];
                        var properties = buffered.properties;
                        var actions = buffered.actions;
                        if (key !== 'elements-in-place') {
                            var top = parseFloat(e2pdf.helper.getFloat(context.css('top')) + (parseFloat(properties['top'] - min_top)) - top_correction);
                            if (context.hasClass('e2pdf-context-bottom')) {
                                top += context.height();
                            }
                            var left = parseFloat(e2pdf.helper.getFloat(context.css('left')) + (parseFloat(properties['left']) - min_left) - left_correction);
                            properties['top'] = top;
                            properties['left'] = left;
                        }
                        var el = e2pdf.element.create(buffered.type, page, properties, actions);
                        page.append(el);
                        e2pdf.properties.render(el);
                        e2pdf.element.select(el);
                    }
                    e2pdf.event.fire('after.element.paste');
                    break;
                case 'actions':
                    e2pdf.actions.apply(el, e2pdf.storage.get('actions').actions);
                    break;
                case 'width':
                    e2pdf.properties.set(el, 'width', e2pdf.storage.get('width').width);
                    e2pdf.properties.render(el);
                    break;
                case 'height':
                    e2pdf.properties.set(el, 'height', e2pdf.storage.get('height').height);
                    e2pdf.properties.render(el);
                    break;
            }
        }
    },
    // e2pdf.wysiwyg
    wysiwyg: {
        // e2pdf.wysiwyg.apply
        apply: function (el) {
            var command = el.attr('data-command');
            var node = jQuery(e2pdf.wysiwyg.helper.getSelectedNode());
            if (command !== 'undo' && command !== 'redo' && command !== 'color') {
                if (node.hasClass('e2pdf-element')) {
                    var html_node = node;
                } else {
                    var html_node = node.closest('.e2pdf-element');
                }
                if (html_node && html_node.find('.e2pdf-html').length > 0) {
                    if (html_node.find('textarea.e2pdf-html').length > 0 || node.is('textarea')) {
                        alert(e2pdf.lang.get('The WYSIWYG editor is disabled for this HTML object'));
                        return;
                    }
                } else {
                    alert(e2pdf.lang.get('WYSIWYG can only be applied within HTML elements'));
                    return;
                }
            }
            if (command === 'H1') {
                if (node.is("h1") && document.getSelection().toString() === node.text()) {
                    e2pdf.wysiwyg.clear('h1');
                } else {
                    var html = jQuery('<h1>').html(e2pdf.wysiwyg.helper.getSelectionHtml()).prop('outerHTML');
                    document.execCommand('insertHTML', false, html);
                }
            } else if (command === 'H2') {
                if (node.is("h2") && document.getSelection().toString() === node.text()) {
                    e2pdf.wysiwyg.clear('h2');
                } else {
                    var html = jQuery('<h2>').html(e2pdf.wysiwyg.helper.getSelectionHtml()).prop('outerHTML');
                    document.execCommand('insertHTML', false, html);
                }
            } else if (command === 'createlink') {
                url = prompt(e2pdf.lang.get('Enter link here') + ': ', 'http:\/\/');
                document.execCommand(command, false, url);
            } else if (command === 'font-size') {
                var font_size = el.find('option:selected').html();
                if (node.is("span") && document.getSelection().toString() === node.text()) {
                    var html = node.css('font-size', font_size + "px").prop('outerHTML');
                } else {
                    var html = jQuery('<span>').html(e2pdf.wysiwyg.helper.getSelectionHtml()).css('font-size', font_size + "px").prop('outerHTML');
                }
                document.execCommand('insertHTML', false, html);
                el.val('');
            } else if (command === 'font') {
                e2pdf.font.load(el);
                var font = el.find('option:selected').html();
                if (node.is("span") && document.getSelection().toString() === node.text()) {
                    var html = node.css('font-family', font).prop('outerHTML');
                } else {
                    var html = jQuery('<span>').html(e2pdf.wysiwyg.helper.getSelectionHtml()).css('font-family', font).prop('outerHTML');
                }
                document.execCommand('insertHTML', false, html);
                el.val('');
            } else if (command === 'color') {
                e2pdf.wysiwyg.helper.restoreSelection(e2pdf.static.selectionRange);
                var color = el.val();
                document.execCommand('foreColor', false, color);
                e2pdf.static.selectionRange = e2pdf.wysiwyg.helper.saveSelection();
            } else if (command === 'clear') {
                document.execCommand("removeformat", false, "");
                e2pdf.wysiwyg.clear();
            } else {
                document.execCommand(command, false, null);
            }
            e2pdf.event.fire('after.wysiwyg.apply');
        },
        // e2pdf.wysiwyg.clear
        clear: function (tags) {
            if (!tags) {
                var tags = "h1,h2";
            }
            var array = tags.toLowerCase().split(",");
            e2pdf.wysiwyg.helper.getSelectedNodes().forEach(function (node) {
                if (node.nodeType === 1 &&
                        array.indexOf(node.tagName.toLowerCase()) > -1) {
                    e2pdf.wysiwyg.helper.replaceWithOwnChildren(node);
                }
            });
        },
        // e2pdf.wysiwyg.helper
        helper: {
            // e2pdf.wysiwyg.helper.getSelectedNodes
            getSelectedNodes: function () {
                var nodes = [];
                if (window.getSelection) {
                    var sel = window.getSelection();
                    for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                        nodes.push.apply(nodes, e2pdf.wysiwyg.helper.getRangeSelectedNodes(sel.getRangeAt(i), true));
                    }
                }
                return nodes;
            },
            // e2pdf.wysiwyg.helper.replaceWithOwnChildren
            replaceWithOwnChildren: function (el) {
                var parent = el.parentNode;
                while (el.hasChildNodes()) {
                    parent.insertBefore(el.firstChild, el);
                }
                parent.removeChild(el);
            },
            // e2pdf.wysiwyg.helper.getRangeSelectedNodes
            getRangeSelectedNodes: function (range, includePartiallySelectedContainers) {
                var node = range.startContainer;
                var endNode = range.endContainer;
                var rangeNodes = [];
                if (node === endNode) {
                    rangeNodes = [node];
                } else {
                    while (node && node !== endNode) {
                        rangeNodes.push(node = e2pdf.wysiwyg.helper.nextNode(node));
                    }
                    node = range.startContainer;
                    while (node && node !== range.commonAncestorContainer) {
                        rangeNodes.unshift(node);
                        node = node.parentNode;
                    }
                }

                if (includePartiallySelectedContainers) {
                    node = range.commonAncestorContainer;
                    while (node) {
                        rangeNodes.push(node);
                        node = node.parentNode;
                    }
                }

                return rangeNodes;
            },
            // e2pdf.wysiwyg.helper.getSelectedNode
            getSelectedNode: function () {
                var node, selection;
                if (window.getSelection) {
                    selection = getSelection();
                    node = selection.anchorNode;
                }
                if (!node && document.selection) {
                    selection = document.selection;
                    var range = selection.getRangeAt ? selection.getRangeAt(0) : selection.createRange();
                    node = range.commonAncestorContainer ? range.commonAncestorContainer :
                            range.parentElement ? range.parentElement() : range.item(0);
                }
                if (node) {
                    return (node.nodeName === "#text" ? node.parentNode : node);
                }
            },
            // e2pdf.wysiwyg.helper.nextNode
            nextNode: function (node) {
                if (node.hasChildNodes()) {
                    return node.firstChild;
                } else {
                    while (node && !node.nextSibling) {
                        node = node.parentNode;
                    }
                    if (!node) {
                        return null;
                    }
                    return node.nextSibling;
                }
            },
            // e2pdf.wysiwyg.helper.getSelectionHtml
            getSelectionHtml: function () {
                var html = "";
                if (typeof window.getSelection != 'undefined') {
                    var sel = window.getSelection();
                    if (sel.rangeCount) {
                        var container = document.createElement("div");
                        for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                            container.appendChild(sel.getRangeAt(i).cloneContents());
                        }
                        html = container.innerHTML;
                    }
                } else if (typeof document.selection != 'undefined') {
                    if (document.selection.type === "Text") {
                        html = document.selection.createRange().htmlText;
                    }
                }
                return html;
            },
            // e2pdf.wysiwyg.helper.saveSelection
            saveSelection: function () {
                if (window.getSelection) {
                    sel = window.getSelection();
                    if (sel.getRangeAt && sel.rangeCount) {
                        return sel.getRangeAt(0);
                    }
                } else if (document.selection && document.selection.createRange) {
                    return document.selection.createRange();
                }
                return null;
            },
            // e2pdf.wysiwyg.helper.restoreSelection
            restoreSelection: function (range) {
                if (range) {
                    if (window.getSelection) {
                        sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                    } else if (document.selection && range.select) {
                        range.select();
                    }
                }
            },
            // e2pdf.wysiwyg.helper.dropSelection
            dropSelection: function () {
                if (window.getSelection) {
                    sel = window.getSelection();
                    sel.removeAllRanges();
                }
            }
        }
    },
    // e2pdf.visual
    visual: {
        // e2pdf.visual.mapper
        mapper: {
            cursorPosStart: null,
            cursorPosEnd: null,
            cursorRange: null,
            selected: null,
            // e2pdf.visual.mapper.init
            init: function (el) {
                if (el.hasClass('e2pdf-focused') && (el.data('data-type') === 'e2pdf-input' || el.data('data-type') === 'e2pdf-textarea' || el.data('data-type') === 'e2pdf-html')) {
                    if (el.find('.e2pdf-inner-element').is('div')) {
                        if (window.getSelection) {
                            sel = window.getSelection();
                            if (sel.getRangeAt && sel.rangeCount) {
                                e2pdf.visual.mapper.cursorRange = sel.getRangeAt(0);
                            }
                        } else if (document.selection && document.selection.createRange) {
                            e2pdf.visual.mapper.cursorRange = document.selection.createRange();
                        }
                        e2pdf.visual.mapper.cursorPosStart = null;
                        e2pdf.visual.mapper.cursorPosEnd = null;
                    } else {
                        e2pdf.visual.mapper.cursorPosStart = el.find('.e2pdf-inner-element').prop('selectionStart');
                        e2pdf.visual.mapper.cursorPosEnd = el.find('.e2pdf-inner-element').prop('selectionEnd');
                        e2pdf.visual.mapper.cursorRange = null;
                    }
                } else {
                    e2pdf.visual.mapper.cursorPosStart = null;
                    e2pdf.visual.mapper.cursorPosEnd = null;
                    e2pdf.visual.mapper.cursorRange = null;
                }

                e2pdf.visual.mapper.selected = el;
                var modal = jQuery('<div>', {'data-modal': 'visual-mapper'});
                e2pdf.dialog.create(modal);
            },
            // e2pdf.visual.mapper.markup
            markup: function () {
                e2pdf.dialog.rebuild();
            },
            // e2pdf.visual.mapper.rebuild
            rebuild: function () {
                if (jQuery('.e2pdf-vm-content').length > 0) {
                    var vc_content = jQuery('.e2pdf-vm-content');
                    if (jQuery('.e2pdf-vm-wrapper').length > 0) {
                        jQuery('.e2pdf-vm-wrapper').remove();
                    }

                    vc_content.find('input[type="hidden"]').each(function () {
                        if (jQuery(this).attr('e2pdf-vm-hidden') != 'true') {
                            jQuery(this).attr('e2pdf-vm-hidden', 'true');
                        }
                    });
                    if (e2pdf.static.vm.hidden) {
                        vc_content.find('input[e2pdf-vm-hidden="true"]').each(function () {
                            jQuery(this).attr('type', 'text');
                        });
                    } else {
                        vc_content.find('input[e2pdf-vm-hidden="true"]').each(function () {
                            jQuery(this).attr('type', 'hidden');
                        });
                    }

                    var vc_wrapper = jQuery('<div>', {'class': 'e2pdf-vm-wrapper'});
                    vc_content.find('input[type="text"], input[type="radio"], input[type="checkbox"], input[type="password"], input[type="url"], input[type="number"], input[type="tel"], input[type="phone"], input[type="credit_card_cvc"], input[type="email"], input[type="color_picker"], input[type="range"], input[type="file"], input[type="date"], input[type="datetime-local"], input[type="time"], button[type="upload"], textarea, select').each(function () {
                        vc_wrapper.append(e2pdf.visual.mapper.load(jQuery(this)));
                    });
                    vc_content.append(vc_wrapper);
                    if (e2pdf.pdf.settings.get('extension') == 'wordpress' || e2pdf.pdf.settings.get('extension') == 'woocommerce') {
                        jQuery('.e2pdf-dialog-visual-mapper input.e2pdf-hide[name="vm_search"]').removeClass('e2pdf-hide');
                    }
                }
            },
            // e2pdf.visual.mapper.clear
            clear: function () {
                if (jQuery('.e2pdf-vm-wrapper').length > 0) {
                    jQuery('.e2pdf-vm-wrapper').remove();
                }
            },
            // e2pdf.visual.mapper.load
            load: function (el) {
                var loaded = false;
                jQuery('.e2pdf-vm-field').removeClass('e2pdf-hide');
                if (el.is(":visible") && !el.hasClass('e2pdf-no-vm')) {
                    var width = el.css('width');
                    var height = el.css('height');
                    var top = el.offset().top - el.closest('.e2pdf-vm-content').offset().top;
                    var left = el.offset().left - el.closest('.e2pdf-vm-content').offset().left;
                    loaded = jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-vm-element'}).css({
                        'width': width, 'height': height, 'top': top, 'left': left
                    });
                    var name = el.attr('name');
                    var type = el.attr('type');
                    var value = el.attr('value');
                    if (el.is('select')) {
                        type = 'select';
                        value = '';
                        el.find('option').each(function () {
                            value += jQuery(this).attr('value');
                            if (jQuery(this)[0] !== el.find('option:last-child')[0]) {
                                value += "\n";
                            }
                        });
                    }

                    loaded.data('name', name);
                    loaded.data('type', type);
                    loaded.data('value', value);
                    jQuery('.e2pdf-vm-field').addClass('e2pdf-hide');
                }
                return loaded;
            },
            // e2pdf.visual.mapper.apply
            apply: function (el) {
                if (e2pdf.visual.mapper.selected) {
                    var name = el.data('name');
                    var group = el.data('name');
                    var value = el.data('value');
                    if (e2pdf.visual.mapper.cursorPosStart !== null && e2pdf.visual.mapper.cursorPosEnd !== null) {
                        var value = e2pdf.visual.mapper.selected.find('.e2pdf-inner-element').val();
                        var textBefore = value.substring(0, e2pdf.visual.mapper.cursorPosStart);
                        var textAfter = value.substring(e2pdf.visual.mapper.cursorPosEnd, value.length);
                        var final = textBefore + name + textAfter;
                        if (!e2pdf.static.vm.replace) {
                            e2pdf.visual.mapper.cursorPosStart = e2pdf.visual.mapper.cursorPosEnd + name.length;
                            e2pdf.visual.mapper.cursorPosEnd = e2pdf.visual.mapper.cursorPosStart;
                        } else {
                            e2pdf.visual.mapper.cursorPosEnd = e2pdf.visual.mapper.cursorPosStart + name.length;
                        }
                        name = final;
                    } else if (e2pdf.visual.mapper.cursorRange !== null) {
                        e2pdf.visual.mapper.cursorRange.deleteContents();
                        var node = document.createTextNode(name);
                        e2pdf.visual.mapper.cursorRange.insertNode(node);
                    } else {
                        if (!e2pdf.static.vm.replace) {
                            e2pdf.element.init(e2pdf.visual.mapper.selected);
                            name = e2pdf.properties.getValue(e2pdf.visual.mapper.selected, 'value', 'string') + name;
                        }
                    }
                    switch (e2pdf.visual.mapper.selected.data('data-type')) {
                        case 'e2pdf-checkbox':
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'value', name);
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'option', value);
                            break;
                        case 'e2pdf-radio':
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'group', group);
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'value', name);
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'option', value);
                            break;
                        case 'e2pdf-select':
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'value', name);
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'options', value);
                            break;
                        case 'e2pdf-image':
                        case 'e2pdf-qrcode':
                        case 'e2pdf-barcode':
                        case 'e2pdf-graph':
                            e2pdf.properties.set(e2pdf.visual.mapper.selected, 'value', name);
                            break;
                        default:
                            if (e2pdf.visual.mapper.cursorRange === null) {
                                e2pdf.properties.set(e2pdf.visual.mapper.selected, 'value', name);
                            }
                            break;
                    }
                    if (e2pdf.visual.mapper.cursorRange === null) {
                        e2pdf.properties.render(e2pdf.visual.mapper.selected);
                    }
                }
                if (e2pdf.static.vm.close) {
                    e2pdf.dialog.close();
                }
            }
        }
    },
    zoom: {
        zoom: 1,
        click: {
            x: 0,
            y: 0
        },
        apply: function (el) {
            jQuery('.e2pdf-tpl').removeClass(function (index, className) {
                return (className.match(/(^|\s)e2pdf-z\S+/g) || []).join(' ');
            });
            if (el.val() !== '100') {
                jQuery('.e2pdf-tpl').addClass("e2pdf-z" + el.val());
            }

            e2pdf.zoom.zoom = el.val() / 100;
            jQuery('.e2pdf-tpl').scrollLeft(((jQuery('.e2pdf-tpl-inner').width() * e2pdf.zoom.zoom) - jQuery('.e2pdf-tpl').width()) / 2);
        }
    }
};
jQuery(window).resize(function () {
    e2pdf.visual.mapper.clear();
    if (e2pdf.static.observer !== null) {
        e2pdf.static.observer.disconnect();
        e2pdf.static.observer = null;
    }
    if (this.e2pdfResizeTO) {
        clearTimeout(this.e2pdfResizeTO);
    }
    this.e2pdfResizeTO = setTimeout(function () {
        jQuery(this).trigger('e2pdfResizeEnd');
    }, 500);
});
jQuery(document).on('click', '.notice-dismiss', function (e) {
    jQuery(this).parent().addClass('e2pdf-hide');
    jQuery(this).trigger('e2pdfResizeEnd');
});
jQuery(window).bind('e2pdfResizeEnd', function () {
    e2pdf.dialog.rebuild();
});
jQuery(document).on('change', 'input.e2pdf-collapse[type="checkbox"]', function (e) {
    var collapse = jQuery(this).attr('data-collapse');
    if (collapse) {
        if (jQuery(this).is(':checked')) {
            jQuery('.' + collapse).removeClass('e2pdf-hide');
        } else {
            jQuery('.' + collapse).addClass('e2pdf-hide');
        }
    }
});
jQuery(document).on('change', '.e2pdf-export-disposition input[type="radio"]', function (e) {
    if (jQuery(this).val() == 'attachment') {
        jQuery(this).closest('form').removeAttr('target');
    } else {
        jQuery(this).closest('form').attr('target', '_blank');
    }
});
jQuery(document).on('click', 'a.e2pdf-collapse', function (e) {
    var collapse = jQuery(this).attr('data-collapse');
    if (collapse) {
        if (jQuery(this).hasClass('e2pdf-collapsed')) {
            jQuery('.' + collapse).addClass('e2pdf-hide');
            jQuery(this).removeClass('e2pdf-collapsed');
        } else {
            jQuery('.' + collapse).removeClass('e2pdf-hide');
            jQuery(this).addClass('e2pdf-collapsed');
        }
    }
});
jQuery(document).ready(function () {
    jQuery(document).on('click', 'a.e2pdf-link[disabled="disabled"]', function (e) {
        e.stopPropagation();
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    });
    jQuery(document).on('click', '.e2pdf-argument-add', function (e) {
        var parent = jQuery(this).closest('.e2pdf-grid');
        var argument = parent.find('.e2pdf-argument').first().clone();
        var index = parent.find('.e2pdf-argument').length + 1;
        argument.find('input').val('').attr('placeholder', 'arg' + index);
        argument.appendTo(parent.find('.e2pdf-arguments'));
    });
    jQuery(document).on('click', '.e2pdf-argument-delete', function (e) {
        var parent = jQuery(this).closest('.e2pdf-grid');
        if (parent.find('.e2pdf-argument').length > 1) {
            parent.find('.e2pdf-argument').last().remove();
        }
    });
    jQuery(document).on('click', '.e2pdf-action-add', function (e) {
        var actions = jQuery(this).closest('.e2pdf-actions-wrapper').find('.e2pdf-actions');
        var form = jQuery(this).closest('form');
        if (form.attr('id') === 'e2pdf-tpl-actions') {
            var element = jQuery('.e2pdf-tpl');
        } else if (form.attr('id') === 'e2pdf-page-options') {
            var element = jQuery('.e2pdf-page[data-page_id="' + form.find('input[name="page_id"]').val() + '"]').first();
        } else {
            var element = jQuery(".e2pdf-element[data-element_id='" + form.find('input[name="element_id"]').val() + "']").first();
        }
        e2pdf.actions.add(element, actions);
    });
    jQuery(document).on('click', '.e2pdf-action-condition-add', function (e) {
        var action_conditions = jQuery(this).closest('.e2pdf-action');
        var form = jQuery(this).closest('form');
        if (form.attr('id') === 'e2pdf-tpl-actions') {
            var element = jQuery('.e2pdf-tpl');
        } else if (form.attr('id') === 'e2pdf-page-options') {
            var element = jQuery('.e2pdf-page[data-page_id="' + form.find('input[name="page_id"]').val() + '"]');
        } else {
            var element = jQuery(".e2pdf-element[data-element_id='" + form.find('input[name="element_id"]').val() + "']").first();
        }

        e2pdf.actions.conditions.add(element, action_conditions);
    });
    jQuery(document).on('click', '.e2pdf-action-delete', function (e) {
        if (!confirm(e2pdf.lang.get('Action will be removed! Continue?'))) {
            return false;
        }
        var action = jQuery(this).closest('.e2pdf-action');
        e2pdf.actions.delete(action);
    });
    jQuery(document).on('click', '.e2pdf-action-duplicate', function (e) {
        var action = jQuery(this).closest('.e2pdf-action');
        e2pdf.actions.duplicate(action);
    });
    jQuery(document).on('click', '.e2pdf-action-condition-delete', function (e) {
        var action = jQuery(this).closest('.e2pdf-action');
        if (action.find('.e2pdf-condition').length === 1) {
            alert(e2pdf.lang.get('Last condition can\'t be removed'));
            return false;
        }
        if (!confirm(e2pdf.lang.get('Condition will be removed! Continue?'))) {
            return false;
        }
        var condition = jQuery(this).closest('.e2pdf-condition');
        e2pdf.actions.conditions.delete(condition);
    });
    jQuery(document).on('click', '.e2pdf-delete-reupload-page', function (e) {
        jQuery(this).closest('.e2pdf-grid').find('input[name^="positions"]').val('0');
    });
    jQuery(document).on('click', '.e2pdf-delete-pdf', function (e) {
        if (!confirm(e2pdf.lang.get('Pre-uploaded PDF will be removed from E2Pdf Template! Continue?'))) {
            return false;
        }
        jQuery('.e2pdf-tpl .e2pdf-page').each(function () {
            var el = jQuery(this);
            el.css('background', '');
        });
        jQuery('.e2pdf-form-builder > input[name="pdf"]').val('');
    });
    jQuery(document).on('change', '#e2pdf-zoom', function (e) {
        e2pdf.zoom.apply(jQuery(this));
    });
    jQuery(document).on('change', '.e2pdf-extension', function (e) {
        e2pdf.request.submitRequest('e2pdf_extension', jQuery(this));
    });
    jQuery(document).on('change', '.e2pdf-item', function (e) {
        if (jQuery(this).val() == '-1') {
            jQuery(this).closest('form').find('.e2pdf-w-apply, .e2pdf-w-empty, .e2pdf-w-auto').attr('disabled', 'disabled');
            jQuery(this).closest('form').find('#e2pdf-item-options').removeClass('e2pdf-hide');
            jQuery(this).closest('form').find('.e2pdf-item-merged').addClass('e2pdf-hide');
            jQuery('#e2pdf-merged-item-dataset-title').addClass('e2pdf-hide');
            jQuery('#e2pdf-item-dataset-title').removeClass('e2pdf-hide');
        } else if (jQuery(this).val() == '-2') {
            jQuery(this).closest('form').find('.e2pdf-w-apply, .e2pdf-w-empty, .e2pdf-w-auto').attr('disabled', false);
            jQuery(this).closest('form').find('#e2pdf-item-options').addClass('e2pdf-hide');
            jQuery(this).closest('form').find('.e2pdf-item-merged').removeClass('e2pdf-hide');
            jQuery('#e2pdf-merged-item-dataset-title').removeClass('e2pdf-hide');
            jQuery('#e2pdf-item-dataset-title').addClass('e2pdf-hide');
        } else {
            jQuery(this).closest('form').find('.e2pdf-w-apply, .e2pdf-w-empty, .e2pdf-w-auto').attr('disabled', false);
            jQuery(this).closest('form').find('#e2pdf-item-options, .e2pdf-item-merged').addClass('e2pdf-hide');
            jQuery('#e2pdf-merged-item-dataset-title').addClass('e2pdf-hide');
            jQuery('#e2pdf-item-dataset-title').removeClass('e2pdf-hide');
        }
    });
    jQuery(document).on('change', '.e2pdf-action-action select, .e2pdf-action-property select, .e2pdf-action-format select', function (e) {
        var action = jQuery(this).closest('.e2pdf-action');
        e2pdf.actions.change(action, jQuery(this));
    });
    jQuery(document).on('click', '.e2pdf-tabs a', function (e) {
        jQuery(this).closest('.e2pdf-tabs-panel').find('.tabs-panel').hide();
        var tab = jQuery(this);
        tab.closest('ul').find('li').removeClass('active');
        tab.parent('li').addClass('active');
        jQuery(document.getElementById(tab.attr('data-tab'))).show();
    });
    jQuery(document).on('click', '.e2pdf-hidden-dropdown', function (e) {
        var parent = jQuery(this).closest('.e2pdf-closed');
        if (parent.hasClass('e2pdf-opened')) {
            parent.removeClass('e2pdf-opened');
        } else {
            jQuery('.e2pdf-closed').each(function () {
                jQuery(this).removeClass('e2pdf-opened');
            });
            parent.addClass('e2pdf-opened');
        }
    });
    jQuery(document).on('click', '.e2pdf-submit-form', function (e) {
        e.preventDefault();
        var el = jQuery(this);
        if (el.attr('form-id') == 'e2pdf-build-form' && el.hasClass('restore')) {
            if (!confirm(e2pdf.lang.get('Saved Template will be overwritten! Continue?'))) {
                return false;
            }
        }
        if (el.attr('form-id') == 'license_key') {
            if (el.closest('form').find('input[name="license_key"]').val().trim() == '' && !confirm(e2pdf.lang.get('Website will be forced to use "FREE" License Key! Continue?'))) {
                return false;
            }
        }
        e2pdf.request.submitForm(el);
    });
    jQuery(document).on('click', '.e2pdf-submit-local', function (e) {
        var el = jQuery(this);
        if (el.hasClass('e2pdf-noclose')) {
            e2pdf.request.submitLocal(el, true);
        } else {
            e2pdf.request.submitLocal(el);
        }
    });
    jQuery(document).on('click', '.e2pdf-delete', function (e) {
        var message = Object.keys(e2pdf.element.selected).length > 1 ? e2pdf.lang.get('Elements will be removed! Continue?') : e2pdf.lang.get('Element will be removed! Continue?');
        if (!confirm(message)) {
            e2pdf.delete('.e2pdf-context');
            return false;
        }
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.element.delete(selected);
        }
        e2pdf.element.unselect();
    });
    jQuery(document).on('click', '.e2pdf-copy', function (e) {
        var type = jQuery(this).attr('type');
        if (type == 'elements') {
            e2pdf.storage.delete('elements');
        }
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.copy(type, selected);
        }
    });
    jQuery(document).on('click', '.e2pdf-paste', function (e) {
        var type = jQuery(this).attr('type');
        if (type == 'elements' || type == 'elements-in-place') {
            e2pdf.paste(type);
        } else {
            for (var key in e2pdf.element.selected) {
                var selected = e2pdf.element.selected[key];
                e2pdf.paste(type, selected);
            }
        }
    });
    jQuery(document).on('click', '.e2pdf-cut', function (e) {
        e2pdf.storage.delete('elements');
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.copy('elements', selected);
            e2pdf.element.delete(selected);
        }
    });
    jQuery(document).on('click', '.e2pdf-inner-context-menu > a', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
    });
    jQuery(document).on('mouseover', '.e2pdf-inner-context-menu', function (e) {
        jQuery(this).find('ul').show();
    });
    jQuery(document).on('mouseout', '.e2pdf-inner-context-menu', function (e) {
        jQuery(this).find('ul').hide();
    });
    jQuery(document).on('click', '.e2pdf-resize', function (e) {
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            selected.addClass('e2pdf-focused');
        }
    });
    jQuery(document).on('click', '.e2pdf-hidden', function (e) {
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.element.hide(selected);
        }
    });
    jQuery(document).on('click', '.e2pdf-unhidden', function (e) {
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.element.show(selected);
        }
    });
    jQuery(document).on('click', '.e2pdf-lock', function (e) {
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.properties.set(selected, 'locked', '1');
            selected.addClass('e2pdf-locked');
        }
    });
    jQuery(document).on('click', '.e2pdf-unlock', function (e) {
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.properties.set(selected, 'locked', '0');
            selected.removeClass('e2pdf-locked');
        }
    });
    jQuery(document).on('click', '.e2pdf-checkbox', function (e) {
        return false;
    });
    jQuery(document).on('click', '.e2pdf-radio', function (e) {
        return false;
    });
    jQuery(document).on('click', '.e2pdf-hidden-elements', function (e) {
        if (jQuery(this).hasClass('e2pdf-inactive')) {
            jQuery('html').addClass('e2pdf-show-all-elements');
            jQuery(this).removeClass('e2pdf-inactive');
        } else {
            jQuery('html').removeClass('e2pdf-show-all-elements');
            jQuery(this).addClass('e2pdf-inactive');
        }
    });
    jQuery(document).on('click', '.e2pdf-locked-elements', function (e) {
        if (jQuery(this).hasClass('e2pdf-inactive')) {
            jQuery('html').addClass('e2pdf-unlock-all-elements');
            jQuery(this).removeClass('e2pdf-inactive');
        } else {
            jQuery('html').removeClass('e2pdf-unlock-all-elements');
            jQuery(this).addClass('e2pdf-inactive');
        }
    });
    jQuery(document).on('click', '.e2pdf-wysiwyg-source', function (e) {
        if (jQuery(this).hasClass('e2pdf-inactive')) {
            jQuery(this).removeClass('e2pdf-inactive');
        } else {
            jQuery(this).addClass('e2pdf-inactive');
        }
        jQuery('.e2pdf-html').each(function () {
            var el = jQuery(this).parent();
            var children = jQuery(this);
            if (children.is('textarea')) {
                e2pdf.properties.set(el, 'value', children.val());
            } else {
                e2pdf.properties.set(el, 'value', children.html());
            }
            e2pdf.properties.render(el);
        });
    });
    jQuery(document).on('click', '.e2pdf-add-page', function (e) {
        if (e2pdf.pdf.settings.get('pdf')) {
            alert(e2pdf.lang.get('Adding new pages not available in "Uploaded PDF"'));
        } else if (e2pdf_params['license_type'] == 'FREE') {
            alert(e2pdf.lang.get('Only single-page PDFs are allowed with the "FREE" license type'));
        } else {
            e2pdf.pages.createPage();
        }
    });
    jQuery(document).on('click', '.e2pdf-wysiwyg-table', function (e) {
        e.preventDefault();
        var gridContainer = jQuery('.e2pdf-wysiwyg-table-grid');
        if (gridContainer.hasClass('e2pdf-hide')) {
            var node = jQuery(e2pdf.wysiwyg.helper.getSelectedNode());
            if (node.hasClass('e2pdf-element')) {
                var html_node = node;
            } else {
                var html_node = node.closest('.e2pdf-element');
            }
            if (html_node && html_node.find('.e2pdf-html').length > 0) {
                if (html_node.find('textarea.e2pdf-html').length > 0 || node.is('textarea')) {
                    alert(e2pdf.lang.get('The WYSIWYG editor is disabled for this HTML object'));
                    return;
                }
            } else {
                alert(e2pdf.lang.get('WYSIWYG can only be applied within HTML elements'));
                return;
            }
            e2pdf.static.selectionRange = e2pdf.wysiwyg.helper.saveSelection();
            gridContainer.removeClass('e2pdf-hide').empty();
            for (let i = 0; i < 100; i++) {
                let gridItem = jQuery('<div>');
                gridItem.on('mouseover', function () {
                    let rowCol = calculateRowCol(i);
                    highlightGrid(gridContainer, rowCol.rows, rowCol.cols);
                });
                gridItem.on('mouseout', function () {
                    highlightGrid(gridContainer, 0, 0);
                });
                gridItem.on('click', function () {
                    e2pdf.wysiwyg.helper.restoreSelection(e2pdf.static.selectionRange);
                    let rowCol = calculateRowCol(i);
                    let html = '<table>\n';
                    for (let r = 0; r < rowCol.rows; r++) {
                        html += '<tr>\n';
                        for (let c = 0; c < rowCol.cols; c++) {
                            html += '<td class="r' + r + ' c' + c + '">r' + r + ' c' + c + '</td>\n';
                        }
                        html += '</tr>\n';
                    }
                    html += '</table>\n';
                    document.execCommand('insertHTML', false, html);
                    gridContainer.addClass('e2pdf-hide');
                    e2pdf.static.selectionRange = e2pdf.wysiwyg.helper.saveSelection();
                });
                gridContainer.append(gridItem);
            }
            gridContainer.append(jQuery('<span>').text('0x0'));
        } else {
            gridContainer.addClass('e2pdf-hide');
        }
        function calculateRowCol(index) {
            let rows = Math.floor(index / 10) + 1;
            let cols = (index % 10) + 1;
            return {rows, cols};
        }
        function highlightGrid(gridContainer, rows, cols) {
            gridContainer.find('div').each(function (idx) {
                let itemRowCol = calculateRowCol(idx);
                gridContainer.find('span').text(rows + 'x' + cols);
                if (itemRowCol.rows <= rows && itemRowCol.cols <= cols) {
                    jQuery(this).addClass('e2pdf-highlight');
                } else {
                    jQuery(this).removeClass('e2pdf-highlight');
                }
            });
        }
    });
    jQuery(document).on('click', '.e2pdf-create-pdf', function (e) {
        if (e2pdf.url.get('revision_id')) {
            alert(e2pdf.lang.get('Not Available in Revision Edit Mode'));
            return false;
        }
        e2pdf.createPdf(jQuery(this));
    });
    jQuery(document).on('click', '.e2pdf-up-page', function (e) {
        e2pdf.pages.movePage(jQuery(this), 'up');
    });
    jQuery(document).on('click', '.e2pdf-down-page', function (e) {
        e2pdf.pages.movePage(jQuery(this), 'down');
    });
    jQuery(document).on('click', '.e2pdf-delete-page', function (e) {
        if (e2pdf.pdf.settings.get('pdf')) {
            if (!confirm(e2pdf.lang.get('All pages will be removed! Continue?'))) {
                return false;
            }
            jQuery('.e2pdf-tpl .e2pdf-page').each(function () {
                var el = jQuery(this);
                e2pdf.pages.deletePage(el);
            });
        } else {
            if (!confirm(e2pdf.lang.get('Page will be removed! Continue?'))) {
                return false;
            }
            var el = jQuery(jQuery(this));
            e2pdf.pages.deletePage(el);
        }
    });
    jQuery(document).on('click', '.e2pdf-delete-all-pages', function (e) {
        if (!confirm(e2pdf.lang.get('All pages will be removed! Continue?'))) {
            return false;
        }
        jQuery('.e2pdf-tpl .e2pdf-page').each(function () {
            var el = jQuery(this);
            e2pdf.pages.deletePage(el);
        });
    });
    jQuery(document).on('click', '.e2pdf-delete-font', function (e) {
        if (!confirm(e2pdf.lang.get('Font will be removed! Continue?'))) {
            return false;
        }
        var el = jQuery(jQuery(this));
        e2pdf.font.delete(el);
    });
    jQuery(document).on('click', '.e2pdf-modal', function (e) {
        e2pdf.dialog.create(jQuery(this));
    });
    jQuery(document).on('click', 'body', function (e) {
        e2pdf.delete('.e2pdf-context');
    });
    jQuery(document).on('change', '.e2pdf-export-template', function (e) {
        e2pdf.request.submitRequest('e2pdf_templates', jQuery(this));
    });
    jQuery(document).on('change', '.e2pdf-export-dataset', function (e) {
        if (jQuery(this).is('select')) {
            var data = {};
            data['id'] = jQuery('.e2pdf-export-template').val();
            data['datasets'] = {};
            jQuery('.e2pdf-export-dataset').each(function () {
                data['datasets'][jQuery(this).attr('name')] = jQuery(this).val();
            });
            e2pdf.request.submitRequest('e2pdf_dataset', jQuery(this), data);
        }
    });
    jQuery(document).on('click', '.e2pdf-datasets-refresh', function (e) {
        var data = {};
        data['id'] = jQuery('.e2pdf-export-template').val();
        data['dataset'] = jQuery(this).closest('.e2pdf-select2-wrapper').find('select').first().val();
        data['name'] = jQuery(this).closest('.e2pdf-select2-wrapper').find('select').first().attr('name');
        e2pdf.request.submitRequest('e2pdf_datasets_refresh', jQuery(this), data);
    });
    jQuery(document).on('change', 'fieldset.e2pdf-export-dataset input[type="checkbox"]', function (e) {
        if (jQuery(this).is(':checked')) {
            if (jQuery(this).val() == '') {
                jQuery(this).closest('fieldset').find('input[type="checkbox"]').prop('checked', true);
            }
        } else {
            if (jQuery(this).val() == '') {
                jQuery(this).closest('fieldset').find('input[type="checkbox"]').prop('checked', false);
            } else {
                jQuery(this).closest('fieldset').find('input[type="checkbox"][value=""]').prop('checked', false);
            }
        }

        if (jQuery(this).closest('fieldset').find('input[type="checkbox"]').length - 1 == jQuery(this).closest('fieldset').find('input[type="checkbox"]:checked').length) {
            jQuery(this).closest('fieldset').find('input[type="checkbox"][value=""]').prop('checked', true);
        }

        if (jQuery(this).closest('fieldset').find('input[type="checkbox"]:checked').length > 0) {
            jQuery('.e2pdf-export-form-submit').attr('disabled', false);
        } else {
            jQuery('.e2pdf-export-form-submit').attr('disabled', true);
        }
    });
    jQuery(document).on('change', '#e2pdf-font', function (e) {
        e2pdf.font.load(jQuery(this));
        e2pdf.font.apply(jQuery('.e2pdf-tpl'), jQuery(this));
    });
    jQuery(document).on('change', '#e2pdf-font-size', function (e) {
        e2pdf.font.size(jQuery('.e2pdf-tpl'), jQuery(this));
    });
    jQuery(document).on('change', '#e2pdf-text-align', function (e) {
        var text_align = jQuery(this).val();
        jQuery('.e2pdf-tpl .e2pdf-element ').each(function () {
            if (
                    jQuery(this).data('data-type') == 'e2pdf-input'
                    || jQuery(this).data('data-type') == 'e2pdf-textarea'
                    || jQuery(this).data('data-type') == 'e2pdf-html'
                    || jQuery(this).data('data-type') == 'e2pdf-page-number'
                    || jQuery(this).data('data-type') == 'e2pdf-link'
                    ) {
                if (e2pdf.properties.getValue(jQuery(this), 'text_align', 'string') == '') {
                    var children = e2pdf.element.children(jQuery(this));
                    children.css('text-align', text_align);
                }
            }
        });
    });
    jQuery(document).on('keydown', '.e2pdf-numbers', function (e) {
        if (jQuery.inArray(e.key, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'Backspace', 'Delete', 'Tab', 'Escape', 'Enter', '+', '-', '.']) !== -1 ||
                jQuery.inArray(e.keyCode, [46, 8, 9, 27, 13, 107, 109, 110, 189, 190]) !== -1 ||
                ((e.keyCode === 65 || e.keyCode === 86 || e.keyCode === 67) && (e.ctrlKey === true || e.metaKey === true)) ||
                (e.shiftKey === true && e.keyCode === 187) ||
                (e.keyCode >= 35 && e.keyCode <= 40)
                ) {

            if ((e.keyCode === 189 || e.keyCode === 109 || e.key === '-') && !jQuery(this).hasClass('e2pdf-number-negative')) {
                e.preventDefault();
            } else if ((e.keyCode === 187 || e.keyCode === 107 || e.key === '+') && !jQuery(this).hasClass('e2pdf-number-positive')) {
                e.preventDefault();
            } else {
                return;
            }
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
    jQuery(document).on('keydown', '.e2pdf-enter', function (e) {
        if (e.which == 13) {
            e.preventDefault();
            if (jQuery(this).attr('name') == 'email' || jQuery(this).attr('name') == 'email_code') {
                jQuery(this).closest('form').find('.e2pdf-submit-form[action="e2pdf_email"]').click();
            } else {
                jQuery(this).closest('form').find('.e2pdf-submit-form').click();
            }
        }
    });
    jQuery(document).on('change', '.e2pdf-numbers', function (e) {
        var value = jQuery(this).val().trim();
        var prefix = '';
        if (jQuery(this).hasClass('e2pdf-number-positive') && value.startsWith('+')) {
            prefix = '+';
        }
        value = parseFloat(value);
        if (isNaN(value)) {
            value = 0;
        }
        jQuery(this).val(prefix + value);
    });
    jQuery(document).on('change', '#e2pdf-font-color', function (e) {
        e2pdf.font.fontcolor(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font-color'));
    });
    jQuery(document).on('change', '#e2pdf-rtl', function (e) {
        if (jQuery(this).is(':checked')) {
            jQuery('.e2pdf-tpl').attr('dir', 'rtl');
        } else {
            jQuery('.e2pdf-tpl').attr('dir', false);
        }
    });
    jQuery(document).on('change', '#e2pdf-line-height', function (e) {
        e2pdf.font.line(jQuery('.e2pdf-tpl'), jQuery(this));
    });
    jQuery(document).on('click', '.e2pdf-upload', function (e) {
        e.preventDefault();
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.mediaUploader.init(selected);
        }
    });
    jQuery(document).on('click', '.e2pdf-visual', function (e) {
        for (var key in e2pdf.element.selected) {
            var selected = e2pdf.element.selected[key];
            e2pdf.visual.mapper.init(selected);
        }
    });
    jQuery(document).on('click', '.e2pdf-apply-wysiwyg', function (e) {
        e2pdf.wysiwyg.apply(jQuery(this));
    });
    jQuery(document).on('click', '.e2pdf-apply-wysiwyg-color', function (e) {
        e2pdf.static.selectionRange = e2pdf.wysiwyg.helper.saveSelection();
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        var color_panel = jQuery(this).parent().find('.wp-picker-container');
        if (!color_panel.hasClass('wp-picker-active')) {
            color_panel.find('.wp-color-result').click();
            if (color_panel.find('.wp-color-close').length === 0) {
                var close = jQuery('<a>', {"class": "wp-color-close", "href": "javascript:void(0);", 'onclick': "e2pdf.helper.color.close(this);"});
                var width = parseFloat(jQuery(this).css('width'));
                var height = parseFloat(jQuery(this).css('height'));
                close.css({'width': width, "height": height, "margin-top": -height});
                color_panel.append(close);
            }
        }
    });
    jQuery(document).on('change', '.e2pdf-wysiwyg-font-color', function (e) {
        e2pdf.wysiwyg.apply(jQuery(this));
    });
    jQuery(document).on('change', '.e2pdf-wysiwyg-fontsize', function (e) {
        e2pdf.wysiwyg.apply(jQuery(this));
    });
    jQuery(document).on('change', '.e2pdf-wysiwyg-font', function (e) {
        e2pdf.wysiwyg.apply(jQuery(this));
    });
    jQuery(document).on('change', '.e2pdf-upload-pdf', function (e) {
        e2pdf.request.upload('e2pdf_upload', jQuery('.e2pdf-w-upload'));
    });
    jQuery(document).on('click', '.e2pdf-w-reupload', function (e) {
        if (e2pdf.url.get('revision_id')) {
            alert(e2pdf.lang.get('Not Available in Revision Edit Mode'));
            return false;
        }

        var message = "";
        if (e2pdf.static.unsaved) {
            message += e2pdf.lang.get('WARNING: Template has changes after last save! Changes will be lost! Continue?') + "\r\n";
        }
        message += e2pdf.lang.get('Saved Template will be overwritten! Continue?');
        jQuery(this).attr('disabled', 'disabled');
        jQuery(this).closest('form').append(
                jQuery('<div>', {'class': 'e2pdf-grid e2pdf-confirmation e2pdf-center e2pdf-mb20'}).append(
                jQuery('<div>', {'class': 'e2pdf-w100 e2pdf-mb5'}).text(message),
                jQuery('<div>', {'class': 'e2pdf-w100'}).append(
                jQuery('<div>', {'class': 'e2pdf-ib e2pdf-pr5'}).append(
                jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-confirmation-confirm e2pdf-link button'}).html(e2pdf.lang.get('Confirm'))
                ),
                jQuery('<div>', {'class': 'e2pdf-ib e2pdf-pl5'}).append(
                jQuery('<a>', {'href': 'javascript:void(0);', 'class': 'e2pdf-confirmation-cancel e2pdf-link button'}).html(e2pdf.lang.get('Cancel'))
                )
                )
                )
                );
    });
    jQuery(document).on('change', '#e2pdf-revision', function (e) {
        if (e2pdf.static.unsaved) {
            if (!confirm(e2pdf.lang.get('WARNING: Template has changes after last save! Changes will be lost!'))) {
                var revision_id = e2pdf.url.get('revision_id') ? e2pdf.url.get('revision_id') : '0';
                jQuery(this).val(revision_id);
                return false;
            }
        }
        e2pdf.static.unsaved = false;
        var revision_id = jQuery(this).val();
        var template_id = e2pdf.pdf.settings.get('ID');
        var url = e2pdf.url.build('e2pdf-templates', 'action=edit&id=' + template_id + '&revision_id=' + revision_id);
        if (revision_id === '0') {
            url = e2pdf.url.build('e2pdf-templates', 'action=edit&id=' + template_id);
        }
        jQuery(this).attr('disabled', 'disabled');
        location.href = url;
    });
    jQuery(document).on('click', '#e2pdf-unlink-license-key', function (e) {
        if (!confirm(e2pdf.lang.get('Website will be forced to use "FREE" License Key! Continue?'))) {
            return false;
        }
        jQuery('html').addClass('e2pdf-loading');
        var data = {};
        e2pdf.request.submitRequest('e2pdf_license_key', jQuery(this), data);
    });
    jQuery(document).on('click', '#e2pdf-deactivate-all-templates', function (e) {
        if (!confirm(e2pdf.lang.get('All Templates for this Website will be deactivated! Continue?'))) {
            return false;
        }
        jQuery('html').addClass('e2pdf-loading');
        var data = {};
        e2pdf.request.submitRequest('e2pdf_deactivate_all_templates', jQuery(this), data);
    });
    jQuery(document).on('click', '#e2pdf-restore-license-key', function (e) {
        var data = {};
        jQuery(this).html(e2pdf.lang.get('In Progress...'));
        e2pdf.request.submitRequest('e2pdf_restore_license_key', jQuery(this), data);
    });
    jQuery(document).on('click', '.e2pdf-confirmation-confirm', function (e) {
        jQuery(this).closest('.e2pdf-confirmation').remove();
        jQuery('.e2pdf-w-reupload').attr('disabled', false);
        jQuery('.e2pdf-reupload-pdf').click();
    });
    jQuery(document).on('click', '.e2pdf-confirmation-cancel', function (e) {
        jQuery(this).closest('.e2pdf-confirmation').remove();
        jQuery('.e2pdf-w-reupload').attr('disabled', false);
    });
    jQuery(document).on('change', '.e2pdf-reupload-pdf', function (e) {
        e2pdf.request.upload('e2pdf_reupload', jQuery('.e2pdf-w-reupload'));
    });
    jQuery(document).on('click', '.e2pdf-el-properties input[name="group"]', function (e) {
        jQuery(this).autocomplete("search", "");
    });
    jQuery(document).on('click', '.e2pdf-el-properties input[name="wysiwyg_disable"]', function (e) {
        if (!jQuery(this).is(':checked')) {
            if (!confirm(e2pdf.lang.get('Enabling WYSIWYG can affect "HTML" Source'))) {
                return false;
            }
        }
    });
    jQuery(document).on('change', '.e2pdf-settings-style-change', function (e) {
        e2pdf.event.fire('after.settings.style.change');
    });
    jQuery(document).on('change keyup', '.e2pdf-settings-template-change', function (e) {
        e2pdf.event.fire('after.settings.template.change');
    });
    jQuery(document).on('keyup', '.e2pdf-export-dataset-search', function (e) {
        var field_key = jQuery(this).attr('field');
        var search = jQuery('.e2pdf-export-dataset-search[field="' + field_key + '"]').val();
        var dataset_field = jQuery('.e2pdf-export-dataset[name="' + field_key + '"]');
        var options = dataset_field.data('options');
        var regex = new RegExp(search, "gi");
        var selected = 0;
        dataset_field.empty();
        jQuery.each(options, function (i) {
            var option = options[i];
            if (i == 0 || option.value.match(regex) !== null) {
                if (e2pdf.url.get('action') == 'bulk') {
                    dataset_field.append(jQuery('<div>', {'class': 'e2pdf-ib e2pdf-w100'}).append(jQuery('<label>').html(option.value).prepend(jQuery('<input>', {'name': field_key + '[]', 'type': 'checkbox', 'value': option.key}))));
                } else {
                    dataset_field.append(jQuery('<option>', {'value': option.key}).html(option.value));
                    if ((selected == 0 && i !== 0) || (search === '' && i === 0)) {
                        dataset_field.val(option.key);
                        selected = 1;
                    }
                }
            }
        });
        dataset_field.find('input[type="checkbox"][value=""]').prop('checked', true).trigger('change');
        if (dataset_field.find('input[type="checkbox"]').length > 1) {
            jQuery('.e2pdf-export-form-submit').attr('disabled', false);
        } else {
            dataset_field.find('input[type="checkbox"]').attr('disabled', true);
            jQuery('.e2pdf-export-form-submit').attr('disabled', true);
        }
        if (this.datasetLoad) {
            clearTimeout(this.datasetLoad);
        }
        this.datasetLoad = setTimeout(function () {
            dataset_field.trigger('change');
        }, 1000);
    });
    jQuery(document).on('change', 'select[name="preset"]', function (e) {
        if (jQuery(this).val() !== '') {
            var form = jQuery(this).closest('form');
            var size = e2pdf_params['template_sizes'][jQuery(this).val()];
            form.find('input[name="width"]').val(size.width);
            form.find('input[name="height"]').val(size.height);
        }
    });
    jQuery(document).on('click', '.e2pdf-vm-element', function (e) {
        e2pdf.visual.mapper.apply(jQuery(this));
    });
    jQuery(document).on('dblclick', '.e2pdf-drag', function (e) {
        e2pdf.element.unselect();
        e2pdf.element.unfocus();
        var el = jQuery(this).closest('.e2pdf-element');
        e2pdf.element.focus(el);
    });
    jQuery(document).on('click', '.e2pdf-drag', function (e) {
        var el = jQuery(this).closest('.e2pdf-element');
        e2pdf.element.unfocus();
        if (e.ctrlKey || e.metaKey) {
            if (el.hasClass('e2pdf-selected')) {
                e2pdf.element.unselect(el);
            } else {
                e2pdf.element.select(el);
            }
        } else {
            if (el.hasClass('e2pdf-selected')) {
                if (Object.keys(e2pdf.element.selected).length > 1) {
                    e2pdf.element.unselect();
                    e2pdf.element.select(el);
                } else {
                    e2pdf.element.unselect(el);
                }
            } else {
                if (Object.keys(e2pdf.element.selected).length > 0) {
                    e2pdf.element.unselect();
                    e2pdf.element.select(el);
                } else {
                    e2pdf.element.select(el);
                }
            }
        }
    });
    jQuery(document).on('click', '.e2pdf-activate-template', function (e) {
        var data = {};
        data['id'] = jQuery(this).attr('data-id');
        e2pdf.request.submitRequest('e2pdf_activate_template', jQuery(this), data);
    });
    jQuery(document).on('click', '.e2pdf-deactivate-template', function (e) {
        var data = {};
        data['id'] = jQuery(this).attr('data-id');
        e2pdf.request.submitRequest('e2pdf_deactivate_template', jQuery(this), data);
    });
    jQuery(document).on('click', '.e2pdf-delete-item', function (e) {
        e.preventDefault();
        if (!confirm(e2pdf.lang.get('Dataset will be removed! Continue?'))) {
            return false;
        }
        var data = {};
        data['template'] = jQuery(this).attr('template');
        data['dataset'] = jQuery(this).attr('dataset');
        e2pdf.request.submitRequest('e2pdf_delete_item', jQuery(this), data);
    });
    jQuery(document).on('click', '.e2pdf-delete-items', function (e) {
        e.preventDefault();
        if (!confirm(e2pdf.lang.get('All datasets will be removed! Continue?'))) {
            return false;
        }
        var data = {};
        data['template'] = jQuery(this).attr('template');
        e2pdf.request.submitRequest('e2pdf_delete_items', jQuery(this), data);
    });
    jQuery(document).on('click', '.e2pdf-bulk-action', function (e) {
        e.preventDefault();
        switch (jQuery(this).attr('action')) {
            case 'start':
                if (!confirm(e2pdf.lang.get('The bulk export task will be started! Continue?'))) {
                    return false;
                }
                break;
            case 'stop':
                if (!confirm(e2pdf.lang.get('The bulk export task will be stopped! Continue?'))) {
                    return false;
                }
                break;
            case 'delete':
                if (!confirm(e2pdf.lang.get('The bulk export task will be removed! Continue?'))) {
                    return false;
                }
                break;
        }
        var data = {};
        data['bulk'] = jQuery(this).attr('bulk');
        data['action'] = jQuery(this).attr('action');
        e2pdf.request.submitRequest('e2pdf_bulk_action', jQuery(this), data);
    });
    jQuery(document).on('click', '.e2pdf-copy-field', function (e) {
        jQuery(this).select();
    });
    jQuery(document).on('click focus', '.e2pdf-autocomplete-cl', function (e) {
        jQuery(this).autocomplete("search", '');
    });
    jQuery(document).on('change', 'input[name="vm_hidden"]', function (e) {
        if (jQuery(this).is(':checked')) {
            e2pdf.static.vm.hidden = true;
        } else {
            e2pdf.static.vm.hidden = false;
        }
        e2pdf.visual.mapper.rebuild();
    });
    jQuery(document).on('keyup', 'input[name="vm_search"]', function (e) {
        var value = jQuery(this).val();
        if (value != '') {
            jQuery('.e2pdf-vm-content').find('.e2pdf-vm-item, h3').hide();
            jQuery('.e2pdf-vm-content').find('label').filter(function (c) {
                return jQuery(this).text().toLowerCase().indexOf(value.toLowerCase()) >= 0;
            }).closest('.e2pdf-vm-item').show().closest('.e2pdf-grid').prev('h3').show();
            jQuery('.e2pdf-vm-content').find('input').filter(function (c) {
                return jQuery(this).val().toLowerCase().indexOf(value.toLowerCase()) >= 0;
            }).closest('.e2pdf-vm-item').show().closest('.e2pdf-grid').prev('h3').show();
            jQuery('.e2pdf-vm-content').find('h3').filter(function (c) {
                return jQuery(this).text().toLowerCase().indexOf(value.toLowerCase()) >= 0;
            }).show().next('.e2pdf-grid').find('.e2pdf-vm-item').show();
        } else {
            jQuery('.e2pdf-vm-content').find('.e2pdf-vm-item, h3').show();
        }
        e2pdf.visual.mapper.rebuild();
    });
    jQuery(document).on('change', 'input[name="vm_replace"]', function (e) {
        if (jQuery(this).is(':checked')) {
            e2pdf.static.vm.replace = true;
        } else {
            e2pdf.static.vm.replace = false;
        }
    });
    jQuery(document).on('change', 'input[name="vm_close"]', function (e) {
        if (jQuery(this).is(':checked')) {
            e2pdf.static.vm.close = true;
        } else {
            e2pdf.static.vm.close = false;
        }
    });
    jQuery(document).on('paste', '.e2pdf-inner-element[contenteditable="true"]', function (e) {
        if (typeof e.clipboardData != 'undefined') {
            var clipboardData = e.clipboardData;
        } else if (typeof window.clipboardData != 'undefined') {
            var clipboardData = window.clipboardData;
        } else {
            var clipboardData = e.originalEvent.clipboardData;
        }
        if (typeof clipboardData.getData('text/plain') != 'undefined') {
            e.preventDefault();
            let data = clipboardData.getData('text/html') || clipboardData.getData('text/plain');
            data = e2pdf.helper.stripHTML(
                    data,
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                    'div', 'p', 'summary', 'details', 'figure', 'figure', 'figcaption', 'blockquote', 'main',
                    'span', 'a', 'br',
                    'b', 's', 'i', 'u', 'em', 'strong', 'small',
                    'sub', 'sup',
                    'table', 'th', 'thead', 'tbody', 'tr', 'td',
                    'ul', 'ol', 'li',
                    'a'
                    );
            return document.execCommand('insertHtml', false, data);
        }
    });
    if (jQuery('.e2pdf-form-builder > input[name="sub_action"]').length > 0) {
        e2pdf.pdf.settings.set('sub_action', jQuery('.e2pdf-form-builder > input[name="sub_action"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="ID"]').length > 0) {
        e2pdf.pdf.settings.set('ID', jQuery('.e2pdf-form-builder > input[name="ID"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="width"]').length > 0) {
        e2pdf.pdf.settings.set('width', jQuery('.e2pdf-form-builder > input[name="width"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="height"]').length > 0) {
        e2pdf.pdf.settings.set('height', jQuery('.e2pdf-form-builder > input[name="height"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="extension"]').length > 0) {
        e2pdf.pdf.settings.set('extension', jQuery('.e2pdf-form-builder > input[name="extension"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="item"]').length > 0) {
        e2pdf.pdf.settings.set('item', jQuery('.e2pdf-form-builder > input[name="item"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="item1"]').length > 0) {
        e2pdf.pdf.settings.set('item1', jQuery('.e2pdf-form-builder > input[name="item1"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="item2"]').length > 0) {
        e2pdf.pdf.settings.set('item2', jQuery('.e2pdf-form-builder > input[name="item2"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="item"]').length > 0) {
        e2pdf.pdf.settings.set('pdf', jQuery('.e2pdf-form-builder > input[name="pdf"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="format"]').length > 0) {
        e2pdf.pdf.settings.set('format', jQuery('.e2pdf-form-builder > input[name="format"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="activated"]').length > 0) {
        e2pdf.pdf.settings.set('activated', jQuery('.e2pdf-form-builder > input[name="activated"]').val());
    }

    if (jQuery('.e2pdf-form-builder > input[name="hooks"]').length > 0) {
        e2pdf.pdf.settings.set('hooks', jQuery('.e2pdf-form-builder > input[name="hooks"]').val());
    }

    jQuery('#e2pdf-zoom').trigger('change');

    if (jQuery('body').hasClass('toplevel_page_e2pdf')) {
        if (e2pdf.url.get('id')) {
            e2pdf.select2.val(jQuery('.e2pdf-export-template'), e2pdf.url.get('id'));
            e2pdf.static.autoloadExport = true;
        }
    }

    if (jQuery('body').hasClass('e2pdf_page_e2pdf-templates')) {
        jQuery('.e2pdf-color-picker-load').each(function () {
            jQuery(this).wpColorPicker(
                    {
                        defaultColor: function () {
                            var el = jQuery(event.target).parent().find('.e2pdf-color-picker');
                            if (el.attr('data-default')) {
                                return el.attr('data-default');
                            } else {
                                return;
                            }
                        },
                        change: function (event, ui) {
                            jQuery(this).val(ui.color.toString()).change();
                        }
                    }
            ).removeClass('e2pdf-color-picker-load');
        });
        jQuery(window).bind("beforeunload", function (e) {
            if (e2pdf.static.unsaved) {
                var confirmationMessage = "\o/";
                (e || window.event).returnValue = confirmationMessage;
                return confirmationMessage;
            }
        });
        if (jQuery('#e2pdf-build-form').length > 0) {
            postboxes.add_postbox_toggles(pagenow);
        }

        if (typeof (jQuery.ui) != 'undefined' && typeof (jQuery.ui.draggable) != 'undefined'
                && typeof (jQuery.ui.droppable) != 'undefined'
                && jQuery('.e2pdf-tpl').length > 0) {
            // jQuery UI scale bug fix
            jQuery.ui.ddmanager.prepareOffsets = function (t, event) {
                var i, j,
                        m = jQuery.ui.ddmanager.droppables[ t.options.scope ] || [],
                        type = event ? event.type : null,
                        list = (t.currentItem || t.element).find(":data(ui-droppable)").addBack();
                droppablesLoop: for (i = 0; i < m.length; i++) {
                    if (m[ i ].options.disabled || (t && !m[ i ].accept.call(m[ i ].element[ 0 ], (t.currentItem || t.element)))) {
                        continue;
                    }
                    for (j = 0; j < list.length; j++) {
                        if (list[ j ] === m[ i ].element[ 0 ]) {
                            m[ i ].proportions().height = 0;
                            continue droppablesLoop;
                        }
                    }
                    m[ i ].visible = m[ i ].element.css("display") !== "none";
                    if (!m[ i ].visible) {
                        continue;
                    }
                    if (type === "mousedown") {
                        m[ i ]._activate.call(m[ i ], event);
                    }
                    m[ i ].offset = m[ i ].element.offset();
                    m[ i ].proportions({width: m[ i ].element[ 0 ].offsetWidth * e2pdf.zoom.zoom, height: m[ i ].element[ 0 ].offsetHeight * e2pdf.zoom.zoom});
                }
            };
            jQuery('.e2pdf-tpl').data('data-type', 'e2pdf-tpl');

            var actions = JSON.parse(jQuery('.e2pdf-load-tpl').find('.e2pdf-data-actions').val());
            e2pdf.actions.apply(jQuery('.e2pdf-tpl'), actions);

            var properties = JSON.parse(jQuery('.e2pdf-load-tpl').find('.e2pdf-data-properties').val());
            e2pdf.properties.apply(jQuery('.e2pdf-tpl'), properties);
            e2pdf.helper.cssGlobal(e2pdf.helper.getString(properties['css']));

            jQuery('.e2pdf-load-tpl').remove();
            jQuery('.e2pdf-load-el').each(function () {
                var element = jQuery(this);
                var type = element.attr('data-type');
                var page = element.closest('.e2pdf-page');
                var properties = JSON.parse(element.find('.e2pdf-data-properties').val());
                var actions = JSON.parse(element.find('.e2pdf-data-actions').val());
                properties['width'] = element.attr('data-width');
                properties['height'] = element.attr('data-height');
                properties['top'] = element.attr('data-top');
                properties['left'] = element.attr('data-left');
                properties['value'] = element.find('.e2pdf-data-value').val();
                properties['name'] = element.find('.e2pdf-data-name').val();
                var el = e2pdf.element.create(type, page, properties, actions, false, true, element.attr('data-element_id'));
                jQuery(this).replaceWith(el);
                e2pdf.properties.render(el);
            });
            jQuery('.e2pdf-load-page').each(function () {
                var page = jQuery(this);
                var actions = JSON.parse(page.find('.e2pdf-data-actions').val());
                var properties = JSON.parse(page.find('.e2pdf-data-properties').val());
                page.find('.e2pdf-data-properties').remove();
                page.find('.e2pdf-data-actions').remove();
                page.removeClass('e2pdf-load-page');
                e2pdf.pages.createPage(page, properties, actions, true);
            });
            jQuery('.e2pdf-be').draggable({
                helper: function () {
                    var element = jQuery(this).clone();
                    var type = element.attr('data-type');
                    var page = element.closest('.e2pdf-page');
                    var el = e2pdf.element.create(type, page, false, false, true);
                    e2pdf.font.apply(el, jQuery('#e2pdf-font'));
                    e2pdf.font.size(el, jQuery('#e2pdf-font-size'));
                    e2pdf.font.line(el, jQuery('#e2pdf-line-height'));
                    e2pdf.font.fontcolor(el, jQuery('#e2pdf-font-color'));
                    e2pdf.properties.render(el);
                    el.css('z-index', 1);
                    if (e2pdf.zoom.zoom != 1) {
                        el.css('transform', 'scale(' + e2pdf.zoom.zoom + ')');
                        el.css('transform-origin', '0 0');
                    }
                    return el;
                },
                start: function (ev, ui) {
                    e2pdf.static.guide.x = ev.originalEvent.pageX - jQuery(this).offset().left;
                    e2pdf.static.guide.y = ev.originalEvent.pageY - jQuery(this).offset().top;
                    e2pdf.element.unselect();
                },
                stop: function (ev, ui) {
                    jQuery(".e2pdf-guide-v, .e2pdf-guide-h").hide();
                },
                drag: function (ev, ui) {
                    if (e2pdf.static.drag.page !== null) {
                        var pos = {left: ev.originalEvent.pageX - e2pdf.static.guide.x, top: ev.originalEvent.pageY - e2pdf.static.guide.y};
                        var guides = {top: {dist: e2pdf.static.guide.distance + 1}, left: {dist: e2pdf.static.guide.distance + 1}};
                        var w = parseFloat(jQuery(ui.helper).css('width')) * e2pdf.zoom.zoom;
                        var h = parseFloat(jQuery(ui.helper).css('height')) * e2pdf.zoom.zoom;
                        var el_guides = e2pdf.guide.calc(null, pos, w, h, true);
                        jQuery.each(e2pdf.static.guide.guides, function (i, guide) {
                            jQuery.each(el_guides, function (i, elemGuide) {
                                if (guide.type == elemGuide.type) {
                                    var prop = guide.type == "h" ? "top" : "left";
                                    var d = Math.abs(elemGuide[prop] - guide[prop]);
                                    if (d < guides[prop].dist) {
                                        guides[prop].dist = d;
                                        guides[prop].offset = elemGuide[prop] - pos[prop];
                                        guides[prop].guide = guide;
                                    }
                                }
                            });
                        });
                        if (guides.top.dist <= e2pdf.static.guide.distance) {
                            e2pdf.static.drag.page.find('.e2pdf-guide-h').css("top", guides.top.guide.top / e2pdf.zoom.zoom - e2pdf.static.drag.page.offset().top / e2pdf.zoom.zoom - 1).show();
                            var snap_top = guides.top.guide.top - guides.top.offset - jQuery(this).offset().top;
                            ui.position.top = snap_top;
                        } else {
                            jQuery('.e2pdf-guide-h').hide();
                        }
                        if (guides.left.dist <= e2pdf.static.guide.distance) {
                            e2pdf.static.drag.page.find('.e2pdf-guide-v').css("left", guides.left.guide.left / e2pdf.zoom.zoom - e2pdf.static.drag.page.offset().left / e2pdf.zoom.zoom - 1).show();
                            var snap_left = guides.left.guide.left - guides.left.offset - jQuery(this).offset().left + 5;
                            ui.position.left = snap_left;
                        } else {
                            jQuery('.e2pdf-guide-v').hide();
                        }
                    }
                }
            });
            e2pdf.welcomeScreen();
        }
        e2pdf.font.load(jQuery('#e2pdf-font'));
        jQuery('.e2pdf-load-font').each(function () {
            e2pdf.font.load(jQuery(this));
            jQuery(this).remove();
        });
        e2pdf.font.apply(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font'));
        e2pdf.font.size(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font-size'));
        e2pdf.font.line(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-line-height'));
        e2pdf.font.fontcolor(jQuery('.e2pdf-tpl'), jQuery('#e2pdf-font-color'));
    }

    jQuery(document).keydown(function (e) {
        if (
                jQuery('.e2pdf-focused').length == 0
                && jQuery(e.target).closest('.e2pdf-dialog-visual-mapper').length == 0
                && jQuery(e.target).closest('.e2pdf-dialog-element-properties').length == 0
                && Object.keys(e2pdf.element.selected).length > 0
                && jQuery.inArray(e.which, [37, 38, 39, 40, 46]) !== -1) {
            e.preventDefault();
            switch (e.which) {
                case 37:
                    // left
                    var diff = 1;
                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        if (parseFloat(selected.css('left')) > 0) {
                            if (parseFloat(selected.css('left')) - 1 < 0) {
                                diff = Math.min(diff, selected.css('left'));
                            }
                        } else {
                            diff = Math.min(diff, 0);
                        }
                    }

                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        selected.finish().animate({
                            left: "-=" + diff
                        }, 0);
                        e2pdf.properties.set(selected, 'left', e2pdf.helper.getFloat(selected.css('left')));
                    }
                    break;
                case 38:
                    // top
                    var diff = 1;
                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        if (parseFloat(selected.css('top')) > 0) {
                            if (parseFloat(selected.css('top')) - 1 < 0) {
                                diff = Math.min(diff, selected.css('top'));
                            }
                        } else {
                            diff = Math.min(diff, 0);
                        }
                    }

                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        selected.finish().animate({
                            top: "-=" + diff
                        }, 0);
                        e2pdf.properties.set(selected, 'top', e2pdf.helper.getFloat(selected.css('top')));
                    }
                    break;
                case 39:
                    // right
                    var diff = 1;
                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        if (parseFloat(selected.css('right')) > 0) {
                            if (parseFloat(selected.css('right')) - 1 < 0) {
                                diff = Math.min(diff, selected.css('right'));
                            }
                        } else {
                            diff = Math.min(diff, 0);
                        }
                    }

                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        selected.finish().animate({
                            left: "+=" + diff
                        }, 0);
                        e2pdf.properties.set(selected, 'left', e2pdf.helper.getFloat(selected.css('left')));
                    }
                    break;
                case 40:
                    // down
                    var diff = 1;
                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        if (parseFloat(selected.css('bottom')) > 0) {
                            if (parseFloat(selected.css('bottom')) - 1 < 0) {
                                diff = Math.min(diff, selected.css('bottom'));
                            }
                        } else {
                            diff = Math.min(diff, 0);
                        }
                    }

                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        selected.finish().animate({
                            top: "+=" + diff
                        }, 0);
                        e2pdf.properties.set(selected, 'top', e2pdf.helper.getFloat(selected.css('top')));
                    }
                    break;

                case 46:
                    // delete
                    var message = Object.keys(e2pdf.element.selected).length > 1 ? e2pdf.lang.get('Elements will be removed! Continue?') : e2pdf.lang.get('Element will be removed! Continue?');
                    if (!confirm(message)) {
                        e2pdf.delete('.e2pdf-context');
                        return false;
                    }

                    for (var key in e2pdf.element.selected) {
                        var selected = e2pdf.element.selected[key];
                        e2pdf.element.delete(selected);
                    }

                    e2pdf.element.unselect();
                    break;
            }
        }
    });

    jQuery(document).on('click', '.e2pdf-select2', function (e) {
        e2pdf.select2.init(jQuery(this));
    });

    jQuery(document).on('click', '.e2pdf-select2-dropdown > div', function (e) {
        e2pdf.select2.click(jQuery(this));
    });

    jQuery(document).on('keyup', '.e2pdf-select2', function (e) {
        e2pdf.select2.filter(jQuery(this));
    });

    jQuery(document).on('mousedown', 'body', function (e) {
        if (!jQuery(e.target).hasClass('e2pdf-drag') &&
                jQuery(e.target).closest('.e2pdf-context-menu').length == 0 &&
                jQuery(e.target).closest('.e2pdf-element').length == 0 &&
                jQuery(e.target).closest('.e2pdf-dialog-visual-mapper').length == 0 &&
                jQuery(e.target).closest('.e2pdf-dialog-element-properties').length == 0 &&
                jQuery(e.target).closest('.e2pdf-panel-options').length == 0 &&
                jQuery(e.target).closest('.e2pdf-wysiwyg-color').length == 0 &&
                e.ctrlKey !== true) {
            e2pdf.element.unselect();
            e2pdf.element.unfocus();
        }
        if (jQuery(e.target).closest('.e2pdf-closed').length == 0) {
            jQuery('.e2pdf-closed').each(function () {
                jQuery(this).removeClass('e2pdf-opened');
            });
        }
        if (!jQuery(e.target).hasClass('e2pdf-wysiwyg-table-grid') && !jQuery(e.target).parent().hasClass('e2pdf-wysiwyg-table-grid')) {
            jQuery('.e2pdf-wysiwyg-table-grid').addClass('e2pdf-hide');
        }
        if (jQuery(e.target).closest('.e2pdf-select2-dropdown').length == 0) {
            jQuery('.e2pdf-select2-dropdown').each(function () {
                e2pdf.select2.close(jQuery(this));
            });
        }
    });
    jQuery('.e2pdf-onload .disabled, .e2pdf-onload [disabled="disabled"], .e2pdf-onload[disabled="disabled"]').removeClass('disabled').attr('disabled', false);
    jQuery('.e2pdf-onload').removeClass('e2pdf-onload');
    if (jQuery('.e2pdf-bulks-list').length > 0)
    {
        e2pdf.bulk.progress();
    }
});