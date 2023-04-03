<?php
function get_spreadshirt_data($endPoint)
{
    $curl = curl_init();
    $url = 'https://api.spreadshirt.net/api/v1/shops/101082106/' . $endPoint . '?mediaType=json&fullData=true';
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: SprdAuth apiKey="dd30b4db-8cd6-4fb8-86b3-e680984b9e18"',
            'User-Agent: greenwich/1.0',
            'Access-Control-Allow-Origin: *',
        ),
    ));
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Error: ' . curl_error($curl);
    } else {
        return json_decode($response);
    }
    curl_close($curl);
}



function set_spreadshirt_products()
{
    $response = get_spreadshirt_data('sellables');
    $allItems = $response->sellables;
    foreach ($allItems as $item) {
        $productType = get_spreadshirt_data('productTypes/' . $item->productTypeId);
        try {
            if (strlen($productType->categoryName) > 1) {
                // get colors
                $colors = getProductColors($item->productTypeId);
                $sizes = getProductSizes($item->productTypeId);

                $product_data = array(
                    'SKU' => $item->sellableId,
                    'parent' => null,
                    'name' => $item->name . ' - ' . $productType->categoryName,
                    'regular_price' => $item->price->amount,
                    'description' => $item->description,
                    'short_description' => $item->description,
                    'tags' => $item->tags,
                    'image' =>  $item->previewImage->url,
                    'type' => 'simple',
                    'status' => 'publish',
                );


                $size_attribute = new WC_Product_Attribute();
                $size_attribute->set_id(0);
                $size_attribute->set_name('size');
                $size_attribute->set_options($sizes);
                $size_attribute->set_position(1);
                $size_attribute->set_visible(1);
                $size_attribute->set_variation(1);

                $color_attribute = new WC_Product_Attribute();
                $color_attribute->set_id(0);
                $color_attribute->set_name('color');
                $color_attribute->set_options($colors);
                $color_attribute->set_position(0);
                $color_attribute->set_visible(1);
                $color_attribute->set_variation(1);

                $attributes = array($size_attribute, $color_attribute);

                //Save main product to get its id
                $id = createProduct($product_data, $attributes);
                update_post_meta($id, 'image_meta_url', $product_data['image']);
                wp_set_object_terms($id, $productType->categoryName, 'product_cat');
                wp_set_object_terms($id, $product_data['tags'], 'product_tag');

                foreach ($colors as $color) {
                    $color_variation = new WC_Product_Variation();
                    $color_variation->set_regular_price($item->price->amount);
                    $color_variation->set_parent_id($id);
                    $color_variation->set_attributes(array(
                        'color' => $color
                    ));
                    $color_variation->save();
                }

                foreach ($sizes as $size) {
                    $size_variation = new WC_Product_Variation();
                    $size_variation->set_regular_price($size->price->amount);
                    $size_variation->set_parent_id($id);
                    $size_variation->set_attributes(array(
                        'size' => $size
                    ));

                    $size_variation->save();
                }
            }
        } catch (Exception $ex) {
            return;
        }
    }
}

function createProduct($product_data, $attributes)
{
    // create product 
    $product = new WC_Product_Variable;
    $product->set_name($product_data['name']);
    $product->set_sku($product_data['SKU']);
    $product->set_regular_price($product_data['regular_price']);
    $product->set_description($product_data['description']);
    $product->set_attributes($attributes);
    $product_id = $product->save();
    return $product_id;
}

function getProductColors($productTypeId)
{
    $ptype = get_spreadshirt_data('productTypes/' . $productTypeId);
    $colors = array();
    $variations = $ptype->appearances;

    foreach ($variations as $color) {
        if (!in_array($color->name, $colors)) {
            array_push($colors, $color->name);
        }
    }
    // }
    return $colors;
}


function getProductSizes($productTypeId)
{
    $data = get_spreadshirt_data('productTypes/' . $productTypeId)->sizes;
    $sizes = array();
    foreach ($data as $size) {
        if (!in_array($size->name, $sizes)) {
            array_push($sizes, $size->name);
        }
    }
    return $sizes;
}
