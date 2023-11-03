<?php
// Define a function to add a menu item in the dashboard
function custom_user_import_menu() {
    add_menu_page(
        'User Import',
        'User Import',
        'manage_options',
        'custom-user-import',
        'custom_user_import_page'
    );
}
add_action('admin_menu', 'custom_user_import_menu');

// Create the user import page
function custom_user_import_page() {
    ?>
    <div class="wrap">
        <h2>User Import</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" />
            <input type="submit" name="import_users" value="Import Users" class="button button-primary" />
        </form>
        <?php
        // Check if a success message is set and display it in green color
        $success_message_output = custom_user_import_handler();
        if (!empty($success_message_output)) {
            echo '<div class="notice notice-success is-dismissible">' . $success_message_output . '</div>';
        }
        ?>
    </div>
    <?php
}

// Handle the user import
function custom_user_import_handler() {
    $success_message = '';
    $error_message = '';
    

    if (isset($_POST['import_users'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, 'r')) !== false) {
                $row_count = 0;

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    // Increment the row count
                    $row_count++;

                    // Skip the first row (header)
                    if ($row_count === 1) {
                        continue;
                    }

                    // Check if there are more than 7 fields
                    if (count($data) != 7) {
                        $error_message .= "Error: Row $row_count does not have exactly 7 fields. Skipping import for this row.\n";
                        continue;
                    }

                    // Extract user data from CSV columns
                    $username = $data[0];
                    $email = $data[1];
                    $first_name = $data[2];
                    $last_name = $data[3];
                    $password = $data[4];
                    $address = $data[5];
                    $phone = $data[6];

                    // Create user
                    $user_id = wp_create_user($username, $password, $email);

                    // Add metadata
                    if (!is_wp_error($user_id)) {
                        // Set default user role as Subscriber
                        wp_update_user(['ID' => $user_id, 'role' => 'subscriber']);

                        // Store First Name and Last Name as separate meta keys
                        update_user_meta($user_id, 'first_name', $first_name);
                        update_user_meta($user_id, 'last_name', $last_name);

                        // Change meta keys to lowercase
                        update_user_meta($user_id, 'address', $address);
                        update_user_meta($user_id, 'phone', $phone);

                       
                    }
                }

                fclose($handle);
                $processed = $row_count == 0 ? $row_count : $row_count-1;

                // Set the success message
                $success_message = 'Users imported successfully. ' .$processed . ' records processed.';
              //print_r($processed_count);die;
            }
        } else {
            $error_message = 'Error: No valid CSV file uploaded.';
        }
    }


    return $success_message;
}
add_action('admin_init', 'custom_user_import_handler');
?>
