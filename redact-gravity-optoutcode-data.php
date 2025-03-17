<?php

// Register a custom API route
//Endpoint: https://wordpress-1086277-3906279.cloudwaysapps.com/wp-json/custom/v1/redact-entry/{entryID}
add_action('rest_api_init', function () {
	register_rest_route('custom/v1', '/redact-entry/(?P<id>\d+)', array(
		'methods' => 'POST',
		'callback' => 'handle_redact_entry',
		'permission_callback' => 'validate_secret_token',
	));
});

// Function to validate the request origin by IP
function validate_ip_address()
{
	// Get the IP address of the client
	$client_ip = $_SERVER['REMOTE_ADDR'];
	// Define allowed IP addresses or ranges
	$allowed_ips = [
		'54.86.50.139',
		'202.173.124.247',   // My IP
	];

	// Check if the client IP matches any allowed IPs
	if (in_array($client_ip, $allowed_ips)) {
		return true; // IP is allowed
	}

	// If the IP does not match, return an error
	return new WP_Error('unauthorized', 'IP address not authorized', array('status' => 403));
}

// Validate the secret token
function validate_secret_token()
{

	$token = isset($_SERVER['HTTP_X_API_TOKEN']) ? $_SERVER['HTTP_X_API_TOKEN'] : '';
	// Define the expected token (secret token)
	$expected_token = expected_token;

	// Compare the received token with the expected token
	if ($token !== $expected_token) {
		return new WP_Error('unauthorized', 'Invalid token', array('status' => 401));
	}

	return true;
}

// Handle the redaction of the Gravity Forms entry
function handle_redact_entry($data)
{

	// Validate IP address
	// $ip_validation = validate_ip_address();
	// if (is_wp_error($ip_validation)) {
	// 	return $ip_validation;
	// }

	$entry_id = $data['id'];

	// Ensure the GF Entry ID exists
	if (!GFAPI::entry_exists($entry_id)) {
		return new WP_Error('not_found', 'Entry not found', array('status' => 404));
	}

	// Define field mappings based on form ID
	$field_mappings = [];

	// Get the entry details
	$entry = GFAPI::get_entry($entry_id);

	// Get form ID of the entry
	$form_id = $entry['form_id'];

	// Allowed form IDs
	$allowed_form_ids = array(22, 23, 28, 49, 7, 50);

	// If form ID is not allowed, return an error
	if (!in_array($form_id, $allowed_form_ids)) {
		return new WP_Error('forbidden', 'Form ID not allowed', array('status' => 403));
	}

	// Define excluded fields per form ID
	$excluded_fields_by_form = [
		22 => ['19', '14', '17', 'id', 'date_created', '16'],  //Loan
		23 => ['13', '8', '11', 'id', 'date_created', '10'],  //Insurance
		28 => ['13', '8', '11', 'id', 'date_created', '10'],            //Lease
		49 => ['createdAt'],       //Mobile Apps
		7 => ['createdAt', 'dealer_name'],  //Consumer Advocate
		50 => ['date_created', 'id', '21', '17', '23', '24', '25', '26'] //Optout
	];

	// If no excluded fields are set for a form, fallback to an empty array
	$excluded_fields = isset($excluded_fields_by_form[$form_id]) ? $excluded_fields_by_form[$form_id] : [];

	
	// Function to redact every second character
	function redact_field()
	{
		$redacted_value = '';
		return $redacted_value;
	}

	// Function to redact the last 6 digits with asterisks
	function redact_last_six_digits($value)
	{
		if (strlen($value) <= 6) {
			return str_repeat('*', strlen($value));
		}
		return substr($value, 0, -6) . str_repeat('*', 6);
	}

	// Loop through all fields in the entry
	foreach ($entry as $field_id => $field_value) {

		// Skip excluded fields
		if (in_array($field_id, $excluded_fields)) {
			continue;
		}

		// Check if the field is "vin" or "field_contains_vins_one" to redact last 6 digits
		if (in_array($field_id, ['vin', 'field_contains_vins_one']) && !empty($field_value)) {
			$entry[$field_id] = redact_last_six_digits($field_value);
		} else {
			// Redact other fields (make them blank or null)
			$entry[$field_id] = redact_field();
		}
	}

	// //Perform redaction

	//Update the entry with redacted data
	$result = GFAPI::update_entry($entry);

	if (is_wp_error($result)) {
		return new WP_Error('update_failed', 'Failed to update the entry', array('status' => 500));
	}

	return array(
		'status' => 'success',
		'message' => 'Entry redacted successfully'
	);
}
