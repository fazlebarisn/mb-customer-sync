<?php 
/*
 * Plugin Name:       MB Synchronize all Customer
 * Description:       This plugin synchronizes all Customer meta from a database
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            CanSoft
 * Author URI:        https://cansoft.com/
 */
// Include your functions here

// require_once( plugin_dir_path( __FILE__ ) . '/inc/all-functions/mb-customer-meta-sync.php');


require_once( plugin_dir_path( __FILE__ ) . '/inc/all-functions/get-user-id-by-customer_code-meta-value.php');
require_once( plugin_dir_path( __FILE__ ) . '/inc/all-functions/mb-customer-sync.php');


 require_once( plugin_dir_path( __FILE__ ) . '/inc/api/fetch-all-customer-from-ezposcustomer-table.php');


//WORDPRESS HOOK FOR ADD A CRON JOB EVERY 2 Min

function mb_customer_cron_schedules($schedules){
    if(!isset($schedules['every_twelve_hours'])){
        $schedules['every_twelve_hours'] = array(
            'interval' => 12*60*60, // Every 12 hours
            'display' => __('Every 12 hours'));
    }
    return $schedules;
}

add_filter('cron_schedules','mb_customer_cron_schedules');




// Enqueue all assets
function mb_customer_all_assets(){
    wp_enqueue_script('mb-customer-script', plugin_dir_url( __FILE__ ) . 'assets/admin/js/script.js', null, time(), true);
}
add_action( 'admin_enqueue_scripts', 'mb_customer_all_assets' );


/**
 * Add menu page for this plugin
 */
function mb_customer_sync_menu_pages(){
    //add_menu_page('Mb Customer Sync', 'Customer Sync', 'manage_options', 'mb-customer-sync', 'customer_sync_page');
    add_submenu_page( 'mb_syncs', 'Mb Customer Sync', 'Mb Customer Sync', 'manage_options', 'mb-customer-sync', 'customer_sync_page' );
}
add_action( 'admin_menu', 'mb_customer_sync_menu_pages', 999 );

/**
 * Main function for product sync
 */
function customer_sync_page(){
    ?>
    <style>
        .wrap .d-flex {
            display: flex;
            align-items: center;
            justify-content: space-evenly;
        }
    </style>
        <div class="wrap">
            <h1>This Page for Sincronize all Customer Pricelist</h1><br>
            <div class="d-flex">
            	<form method="GET">

            		<input type="hidden" name="page" value="mb-customer-sync">
	                <input type="hidden" name="page_no" value="1">
	                

	                <?php submit_button('Run', 'primary', 'mb-all-customer-sync'); ?>

	            </form>

                <form method="POST">
                    <?php 
                        submit_button( 'Start ezposcustomer Cron Now', 'primary', 'mb-ezposcustomer-sync-cron' );
                        // submit_button( 'Menual Start', 'primary', 'mb-ezposcustomer-menual-sync-cron' );
                    ?>
                </form>
            </div>
          
            <?php 

                if(isset($_GET['page_no'])){
                        
                    $pageno = $_GET['page_no'] ?? 1;
                    // global $wpdb;
                    // $last_user_id = $wpdb->get_var("SELECT ID FROM $wpdb->users ORDER BY ID DESC LIMIT 1");
                    //dd($last_user_id);
                    
                    $all_customers = fetch_all_customer_form_ezposcustomer_table($pageno);

                    //dd($all_customers);
                    $api_ids = [];

                    // $start = microtime(true);
                    $arraychunk = array_chunk($all_customers, 100);
                    //$i = 1000;
                    foreach ($arraychunk as $allCustomers) {
                       // $i++;
                        foreach($allCustomers as $_c_meta){
                            
                        	 // Disable email notifications
                            remove_action('user_register', 'wp_send_new_user_notifications', 10);
                            remove_action('edit_user_created_user', 'wp_send_new_user_notifications', 10);

                            $existingUserId = user_exists_by_email_for_customer($_c_meta["email"]);
                            //dd($existingUserId);

                            if ($existingUserId) {

                                // wp_update_user(array(
                                //     'ID' => $existingUserId,
                                //     'user_login' => $_c_meta["email"],
                                //     'first_name' => $_c_meta["firstname"],
                                //     'user_pass' => "123",
                                //     'user_nicename' => $_c_meta["firstname"],
                                //     'user_registered' => "2023-11-22 12:10:12",
                                //     'user_status' => 0,
                                //     'display_name' => $_c_meta["firstname"]. " " .$_c_meta["lastname"],
                                //     'user_url' => $_c_meta["address"]["website"]
                                // ));

                                //dd($resutl);
                                // $userdetails = new WP_User($existingUserId);

                                // $result = $userdetails->set_role('customer');

                                $cusStatus = $_c_meta["status"] == "1" ? "Active" : "Inactive";
                                $aproveStatus = $_c_meta["approved"] == "1" ? "Yes" : "No";
                                
                                update_user_meta($existingUserId, "status", $cusStatus);
                                update_user_meta($existingUserId, "approve_status", $aproveStatus);
                                update_user_meta($existingUserId, "nickname", $_c_meta["firstname"]);
                                var_dump($_c_meta["address"]["account_no"]);
                                if ($_c_meta["address"]["account_no"]) {
                                    update_user_meta($existingUserId, "customer_code", $_c_meta["address"]["account_no"]);
                                }
                                
                                update_user_meta($existingUserId, "customer_id", $_c_meta["customer_id"]);
                                update_user_meta($existingUserId, "telephone", $_c_meta["telephone"]);
                                update_user_meta($existingUserId, "classification", $_c_meta["group"]["meta"]["name"]);

                                update_user_meta($existingUserId, "date_of_birth", $_c_meta["birth_date"]);

                                update_user_meta($existingUserId, "pricelist_type", $_c_meta["CUS_Pricelist"]);

                                update_user_meta($existingUserId, "account_number", $_c_meta["address"]["account_no"]);
                                update_user_meta($existingUserId, "card_number", $_c_meta["address"]["showroom_no"]);
                                update_user_meta($existingUserId, "billing_address_1", $_c_meta["address"]["address_1"]);
                                update_user_meta($existingUserId, "billing_address_2", $_c_meta["address"]["address_2"]);
                                update_user_meta($existingUserId, "billing_city", $_c_meta["address"]["city"]);
                                update_user_meta($existingUserId, "billing_postcode", $_c_meta["address"]["postcode"]);
                                update_user_meta($existingUserId, "billing_country", $_c_meta["address"]["country"]["name"]);
                                update_user_meta($existingUserId, "billing_state", $_c_meta["CUS_State"]);
                                update_user_meta($existingUserId, "billing_phone", $_c_meta["address"]["busphone"]);

                               echo "This email already exits ".$_c_meta["email"]."Data update Only. <br>";

                            }else{
                                //dd($_c_meta["email"]);
                                
                                if ($_c_meta["email"]) {
                                     $userId = wp_create_user($_c_meta["email"], "123", $_c_meta["email"]);
                                     //dd( $userId);
                                }else{
                                    $userId = wp_create_user($_c_meta["firstname"], "123", $_c_meta["email"]);
                                }
                               
                                // var_dump($_c_meta["CUS_EMail"]);
                                //dd($userId);

                                if (!is_wp_error($userId)) {

                                    echo "<strong style='color:red'>User Create Successfully customer Id is</strong>".$userId;

                                    wp_update_user(array(
                                        'ID' => $userId,
                                        'first_name' => $_c_meta["firstname"],
                                        'display_name' => $_c_meta["firstname"]. " " .$_c_meta["lastname"],
                                        'user_url' => $_c_meta["address"]["website"]
                                    ));

                                    //dd($resutl);
                                    $userdetails = new WP_User($userId);

                                    $result = $userdetails->set_role('customer');

                                    $cusStatus = $_c_meta["status"] == "1" ? "Active" : "Inactive";
                                    $aproveStatus = $_c_meta["approved"] == "1" ? "Yes" : "No";
                                    
                                    update_user_meta($userId, "status", $cusStatus);
                                    
                                    update_user_meta($userId, "approve_status", $aproveStatus);
                                    update_user_meta($userId, "nickname", $_c_meta["firstname"]);

                                    update_user_meta($userId, "customer_code", $_c_meta["CUS_Code"]);
                                    update_user_meta($userId, "customer_id", $_c_meta["customer_id"]);
                                    update_user_meta($userId, "telephone", $_c_meta["telephone"]);
                                    update_user_meta($userId, "classification", $_c_meta["group"]["meta"]["name"]);

                                    update_user_meta($userId, "date_of_birth", $_c_meta["birth_date"]);

                                    update_user_meta($userId, "pricelist_type", $_c_meta["CUS_Pricelist"]);

                                    update_user_meta($userId, "account_number", $_c_meta["address"]["account_no"]);
                                    update_user_meta($userId, "card_number", $_c_meta["address"]["showroom_no"]);
                                    update_user_meta($userId, "billing_address_1", $_c_meta["address"]["address_1"]);
                                    update_user_meta($userId, "billing_address_2", $_c_meta["address"]["address_2"]);
                                    update_user_meta($userId, "billing_city", $_c_meta["address"]["city"]);
                                    update_user_meta($userId, "billing_postcode", $_c_meta["address"]["postcode"]);
                                    update_user_meta($userId, "billing_country", $_c_meta["address"]["country"]["name"]);
                                    update_user_meta($userId, "billing_state", $_c_meta["CUS_State"]);
                                    update_user_meta($userId, "billing_phone", $_c_meta["address"]["busphone"]);
                                    update_user_meta($userId, "website", $_c_meta["address"]["website"]);

                                    // echo "<pre>";
                                    // print_r($userId);
                                    // echo "</pre>";

                                    // echo "<pre>";
                                    // print_r($_c_meta["CUS_Code"]);
                                    // echo "</pre>";


                                }else{
                                    // Error handling if user creation fails
                                    $error_message = $userId->get_error_message();
                                    error_log('User creation failed: ' . $error_message);
                                }
                            }

                        }
                    }

                    

                    // API endpoint
                   //  $apiUrl = 'https://modern.cansoft.com/db-clone/api/j3-mijoshop-customer/update?key=58fff5F55dd444967ddkhzf';
                   // // $apiUrl = 'https://modern.cansoft.com/db-clone/api/ezpos-customers/update?key=58fff5F55dd444967ddkhzf';
                   //  //modern.cansoft.com/db-clone/api/ezpos-customers?key=58fff5F55dd444967ddkhzf
                    
                   //  // List of update IDs
                   //  $updateIds = implode(",", $api_ids);
                    
                   //  // Prepare the request payload
                   //  $requestData = [
                   //      'id' => $updateIds,
                   //      'clone_status' => 'Synced',
                   //  ];

                   //  // Use cURL to make the API request
                   //  $ch = curl_init($apiUrl);
                   //  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                   //  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestData));
                   //  $response = curl_exec($ch);

                   //  //$total = microtime(true) - $start;

                   //  // Check for errors or process the response as needed
                   //  if ($response === false) {
                   //      // Handle cURL error
                   //      echo 'cURL Error: ' . curl_error($ch);
                   //  } else {
                   //      // Process the API response
                   //      // $response contains the API response data
                   //      echo 'API Response: ' . $response;
                   //  }

                   //  // Close the cURL session
                   //  curl_close($ch);

                    $total = microtime(true) - $start;
                    echo "<span style='color:red;font-weight:bold'>Total Execution Time: </span>" . $total;


                    if(! count( $all_customers )){
                        wp_redirect( admin_url( "/profile.php?page=mb-customer-sync" ) );
                        exit();
                    }
                }


                // if (isset($_POST['mb-icpricp-product-sync-menual'])) {

                //     mb_customer_meta_sync(1);
                //     wp_redirect( admin_url( "/edit.php?page=mb-customer-sync" ) );
                //     exit();
                // }


                //It work when Click Strt cron  button
                if(isset($_POST['mb-ezposcustomer-sync-cron'])){
                    if (!wp_next_scheduled('mb_ezposcustomer_add_with_cron')) {
                        wp_schedule_event(time(), 'every_twelve_hours', 'mb_ezposcustomer_add_with_cron');
                    }
                    wp_redirect( admin_url( "/edit.php?page=mb-customer-pricelist-sync" ) );
                    exit();
                }

            ?>
        </div>
    <?php 
}

//For clear cron schedule
function woo_customer_syncronization_apis_plugin_deactivation(){
    wp_clear_scheduled_hook('mb_ezposcustomer_add_with_cron');
    
}
register_deactivation_hook(__FILE__, 'woo_customer_syncronization_apis_plugin_deactivation');


// This happend when icitem caron job is runnning


// This happend when icpricp caron job is runnning

function mb_run_cron_for_ezposcustomer_table(){

    $start = microtime(true);

    mb_customer_sync(1);

    $total = microtime(true) - $start;

    $total = "Total execution time is ". $total;
    
    file_put_contents(plugin_dir_path(__FILE__) . 'cron_debug.log', $total, FILE_APPEND);
    
}

add_action('mb_ezposcustomer_add_with_cron', 'mb_run_cron_for_ezposcustomer_table');

