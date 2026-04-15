<?php

/**
 * Меню с Id-ta
 */
function get_acf_choices($field_name){
    $field = get_field_object($field_name);
    return $field && isset($field['choices']) ? $field['choices'] : [];
}

function normalize_acf_values($field){
    if(!$field) return [];

    return array_map(function($item){
        return is_array($item) ? $item['value'] : $item;
    }, (array)$field);
}

// ===== МЕНЮ =====
add_action('admin_menu', function () {
    add_menu_page(
        'Диагностика',
        'Диагностика',
        'read',
        'vehicle-admin',
        'vehicle_admin_page',
        'dashicons-car',
        26
    );
});

// ===== SAVE =====
add_action('admin_post_save_vehicle', function () {

    if (!is_user_logged_in()) wp_die('Нямате достъп');

    foreach ($_POST['vehicles'] as $post_id => $data) {

        $post_id = intval($post_id);

        foreach ($data as $field => $value) {

            if (in_array($field, ['status_level','error_type','problem_type'])) {
                update_field($field, isset($value) ? array_values((array)$value) : [], $post_id);
            } else {
                update_field($field, sanitize_text_field($value), $post_id);
            }
        }

        // DATE
        if (!empty($data['date'])) {
            update_field('date', date('Ymd', strtotime($data['date'])), $post_id);
        }

        // FILE UPLOAD
        if (!empty($_FILES['vehicles']['name'][$post_id]['file_diagnostic'][0])) {

            require_once(ABSPATH . 'wp-admin/includes/file.php');

            $files = $_FILES['vehicles'];

            $acf_fields = [
                'file_diagnostic',
                'file_diagnostic_copy',
                'file_diagnostic_2',
                'file_diagnostic_3',
                'file_diagnostic_4'
            ];

            for ($i = 0; $i < 5; $i++) {

                if (!empty($files['name'][$post_id]['file_diagnostic'][$i])) {

                    $file_array = [
                        'name' => $files['name'][$post_id]['file_diagnostic'][$i],
                        'type' => $files['type'][$post_id]['file_diagnostic'][$i],
                        'tmp_name' => $files['tmp_name'][$post_id]['file_diagnostic'][$i],
                        'error' => $files['error'][$post_id]['file_diagnostic'][$i],
                        'size' => $files['size'][$post_id]['file_diagnostic'][$i],
                    ];

                    $upload = wp_handle_upload($file_array, ['test_form'=>false]);

                    if (!isset($upload['error'])) {

                        $attach_id = wp_insert_attachment([
                            'post_mime_type'=>$upload['type'],
                            'post_title'=>basename($upload['file']),
                            'post_status'=>'inherit'
                        ], $upload['file'], $post_id);

                        require_once(ABSPATH.'wp-admin/includes/image.php');
                        wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));

                        update_field($acf_fields[$i], $attach_id, $post_id);
                    }
                }
            }
        }
    }
    $redirect = admin_url('admin.php?page=vehicle-admin&saved=1');

    if (!empty($_POST['scroll_position'])) {
        $redirect .= '#pos-' . intval($_POST['scroll_position']);
    }

    wp_redirect($redirect);
    exit;
});

// ===== PAGE =====
function vehicle_admin_page() {
?>

<div class="wrap vehicle-wrap">

<h1>🚗 Диагностика</h1>

<?php if(isset($_GET['saved'])): ?>
<div class="notice notice-success"><p>✅ Запазено успешно</p></div>
<?php endif; ?>

<form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">

<input type="hidden" name="action" value="save_vehicle">
<input type="hidden" name="scroll_position" id="scroll_position">

<button class="save-all">💾 Запази ВСИЧКО</button>

<?php
$q = new WP_Query([
    'post_type'=>'vehicle',
    'posts_per_page'=>-1,
    'orderby'=>'date',
    'order'=>'DESC'
]);

while($q->have_posts()): $q->the_post();
$id = get_the_ID();

$status = normalize_acf_values(get_field('status_level'));
if(empty($status)) $status = ['Няма'];

$error = normalize_acf_values(get_field('error_type'));
$problem = normalize_acf_values(get_field('problem_type'));
?>

<div class="card" id="pos-<?php echo $id; ?>">

<h2><?php the_title(); ?> (ID: <?php echo $id; ?>)</h2>

<div class="grid">

<label>ID
<input name="vehicles[<?php echo $id;?>][vehicle_id]" value="<?php echo esc_attr(get_field('vehicle_id')); ?>">
</label>

<label>Клиент
<input name="vehicles[<?php echo $id;?>][comment_name]" value="<?php echo esc_attr(get_field('comment_name')); ?>">
</label>

<label>Рег. номер
<input name="vehicles[<?php echo $id;?>][reg_nomer]" value="<?php echo esc_attr(get_field('reg_nomer')); ?>">
</label>

<label>Година
<input name="vehicles[<?php echo $id;?>][godina_prevozno_sredstvo]" value="<?php echo esc_attr(get_field('godina_prevozno_sredstvo')); ?>">
</label>

<label>Марка
<input name="vehicles[<?php echo $id;?>][marka_prevozno_sredstvo]" value="<?php echo esc_attr(get_field('marka_prevozno_sredstvo')); ?>">
</label>

<label>Модел
<input name="vehicles[<?php echo $id;?>][model_na_prevozno_sredstvo]" value="<?php echo esc_attr(get_field('model_na_prevozno_sredstvo')); ?>">
</label>

<label>Дата
<input type="date" name="vehicles[<?php echo $id;?>][date]" value="<?php echo get_field('date') ? date('Y-m-d', strtotime(get_field('date'))) : ''; ?>">
</label>

<label>Км
<input name="vehicles[<?php echo $id;?>][kilometers]" value="<?php echo esc_attr(get_field('kilometers')); ?>">
</label>

</div>

<label>Диагноза
<textarea name="vehicles[<?php echo $id;?>][comment]"><?php echo esc_textarea(get_field('comment')); ?></textarea>
</label>

<div class="grid-3">

<label>Статус
<select multiple name="vehicles[<?php echo $id;?>][status_level][]">
<?php foreach(get_acf_choices('status_level') as $value => $label): ?>
<option value="<?php echo $value;?>" <?php echo in_array($value,$status)?'selected':''; ?>>
<?php echo $label;?>
</option>
<?php endforeach;?>
</select>
</label>

<label>Тип грешка
<select multiple name="vehicles[<?php echo $id;?>][error_type][]">
<?php foreach(get_acf_choices('error_type') as $value => $label): ?>
<option value="<?php echo $value;?>" <?php echo in_array($value,$error)?'selected':''; ?>>
<?php echo $label;?>
</option>
<?php endforeach;?>
</select>
</label>

<label>Проблеми
<select multiple name="vehicles[<?php echo $id;?>][problem_type][]">
<?php foreach(get_acf_choices('problem_type') as $value => $label): ?>
<option value="<?php echo $value;?>" <?php echo in_array($value,$problem)?'selected':''; ?>>
<?php echo $label;?>
</option>
<?php endforeach;?>
</select>
</label>

</div>

<!-- FILES -->
<div class="files">

<b>📎 Файлове:</b><br>

<?php
$files = ['file_diagnostic','file_diagnostic_copy','file_diagnostic_2','file_diagnostic_3','file_diagnostic_4'];
foreach($files as $f){
$file = get_field($f);
if($file){
echo '<a href="'.$file['url'].'" target="_blank">📄 Отвори</a> ';
}
}
?>

<br><br>
<input type="file" name="vehicles[<?php echo $id;?>][file_diagnostic][]" multiple>

</div>

<button class="save-one">💾 Запази</button>

</div>

<?php endwhile; wp_reset_postdata(); ?>

</form>
</div>

<script>
document.querySelectorAll('.save-one').forEach(btn => {
    btn.addEventListener('click', function(){
        const card = this.closest('.card');
        if(card){
            document.getElementById('scroll_position').value = card.id.replace('pos-','');
        }
    });
});
</script>

<style>

.vehicle-wrap{
    margin-left:20px;
}

.vehicle-wrap .card{
    width: calc(100% - 20px);
    max-width:none;
    background:#e6f4ea;
    border:2px solid #28a745;
    border-radius:14px;
    padding:25px;
    margin-bottom:25px;
}

.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}

.grid-3{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

label{
    font-weight:bold;
    font-size:16px;
}

input, textarea, select{
    width:100%;
    padding:12px;
    border:2px solid #28a745;
    border-radius:8px;
    font-size:16px;
    margin-top:5px;
}

textarea{
    min-height:120px;
}

select{
    height:140px;
}

.files a{
    background:#fff;
    padding:8px 12px;
    border:1px solid #28a745;
    border-radius:6px;
    margin-right:5px;
}

.save-all{
    background:#0073aa;
    color:#fff;
    padding:12px 25px;
    border:none;
    border-radius:8px;
    font-size:18px;
    margin-bottom:20px;
}

.save-one{
    background:#28a745;
    color:#fff;
    padding:10px 20px;
    border:none;
    border-radius:8px;
    margin-top:15px;
    font-size:16px;
}

</style>

<?php
}
