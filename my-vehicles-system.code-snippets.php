<?php

/**
 * My Vehicles System
 */

// ➕ WPForms → Добавяне на МПС

add_action('wpforms_process_complete', function($fields) {

    $user_id = get_current_user_id();
    if (!$user_id) return;

    $name  = $fields[1]['value'] ?? '';
    $email = $fields[2]['value'] ?? '';
    $vehicle_id_input = $fields[4]['value'] ?? '';
    $brand = $fields[5]['value'] ?? '';
    $model = $fields[6]['value'] ?? '';

    if (!$vehicle_id_input) return;

    $query = new WP_Query([
        'post_type' => 'vehicle',
        'meta_query' => [
            [
                'key' => 'vehicle_id',
                'value' => $vehicle_id_input,
                'compare' => '='
            ]
        ]
    ]);

    if ($query->have_posts()) {

        $query->the_post();
        $post_id = get_the_ID();

        $data = [
            'user_id' => $user_id,
            'email' => $email,
            'name' => $name,
            'brand' => $brand,
            'model' => $model,
            'id' => $vehicle_id_input
        ];

        add_post_meta($post_id, 'pending_user_' . $user_id, $data);
    }

    wp_reset_postdata();

}, 10, 3);


// 🚗 МОИТЕ МПС 

add_shortcode('my_vehicles', function () {

    if (!is_user_logged_in()) {
        return '<p>Трябва да сте влезли.</p>';
    }

    $user_id = get_current_user_id();

    $query = new WP_Query([
        'post_type' => 'vehicle',
        'meta_query' => [
            [
                'key' => 'assigned_users',
                'value' => $user_id,
'compare' => 'LIKE'

            ]
        ]
    ]);

    ob_start();

    echo "<div class='vehicle-grid'>";

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            echo "<div class='vehicle-card'>";
            echo "<h3>" . get_field('brand') . " " . get_field('model') . "</h3>";
            echo "<p><strong>ID:</strong> " . get_field('vehicle_id') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p class='no-results'>Нямате добавени автомобили.</p>";
    }

    echo "</div>";

    wp_reset_postdata();

    return ob_get_clean();
});


// ✅ ОДОБРЕНИЕ

function approve_user_vehicle($vehicle_id, $user_id) {

    $users = get_field('assigned_users', $vehicle_id) ?: [];

    if (!in_array($user_id, $users)) {
        $users[] = $user_id;
    }

    update_field('assigned_users', $users, $vehicle_id);
    delete_post_meta($vehicle_id, 'pending_user_' . $user_id);
}


// 🛠️ АДМИН ПАНЕЛ 

add_action('admin_menu', function () {
    add_menu_page(
        'Заявки МПС',
        'Заявки МПС',
        'manage_options',
        'vehicle_requests',
        'vehicle_requests_page'
    );
});

function vehicle_requests_page() {

    echo "<h1>🚗 Заявки за МПС</h1>";

    if (isset($_GET['approve'])) {
        approve_user_vehicle(intval($_GET['approve']), intval($_GET['user']));
        echo "<div class='notice-success'>Одобрено!</div>";
    }

    if (isset($_GET['reject'])) {
        delete_post_meta(intval($_GET['reject']), 'pending_user_' . intval($_GET['user']));
        echo "<div class='notice-error'>Отказано!</div>";
    }

    $vehicles = get_posts([
        'post_type' => 'vehicle',
        'posts_per_page' => -1
    ]);

    foreach ($vehicles as $vehicle) {

        $meta = get_post_meta($vehicle->ID);

        foreach ($meta as $key => $value) {

            if (strpos($key, 'pending_user_') === 0) {

                $data = maybe_unserialize($value[0]);

                if (!is_array($data)) {
                    $data = [
                        'user_id' => $data,
                        'email' => get_userdata($data)->user_email,
                        'name' => '',
                        'brand' => '',
                        'model' => '',
                        'id' => get_field('vehicle_id', $vehicle->ID)
                    ];
                }

                echo "<div class='admin-card'>";
                echo "<h3>Заявка</h3>";
                echo "<p><strong>Email:</strong> {$data['email']}</p>";
                echo "<p><strong>Име:</strong> {$data['name']}</p>";
                echo "<p><strong>Марка:</strong> {$data['brand']}</p>";
                echo "<p><strong>Модел:</strong> {$data['model']}</p>";
                echo "<p><strong>ID:</strong> {$data['id']}</p>";

                echo "<div class='admin-buttons'>";
                echo "<a class='btn-approve' href='?page=vehicle_requests&approve={$vehicle->ID}&user={$data['user_id']}'>ОДОБРИ</a>";
                echo "<a class='btn-reject' href='?page=vehicle_requests&reject={$vehicle->ID}&user={$data['user_id']}'>ОТКАЖИ</a>";
                echo "</div>";

                echo "</div>";
            }
        }
    }
}



// 📋 МЕНЮ

add_filter('wp_nav_menu_items', function ($items, $args) {

    // ако не е логнат → добавяме Вход, но НЕ трием менюто
    if (!is_user_logged_in()) {
        $items .= '<li><a href="/login">Вход</a></li>';
        return $items;
    }

    $user_id = get_current_user_id();

    // взимаме всички МПС
    $vehicles = new WP_Query([
        'post_type' => 'vehicle',
        'posts_per_page' => -1
    ]);

    $filtered_vehicles = [];

    if ($vehicles->have_posts()) {
        while ($vehicles->have_posts()) {
            $vehicles->the_post();

            $assigned = get_field('assigned_users');

            if ($assigned && is_array($assigned)) {
                foreach ($assigned as $user) {

                    if (is_object($user) && $user->ID == $user_id) {
                        $filtered_vehicles[] = get_the_ID();
                    }

                    if (is_numeric($user) && $user == $user_id) {
                        $filtered_vehicles[] = get_the_ID();
                    }
                }
            }
        }
    }

    wp_reset_postdata();


    // 📋 ПРОФИЛ DROPDOWN

    $items .= '<li class="menu-item menu-item-has-children">';
    $items .= '<a href="#">Профил</a>';
    $items .= '<ul class="sub-menu">';

    $items .= '<li><a href="/account">Акаунт</a></li>';
    $items .= '<li><a href="/add-vehicle">Добави МПС</a></l




// 🎨 ДИЗАЙН 

add_action('wp_head', function () {
?>
<style>

/* МОИТЕ МПС */
.vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.vehicle-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: 0.3s;
}

.vehicle-card:hover {
    transform: translateY(-5px);
}

/* ADMIN */
.admin-card {
    background: #fff;
    border-left: 5px solid #0057b8;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
}

.admin-buttons {
    margin-top: 10px;
}

.btn-approve {
    background: green;
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    margin-right: 10px;
    border-radius: 5px;
}

.btn-reject {
    background: red;
    color: white;
    padding: 6px 12px;
    text-decoration: none;
    border-radius: 5px;
}

.notice-success {
    background: #e8f5e9;
    padding: 10px;
    margin: 10px 0;
    color: green;
}

.notice-error {
    background: #fdecea;
    padding: 10px;
    margin: 10px 0;
    color: red;
}

</style>
<?php
});
