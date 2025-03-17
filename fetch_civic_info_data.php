<?php

add_action('rest_api_init', function () {
	register_rest_route('custom/v1', '/civic-info', [
		'methods' => 'GET',
		'callback' => 'fetch_civic_info_data',
		'permission_callback' => '__return_true', // Add authentication if needed
	]);
});

function fetch_civic_info_data(WP_REST_Request $request)
{
	$address = sanitize_text_field($request->get_param('address'));

	if (empty($address)) {
		return new WP_Error('no_address', 'Address parameter is required', ['status' => 400]);
	}

	$api_key = 'AIzaSyBoy9r2EDyzLDJlzc_sUtZJIPpzVNjRnxU'; // Replace with your actual API key
	$url = "https://civicinfo.googleapis.com/civicinfo/v2/representatives?address=" . urlencode($address) . "&key=" . $api_key;

	// Define the referrer in the headers
	$headers = [
		'Referer' => 'https://vehicleprivacyreport.com/', // Replace with your actual referrer
	];

	$response = wp_remote_get($url, ['headers' => $headers]);

	if (is_wp_error($response)) {
		return new WP_Error('api_error', 'Unable to fetch data', ['status' => 500]);
	}

	// Fetch the data from your custom function (loadIssueTreeDataFromJsonByType)
	$issueDetails = loadIssueTreeDataFromJsonByType('Elected Officials');

	// Log the issueDetails to check what is being returned
	error_log('Loaded Issue Details: ' . print_r($issueDetails, true));

	// Retrieve the body content of the response
	$body = wp_remote_retrieve_body($response);

	// Decode the response body into an associative array
	$response_data = json_decode($body, true);

	//  Check if decoding the response was successful
	if (is_array($response_data)) {
		// Append the issueDetails to the response data
		$response_data['issueDetails'] = $issueDetails;

		error_log("response_data: " . $response_data);
	} else {
		// Handle error if decoding fails
		return new WP_Error('response_error', 'Failed to decode the API response.', ['status' => 500]);
	}

	// Return the modified response with the added issueDetails
	return rest_ensure_response($response_data);
}