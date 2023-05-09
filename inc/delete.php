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
    delete_all_product_posts();
    delete_all_product_categories();
    delete_images_with_metadata();
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


function delete_images_with_metadata()
{
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'meta_key' => 'wooSpreadImage',
        'meta_value' => true,
        'post_status'    => 'inherit'
    );
    $loop = new WP_Query($args);
    while ($loop->have_posts()) : $loop->the_post();
        wp_delete_attachment(get_the_ID(), true); // Delete the attachment permanently
    endwhile;
}


function delete_all_product_posts()
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_key' => 'wooSpreadProduct',
        'meta_value' => true,
    );
    $loop = new WP_Query($args);
    while ($loop->have_posts()) : $loop->the_post();
        wp_delete_post(get_the_ID()); // Delete the attachment permanently
    endwhile;
}
