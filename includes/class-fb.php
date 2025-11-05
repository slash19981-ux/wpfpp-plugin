<?php
namespace WPFPP; if (!defined('ABSPATH')) exit;
class FB {
    public static function init(){}
    protected function s(){ return get_option(WPFPP_OPTION,[]); }
    public function post_to_page(string $page_id, string $message, array $att_ids){
        $s=$this->s(); if(!empty($s['force_oauth'])) return ['ok'=>false,'error'=>'Force OAuth is enabled. Please reconnect in Settings.'];
        $token=$s['page_access_token']??''; if(!$token) return ['ok'=>false,'error'=>'Missing Page access token'];
        if(!$page_id) $page_id=$s['page_id']??''; if(!$page_id) return ['ok'=>false,'error'=>'Missing Page ID'];
        $attached=[];
        foreach(array_slice($att_ids,0,3) as $id){
            $url=wp_get_attachment_image_url($id,'full'); if(!$url) continue;
            $u=wp_remote_post("https://graph.facebook.com/v19.0/{$page_id}/photos",[ 'timeout'=>45,'body'=>['published'=>'false','url'=>$url,'access_token'=>$token] ]);
            if(is_wp_error($u)) continue; $b=json_decode(wp_remote_retrieve_body($u),true); if(!empty($b['id'])) $attached[]=['media_fbid'=>$b['id']];
        }
        $body=['message'=>$message,'access_token'=>$token]; foreach($attached as $i=>$m){ $body["attached_media[$i]"]=json_encode($m); }
        $res=wp_remote_post("https://graph.facebook.com/v19.0/{$page_id}/feed",['timeout'=>45,'body'=>$body]);
        if (is_wp_error($res)) return ['ok'=>false,'error'=>$res->get_error_message()];
        $b=json_decode(wp_remote_retrieve_body($res),true);
        return !empty($b['id']) ? ['ok'=>true,'id'=>$b['id']] : ['ok'=>false,'error'=>($b['error']['message']??'Unknown')];
    }
}
