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
function test()
{
    $response = get_spreadshirt_data('sellables');
    $id = $response->sellables[0]->productTypeId;
    $ptype = get_spreadshirt_data('productTypes/' . $id);
    //$id = $item->productTypeId;
    echo '<pre>';
    //var_dump($ptype->appearances);
    echo '</pre>';
}
// function set_custom_taxonomies()
// {
//     $categories = get_spreadshirt_data('productTypeDepartments')->productTypeDepartments;
//     foreach ($categories as $category) {
//         $name = $category->name;
//         $category_id = $category->id;
//         $public = $category->lifeCycleState = 'ACTIVATED';
//         $cat_exists = term_exists($name, 'product_cat');
//         if ($cat_exists !== 0 && $cat_exists !== null && $public) {
//             // Category already exists, update it
//             $cat_id = $cat_exists['term_id'];
//             wp_update_term(
//                 $cat_id,
//                 'product_cat',
//                 array(
//                     'name' => $name,
//                     'slug' => sanitize_title($name),
//                     'term_id' => (int) $category_id
//                 )
//             );
//         } elseif ($public) {
//             // Category doesn't exist, add it
//             $cid = wp_insert_term(
//                 $name,
//                 'product_cat',
//                 array(
//                     'slug' => sanitize_title($name),
//                     'term_id' => (int) $category_id
//                 )
//             );
//             if (!is_wp_error($cid)) {
//                 $cat_id = isset($cid['term_id']) ? $cid['term_id'] : 0;
//             } else {
//                 echo $cid->get_error_message();
//             }
//         } else {
//             // Category exists but not public, skip it
//             continue;
//         }
//         // Add/update subcategories
//         foreach ($category->categories as $subcategory) {
//             $subname = $subcategory->name;
//             $subid = $subcategory->id;
//             $subcat_exists = term_exists($subname, 'product_cat');
//             $args = array(
//                 'slug' => sanitize_title($subname),
//                 'term_id' => (int) $subid,
//                 'parent' => (int) $cat_id
//             );
//             if ($subcat_exists !== 0 && $subcat_exists !== null) {
//                 // Subcategory exists, update it
//                 $subcat_id = $subcat_exists['term_id'];
//                 wp_update_term($subcat_id, 'product_cat', $args);
//             } else {
//                 // Subcategory doesn't exist, add it
//                 wp_insert_term($subname, 'product_cat', $args);
//             }
//         }
//         // Delete subcategories that no longer exist in $categories
//         $subcategories = get_terms(
//             array(
//                 'taxonomy' => 'product_cat',
//                 'parent' => $cat_id,
//                 'hide_empty' => false
//             )
//         );
//         foreach ($subcategories as $subcategory) {
//             if (!in_array($subcategory->name, array_column($category->categories, 'name'))) {
//                 wp_delete_term($subcategory->term_id, 'product_cat');
//             }
//         }
//     }
// }
// https://stackoverflow.com/questions/63228920/saving-product-attributes-with-woocommerce-methods-not-syncing-to-front-end
// add_action('wp_loaded', 'set_spreadshirt_products');
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

                //Create the attribute object
                $attribute = new WC_Product_Attribute();
                //pa_size tax id
                $attribute->set_id(0); // -> SET to 0
                //pa_size slug
                $attribute->set_name('color'); // -> removed 'pa_' prefix
                //Set terms slugs
                $attribute->set_options($colors);
                $attribute->set_position(0);
                //If enabled
                $attribute->set_visible(1);

                //If we are going to use attribute in order to generate variations
                $attribute->set_variation(1);

                //Save main product to get its id
                $id = createProduct($product_data, $attribute);
                update_post_meta($id, 'image_meta_url', $product_data['image']);
                wp_set_object_terms($id, $productType->categoryName, 'product_cat');
                wp_set_object_terms($id, $product_data['tags'], 'product_tag');

                foreach ($colors as $color) {
                    $variation = new WC_Product_Variation();
                    $variation->set_regular_price($item->price->amount);
                    $variation->set_parent_id($id);

                    $variation->set_attributes(array(
                        'color' => $color // -> removed 'pa_' prefix
                    ));

                    $variation->save();
                }
            }
        } catch (Exception $ex) {
            return;
        }
    }
}
// // https://stackoverflow.com/questions/63228920/saving-product-attributes-with-woocommerce-methods-not-syncing-to-front-end
// // add_action('wp_loaded', 'set_spreadshirt_products');
// function set_spreadshirt_products()
// {
//     $response = get_spreadshirt_data('sellables');
//     $allItems = $response->sellables;
//     foreach ($allItems as $item) {
//         $productType = get_spreadshirt_data('productTypes/' . $item->productTypeId);
//         try {
//             if (strlen($productType->categoryName) > 1) {
//                 // Add global attributes.
//                 $global_attributes = ['Size', 'Colour'];
//                 foreach ($global_attributes as $global_attribute) {
//                     wc_create_attribute([
//                         'name' => $global_attribute,
//                         'type' => 'text',
//                     ]);
//                 }
//                 $colors = getProductColors($item->productTypeId);
//                 // Add product.
//                 $product_attributes = [
//                     'Size' => 'Small',
//                     'Colour' =>  $colors
//                 ];
//                 // Add names, prices, etc.
//                 $attributes = [];
//                 foreach ($product_attributes as $name => $value) {
//                     $attribute = new WC_Product_Attribute();
//                     $attribute->set_name($name);
//                     // $attribute->set_options([$value]); // Pass the value in an array.
//                     if (is_array($value)) {
//                         $attribute->set_options($value); // Pass the value in an array.
//                     } else {
//                         $attribute->set_options([$value]); // Pass the value in an array.
//                     }
//                     // if (in_array($name, $global_attributes)) {
//                     //     // Deal with global attributes.
//                     //     $term_name = "pa_" . sanitize_title($name);
//                     //     if (!$term = get_term_by('name', $value, $term_name)) {
//                     //         if (is_array($value)) {
//                     //             wp_insert_term([$value], $term_name);
//                     //         }else{
//                     //             wp_insert_term([$value], $term_name);
//                     //         }
//                     //         $term = get_term_by('name', $value, $term_name);
//                     //     }
//                     //     // $attribute_object->set_id($taxonomy_id);
//                     //     // Also the value for set_options should be enclosed in an array:
//                     //     // $attribute_object->set_options( [$value] );
//                     //     $attribute->set_id($term->term_taxonomy_id);
//                     //     $attribute->set_name($term_name);
//                     //     //$attribute->set_options([$value]);
//                     //     if (is_array($value)) {
//                     //         $attribute->set_options($value); // Pass the value in an array.
//                     //     } else {
//                     //         $attribute->set_options([$value]); // Pass the value in an array.
//                     //     }
//                     // }
//                     $attributes[] = $attribute;
//                 }
//                 $product_data = array(
//                     'SKU' => $item->sellableId,
//                     'parent' => null,
//                     'name' => $item->name,
//                     'regular_price' => $item->price->amount,
//                     'description' => $item->description,
//                     'short_description' => $item->description,
//                     'tags' => $item->tags,
//                     'image' =>  $item->previewImage->url,
//                     'type' => 'simple',
//                     'status' => 'publish',
//                 );
//                 $product_id = createProduct($product_data, $attributes);
//                 update_post_meta($product_id, 'image_meta_url', $product_data['image']);
//                 wp_set_object_terms($product_id, $productType->categoryName, 'product_cat');
//                 wp_set_object_terms($product_id, $product_data['tags'], 'product_tag');
//             }
//         } catch (Exception $ex) {
//             return;
//         }
//     }
// }
function createProduct($product_data, $attribute)
{
    // create product 
    $product = new WC_Product_Variable;
    $product->set_name($product_data['name']);
    $product->set_sku($product_data['SKU']);
    $product->set_regular_price($product_data['regular_price']);
    $product->set_description($product_data['description']);
    $product->set_attributes(array($attribute));
    $product_id = $product->save();
    return $product_id;
}
function getProductColors($productTypeId)
{
    $ptype = get_spreadshirt_data('productTypes/' . $productTypeId);
    // $names = array();
    $colors = array();
    $variations = $ptype->appearances;
    foreach ($ptype as $item) {
        // if (!in_array($item->name, $names)) {
        //     array_push($names, $item->name);
        // }
        foreach ($variations as $color) {
            if (!in_array($color->name, $colors)) {
                array_push($colors, $color->name);
            }
        }
    }
    return $colors;
}
