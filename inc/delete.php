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
