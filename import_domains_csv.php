<?php

function import_domains_from_csv_to_db($file_path) {
    global $wpdb;

    // Define table name
    $table_name = $wpdb->prefix . 'trusted_domains_badges';

    // Step 1: Create the custom table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        domain VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY domain (domain)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Step 2: Open the CSV file and process it
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $is_first_row = true; // Initialize a flag for the first row
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if ($is_first_row) {
                $is_first_row = false; // Skip the first row (header)
                continue;
            }
            $domain = sanitize_text_field(trim($data[0])); // Assuming domain is in the first column
            if (!empty($domain)) {
                $wpdb->insert($table_name, ['domain' => $domain], ['%s']);
            }
        }
        fclose($handle);
        return "Domains successfully imported, skipping the header row.";
    } else {
        return "Error: Unable to open the file.";
    }
}

// Hook to trigger the function
function trigger_import_domains_csv() {
    $file_path = WP_CONTENT_DIR . '/uploads/all_domains.csv'; // Replace with the actual path to your CSV file
    echo import_domains_from_csv_to_db($file_path);
}
add_action('admin_init', 'trigger_import_domains_csv');