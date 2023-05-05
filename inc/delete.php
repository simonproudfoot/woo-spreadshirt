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
    delete_woo_spreadshirt_folder();
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

function delete_woo_spreadshirt_folder() {
    $upload_dir = wp_upload_dir(); // Get the WordPress upload directory info
    $woo_spreadshirt_dir = $upload_dir['basedir'] . '/wooSpreadshirt'; // Set the path to the folder to delete
    
    if (is_dir($woo_spreadshirt_dir)) {
        // If the directory exists, delete all files and subdirectories recursively
        $files = array_diff(scandir($woo_spreadshirt_dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$woo_spreadshirt_dir/$file")) ? delete_directory("$woo_spreadshirt_dir/$file") : unlink("$woo_spreadshirt_dir/$file");
        }
        // Finally, remove the empty directory
        rmdir($woo_spreadshirt_dir);
        
        // Delete associated media items from the WordPress media library
        global $wpdb;
        $media_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE guid LIKE '%/wooSpreadshirt/%'"); // Get all media IDs associated with the deleted folder
        foreach ($media_ids as $media_id) {
            wp_delete_attachment($media_id, true); // Delete each associated media item permanently
        }
    }
}
