<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class UI {
    public static function init(){
        add_action('admin_post_wpfpp_compose', [__CLASS__,'go_compose']);
        add_action('add_meta_boxes', [__CLASS__,'mb']);
        add_action('save_post', [__CLASS__,'save_mb']);
    }
    public static function go_compose(){
        if (!current_user_can(Helpers::can_post_cap())) wp_die('No access');
        $post_id=intval($_GET['post_id']??0); wp_safe_redirect(admin_url('admin.php?page=wpfpp-app&tab=compose&post_id='.$post_id)); exit;
    }
    public static function mb(){ add_meta_box('wpfpp_mb','FB Page Poster',[__CLASS__,'mb_view'],['post','page','product'],'side','high'); }
    public static function mb_view($post){
        $posted=get_post_meta($post->ID,'_wpfpp_posted',true);
        printf('<p><a class="button button-primary" href="%s">%s</a></p>',
            esc_url(admin_url('admin.php?page=wpfpp-app&tab=compose&post_id='.$post->ID)),
            $posted? 'Post Again':'Post to Facebook');
        if ($posted){ echo '<p><strong>Last posted:</strong> '.esc_html($posted).'</p>'; }

        $auto   = get_post_meta($post->ID,'_wpfpp_auto_on_publish',true);
        $custom = get_post_meta($post->ID,'_wpfpp_auto_custom_text',true);
        echo '<hr/><p><label><input type="checkbox" name="wpfpp_auto_on_publish" value="1" '.checked($auto,'1',false).' /> Post to Facebook on Publish</label></p>';
        echo '<p><textarea name="wpfpp_auto_custom_text" rows="3" style="width:100%;" placeholder="'.esc_attr(get_the_title($post).' '.get_permalink($post)).'">'.esc_textarea($custom).'</textarea><br/><small>(Optional) Custom text for the auto post)</small></p>';
        wp_nonce_field('wpfpp_mb_save','wpfpp_mb_nonce');
    }
    public static function save_mb($post_id){
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['wpfpp_mb_nonce']) || !wp_verify_nonce($_POST['wpfpp_mb_nonce'],'wpfpp_mb_save')) return;
        if (!current_user_can(Helpers::can_post_cap())) return;
        $auto   = !empty($_POST['wpfpp_auto_on_publish']) ? '1' : '';
        $custom = isset($_POST['wpfpp_auto_custom_text']) ? sanitize_textarea_field($_POST['wpfpp_auto_custom_text']) : '';
        update_post_meta($post_id, '_wpfpp_auto_on_publish', $auto);
        if ($custom !== '') update_post_meta($post_id, '_wpfpp_auto_custom_text', $custom);
        else delete_post_meta($post_id, '_wpfpp_auto_custom_text');
    }
}
