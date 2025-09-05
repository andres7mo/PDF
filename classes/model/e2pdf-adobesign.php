<?php

/**
 * File: /model/e2pdf-adobesign.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_AdobeSign extends Model_E2pdf_Model {

    protected $api = null;
    protected $provider = array(
        'api_access_point' => false,
        'web_access_point' => false,
        'access_token' => false,
        'refresh_token' => false,
        'code' => false,
        'client_id' => false,
        'client_secret' => false,
    );

    public function __construct() {

        parent::__construct();

        $this->provider = array(
            'api_access_point' => get_option('e2pdf_adobesign_api_access_point'),
            'web_access_point' => get_option('e2pdf_adobesign_web_access_point'),
            'access_token' => get_transient('e2pdf_adobesign_access_token'),
            'refresh_token' => get_option('e2pdf_adobesign_refresh_token'),
            'code' => get_option('e2pdf_adobesign_code'),
            'client_id' => get_option('e2pdf_adobesign_client_id'),
            'client_secret' => get_option('e2pdf_adobesign_client_secret'),
        );

        if (!$this->provider['access_token'] && $this->provider['refresh_token']) {
            $this->set(
                    array(
                        'action' => 'oauth/v2/refresh',
                        'data' => array(
                            'grant_type' => 'refresh_token',
                            'client_id' => $this->provider['client_id'],
                            'client_secret' => $this->provider['client_secret'],
                            'refresh_token' => $this->provider['refresh_token'],
                        ),
                    )
            );
            $access_token = $this->request('access_token');
            if ($access_token) {
                set_transient('e2pdf_adobesign_access_token', $access_token, 1800);
                set_transient('e2pdf_adobesign_refresh_token', $this->provider['refresh_token'], 2592000);
                $this->provider['access_token'] = $access_token;
            }
            $this->flush();
        }
    }

    // get code
    public function get_code() {
        $response = array();
        if ($this->provider['client_id'] && $this->provider['client_secret']) {
            $scopes = array();
            $options = Model_E2pdf_Options::get_options(false, array('adobesign_group'));
            foreach ($options as $group_key => $group) {
                foreach ($group['options'] as $option_key => $option_value) {
                    if (substr($option_value['key'], 0, 21) === 'e2pdf_adobesign_scope' && $option_value['value']) {
                        $scopes[] = substr($option_value['key'], 22) . ':' . $option_value['value'];
                    }
                }
            }
            $data = array(
                'response_type' => 'code',
                'client_id' => $this->provider['client_id'],
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
                'redirect_uri' => urlencode(
                        get_option('e2pdf_adobe_api_version') == '1' ? site_url('/e2pdf-rpc/v1/adobe/auth?api_key=' . get_option('e2pdf_adobe_api_key')) : $this->helper->get_url(
                                array(
                                    'page' => 'e2pdf-settings',
                                    'action' => 'adobesign',
                                )
                        ),
                ),
                'scope' => implode('+', $scopes),
            );
            $response['redirect'] = sprintf('https://secure.%s.echosign.com/public/oauth/v2', get_option('e2pdf_adobesign_region')) . '?' . urldecode(http_build_query($data));
        }
        return $response;
    }

    // get token
    public function get_token() {
        $response = array();
        $this->set(
                array(
                    'action' => 'oauth/v2/token',
                    'data' => array(
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => get_option('e2pdf_adobe_api_version') == '1' ? site_url('/e2pdf-rpc/v1/adobe/auth?api_key=' . get_option('e2pdf_adobe_api_key')) : $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf-settings',
                                            'action' => 'adobesign',
                                        )
                                ),
                        'client_id' => $this->provider['client_id'],
                        'client_secret' => $this->provider['client_secret'],
                        'code' => $this->provider['code'],
                    ),
                )
        );
        $request = $this->request();

        $this->flush();
        if (isset($request['access_token']) && isset($request['refresh_token'])) {
            update_option('e2pdf_adobesign_refresh_token', $request['refresh_token']);
            update_option('e2pdf_adobesign_api_access_point', $request['api_access_point']);
            update_option('e2pdf_adobesign_web_access_point', $request['web_access_point']);
            set_transient('e2pdf_adobesign_access_token', $request['access_token'], 1800);
            set_transient('e2pdf_adobesign_refresh_token', $request['refresh_token'], 2592000);
        } elseif (isset($request['error'])) {
            $response['error'] = $request['error'];
        } else {
            $response['error'] = __('Something went wrong!', 'e2pdf');
        }
        return $response;
    }

    // request
    public function request($key = false) {

        if ($this->api && isset($this->api->action) && ($this->provider['api_access_point'] || $this->api->action == 'oauth/v2/token')) {

            $response = array();
            $headers = array();
            $data = array();
            $json_data = '[]';

            if (!empty($this->api->data)) {
                $data = array_merge($data, $this->api->data);
            }

            if ($this->api->action != 'oauth/v2/refresh' && $this->api->action != 'oauth/v2/token') {
                if ($this->provider['access_token']) {
                    $headers[] = 'Authorization: Bearear';
                    $headers[] = 'Access-Token: ' . $this->provider['access_token'];
                } else {
                    $response['error'] = __('Access Token is not set or Incorrect', 'e2pdf');
                    return $response;
                }
            }

            if ($this->api->action == 'oauth/v2/token') {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $this->provider['api_access_point'] = 'https://api.echosign.com/';
            } elseif ($this->api->action == 'oauth/v2/refresh') {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            } elseif ($this->api->action == 'api/rest/v5/transientDocuments') {
                $headers[] = 'Content-Type: multipart/form-data';
            } else {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
                $json_data = json_encode($data);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-Length: ' . strlen($json_data);
            }

            $request_url = $this->provider['api_access_point'] . $this->api->action;
            $timeout = get_option('e2pdf_connection_timeout', '300');

            // phpcs:disable WordPress.WP.AlternativeFunctions
            $ch = curl_init($request_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            }
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

            if (isset($this->api->method) && $this->api->method) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->api->method);
            }

            if ($this->api->action == 'oauth/v2/refresh' || $this->api->action == 'oauth/v2/token') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } elseif ($this->api->action == 'api/rest/v5/transientDocuments') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            }

            $json = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);
            // phpcs:enable

            if ($curl_errno > 0) {
                $response['error'] = "[{$curl_errno}] {$curl_error}";
            } else {
                $result = json_decode($json, true);
                $response = $result;
                if (empty($response)) {
                    $response['error'] = __('Something went wrong!', 'e2pdf');
                }
            }

            if ($key) {
                if (isset($response[$key])) {
                    return $response[$key];
                } else {
                    return false;
                }
            } else {
                return $response;
            }
        }

        return false;
    }

    // flush
    public function flush() {
        $this->api = null;
    }

    // set
    public function set($key, $value = false) {
        if (!$this->api) {
            $this->api = new stdClass();
        }
        if (is_array($key)) {
            foreach ($key as $attr => $value) {
                $this->api->$attr = $value;
            }
        } else {
            $this->api->$key = $value;
        }
    }
}
