<?php

/**
 * File: /model/e2pdf-notification.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Notification extends Model_E2pdf_Model {

    // add notification
    public function add_notification($type, $text) {
        $notifications = get_transient('e2pdf_notifications');
        $notifications[] = array(
            'type' => $type,
            'text' => $text,
        );
        set_transient('e2pdf_notifications', $notifications);
    }

    // get notifications
    public function get_notifications() {
        $notifications = get_transient('e2pdf_notifications');

        if (!is_array($notifications)) {
            $notifications = array();
        }

        if (!get_option('e2pdf_hide_warnings', '0')) {
            if ($this->helper->get('license')->get('status') == 'pre_expired') {
                /* translators: %s: License Key Expire Date */
                $message = sprintf(__('Your E2Pdf License Key will expire at <strong>%s</strong>', 'e2pdf'), $this->helper->get('license')->get('expire'));
                if (current_user_can('manage_options') || current_user_can('e2pdf_license')) {
                    $message .= ' | ' . sprintf('<a class="e2pdf-link" target="_blank" href="%s">%s »</a>', 'https://e2pdf.com/checkout/license/renew/' . get_option('e2pdf_license'), __('Renew License Key', 'e2pdf'));
                }
                array_unshift(
                        $notifications,
                        array(
                            'type' => 'notice',
                            'text' => $message,
                        )
                );
            }

            if ($this->helper->get('license')->get('status') == 'expired') {
                $message = __('Your E2Pdf License Key has expired', 'e2pdf');
                if (current_user_can('manage_options') || current_user_can('e2pdf_license')) {
                    $message .= ' | ' . sprintf('<a class="e2pdf-link" target="_blank" href="%s">%s »</a>', 'https://e2pdf.com/checkout/license/renew/' . get_option('e2pdf_license'), __('Renew License Key', 'e2pdf'));
                }
                array_unshift(
                        $notifications,
                        array(
                            'type' => 'error',
                            'text' => $message,
                        )
                );
            }

            if ($this->helper->get('license')->get('type') == 'FREE' && $this->helper->get('page') == 'e2pdf-templates') {
                array_unshift(
                        $notifications,
                        array(
                            'type' => 'notice',
                            'text' => sprintf(__('You are using E2Pdf FREE License Type', 'e2pdf') . ' | ' . __('Up to 1 Page and up to 1 Template allowed', 'e2pdf') . ' | <a class="e2pdf-link" target="_blank" href="%s">%s »</a>', 'https://e2pdf.com/price', __('Upgrade License Key', 'e2pdf')),
                        )
                );
            }
        }

        if ($this->helper->get('license')->get('error')) {
            foreach ($notifications as $key => $notify) {
                if ($notify['type'] === 'error' && $notify['text'] === $this->helper->get('license')->get('error')) {
                    unset($notifications[$key]);
                }
            }
            if ($this->helper->get('license')->get('error') === 'License Key does not match this site. Please correct License Key to continue usage.') {
                $message = __('E2Pdf License Key does not match this site', 'e2pdf');
                if (current_user_can('manage_options') || current_user_can('e2pdf_license')) {
                    $message .= ' | ' . sprintf('<a class="e2pdf-link" id="e2pdf-restore-license-key"  href="javascript:void(0)" _wpnonce="%s">%s »</a>', wp_create_nonce('e2pdf_license'), __('Restore License Key', 'e2pdf'));
                }
            } else {
                $message = $this->helper->get('license')->get('error');
            }
            array_unshift(
                    $notifications,
                    array(
                        'type' => 'error',
                        'text' => $message,
                    )
            );
        }

        set_transient('e2pdf_notifications', array());
        return $notifications;
    }
}
