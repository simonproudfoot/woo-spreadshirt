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

        $product_data = array(
            'SKU' => $item->sellableId,
            'parent' => null,
            'name' => $item->name,
            'regular_price' => $item->price->amount,
            'description' => $item->description,
            'short_description' => $item->description,
            'tags' => $item->tags,
            'image' =>  $item->previewImage->url,
            'type' => 'simple',
            'status' => 'publish',
        );


        try {
            $productType = get_spreadshirt_data('productTypes/' . $item->productTypeId);
            if (strlen($productType->categoryName) > 1) {
                $new_simple_product = new WC_Product_Simple();
                $new_simple_product->set_name($product_data['name'] . ' - ' . $productType->categoryName);
                $new_simple_product->set_sku($product_data['SKU']);
                $new_simple_product->set_regular_price($product_data['regular_price']);
                $new_simple_product->set_description($product_data['description']);
                $new_product_id = $new_simple_product->save();

                update_post_meta($new_product_id, 'image_meta_url', $product_data['image']);
                update_post_meta($new_product_id, '_knawatfibu_url', $product_data['image']);
                wp_set_object_terms($new_product_id, $productType->categoryName, 'product_cat');
                wp_set_object_terms($new_product_id, $product_data['tags'], 'product_tag');
            }
        } catch (Exception $ex) {
            return;
        }
    }
}


// function test()
// {
//     $response = get_spreadshirt_data('sellables');
//     $id = $response->sellables[0]->productTypeId;
//     $ptype = get_spreadshirt_data('productTypes/'.$id);
    
//     echo '<pre>';
//     var_dump($ptype);
//     echo '</pre>';
// }


// add_action('init', 'test');


function set_custom_taxonomies()
{
    $categories = get_spreadshirt_data('productTypeDepartments')->productTypeDepartments;

    foreach ($categories as $category) {
        $name = $category->name;
        $category_id = $category->id;
        $public = $category->lifeCycleState = 'ACTIVATED';
        $cat_exists = term_exists($name, 'product_cat');

        if ($cat_exists !== 0 && $cat_exists !== null && $public) {
            // Category already exists, update it
            $cat_id = $cat_exists['term_id'];
            wp_update_term(
                $cat_id,
                'product_cat',
                array(
                    'name' => $name,
                    'slug' => sanitize_title($name),
                    'term_id' => (int) $category_id
                )
            );
        } elseif ($public) {
            // Category doesn't exist, add it
            $cid = wp_insert_term(
                $name,
                'product_cat',
                array(
                    'slug' => sanitize_title($name),
                    'term_id' => (int) $category_id
                )
            );
            if (!is_wp_error($cid)) {
                $cat_id = isset($cid['term_id']) ? $cid['term_id'] : 0;
            } else {
                echo $cid->get_error_message();
            }
        } else {
            // Category exists but not public, skip it
            continue;
        }

        // Add/update subcategories
        foreach ($category->categories as $subcategory) {
            $subname = $subcategory->name;
            $subid = $subcategory->id;
            $subcat_exists = term_exists($subname, 'product_cat');
            $args = array(
                'slug' => sanitize_title($subname),
                'term_id' => (int) $subid,
                'parent' => (int) $cat_id
            );
            if ($subcat_exists !== 0 && $subcat_exists !== null) {
                // Subcategory exists, update it
                $subcat_id = $subcat_exists['term_id'];
                wp_update_term($subcat_id, 'product_cat', $args);
            } else {
                // Subcategory doesn't exist, add it
                wp_insert_term($subname, 'product_cat', $args);
            }
        }

        // Delete subcategories that no longer exist in $categories
        $subcategories = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'parent' => $cat_id,
                'hide_empty' => false
            )
        );
        foreach ($subcategories as $subcategory) {
            if (!in_array($subcategory->name, array_column($category->categories, 'name'))) {
                wp_delete_term($subcategory->term_id, 'product_cat');
            }
        }
    }
}

//add_action('wp_loaded', 'add_custom_taxonomies');
