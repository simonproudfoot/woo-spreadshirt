<?php
function convertID($name, $post_id, $field)
{
    $array = get_post_meta($post_id, $field);
    foreach ($array[0] as $element) {
        if ($element['name'] === $name) {
            return $element['id'];
        }
    }
}

function convert_cart_to_spreadshirt_basket()
{
    if (is_admin()) return false;
    if (empty(WC()->cart->get_cart())) return false;
    // Get the user's cart
    $cart = WC()->cart->get_cart();

    // Loop through the cart items and add convert to speadshit basket format
    foreach ($cart as $cart_item) {
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        $quantity = $cart_item['quantity'];

        // Get the product and variation details
        $product = wc_get_product($product_id);
        $variation = wc_get_product($variation_id);
        $variation = $cart_item['variation'];
        $colorName = $variation['attribute_color'];
        $sizeName = $variation['attribute_size'];
        $sizeid = convertID($sizeName, $product_id, 'size_ids');
        $colorid = convertID($colorName, $product_id, 'color_ids');


        // Add the item to the request data
        $request_data['basketItems'][] = array(
            'quantity' => $quantity,
            'element' => array(
                'id' => $product->sku,
                "type" => "sprd:sellable",
                'properties' => array(
                    array(
                        'key' => 'size',
                        'value' => $sizeid
                    ),
                    array(
                        'key' => 'appearance',
                        'value' => $colorid
                    )
                ),
                'shop' => array(
                    'id' => '101082106'
                )
            )
        );
    }
    return json_encode($request_data);
}

function checkout_redirect()
{
    // Check if the current page slug is "checkout"
    if (is_page('checkout')) {
        $cartData = convert_cart_to_spreadshirt_basket();
        $response = get_spreadshirt_basket($cartData);

        $basketId = $response->id;
        $basketUrl = $response->href;
        // var_dump($response->href);
        // Redirect to your desired URL
       // wp_redirect($response->href);
        exit;
    }
}
add_action('template_redirect', 'checkout_redirect');
