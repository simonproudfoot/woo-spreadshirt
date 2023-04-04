<?php

function set_spreadshirt_products()
{
    $response = get_spreadshirt_data('sellables', null);
    $allItems = $response->sellables;
    foreach ($allItems as $item) {
        $productType = get_spreadshirt_data('productTypes/' . $item->productTypeId, null);
        try {
            if (strlen($productType->categoryName) > 1) {
                // get variations
                $colors = getProductColors($item->productTypeId);
                $colorNames = array_map(function ($item) {
                    return $item['name'];
                }, $colors);

                $sizes = getProductSizes($item->productTypeId);
                $sizesNames = array_map(function ($item) {
                    return $item['name'];
                }, $sizes);


        
                $product_data = array(
                    'SKU' => $item->sellableId,
                    'parent' => null,
                    'name' => $item->name . ' - ' . $productType->categoryName,
                    'regular_price' => $item->price->amount,
                    'description' => $productType->description,
                    'short_description' => $item->description,
                    'tags' => $item->tags,
                    'image' =>  $item->previewImage->url,
                    'type' => 'simple',
                    'status' => 'publish',
                );

                $size_attribute = new WC_Product_Attribute();
                $size_attribute->set_id(0);
                $size_attribute->set_name('size');
                $size_attribute->set_options($sizesNames);
                $size_attribute->set_position(1);
                $size_attribute->set_visible(1);
                $size_attribute->set_variation(1);

                $color_attribute = new WC_Product_Attribute();
                $color_attribute->set_id(0);
                $color_attribute->set_name('color');
                $color_attribute->set_options($colorNames);
                $color_attribute->set_position(0);
                $color_attribute->set_visible(1);
                $color_attribute->set_variation(1);

                $attributes = array($size_attribute, $color_attribute);

                //Save main product to get its id
                $id = createProduct($product_data, $attributes);
                update_post_meta($id, 'image_meta_url', $product_data['image']);
                wp_set_object_terms($id, array($productType->name, $productType->categoryName), 'product_cat');

                update_post_meta($id, 'image_meta_url', $product_data['image']);
                update_post_meta($id, '_knawatfibu_url', $product_data['image']);

                update_post_meta($id, 'size_ids', $sizes);
                update_post_meta($id, 'color_ids', $colors);

                wp_set_object_terms($id, $product_data['tags'], 'product_tag');



                foreach ($colorNames as $color) {
                    $color_variation = new WC_Product_Variation();
                    $color_variation->set_regular_price($item->price->amount);
                    $color_variation->set_parent_id($id);
                    $color_variation->set_attributes(array(
                        'color' => $color
                    ));
                    $color_variation->save();
                }

                foreach ($sizesNames as $size) {
                    $size_variation = new WC_Product_Variation();
                    $size_variation->set_regular_price($item->price->amount);
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
    $ptype = get_spreadshirt_data('productTypes/' . $productTypeId, null);
    $colors = array();
    $variations = $ptype->appearances;

    foreach ($variations as $color) {
        if (!in_array($color->name, $colors)) {
            array_push($colors, array('id' => $color->id, 'name' => $color->name));
        }
    }
    return $colors;
}



function getProductSizes($productTypeId)
{
    $data = get_spreadshirt_data('productTypes/' . $productTypeId, null)->sizes;
    $sizes = array();
    foreach ($data as $size) {
        if (!in_array($size->name, $sizes)) {
            array_push($sizes, array('id' => $size->id, 'name' => $size->name));
        }
    }
    return $sizes;
}


function test()
{
    $response = get_spreadshirt_data('sellables', null);
    //$id = $response->sellables[0]->productTypeId;
    // $ptype = get_spreadshirt_data('productTypes/' . $id);
    echo '<pre>';
    // var_dump($response);
    echo '</pre>';
}

//add_action('init', 'test');

add_action('admin_head-edit.php', 'my_custom_button_in_all_products_page');
function my_custom_button_in_all_products_page()
{
    global $pagenow;
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
        echo '<button class="button" onclick="set_spreadshirt_products()">Set Spreadshirt Products</button>';
    }
}
