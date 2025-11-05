<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class OAuth {
    public static function init(){
        add_action('admin_post_wpfpp_fb_callback', [__CLASS__,'callback']);
        add_action('admin_post_wpfpp_pick_page', [__CLASS__,'pick_page']);
        add_action('admin_post_wpfpp_disconnect', [__CLASS__,'disconnect']);
    }
    public static function callback(){
        if (!current_user_can('manage_options')) wp_die('No access');
        $code=sanitize_text_field($_GET['code']??''); $state=sanitize_text_field($_GET['state']??'');
        if(!$code || !wp_verify_nonce($state,'wpfpp_fb_state')) wp_die('Invalid state');
        $s=get_option(WPFPP_OPTION,[]); $app_id=$s['app_id']??''; $app_secret=$s['app_secret']??'';
        $redirect=admin_url('admin-post.php?action=wpfpp_fb_callback');
        $r=wp_remote_get('https://graph.facebook.com/v19.0/oauth/access_token?'.http_build_query(['client_id'=>$app_id,'redirect_uri'=>$redirect,'client_secret'=>$app_secret,'code'=>$code]));
        $b=json_decode(wp_remote_retrieve_body($r),true); $user=$b['access_token']??''; if(!$user) wp_die('No user token');
        $r2=wp_remote_get('https://graph.facebook.com/v19.0/oauth/access_token?'.http_build_query(['grant_type'=>'fb_exchange_token','client_id'=>$app_id,'client_secret'=>$app_secret,'fb_exchange_token'=>$user]));
        $b2=json_decode(wp_remote_retrieve_body($r2),true); $user_ll=$b2['access_token']??$user;
        $r3=wp_remote_get('https://graph.facebook.com/v19.0/me/accounts?'.http_build_query(['access_token'=>$user_ll]));
        $b3=json_decode(wp_remote_retrieve_body($r3),true); $pages=$b3['data']??[];
        update_option('wpfpp_oauth_user_token_ll',$user_ll,false);
        update_option('wpfpp_oauth_pages_cache',$pages,false);
        wp_safe_redirect(admin_url('admin.php?page=wpfpp-app&tab=settings&pick_page=1')); exit;
    }
    public static function pick_page(){
        if (!current_user_can('manage_options')) wp_die('No access');
        check_admin_referer('wpfpp_pick_page'); $choice=sanitize_text_field($_POST['page_choice']??'');
        if(!$choice) wp_die('No page'); list($pid,$ptok)=array_pad(explode('|',$choice,2),2,'');
        $s=get_option(WPFPP_OPTION,[]); $s['page_id']=$pid; $s['page_access_token']=$ptok; update_option(WPFPP_OPTION,$s);
        wp_safe_redirect(admin_url('admin.php?page=wpfpp-app&tab=settings&connected=1')); exit;
    }
    public static function disconnect(){
        if (!current_user_can('manage_options')) wp_die('No access');
        check_admin_referer('wpfpp_disconnect');
        $s=get_option(WPFPP_OPTION,[]); $s['page_id']=''; $s['page_access_token']=''; update_option(WPFPP_OPTION,$s);
        delete_option('wpfpp_oauth_user_token_ll'); delete_option('wpfpp_oauth_pages_cache');
        wp_safe_redirect(admin_url('admin.php?page=wpfpp-app&tab=settings&disconnected=1')); exit;
    }
    public static function refresh_page_token(){
        $user_ll=get_option('wpfpp_oauth_user_token_ll',''); $s=get_option(WPFPP_OPTION,[]); if(!$user_ll || empty($s['page_id'])) return;
        $r=wp_remote_get('https://graph.facebook.com/v19.0/me/accounts?'.http_build_query(['access_token'=>$user_ll]));
        $b=json_decode(wp_remote_retrieve_body($r),true); $pages=$b['data']??[];
        foreach($pages as $p){ if($p['id']==$s['page_id'] && !empty($p['access_token'])){ $s['page_access_token']=$p['access_token']; update_option(WPFPP_OPTION,$s); break; } }
    }
}
