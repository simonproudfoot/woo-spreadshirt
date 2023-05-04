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
                        // 'image' => $item->previewImage->url,
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
                    wp_set_object_terms($id, array($productType->name, $productType->categoryName), 'product_cat');

                    update_post_meta($id, 'image_meta_url', $product_data['image']);
                    update_post_meta($id, '_knawatfibu_url', $product_data['image']);
                    update_post_meta($id, 'size_ids', $sizes);
                    update_post_meta($id, 'color_ids', $colors);
                    update_post_meta($id, 'additional_data', json_encode($additional_data));

                    $imageId = save_image_to_media_library($item->previewImage->url, $item->sellableId);

                    set_post_thumbnail($id, $imageId);

                    $colorData = [];

                    wp_set_object_terms($id, $product_data['tags'], 'product_tag');
                    foreach ($colors as $color) {
                        $color_variation = new WC_Product_Variation();
                        $color_variation->set_regular_price($item->price->amount);
                        $color_variation->set_parent_id($id);
                        $color_variation->set_attributes(array(
                            'color' => $color['name']
                        ));
                        $varID =  $color_variation->save();

                        $varDetails = array(
                            'variantId' => $varID,
                            'colorName' => $color['name'],
                            'appearanceId' => $color['id']
                        );

                        array_push($colorData, $varDetails);
                    }

                    update_post_meta($id, 'colors', json_encode($colorData));

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


function getVariantImages($sellableId, $ideaId, $appearanceId)
{
    $url = 'sellables/' . $sellableId . '?appearanceId=' . $appearanceId . '&ideaId=' . $ideaId;
    $data = get_spreadshirt_data($url, null, null);
    if (!isset($data->images)) {
        return '';
    }
    $images = $data->images;

    foreach ($images as $image) {
        if ($image->type === "MODEL") {
            return $image->url;
        }
    }
    foreach ($images as $image) {
        if ($image->type === "PRODUCT") {
            return $image->url;
        }
    }
    foreach ($images as $image) {
        if ($image->type === "DESIGN") {
            return $image->url;
        }
    }
}




function save_image_to_media_library($image_url, $fileName)
{
    // Download the image from the URL
    $image_data = get_image_from_api($image_url);

    // Check if the file already exists in the media library
    $attachment_id = attachment_url_to_postid($image_url);
    if ($attachment_id) {
        // If the file already exists, return the existing attachment ID
        return $attachment_id;
    }

    // Include the image.php file for the wp_generate_attachment_metadata() function
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Specify the uploads directory
    $upload_dir = wp_upload_dir();
    $upload_dir['path'] = trailingslashit($upload_dir['basedir']) . 'wooSpreadshirt/';
    $upload_dir['url'] = trailingslashit($upload_dir['baseurl']) . 'wooSpreadshirt/';

    // Create the wooSpreadshirt directory if it doesn't exist
    if (!file_exists($upload_dir['path'])) {
        mkdir($upload_dir['path'], 0755);
    }

    // Get the file extension from the image data
    $file_ext = '';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $image_data);
    finfo_close($finfo);
    $allowed_types = array(
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
    );
    if (isset($allowed_types[$mime_type])) {
        $file_ext = $allowed_types[$mime_type];
    }

    // Create a new file in the WordPress media library with the specified filename
    $file_path = $upload_dir['path'] . $fileName . '.' . $file_ext;
    file_put_contents($file_path, $image_data);

    // Create a new attachment post in the database
    $attachment_data = array(
        'post_title' => sanitize_file_name($fileName),
        'post_type' => 'attachment',
        'post_mime_type' => $mime_type,
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attachment_id = wp_insert_attachment($attachment_data, $file_path);

    // Generate metadata for the attachment post
    $attachment_data['ID'] = $attachment_id;
    $attachment_data['guid'] = $upload_dir['url'] . $fileName . '.' . $file_ext;
    $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attachment_metadata);

    // Return the attachment ID
    return $attachment_id;
}




function change_variation_image_url_by_id($variation_id, $imgUrl) 
{
    // Get the variation object
    $variation_obj = new WC_Product_Variation($variation_id);
    $imgId = save_image_to_media_library($imgUrl, 'test-' . $variation_id . '.jpg');
    // Set the new image ID for the variation
    $variation_obj->set_image_id($imgId);
    // Save the variation
    $variation_obj->save();
}



function update_variation_images_on_product_page_load()
{
    global $product;
    $sellableId = $product->sku;
    $productMeta = json_decode(get_post_meta($product->id, 'additional_data')[0], true);
    $variantData = json_decode(get_post_meta($product->id, 'colors')[0], true);
    $ideaId = $productMeta['ideaId'];
    foreach ($variantData as $variation) {
        $new_image_url = getVariantImages($sellableId, $ideaId, $variation['appearanceId']);
        change_variation_image_url_by_id($variation['variantId'], $new_image_url);
    }
}

add_action('woocommerce_before_single_product', 'update_variation_images_on_product_page_load');
