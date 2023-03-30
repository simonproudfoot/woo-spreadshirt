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
    return true;
}
