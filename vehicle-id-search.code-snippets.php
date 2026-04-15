<?php

/**
 * Vehicle ID Search
 */
function vehicle_search_and_results() {

    ob_start();
?>

<!-- ===== ФОРМА ===== -->
<form action="<?php echo esc_url(get_permalink()); ?>" method="get" style="max-width:600px;margin:auto;">
    <label style="font-size:18px;"><strong>Въведете ID на превозното средство:</strong></label><br>

    <input type="text" 
           name="vehicle_id" 
           value="<?php echo isset($_GET['vehicle_id']) ? esc_attr($_GET['vehicle_id']) : ''; ?>" 
           required 
           style="width:100%; padding:12px; font-size:16px; margin-bottom:10px;">
    
    <div style="text-align:center;">
        <button type="submit" style="padding:14px 35px;background:#0073aa;color:#fff;border:none;border-radius:6px;font-size:17px;cursor:pointer;">
            Търси
        </button>
    </div>
</form>

<hr>

<!-- ===== LIBRARIES ===== -->
<link href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/js/lightbox.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ===== STYLE ===== -->
<style>

.vehicle-card {
    padding:25px;
    border:1px solid #28a745;
    margin:25px auto;
    border-radius:12px;
    background:#e6f4ea;
    max-width:1200px;
}

.vehicle-card li {
    font-size:17px;
    margin-bottom:8px;
}

.section-title {
    font-size:20px;
    font-weight:bold;
    color:#1e7e34;
    margin-top:20px;
    border-bottom:2px solid #28a745;
    padding-bottom:5px;
}

.problem-tag {
    display:inline-block;
    background:#fff;
    border:1px solid #28a745;
    border-radius:6px;
    padding:6px 10px;
    margin:4px;
    font-size:14px;
}

.vehicle-files a {
    display:inline-block;
    margin:6px 10px 6px 0;
    padding:10px 16px;
    background:#fff;
    border:1px solid #28a745;
    border-radius:6px;
    text-decoration:none;
    color:#28a745;
}

.vehicle-files a:hover {
    background:#28a745;
    color:#fff;
}

.vehicle-gallery {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(140px,1fr));
    gap:10px;
    margin-top:10px;
}

.vehicle-gallery img {
    width:100%;
    height:120px;
    object-fit:cover;
    border-radius:8px;
}

.chart-container {
    max-width:1400px;
    margin:40px auto;
    background:#e6f4ea;
    border:1px solid #28a745;
    border-radius:12px;
    padding:20px;
}

.chart-wrapper {
    height:450px;
}

.vehicle-error {
    max-width:600px;
    margin:30px auto;
    padding:20px;
    background:#f8d7da;
    border:1px solid #dc3545;
    color:#721c24;
    border-radius:10px;
    text-align:center;
    font-size:18px;
}

</style>

<?php

if (!empty($_GET['vehicle_id'])) {

    $vehicle_id = sanitize_text_field($_GET['vehicle_id']);

    echo '<h2 style="text-align:center;">Резултати за ID: '.$vehicle_id.'</h2>';

    $query = new WP_Query([
        'post_type' => 'vehicle',
        'posts_per_page' => -1,
        'meta_key' => 'date',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',

        'meta_query' => [
            [
                'key' => 'vehicle_id',
                'value' => $vehicle_id,
                'compare' => '='
            ]
        ]
    ]);

    if ($query->have_posts()) {

        $labels = [];
        $kms = [];
        $ids = [];
        $statuses = [];

        echo '<div class="chart-container">
            <h3 style="text-align:center;">📊 История на обслужване</h3>
            <p style="text-align:center;">Следете развитието на автомобила във времето и поддържайте оптималното му състояние.</p>
            <div style="text-align:center;">👉 Натиснете върху точка</div>
            <div class="chart-wrapper">
                <canvas id="vehicleChart"></canvas>
            </div>
        </div>';

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();

            $date = get_field('date',$id);
            $d = $date ? DateTime::createFromFormat('Ymd',$date) : false;
            $formatted = $d ? $d->format('d.m.Y') : '';

            $km = (int)get_field('kilometers',$id);
            $comment = get_field('comment',$id);

            $status = get_field('status_level', $id);
            $errors = get_field('error_type', $id);
            $problems = get_field('problem_type', $id);

            $status_text = '';
            if($status){
                foreach($status as $s){
                   $status_text .= (is_array($s) ? $s['label'] : $s).' ';

                }
            }

            if($d){
                array_unshift($labels, $formatted);
array_unshift($kms, $km);
array_unshift($ids, $id);
array_unshift($statuses, $status_text);

            }

            echo '<div class="vehicle-card" id="card-'.$id.'">';
            echo '<ul>';

            echo '<li><strong>ID:</strong> '.get_field('vehicle_id',$id).'</li>';
            echo '<li><strong>Дата:</strong> '.$formatted.'</li>';
            echo '<li><strong>Клиент:</strong> '.get_field('comment_name',$id).'</li>';
            echo '<li><strong>Рег. номер:</strong> '.get_field('reg_nomer',$id).'</li>';
            echo '<li><strong>Марка:</strong> '.get_field('marka_prevozno_sredstvo',$id).'</li>';
            echo '<li><strong>Модел:</strong> '.get_field('model_na_prevozno_sredstvo',$id).'</li>';
            echo '<li><strong>Година:</strong> '.get_field('godina_prevozno_sredstvo',$id).'</li>';
            echo '<li><strong>Километри:</strong> '.$km.' км</li>';
            echo '<li><strong>Диагноза:</strong><br>'.nl2br($comment).'</li>';

            if($status){
                echo '<li><strong>🚦 Статус:</strong><br>';
                foreach($status as $s){
    $label = is_array($s) ? $s['label'] : $s;
    echo '<span class="problem-tag">'.$label.'</span>';
}

                echo '</li>';
            }

            if($errors){
                echo '<li><strong>⚠ Тип грешка:</strong><br>';
               foreach($errors as $e){

    $label = is_array($e) ? $e['label'] : $e;

    echo '<span class="problem-tag">'.$label.'</span>';
}

                echo '</li>';
            }

            if($problems){
                echo '<li><strong>🔧 Проблеми:</strong><br>';
                foreach($problems as $p){
    $label = is_array($p) ? $p['label'] : $p;
    echo '<span class="problem-tag">'.$label.'</span>';
}

                echo '</li>';
            }

            echo '</ul>';

            // ФАЙЛОВЕ
            echo '<div class="section-title">📎 Файлове</div>';
            echo '<div class="vehicle-files">';
            $files = ['file_diagnostic','file_diagnostic_copy','file_diagnostic_2','file_diagnostic_3','file_diagnostic_4'];
            foreach($files as $i=>$f){
                $file = get_field($f,$id);
                if($file){
                    echo '<a href="'.$file['url'].'" target="_blank">Файл '.($i+1).'</a>';
                }
            }
            echo '</div>';

            // СНИМКИ
            echo '<div class="section-title">🖼 Снимки</div>';
            echo '<div class="vehicle-gallery">';
            $all_fields = get_fields($id);
            if($all_fields){
                foreach($all_fields as $key=>$value){
                    if(strpos($key,'image_')===0 && $value){
                        echo '<a href="'.$value['url'].'" data-lightbox="v-'.$id.'">
                                <img src="'.$value['sizes']['medium'].'">
                              </a>';
                    }
                }
            }
            echo '</div>';

            echo '</div>';
        }

        wp_reset_postdata();

        echo '<script>
        const ids = '.json_encode($ids).';
        const statuses = '.json_encode($statuses).';

        new Chart(document.getElementById("vehicleChart"), {
            type:"line",
            data:{
                labels:'.json_encode($labels).',
                datasets:[{
                    data:'.json_encode($kms).',
                    borderColor:"#28a745",
                    backgroundColor:"rgba(40,167,69,0.15)",
                    fill:true,
                    tension:0.3,

                    pointRadius:10,
                    pointHoverRadius:18,
                    pointHitRadius:40,
                    pointBorderColor:"#ffffff",
                    pointBorderWidth:2,

pointBackgroundColor:(ctx)=>{
    let s = statuses[ctx.dataIndex] || "";

    // 🔴 ЧЕРВЕНО (опасни)
    if(s.includes("Критично")) return "#dc3545";
    if(s.includes("Сериозен проблем")) return "#dc3545";
    if(s.includes("НЕ карай автомобила")) return "#dc3545";
    if(s.includes("Спешно")) return "#dc3545";

    // 🟠 ОРАНЖЕВО
    if(s.includes("Нужда от внимание")) return "#fd7e14";

    // 🟡 ЖЪЛТО
    if(s.includes("Средно")) return "#ffc107";

    // 🟢 ЗЕЛЕНО
    if(s.includes("Добро")) return "#28a745";

    return "#28a745";
}


                }]
            },
            options:{
                responsive:true,
                maintainAspectRatio:false,
                plugins:{
                    legend:{display:false},
                    tooltip:{
                        backgroundColor:"#1e1e1e",
                        titleColor:"#fff",
                        bodyColor:"#fff",
                        borderColor:"#28a745",
                        borderWidth:2,
                        padding:16,
                        titleFont:{size:18,weight:"bold"},
                        bodyFont:{size:16},
                        displayColors:false,
                        callbacks:{
                            title:(c)=>c[0].label,
                            label:(c)=>{
                                let i=c.dataIndex;
                                return [
                                    "🚦 "+(statuses[i]||"Няма статус"),
                                    "Км: "+c.raw+" км"
                                ];
                            }
                        }
                    }
                },
                onClick:(e)=>{
                    const p=e.chart.getElementsAtEventForMode(e,"nearest",{intersect:true},true);
                    if(p.length){
                        document.getElementById("card-"+ids[p[0].index]).scrollIntoView({behavior:"smooth"});
                    }
                }
            }
        });
        </script>';

    } else {
        echo '<div class="vehicle-error">❌ Няма намерено превозно средство с ID: '.$vehicle_id.'</div>';
    }
}

return ob_get_clean();
}

add_shortcode('vehicle_search_form', 'vehicle_search_and_results');
