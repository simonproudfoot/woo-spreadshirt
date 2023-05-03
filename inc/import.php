<?php

//echo $product->get_sku(); 
//add_action('init', 'updateAll');
function updateAll()
{
    $allStoreItems = array();
    $responseCount = get_spreadshirt_data('sellables?', null, null); // get inital count
    $count = $responseCount->count;
    $limit = $responseCount->limit;
    $loops = ceil($count / $limit);

    for ($i = 0; $i <= $loops; $i++) {
        try {
            $response = get_spreadshirt_data('sellables', null, $i);
            foreach ($response->sellables as $selable) {
                array_push($allStoreItems, $selable);
            }
            if ($i == $loops) {
            }
        } finally {
            set_spreadshirt_products($allStoreItems);
        }
    }
}


function set_spreadshirt_products($allItems)
{

    foreach ($allItems as $item) {

        try {
            $productType = get_spreadshirt_data('productTypes/' . $item->productTypeId, null, null);
            if (strlen($productType->categoryName) > 1 && $item->sellableId) {

                $productID = wc_get_product_id_by_sku($item->sellableId);

                if (empty($productID)) {

                    $additional_data = array("ideaId" => $item->ideaId, 'appearanceIds' => $item->appearanceIds);
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
                    update_post_meta($id, 'additional_data', json_encode($additional_data));

                    wp_set_object_terms($id, $product_data['tags'], 'product_tag');
                    foreach ($colorNames as $color) {
                        $color_variation = new WC_Product_Variation();
                        $color_variation->set_regular_price($item->price->amount);
                        $color_variation->set_parent_id($id);
                        $color_variation->set_attributes(array(
                            'color' => $color
                        ));

                        $variationId = $color_variation->save();
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
            }
        } catch (Exception $ex) {
            echo $ex;
            return;
        }
    }
    return true;
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
    $ptype = get_spreadshirt_data('productTypes/' . $productTypeId, null, null);
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
    $data = get_spreadshirt_data('productTypes/' . $productTypeId, null, null)->sizes;
    $sizes = array();
    foreach ($data as $size) {
        if (!in_array($size->name, $sizes)) {
            array_push($sizes, array('id' => $size->id, 'name' => $size->name));
        }
    }
    return $sizes;

    add_action('admin_head-edit.php', 'my_custom_button_in_all_products_page');
    function my_custom_button_in_all_products_page()
    {
        global $pagenow;
        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
            echo '<button class="button" onclick="set_spreadshirt_products()">Set Spreadshirt Products</button>';
        }
    }
}


//add_action('init', 'getVariantImages');


function getVariantImages($sellableId, $ideaId, $appearanceId)
// function getVariantImages()
{


    // test
    // $sellableId = 'Dp4y5VBdBzCe1ZeQ59Mb-812-7';
    // $appearanceId = 348;
    // $ideaId = '641d9847ceea756f57df14e2';
    // test end 

    $url = 'sellables/' . $sellableId . '?appearanceId=' . $appearanceId . '&ideaId=' . $ideaId;
    $data = get_spreadshirt_data($url, null, null);
    $image = $data->images;

    foreach ($image as $variant) {
        if ($variant->type === "MODEL") {
            $model_urls[] = $variant->url;
        }
    }
    return $model_urls[0];
}


function change_variation_image_url_by_id($variation_id, $image_url)
{
    update_post_meta($variation_id, '_knawatfibu_url', $image_url);
    delete_transient('wc_var_' . $variation_id . '_get_variation_attributes');
}


// function update_variation_images_on_product_page_load()
// {
//     global $product;
//     $sellableId = $product->sku;
//     $productMeta = json_decode(get_post_meta($product->id, 'additional_data')[0], true);
//     $ideaId = $productMeta['ideaId'];
//     $colorVersionIds = $productMeta['appearanceIds'];
//     echo($colorVersionIds);
//     // $variations = $product->get_available_variations();
//     foreach ($variations as $variation) {
//         $variation_id = $variation['variation_id'];
//         $new_image_url = 'https://www.urbanrider.co.uk/media/catalog/product/cache/008f5b54fa8158acf29751501a361c85/d/m/dmw41808e_shield_tee.jpg';
//         change_variation_image_url_by_id($variation_id, $new_image_url);
//     }
    
// }



function update_variation_images_on_product_page_load()
{
    global $product;
    $sellableId = $product->sku;
    $productMeta = json_decode(get_post_meta($product->id, 'additional_data')[0], true);
    $ideaId = $productMeta['ideaId'];

    // match these
    $colorVersionIds = $productMeta['appearanceIds'];
    $variations = $product->get_available_variations();

    echo '<pre>';
    echo 'colorVersionIds';
    var_dump($colorVersionIds);
    echo 'variations';
    var_dump($variations);
    echo '</pre>';
    //$count = 0;
    // foreach ($colorVersionIds as $colorId) {
    //     $new_image_url = getVariantImages($sellableId,  $ideaId, $colorId);
    //     change_variation_image_url_by_id($variation_id[$count], $new_image_url);
    //     $count++;
    // }

    // foreach ($variations as $variation) {
    //     $variation_id = $variation['variation_id'];
    //     $new_image_url = 'https://www.urbanrider.co.uk/media/catalog/product/cache/008f5b54fa8158acf29751501a361c85/d/m/dmw41808e_shield_tee.jpg';
    //     change_variation_image_url_by_id($variation_id, $new_image_url);
    // }
}




add_action('woocommerce_before_single_product', 'update_variation_images_on_product_page_load');



