<?php

<?php

function import_all_products(){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => get_theme_mod('erp_url').'/rest/v1/product/list',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'api-auth-SI:'.get_theme_mod('si_auth'),
            'api-auth-SIP:'.get_theme_mod('sip_auth')
        ),
    ));

    $response = curl_exec($curl);
    $response_json = json_decode($response);
    curl_close($curl);



   $productsArray = $response_json->products;
    $valid_types=explode(',',get_theme_mod('valid_ids'));

    foreach ($productsArray as $productsSingle) {
        if ($subgroup_key = array_search('productSubGroup', array_column($productsSingle->categories, 'type'))) { //elegxos an yparxei to productSubGroup sto proion
            if(in_array($productsSingle->categories[$subgroup_key]->id, $valid_types)) { //elegxos an to proion yparxei sta energa productSubGroups
                echo $productsSingle->id.'<br>';
                $the_id=add_new_product($productsSingle);
                update_product_storage($the_id,$productsSingle);

            }//telos elegxou gia to an yparxei sta energa productSubGroups to proin
        }//telos elegxou an yparxei productSubGroup eggrafi sto proion

    }//telos foreach


    }

function add_new_product($productsSingle)
{
    $post = array(
        'post_author' => 1,
        'post_status' => "publish",
        'post_title' => $productsSingle->descriptions[0]->value,
        'post_parent' => '',
        'post_type' => "product",
    );
    //Create post
    $post_id = wp_insert_post($post);

    //add category to product based on type field
    if (null!==$cat_key = array_search('TYPE', array_column($productsSingle->categories, 'type'))) {
        wp_set_object_terms($post_id, $productsSingle->categories[$cat_key]->value, 'product_cat', true);
    }

    //add brand to product based on brand field

    if (null!==$brand_key = array_search('BRAND', array_column($productsSingle->categories, 'type'))) {
              wp_set_object_terms($post_id, $productsSingle->categories[$brand_key]->value, 'pwb-brand', true);
    }

    $i = 0;
    //add attribute for product type
    if (null!==$type_key = array_search('DIMENSION', array_column($productsSingle->categories, 'type'))) {
        $type_value = $productsSingle->categories[$type_key]->value;
        wp_set_object_terms($post_id, $type_value, 'pa_diastaseis', true);
        $thedata[$i] = array(
            'name' => 'pa_diastaseis',
            'value' => $type_value,
            'is_visible' => '1',
            'is_variation' => '0',
            'is_taxonomy' => '1'
        );
        $i++;
        $str_dimension = explode('X', $type_value, 2);
        wp_set_object_terms($post_id, $str_dimension[0], 'pa_flat', true);
        $thedata[$i] = array(
            'name' => 'pa_flat',
            'value' => $str_dimension[0],
            'is_visible' => '1',
            'is_variation' => '0',
            'is_taxonomy' => '1'
        );
        $i++;
        wp_set_object_terms($post_id, $str_dimension[1], 'pa_megethos', true);
        $thedata[$i] = array(
            'name' => 'pa_megethos',
            'value' => $str_dimension[1],
            'is_visible' => '1',
            'is_variation' => '0',
            'is_taxonomy' => '1'
        );
        $i++;
    }

    //add attribute for product subgroup
    if (null!==$subgroup_key = array_search('productSubGroup', array_column($productsSingle->categories, 'type'))) {
        $subgroup_value = $productsSingle->categories[$subgroup_key]->value;
        wp_set_object_terms($post_id, $subgroup_value, 'pa_subgroup', true);
        $thedata[$i] = array(
            'name' => 'pa_subgroup',
            'value' => $subgroup_value,
            'is_visible' => '1',
            'is_variation' => '0',
            'is_taxonomy' => '1'
        );
    }

    update_post_meta($post_id, '_product_attributes', $thedata);
    update_post_meta($post_id, '_regular_price', $productsSingle->prices->initialPrice);
    update_post_meta($post_id, '_sku', $productsSingle->id);
    update_post_meta($post_id, '_price', $productsSingle->prices->initialPrice);
    update_post_meta($post_id, '_weight', $productsSingle->netWeight);
    update_post_meta($post_id, '_manage_stock', 'yes');

    //---------------------------------STORAGE---------------------------------


     update_field('total_availability', intval($productsSingle->totalAvailability), $post_id);
    //reserved
    if ($salesreserved_key = array_search('SALES_RESERVED_QTY', array_column($productsSingle->properties, 'key'))) {
        update_field('reserved_amount', $productsSingle->properties[$salesreserved_key]->value, $post_id);
    }

    if ($expectedqty_key = array_search('FORCE_EXPECTED_QTY', array_column($productsSingle->properties, 'key'))) {
        update_field('expected_amount', $productsSingle->properties[$expectedqty_key]->value, $post_id);
    }

    if ($expecteddate_key = array_search('ARRIVAL_DATE', array_column($productsSingle->properties, 'key'))) {
        update_field('expected_date', $productsSingle->properties[$expecteddate_key]->value, $post_id);
    }

    if (isset($productsSingle->burdenProduct)) {
        update_field('burden', $productsSingle->burdenProduct->value, $post_id);
    }


    //check if there is a photo and insert
    if ($photo_key = array_search('PHOTO_ID', array_column($productsSingle->properties, 'key'))) {
        attach_images_to_product($post_id, $productsSingle->properties[$photo_key]->value);
    }
    return $post_id;
}



function attach_images_to_product($post_id,$image_name){

    include_once( ABSPATH . 'wp-admin/includes/image.php' );
    $folder_url = '';
    $filename = $image_name.'.jpg';
    $imageurl = $folder_url.$filename;


    $uploaddir = wp_upload_dir();
    $uploadfile = $uploaddir['path'] . '/' . $filename;
    $contents= file_get_contents($imageurl);
    $savefile = fopen($uploadfile, 'w');
    fwrite($savefile, $contents);
    fclose($savefile);

    $wp_filetype = wp_check_filetype(basename($filename), null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $filename,
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $uploadfile );
    $imagenew = get_post( $attach_id );
    $fullsizepath = get_attached_file( $imagenew->ID );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    set_post_thumbnail($post_id, $attach_id);

}

function update_product_storage($product_id,$productsSingle){
     $total_qnt = intval($productsSingle->totalAvailability);
    if ($salesreserved_key = array_search('SALES_RESERVED_QTY', array_column($productsSingle->properties, 'key'))) {
        $final_qnt = $total_qnt - intval($productsSingle->properties[$salesreserved_key]->value);
    }else{
        $final_qnt= $total_qnt;
    }
    wc_update_product_stock( $product_id , $final_qnt, 'set');
}
