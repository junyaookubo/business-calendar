<?php
/*
Plugin Name: Business Calendar
Description: 営業日カレンダーを作成するプラグインです。
Version: 1.0
Author: World Utility co.,ltd.
Author URI: https://worldutility.net/
*/

new BusinessCalendar();

class BusinessCalendar{
    public function __construct(){
        register_activation_hook(__FILE__, array(&$this,'create_post_type'));
        add_action('admin_enqueue_scripts',array(&$this,'plugin_enqueue_styles'));
        add_action('wp_enqueue_scripts',array(&$this,'enqueue_styles'));
        add_action('wp_head', array(&$this,'calendar_ajaxUrl'));
        add_action('wp_ajax_ajax_business_calendar', array(&$this,'ajax_business_calendar'));
        add_action('wp_ajax_nopriv_ajax_business_calendar', array(&$this,'ajax_business_calendar'));
        add_action('init', array(&$this,'create_post_type'));
        add_action('add_meta_boxes', array(&$this,'create_custom_fields'));
        add_action('save_post', array(&$this,'save_custom_fields'));
        add_shortcode('business_calendar', array(&$this,'business_calendar'));
    }

    // CSSの読み込み
    function plugin_enqueue_styles(){
        wp_enqueue_style('business-calendar-admin-style',plugin_dir_url(__FILE__).'business-calendar-admin.css');
    }
    function enqueue_styles(){
        wp_enqueue_style('business-calendar-style',plugin_dir_url(__FILE__).'business-calendar.css');
        wp_enqueue_script('business-calendar-script',plugin_dir_url(__FILE__).'business-calendar.js', array(), '1.0', true);
    }

    // ajaxUrlの設定
    function calendar_ajaxUrl() {
        ?>
            <script>
              let calendar_ajaxUrl = '<?php echo esc_html(admin_url( 'admin-ajax.php')); ?>';
            </script>
        <?php
    }

    // カスタム投稿タイプの作成
    function create_post_type() {
        register_post_type(
            'business-calendar',
            array(
                'label' => '営業日カレンダー',
                'public' => true,
                'has_archive' => true,
                'show_in_rest' => true,
                'menu_position' => 20,
                'supports' => array(
                    'title',
                    'revisions',
                ),
            )
        );
    }

    // カスタムフィールドの作成
    function create_custom_fields(){
        add_meta_box(
            'business_calendar_custom_field',
            'カレンダー設定',
            array(&$this,'custom_field_form'),
            'business-calendar',
            'normal',
            'default'
        );
    }

    // カスタムフィールドのUI
    function custom_field_form($post)
    {
        wp_nonce_field('custom_field_save_meta_box_data', 'custom_field_meta_box_nonce');

        // 定休日
        $holiday_week = get_post_meta($post->ID, 'holiday_week', true);
        if($holiday_week != ''){
            $holiday_week = unserialize($holiday_week);
        }

        // 定休日の説明
        $holiday_text = get_post_meta($post->ID, 'holiday_text', true);

        // 選択された定休日
        $date = get_post_meta($post->ID, 'date', true);
        if($date != ''){
            $date = unserialize($date);
            array_multisort( array_map( "strtotime", $date ), SORT_ASC, $date );
            $str_date = array_map(function($day){
                return strtotime($day);
            },$date);
        }

        // 臨時営業日
        $business_day = get_post_meta($post->ID, 'business_day', true);
        if($business_day != ''){
            $business_day = unserialize($business_day);
            array_multisort( array_map( "strtotime", $business_day ), SORT_ASC, $business_day );
            $str_business_day = array_map(function($day){
                return strtotime($day);
            },$business_day);
        }
        ?>
            <table class="table-wrap">
                <tbody>
                    <tr>
                        <th>ショートコード</th>
                        <td><input type="text" style="width:100%;" readonly value='[business_calendar postid="<?php echo $post->ID; ?>" title="<?php echo get_the_title(); ?>"]'/></td>
                    </tr>
                    <tr>
                        <th><label>定休日の説明</label></th>
                        <td><input type="text" name="holiday_text" value="<?php echo $holiday_text; ?>"></td>
                    </tr>
                    <tr>
                        <th><label>定休日</label></th>
                        <td>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="0" id="sun" <?php if($holiday_week != '' && in_array(0,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="sun">日曜日</label>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="1" id="mon" <?php if($holiday_week != '' && in_array(1,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="mon">月曜日</label>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="2" id="tue" <?php if($holiday_week != '' && in_array(2,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="tue">火曜日</label>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="3" id="wed" <?php if($holiday_week != '' && in_array(3,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="wed">水曜日</label>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="4" id="thu" <?php if($holiday_week != '' && in_array(4,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="thu">木曜日</label>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="5" id="fri" <?php if($holiday_week != '' && in_array(5,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="fri">金曜日</label>
                            <input type="checkbox" class="input-check" name="holiday_week[]" value="6" id="sat" <?php if($holiday_week != '' && in_array(6,$holiday_week)){ echo 'checked';} ?>><label class="input-check-label" for="sat">土曜日</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label>臨時営業日</label></th>
                        <td>
                            <textarea name="business_day" placeholder="YYYY-MM-DD (例 2001-01-01)の形式で登録します。複数登録する場合は改行してください。"><?php
                                if($business_day != ''){
                                        foreach($business_day as $day){
                                            echo $day."\n";
                                        }
                                    }
                            ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label>カレンダー</label></th>
                        <td>
                            <p><?php echo date('Y').'年のカレンダー'; ?></p>
                            <div class="calendar-grid">
                                <?php
                                    $month = 1;
                                    $year = date('Y');
                                    while($month <= 12):
                                ?>
                                <table class="calendar-table">
                                    <thead>
                                        <tr>
                                            <th colspan="7"><span><?php echo $month; ?></span>月</th>
                                        </tr>
                                        <tr>
                                            <th class="sun">日</th>
                                            <th>月</th>
                                            <th>火</th>
                                            <th>水</th>
                                            <th>木</th>
                                            <th>金</th>
                                            <th class="sat">土</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php
                                                $day_count = date('t',mktime(0,0,0,$month,1,$year));
                                                $first_day = date('w',mktime(0,0,0,$month,1,$year));
                                                for($i = 0; $i < $first_day; $i++){
                                                    echo '<td></td>';
                                                }
                                                for($day = 1; $day <= $day_count; $day++){
                                                    if($business_day != '' && in_array(mktime(0,0,0,$month,$day,$year),$str_business_day)){
                                                        echo '<td class="business">'.$day.'</td>';
                                                    }elseif($holiday_week != '' && in_array(($day + $first_day - 1) % 7,$holiday_week)){
                                                        echo '<td class="holiday">'.$day.'</td>';
                                                    }elseif($date != '' && in_array(mktime(0,0,0,$month,$day,$year),$str_date)){
                                                        echo '<td><input type="checkbox" name="date[]" id="day'.$month.'-'.$day.'" value="'.$year.'-'.$month.'-'.$day.'" checked/><label for="day'.$month.'-'.$day.'">'.$day.'</label></td>';
                                                    }else{
                                                        echo '<td><input type="checkbox" name="date[]" id="day'.$month.'-'.$day.'" value="'.$year.'-'.$month.'-'.$day.'"/><label for="day'.$month.'-'.$day.'">'.$day.'</label></td>';
                                                    }
                                                    if(($day + $first_day) % 7 == 0 && $day != $day_count){
                                                        echo '</tr><tr>';
                                                    }
                                                }
                                                while(($day + $first_day) % 7 != 1){
                                                    echo '<td></td>';
                                                    $day++;
                                                }
                                            ?>
                                        </tr>
                                    </tbody>
                                </table>
                                <?php $month++; endwhile; ?>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php
    }

    // 投稿が保存された時
    function save_custom_fields($post_id)
    {
        if (!isset($_POST['custom_field_meta_box_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['custom_field_meta_box_nonce'], 'custom_field_save_meta_box_data')) {
            return;
        }
        if (isset($_POST['holiday_week'])) {
            $holiday_week = serialize($_POST['holiday_week']);
            update_post_meta($post_id, 'holiday_week', $holiday_week);
        }else{
            update_post_meta($post_id, 'holiday_week', '');
        }
        if (isset($_POST['holiday_text'])) {
            update_post_meta($post_id, 'holiday_text', $_POST['holiday_text']);
        }else{
            update_post_meta($post_id, 'holiday_text', '');
        }
        if (isset($_POST['business_day'])) {
            $business_day = str_replace(['\r\n', '\r'], "\n", $_POST['business_day']);
            $business_day = explode("\n", $business_day);
            $business_day = serialize($business_day);
            update_post_meta($post_id, 'business_day', $business_day);
        }else{
            update_post_meta($post_id, 'business_day', '');
        }
        if (isset($_POST['date'])) {
            $date = serialize($_POST['date']);
            update_post_meta($post_id, 'date', $date);
        }else{
            update_post_meta($post_id, 'date', '');
        }
    }


    // フロントUI
    function business_calendar($atts){
        ?>
            <div id="business-calendar-postid-<?php echo $atts['postid']; ?>">
                <?php echo self::draw_business_calendar($atts['postid'],date('Y'),date('m')); ?>
            </div>
        <?php
    }

    // 次・前の月のリンクが押された時
    function ajax_business_calendar(){
        echo self::draw_business_calendar($_POST['postid'],date('Y'),$_POST['month']);
        wp_die();
    }

    // フロントのカレンダー描画
    function draw_business_calendar($postid,$year,$month){

        // 定休日
        $holiday_week = get_post_meta($postid, 'holiday_week', true);
        if($holiday_week != ''){
            $holiday_week = unserialize($holiday_week);
        }

        // 定休日の説明
        $holiday_text = get_post_meta($postid, 'holiday_text', true);

        // 選択された定休日
        $date = get_post_meta($postid, 'date', true);
        if($date != ''){
            $date = unserialize($date);
            array_multisort( array_map( "strtotime", $date ), SORT_ASC, $date );
            $str_date = array_map(function($day){
                return strtotime($day);
            },$date);
        }

        // 臨時営業日
        $business_day = get_post_meta($postid, 'business_day', true);
        if($business_day != ''){
            $business_day = unserialize($business_day);
            array_multisort( array_map( "strtotime", $business_day ), SORT_ASC, $business_day );
            $str_business_day = array_map(function($day){
                return strtotime($day);
            },$business_day);
        }
        ob_start();
        ?>
            <table class="calendar-table">
                <thead>
                    <tr class="month">
                        <th><?php if($month != 1): ?><span class="calendar-link js-month" data-postid="<?php echo $postid; ?>" data-month="<?php echo $month - 1; ?>" data-target-calendar="<?php echo '#business-calendar-postid-'.$postid; ?>">&#8810; 前の月</span><?php endif; ?></th>
                        <th colspan="5"><?php echo $year.'<small>年</small>'.$month.'<small>月</small>'; ?></th>
                        <th><?php if($month != 12): ?><span class="calendar-link js-month" data-postid="<?php echo $postid; ?>" data-month="<?php echo $month + 1; ?>" data-target-calendar="<?php echo '#business-calendar-postid-'.$postid; ?>">次の月 &#8811;</span><?php endif; ?></th>
                    </tr>
                    <tr class="dayWeek">
                        <th>日</th>
                        <th>月</th>
                        <th>火</th>
                        <th>水</th>
                        <th>木</th>
                        <th>金</th>
                        <th>土</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                            $day_count = date('t',mktime(0,0,0,$month,1,$year));
                            $first_day = date('w',mktime(0,0,0,$month,1,$year));
                            for($i = 0; $i < $first_day; $i++){
                                echo '<td></td>';
                            }
                            for($day = 1; $day <= $day_count; $day++){
                                if($business_day != '' && in_array(mktime(0,0,0,$month,$day,$year),$str_business_day)){
                                    echo '<td class="business">'.$day.'<small>臨時営業日</small></td>';
                                }elseif($holiday_week != '' && in_array(($day + $first_day - 1) % 7,$holiday_week)){
                                    echo '<td class="holiday">'.$day.'<small>'.$holiday_text.'</small></td>';
                                }elseif($date != '' && in_array(mktime(0,0,0,$month,$day,$year),$str_date)){
                                    echo '<td class="holiday">'.$day.'<small>'.$holiday_text.'</small></td>';
                                }else{
                                    echo '<td>'.$day.'</td>';
                                }
                                if(($day + $first_day) % 7 == 0 && $day != $day_count){
                                    echo '</tr><tr>';
                                }
                            }
                            while(($day + $first_day) % 7 != 1){
                                echo '<td></td>';
                                $day++;
                            }
                        ?>
                    </tr>
                </tbody>
            </table>
        <?php
        $calendar = ob_get_contents();
        ob_end_clean();
        return $calendar;
    }
}