<?php

/**
 * Save product attributes to post metadata when a product is saved.
 *
 * @param int $post_id The post ID.
 * @param post $post The post object.
 * @param bool $update Whether this is an existing post being updated or not.
 *
 * Refrence: https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
 */
function wh_save_product_custom_meta($post_id, $post, $update) {
    $post_type = get_post_type($post_id);
// If this isn't a 'product' post, don't update it.
    if ($post_type != 'product')
        return;

    if (!empty($_POST['attribute_names']) && !empty($_POST['attribute_values'])) {
        $attribute_names = $_POST['attribute_names'];
        $attribute_values = $_POST['attribute_values'];
        foreach ($attribute_names as $key => $attribute_name) {
            switch ($attribute_name) {
//for color (string)
                case 'pa_diastaseis':
//it may have multiple color (eg. black, brown, maroon, white) but we'll take only the first color.
                    if (!empty($attribute_values[$key][0])) {
                        update_post_meta($post_id, 'pa_diastaseis', $attribute_values[$key][0]);
                    }
                    break;
                default:
                    break;
            }
        }
    }
    if(!empty($_POST['tax_input']['pwb-brand'])){
        $brandArray = $_POST['tax_input']['pwb-brand'];
        $brands = get_term($brandArray[1]);
        $brand_name=$brands->name;
        update_post_meta($post_id, 'tax-brand', $brand_name);
    }

    if(!empty($_POST['tax_input']['product_cat'])){
        $catArray = $_POST['tax_input']['product_cat'];
        $cats = get_term($catArray[1]);
        $cat_name=$cats->name;
        update_post_meta($post_id, 'tax-cat', $cat_name);
    }
}

add_action( 'save_post', 'wh_save_product_custom_meta', 10, 3);

/**
 *  Main ordering logic for orderby attribute
 *  Refrence: https://docs.woocommerce.com/document/custom-sorting-options-ascdesc/
 */
add_filter('woocommerce_get_catalog_ordering_args', 'wh_catalog_ordering_args');

function wh_catalog_ordering_args($args) {
    global $wp_query;
    if (isset($_GET['orderby'])) {
        switch ($_GET['orderby']) {

            case 'pa-diastaseis-asc' :
                $args['order'] = 'ASC';
                $args['meta_key'] = 'pa_diastaseis';
                $args['orderby'] = 'meta_value';
                break;
            case 'pa-color-desc' :
                $args['order'] = 'DESC';
                $args['meta_key'] = 'pa_diastaseis';
                $args['orderby'] = 'meta_value';
                break;
            case 'tax-brand-asc' :
                $args['order'] = 'ASC';
                $args['meta_key'] = 'tax-brand';
                $args['orderby'] = 'meta_value';
                break;

            case 'tax-cat-asc' :
                $args['order'] = 'ASC';
                $args['meta_key'] = 'tax-cat';
                $args['orderby'] = 'meta_value';
                break;

        }
    }
    return $args;
}
