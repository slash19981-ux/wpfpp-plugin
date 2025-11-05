<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class Helpers {
    public static function can_post_cap(): string {
        $s=get_option(WPFPP_OPTION,[]); $role=$s['allowed_role']??'administrator';
        $map=['administrator'=>'manage_options','editor'=>'edit_others_posts','shop_manager'=>'manage_woocommerce'];
        return $map[$role]??'manage_options';
    }
    public static function utm_url(string $url): string {
        $s=get_option(WPFPP_OPTION,[]);
        if (!empty($s['utm_enable'])) $url=add_query_arg(['utm_source'=>'facebook','utm_medium'=>'social','utm_campaign'=>'wpfpp'],$url);
        if (!empty($s['shortlink_enable'])) $url=Shortlink::make($url);
        return $url;
    }
    public static function is_wc(): bool { return class_exists('WooCommerce'); }
    public static function img_from_post($post): array {
        $ids=[]; $t=get_post_thumbnail_id($post->ID); if($t) $ids[]=$t;
        if ($post->post_type==='product' && self::is_wc()){
            $g=get_post_meta($post->ID,'_product_image_gallery',true);
            if($g){ foreach(array_map('intval', explode(',',$g)) as $gid){ if($gid) $ids[]=$gid; } }
        }
        if (!$ids){ foreach(get_attached_media('image',$post->ID) as $att){ $ids[]=$att->ID; } }
        return array_slice(array_values(array_unique(array_map('intval',$ids))),0,9);
    }
    public static function set_posted($post_id,$fb_post_id){
        update_post_meta($post_id,'_wpfpp_posted', current_time('mysql'));
        update_post_meta($post_id,'_wpfpp_fb_post_id', sanitize_text_field($fb_post_id));
    }
    public static function crop_image($attachment_id,$args,$meta=[]){
        $src=get_attached_file($attachment_id); if(!$src) return $attachment_id;
        $ed=wp_get_image_editor($src); if(is_wp_error($ed)) return $attachment_id;
        $x=intval($args['x']??0); $y=intval($args['y']??0); $w=intval($args['w']??0); $h=intval($args['h']??0);
        if($w>0 && $h>0){ $ed->crop($x,$y,$w,$h); }
        $target=$meta['target']??'auto';
        if ($target==='1200x628') $ed->resize(1200,628,true);
        elseif ($target==='1200x1200') $ed->resize(1200,1200,true);
        elseif ($target==='1080x1350') $ed->resize(1080,1350,true);
        $res=$ed->save(); if(is_wp_error($res)) return $attachment_id;
        $filetype=wp_check_filetype($res['path'],null);
        $att=['guid'=>$res['path'],'post_mime_type'=>$filetype['type'],'post_title'=>sanitize_file_name(basename($res['file'])),'post_status'=>'inherit'];
        $new_id=wp_insert_attachment($att,$res['path']);
        require_once ABSPATH.'wp-admin/includes/image.php';
        $meta2=wp_generate_attachment_metadata($new_id,$res['path']); wp_update_attachment_metadata($new_id,$meta2);
        return $new_id;
    }
    public static function smart_center_crop($attachment_id,$ratio='square'){
        $src=get_attached_file($attachment_id); if(!$src) return $attachment_id;
        $ed=wp_get_image_editor($src); if(is_wp_error($ed)) return $attachment_id;
        $size=$ed->get_size(); $W=$size['width']; $H=$size['height']; if($W<=0||$H<=0) return $attachment_id;
        if($ratio==='landscape'){ $tw=1200;$th=628; } elseif($ratio==='portrait'){ $tw=1080;$th=1350; } else { $tw=1200;$th=1200; }
        $target_ratio=$tw/$th; $src_ratio=$W/$H;
        if ($src_ratio>$target_ratio){ $new_w=intval($H*$target_ratio); $new_h=$H; $x=intval(($W-$new_w)/2); $y=0; }
        else { $new_w=$W; $new_h=intval($W/$target_ratio); $x=0; $y=intval(($H-$new_h)/2); }
        $ed->crop($x,$y,$new_w,$new_h); $ed->resize($tw,$th,true);
        $res=$ed->save(); if(is_wp_error($res)) return $attachment_id;
        $filetype=wp_check_filetype($res['path'],null);
        $att=['guid'=>$res['path'],'post_mime_type'=>$filetype['type'],'post_title'=>sanitize_file_name(basename($res['file'])),'post_status'=>'inherit'];
        $new_id=wp_insert_attachment($att,$res['path']);
        require_once ABSPATH.'wp-admin/includes/image.php';
        $meta2=wp_generate_attachment_metadata($new_id,$res['path']); wp_update_attachment_metadata($new_id,$meta2);
        return $new_id;
    }
    public static function log($m){ if(defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG){ error_log('[WPFPP] '.$m); } }
}
