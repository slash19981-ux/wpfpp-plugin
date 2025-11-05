<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class Shortlink {
    public static function install(){ add_option('wpfpp_shortlinks',[]); }
    public static function init(){ add_action('init',[__CLASS__,'rw']); add_action('template_redirect',[__CLASS__,'go']); }
    public static function rw(){ add_rewrite_rule('^go/([A-Za-z0-9_-]+)/?','index.php?wpfpp_go=$matches[1]','top'); add_rewrite_tag('%wpfpp_go%','([^&]+)'); }
    public static function go(){ $t=get_query_var('wpfpp_go'); if(!$t) return; $m=get_option('wpfpp_shortlinks',[]); if(!empty($m[$t])){ wp_redirect($m[$t],301); exit; } }
    public static function make($url){ $m=get_option('wpfpp_shortlinks',[]); $token=substr(md5($url),0,8); $m[$token]=$url; update_option('wpfpp_shortlinks',$m); return home_url('/go/'.$token); }
}
