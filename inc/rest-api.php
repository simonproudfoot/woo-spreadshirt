<?php
// API ENDPOINTS
add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/get-products/', array(
        'methods' => 'GET',
        'callback' => 'updateAll',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('api/v1', '/get-variants/', array(
        'methods' => 'GET',
        'callback' => 'update_all_product_variant_images',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('api/v1', '/delete-products/', array(
        'methods' => 'GET',
        'callback' => 'delete_all_products',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('api/v1', '/get-categories/', array(
        'methods' => 'GET',
        'callback' => 'set_custom_taxonomies',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('api/v1', '/delete-categories/', array(
        'methods' => 'GET',
        'callback' => 'delete_all_product_categories',
        'permission_callback' => '__return_true',
    ));
});
