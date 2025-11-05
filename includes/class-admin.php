<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class Admin {
    public static function init(){
        add_action('admin_menu',[__CLASS__,'menu']);
        add_action('admin_enqueue_scripts',[__CLASS__,'assets']);
        add_action('admin_notices',[__CLASS__,'notices']);
    }
    public static function menu(){
        add_menu_page('FB Page Poster','FB Page Poster', Helpers::can_post_cap(), 'wpfpp-app',[__CLASS__,'render'],'dashicons-share',58);
    }
    public static function assets($hook){
        if (strpos($hook,'wpfpp-app')!==false){
            wp_enqueue_style('wpfpp-admin', WPFPP_URL.'assets/admin.css',[],WPFPP_VERSION);
            wp_enqueue_style('cropper','https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css',[], '1.6.2');
            wp_enqueue_script('cropper','https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js',[], '1.6.2', true);
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('wpfpp-admin', WPFPP_URL.'assets/admin.js',['jquery','jquery-ui-sortable'],WPFPP_VERSION, true);
            wp_enqueue_media();
        }
    }
    public static function notices(){
        if (!current_user_can('manage_options')) return;
        $s=get_option(WPFPP_OPTION,[]);
        if (empty($s['page_id']) || empty($s['page_access_token'])){
            echo '<div class="notice notice-warning"><p><strong>FB Page Poster:</strong> Not connected. Go to <a href="'.esc_url(admin_url('admin.php?page=wpfpp-app&tab=settings')).'">Settings</a> and click "Connect Facebook".</p></div>';
        }
    }
    public static function render(){ include WPFPP_DIR.'views/app.php'; }
}
