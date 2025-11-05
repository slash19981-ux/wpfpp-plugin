<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class Logger {
    public static function install(){
        global $wpdb; $table=$wpdb->prefix.'wpfpp_logs'; $charset=$wpdb->get_charset_collate();
        $sql="CREATE TABLE IF NOT EXISTS $table (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, post_id BIGINT NULL, status VARCHAR(20) NOT NULL, fb_id VARCHAR(100) NULL, message TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(post_id), INDEX(status)) $charset;";
        require_once ABSPATH.'wp-admin/includes/upgrade.php'; dbDelta($sql);
    }
    public static function log($post_id,$status,$fb_id='',$msg=''){
        global $wpdb; $wpdb->insert($wpdb->prefix.'wpfpp_logs',['post_id'=>intval($post_id),'status'=>sanitize_text_field($status),'fb_id'=>sanitize_text_field($fb_id),'message'=>sanitize_textarea_field($msg),'created_at'=>current_time('mysql')]);
    }
    public static function get($limit=50){ global $wpdb; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpfpp_logs ORDER BY id DESC LIMIT %d",$limit)); }
}
