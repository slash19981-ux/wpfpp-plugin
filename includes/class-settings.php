<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class Settings {
    public static function init(){
        add_action('admin_init',[__CLASS__,'register']);
        add_action('wpfpp_refresh_tokens',[__CLASS__,'refresh_event']);
    }
    public static function register(){
        register_setting('wpfpp', WPFPP_OPTION, ['type'=>'array','sanitize_callback'=>[__CLASS__,'sanitize'],'default'=>[]]);
        add_settings_section('wpfpp_main','',function(){},'wpfpp');
        $fields=[
            'app_id'=>'Facebook App ID','app_secret'=>'Facebook App Secret','page_id'=>'Default Page ID','page_access_token'=>'Page Access Token',
            'force_oauth'=>'Force OAuth (ignore existing tokens)',
            'allowed_role'=>'Who can post','utm_enable'=>'Enable UTM','shortlink_enable'=>'Enable short links',
            'autopost_enable'=>'Enable autopost','autopost_frequency'=>'Frequency','autopost_days'=>'Every N days','autopost_instock'=>'Only in-stock (products)','recycle_days'=>'Recycle after X days',
        ];
        foreach($fields as $k=>$label){ add_settings_field($k,$label,[__CLASS__,'field'],'wpfpp','wpfpp_main',['key'=>$k]); }
    }
    public static function sanitize($in){
        $old=get_option(WPFPP_OPTION,[]); $t=['app_id','app_secret','page_id','page_access_token','allowed_role','autopost_frequency'];
        $o=[]; foreach($t as $k){ if(isset($in[$k])) $o[$k]=sanitize_text_field($in[$k]); }
        $o['utm_enable']=!empty($in['utm_enable'])?1:0; $o['shortlink_enable']=!empty($in['shortlink_enable'])?1:0;
        $o['autopost_enable']=!empty($in['autopost_enable'])?1:0; $o['autopost_instock']=!empty($in['autopost_instock'])?1:0;
        $o['force_oauth']=!empty($in['force_oauth'])?1:0; $o['autopost_days']=isset($in['autopost_days'])?intval($in['autopost_days']):0; $o['recycle_days']=isset($in['recycle_days'])?intval($in['recycle_days']):0;
        if(!empty($old['app_id']) && isset($o['app_id']) && $old['app_id']!==$o['app_id']){ $o['page_id']=''; $o['page_access_token']=''; delete_option('wpfpp_oauth_user_token_ll'); delete_option('wpfpp_oauth_pages_cache'); }
        return $o;
    }
    public static function field($args){
        $k=$args['key']; $s=get_option(WPFPP_OPTION,[]); $v=$s[$k]??'';
        switch($k){
            case 'allowed_role':
                $opts=['administrator'=>'Administrator','editor'=>'Editor','shop_manager'=>'Shop Manager'];
                echo '<select name="'.WPFPP_OPTION.'[allowed_role]">'; foreach($opts as $kk=>$lbl){ printf('<option value="%s"%s>%s</option>',$kk,selected($v,$kk,false),$lbl);} echo '</select>'; break;
            case 'utm_enable': case 'shortlink_enable': case 'autopost_enable': case 'autopost_instock': case 'force_oauth':
                printf('<label><input type="checkbox" name="%s[%s]" value="1"%s> Enable</label>', WPFPP_OPTION,$k,checked($v,1,false)); break;
            case 'autopost_frequency':
                $opts=['6h'=>'Every 6 hours','12h'=>'Every 12 hours','24h'=>'Every 24 hours','days'=>'Every N days'];
                echo '<select name="'.WPFPP_OPTION.'[autopost_frequency]">'; foreach($opts as $kk=>$lbl){ printf('<option value="%s"%s>%s</option>',$kk,selected($v,$kk,false),$lbl);} echo '</select>'; break;
            case 'autopost_days': case 'recycle_days':
                printf('<input type="number" name="%s[%s]" value="%s" min="0" />', WPFPP_OPTION,$k,esc_attr($v)); break;
            default:
                printf('<input type="text" class="regular-text" name="%s[%s]" value="%s" />', WPFPP_OPTION,$k,esc_attr($v));
        }
        if($k==='app_id'){
            $appid=$s['app_id']??''; if($appid){
                $state=wp_create_nonce('wpfpp_fb_state'); $redirect=admin_url('admin-post.php?action=wpfpp_fb_callback');
                $login='https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query(['client_id'=>$appid,'redirect_uri'=>$redirect,'state'=>$state,'scope'=>'pages_manage_posts,pages_read_engagement,pages_show_list']);
                echo '<p><code>'.esc_html($redirect).'</code></p><p><a class="button button-primary" href="'.esc_url($login).'">Connect Facebook</a> <a class="button" href="'.esc_url(admin_url('admin-post.php?action=wpfpp_disconnect&_wpnonce='.wp_create_nonce('wpfpp_disconnect'))).'">Disconnect</a></p>';
            }
        }
    }
    public static function refresh_event(){ OAuth::refresh_page_token(); }
}
