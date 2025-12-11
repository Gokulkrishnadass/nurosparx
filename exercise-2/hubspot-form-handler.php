<?php
/**
 * HubSpot Form Handler
 * Route: /wp-json/nxs/v1/lead
 */

add_action('rest_api_init', function () {
    register_rest_route('nxs/v1', '/lead', array(
        'methods'             => 'POST',
        'callback'            => 'nxs_handle_hubspot_lead',
        'permission_callback' => '__return_true', // Public form
    ));
});

function nxs_handle_hubspot_lead(WP_REST_Request $request) {
    $ip = nxs_get_user_ip();

    // Rate limiting: max 3 per IP per hour.
    if (!nxs_rate_limit_check($ip)) {
        return new WP_REST_Response(array(
            'success'    => false,
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'message'    => 'Too many submissions. Please try again later.',
        ), 429);
    }

    // Honeypot – field "website" must be empty.
    $honeypot = $request->get_param('website');
    if (!empty($honeypot)) {
        // Count but silently succeed to avoid tipping off bots.
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'OK',
        ), 200);
    }

    // Sanitize & validate fields.
    $full_name  = sanitize_text_field($request->get_param('full_name'));
    $email      = sanitize_email($request->get_param('email'));
    $phone      = sanitize_text_field($request->get_param('phone'));
    $company    = sanitize_text_field($request->get_param('company_name'));
    $message    = wp_kses_post($request->get_param('message'));

    $utm_source   = sanitize_text_field($request->get_param('utm_source'));
    $utm_medium   = sanitize_text_field($request->get_param('utm_medium'));
    $utm_campaign = sanitize_text_field($request->get_param('utm_campaign'));

    if (empty($email) || !is_email($email)) {
        return new WP_REST_Response(array(
            'success'    => false,
            'error_code' => 'INVALID_EMAIL',
            'message'    => 'Please enter a valid email address.',
        ), 400);
    }

    if (empty($full_name)) {
        return new WP_REST_Response(array(
            'success'    => false,
            'error_code' => 'INVALID_NAME',
            'message'    => 'Please enter your name.',
        ), 400);
    }

    if ($phone) {
        $phone_e164 = nxs_format_us_phone_to_e164($phone);
        if ($phone_e164 === null) {
            return new WP_REST_Response(array(
                'success'    => false,
                'error_code' => 'INVALID_PHONE',
                'message'    => 'Please enter a valid US phone number.',
            ), 400);
        }
        $phone = $phone_e164;
    }

    // Prepare payload for HubSpot.
    $hubspot_payload = array(
        'fields' => array(
            array('name' => 'email',         'value' => $email),
            array('name' => 'firstname',     'value' => $full_name),
            array('name' => 'phone',         'value' => $phone),
            array('name' => 'company',       'value' => $company),
            array('name' => 'message',       'value' => $message),
            array('name' => 'utm_source',    'value' => $utm_source),
            array('name' => 'utm_medium',    'value' => $utm_medium),
            array('name' => 'utm_campaign',  'value' => $utm_campaign),
        ),
        'context' => array(
            'ipAddress' => $ip,
            'pageUri'   => esc_url_raw($request->get_header('referer')),
            'pageName'  => get_bloginfo('name'),
        ),
    );

    // Placeholder HubSpot credentials – replace with env constants or options.
    $portal_id = 'XXXXXX';
    $form_guid = 'YYYYYYYY-YYYY-YYYY-YYYY-YYYYYYYYYYYY';
    $endpoint  = "https://api.hsforms.com/submissions/v3/integration/submit/{$portal_id}/{$form_guid}";

    $hubspot_response = wp_remote_post($endpoint, array(
        'timeout' => 10,
        'headers' => array(
            'Content-Type' => 'application/json',
            // If using Private App Key instead, use Authorization: Bearer <token>
        ),
        'body'    => wp_json_encode($hubspot_payload),
    ));

    $hubspot_success = false;
    $hubspot_lead_id = null;

    if (is_wp_error($hubspot_response)) {
        nxs_log_lead_error('http_error', $hubspot_response->get_error_message());
    } else {
        $code = wp_remote_retrieve_response_code($hubspot_response);
        $body = json_decode(wp_remote_retrieve_body($hubspot_response), true);

        if ($code >= 200 && $code < 300) {
            $hubspot_success = true;
            // Example: HubSpot returns a "inlineMessage" or id-like structure; we simulate.
            $hubspot_lead_id = isset($body['inlineMessage']) ? $body['inlineMessage'] : uniqid('hubspot_', true);
        } else {
            nxs_log_lead_error('hubspot_error', 'Status ' . $code);
        }
    }

    // Store in backup table regardless, but flag status.
    global $wpdb;
    $table = $wpdb->prefix . 'nxs_lead_backups';

    $wpdb->insert($table, array(
        'created_at'       => current_time('mysql'),
        'ip_address'       => $ip,
        'full_name'        => $full_name,
        'email'            => $email,
        'phone'            => $phone,
        'company'          => $company,
        'message'          => $message,
        'utm_source'       => $utm_source,
        'utm_medium'       => $utm_medium,
        'utm_campaign'     => $utm_campaign,
        'hubspot_status'   => $hubspot_success ? 'success' : 'failed',
        'hubspot_lead_id'  => $hubspot_lead_id,
    ), array(
        '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'
    ));

    $lead_id = $wpdb->insert_id;

    if ($hubspot_success) {
        return new WP_REST_Response(array(
            'success' => true,
            'lead_id' => $lead_id,
            'message' => 'Thank you for contacting us.',
        ), 200);
    }

    return new WP_REST_Response(array(
        'success'    => false,
        'lead_id'    => $lead_id,
        'error_code' => 'HUBSPOT_FAILED',
        'message'    => 'We received your request but had an issue syncing with our CRM. Our team will follow up.',
    ), 502);
}

/**
 * Simple rate limiting by IP with transients.
 */
function nxs_rate_limit_check($ip) {
    $key   = 'nxs_lead_rate_' . md5($ip);
    $data  = get_transient($key);

    if (!$data) {
        $data = array('count' => 1, 'start' => time());
        set_transient($key, $data, HOUR_IN_SECONDS);
        return true;
    }

    if (time() - $data['start'] > HOUR_IN_SECONDS) {
        $data = array('count' => 1, 'start' => time());
        set_transient($key, $data, HOUR_IN_SECONDS);
        return true;
    }

    if ($data['count'] >= 3) {
        return false;
    }

    $data['count']++;
    set_transient($key, $data, HOUR_IN_SECONDS);
    return true;
}

/**
 * Format US phone number to E.164 (+1XXXXXXXXXX).
 * Returns null if invalid.
 */
function nxs_format_us_phone_to_e164($phone) {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 10) {
        return '+1' . $digits;
    }
    if (strlen($digits) === 11 && substr($digits, 0, 1) === '1') {
        return '+1' . substr($digits, 1);
    }
    return null;
}

/**
 * Get user IP address.
 */
function nxs_get_user_ip() {
    foreach (array('HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
        if (!empty($_SERVER[$key])) {
            $ip_list = explode(',', $_SERVER[$key]);
            return trim($ip_list[0]);
        }
    }
    return '0.0.0.0';
}

/**
 * Log errors without exposing PII.
 */
function nxs_log_lead_error($type, $message) {
    $safe_message = sprintf('[NXS Lead Error] type=%s msg=%s', $type, $message);
    error_log($safe_message);
}
