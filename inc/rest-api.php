<?php
// API ENDPOINTS
add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/get-products/', array(
        'methods' => 'GET',
        'callback' => 'updateAll',
    ));
    register_rest_route('api/v1', '/delete-products/', array(
        'methods' => 'GET',
        'callback' => 'delete_all_products',
    ));
    register_rest_route('api/v1', '/get-categories/', array(
        'methods' => 'GET',
        'callback' => 'set_custom_taxonomies',
    ));
    register_rest_route('api/v1', '/delete-categories/', array(
        'methods' => 'GET',
        'callback' => 'delete_all_product_categories',
    ));
});
