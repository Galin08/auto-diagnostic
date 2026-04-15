<?php

/**
 * Генератор на ID
 */
function generate_vehicle_id() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $length = 6;
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $randomString;
}


add_action('acf/save_post', 'my_generate_vehicle_id', 20);

function my_generate_vehicle_id($post_id) {
    if (get_post_type($post_id) != 'vehicle') {
        return;
    }
    if (get_field('vehicle_id', $post_id)) {
        return;
    }
    $new_id = generate_vehicle_id();

    $exists = true;
    while ($exists) {
        $query = new WP_Query(array(
            'post_type' => 'vehicle',
            'meta_query' => array(
                array(
                    'key' => 'vehicle_id',
                    'value' => $new_id,
                    'compare' => '='
                )
            )
        ));
        if ($query->have_posts()) {

            $new_id = generate_vehicle_id();
        } else {
            $exists = false;
        }
        wp_reset_postdata();
    }


    update_field('vehicle_id', $new_id, $post_id);
}
