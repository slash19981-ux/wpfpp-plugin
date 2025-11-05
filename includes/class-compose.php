<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class Compose {
    public static function init(){ add_action('admin_post_wpfpp_send',[__CLASS__,'send']); }
    public static function send(){
        if (!current_user_can(Helpers::can_post_cap())) wp_die('No access');
        check_admin_referer('wpfpp_send');
        $post_id=intval($_POST['post_id']??0);
        $text_mode=sanitize_text_field($_POST['text_mode']??'title');
        $custom=sanitize_textarea_field($_POST['custom_text']??'');
        $page_id=sanitize_text_field($_POST['page_id']??'');
        $images=array_slice(array_map('intval',(array)($_POST['images']??[])),0,3);
        $crops=(array)($_POST['crop']??[]);
        $cropmeta=(array)($_POST['cropmeta']??[]);
        $post=get_post($post_id); if(!$post) wp_die('Invalid post');
        $title=get_the_title($post); $url=Helpers::utm_url(get_permalink($post));
        $message=($text_mode==='custom' && $custom)? $custom : ($title.' '.$url);
        $final=[]; $anyCrop=false;
        foreach($images as $id){
            $cj=$crops[$id]??''; $meta=$cropmeta[$id]??[];
            if($cj){ $coords=json_decode(stripslashes($cj),true); if(is_array($coords)){ $id=Helpers::crop_image($id,$coords,$meta); $anyCrop=true; } }
            $final[]=$id;
        }
        if ($post->post_type==='product' && !$anyCrop){
            $tmp=[]; foreach($final as $id){ $tmp[] = Helpers::smart_center_crop($id,'square'); } $final=$tmp;
        }
        $client=new FB(); $res=$client->post_to_page($page_id,$message,$final);
        if($res['ok']){ Helpers::set_posted($post_id,$res['id']??''); Logger::log($post_id,'success',$res['id']??'',''); wp_safe_redirect(admin_url('admin.php?page=wpfpp-app&tab=logs&sent=1')); }
        else { Logger::log($post_id,'error','',$res['error']??''); Helpers::log('Send failed: '.($res['error']??'')); wp_safe_redirect(admin_url('admin.php?page=wpfpp-app&tab=compose&post_id='.$post_id.'&error=1')); }
        exit;
    }
}
