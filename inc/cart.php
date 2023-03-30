<?php
//add_action('init', 'convert_cart_to_spreadshirt_basket');

function convert_cart_to_spreadshirt_basket()
{
    if (is_admin()) return false;
    if (empty (WC()->cart->get_cart())) return false;

    // Get the user's cart
    $cart = WC()->cart->get_cart();

    // Prepare the request data
    $request_data = array(
        'items' => array()
    );


    //  var_dump($request_data);


    // Loop through the cart items and add them to the request data
    foreach ($cart as $cart_item) {
        $product_id = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        $quantity = $cart_item['quantity'];

        // Get the product and variation details
        $product = wc_get_product($product_id);
        $variation = wc_get_product($variation_id);

        //var_dump($product);

        // Add the item to the request data
        $request_data['items'][] = array(
            'productId' => $product_id,
            'quantity' => $quantity,
            'properties' => array(
                // 'size' => $variation->get_attribute('size'),
                // 'color' => $variation->get_attribute('color'),
                // Add any other custom properties you need here
            ),
            'shop' => array(
                'id' => '101082106'
            )
        );
    }
    // var_dump($request_data);
    // Convert the request data to JSON
    $request_json = json_encode($request_data);

    // Set the cURL options
    $curl_options = array(
        CURLOPT_URL => 'https://api.spreadshirt.com/api/v1/baskets?mediaType=json&fullData=true',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $request_json,
        CURLOPT_HTTPHEADER => array(
            'Authorization: SprdAuth apiKey="dd30b4db-8cd6-4fb8-86b3-e680984b9e18"',
            'User-Agent: greenwich/1.0',
            'Access-Control-Allow-Origin: *',
        ),
    );

    // Initialize cURL
    $curl = curl_init();

    // Set the cURL options
    curl_setopt_array($curl, $curl_options);

    // Execute the cURL request
    $response = json_decode(curl_exec($curl));
    // var_dump($response);
    $basketId = $response->id;
    $basketUrl = $response->href;
    // Check for errors
    if (curl_errno($curl)) {
        // Log the error or display an error message to the user
        error_log('Failed to convert cart to Spreadshirt basket: ' . curl_error($curl));
        // Add your own error handling code here
    } else {
        echo '<a href="' . $basketUrl . '"/>go to basket</a>';
    }
    // Close the cURL session
    curl_close($curl);
}
