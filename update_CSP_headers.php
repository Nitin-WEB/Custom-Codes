<?php

function get_trusted_domains()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'trusted_domains_badges';

	$results = $wpdb->get_col("SELECT domain FROM $table_name");
	return $results; // Returns an array of trusted domains.
}

//B1-004
function add_content_security_policy()
{

	// Check if CSP feature is enabled
	if (defined('ENABLE_CSP') && ENABLE_CSP === true) {
		// Define trusted domains
		$trusted_domains = get_trusted_domains();

		$request_origin = '';
		if (!empty($_SERVER['HTTP_ORIGIN'])) {
			$request_origin = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
		} elseif (!empty($_SERVER['HTTP_REFERER'])) {
			$request_origin = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
		}

		// Build frame-ancestors policy dynamically
		if (in_array($request_origin, $trusted_domains)) {
			$frame_ancestors = "'self' http://$request_origin https://$request_origin";
		} else {
			$frame_ancestors = "'self'"; // Default to only allow same origin
		}


		// Apply the CSP header
			header_remove('Content-Security-Policy'); // Ensure no conflicting headers
			header("Content-Security-Policy: frame-ancestors $frame_ancestors;");
	}
}

add_action('send_headers', 'add_content_security_policy');

function update_trusted_domains()
{
	error_log("WP update_trusted_domains triggered");
	global $wpdb;

	$table_name = $wpdb->prefix . 'trusted_domains_badges';
	$api_url = BADGE_API_URL . '/api/domain/v1/fetchWhiteListedDomainsCSP'; // Replace with your actual API URL

	$args = [
		'headers' => [
			'Authorization' => 'Bearer ' . BEARER_TOKEN,
		],
		//Remove/Comment this on prod
		'auth' => [
			'username' => 'zcbzqjfqwf',
			'password' => 'jb5CUKc8yB',
		],
	];
	// Fetch data from API
	$response = wp_remote_get($api_url, $args);

	if (is_wp_error($response)) {
		error_log('Error fetching domains: ' . $response->get_error_message());
		return;
	}

	$body = wp_remote_retrieve_body($response);
	$domains = json_decode($body, true);

	// Check if response contains 'data' key with an array
	if (!isset($domains['data']) || !is_array($domains['data'])) {
		error_log('Invalid API response format: ' . print_r($domains, true));
		return;
	}

	$domains = $domains['data'];

	foreach ($domains as $domain) {

		$domain = trim($domain);

		if (!is_string($domain)) {
			error_log("Skipping invalid domain: " . print_r($domain, true));
			continue;
		}

		// Check if domain already exists
		$existing = $wpdb->get_var(
			$wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE domain = %s", $domain)
		);

		if (!$existing) {
			$wpdb->insert(
				$table_name,
				['domain' => $domain],
				['%s']
			);

			error_log('Inserted new domain in DB: ' . $domain);
		} else {
			error_log('Domain already exists in DB: ' . $domain);
		}

	}


}

function schedule_trusted_domains_update()
{
	if (!wp_next_scheduled('update_trusted_domains_event')) {
		wp_schedule_event(strtotime('13:00:00'), 'daily', 'update_trusted_domains_event');
	}
}
add_action('init', 'schedule_trusted_domains_update');
add_action('update_trusted_domains_event', 'update_trusted_domains');