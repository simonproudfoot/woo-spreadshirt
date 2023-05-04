<?php

function delete_all_product_categories()
{
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));

    foreach ($terms as $term) {
        wp_delete_term($term->term_id, 'product_cat');
    }
}

function delete_all_products()
{
    $allposts = get_posts(array('post_type' => 'product', 'numberposts' => -1));
    foreach ($allposts as $eachpost) {
        wp_delete_post($eachpost->ID, true);
    }
    delete_all_product_categories();
    delete_all_woocommerce_attributes();
    delete_woo_spreadshirt_directory();
    return true;
}


function delete_all_woocommerce_attributes()
{
    global $wpdb;

    // Delete all WooCommerce attributes
    $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");

    // Delete any remaining terms associated with the attributes
    $taxonomy = 'pa_'; // This is the prefix used for WooCommerce product attribute taxonomies
    $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
    foreach ($terms as $term) {
        if (isset($term->term_id)) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }

    $taxonomy2 = ''; // This is the prefix used for WooCommerce product attribute taxonomies
    $terms = get_terms(array('taxonomy' => $taxonomy2, 'hide_empty' => false));
    foreach ($terms as $term) {
        if (isset($term->term_id)) {
            wp_delete_term($term->term_id, $taxonomy2);
        }
    }


    $attributes = json_decode(json_encode(wc_get_attribute_taxonomies()), true);
    sort($attributes);

    foreach ($attributes as $key => $attribute) {
        $deleted = wc_delete_attribute($attribute['attribute_id']);
                    wp_delete_term($term->term_id, $taxonomy2);
        echo '<pre>';
        print_r(sprintf('Deleting %s - Result %s', $attribute['attribute_label'], $deleted));
        echo '</pre>';
    }
}

// Call the function to delete all WooCommerce attributes
//add_action( 'init' , 'delete_all_woocommerce_attributes');


function delete_woo_spreadshirt_directory() {
    // Specify the uploads directory and the wooSpreadshirt directory
    $upload_dir = wp_upload_dir();
    $woo_spreadshirt_dir = trailingslashit($upload_dir['basedir']) . 'wooSpreadshirt';

    // Check if the wooSpreadshirt directory exists
    if (file_exists($woo_spreadshirt_dir)) {
        // Delete the wooSpreadshirt directory and all of its contents
        $files = array_diff(scandir($woo_spreadshirt_dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$woo_spreadshirt_dir/$file")) {
                // Delete subdirectories and their contents
                delete_directory("$woo_spreadshirt_dir/$file");
            } else {
                // Delete files
                unlink("$woo_spreadshirt_dir/$file");
            }
        }
        // Delete the wooSpreadshirt directory itself
        rmdir($woo_spreadshirt_dir);
    }
}

// Helper function to recursively delete a directory and its contents
function delete_directory($dir) {
    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                delete_directory("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }
    return false;
}
