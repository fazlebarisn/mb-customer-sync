<?php 

/**
 * Get Customer id by customer_code Meta value
 */

function user_exists_by_email_for_customer($email) {
    if (empty($email)) {
        return false;
    }

    $user_id = email_exists($email);

    return $user_id ? $user_id : false;
}