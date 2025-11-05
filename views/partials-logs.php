<table class="widefat striped"><thead><tr><th>ID</th><th>Post</th><th>Status</th><th>FB ID</th><th>Message</th><th>Date</th></tr></thead><tbody>
<?php foreach (\WPFPP\Logger::get() as $row): $title=$row->post_id?get_the_title($row->post_id):'-'; $link=$row->post_id?get_permalink($row->post_id):'#'; ?>
  <tr><td><?php echo intval($row->id);?></td><td><?php if($row->post_id):?><a href="<?php echo esc_url($link);?>" target="_blank"><?php echo esc_html($title);?></a><?php else:?>-<?php endif;?></td><td><?php echo esc_html($row->status);?></td><td><?php echo esc_html($row->fb_id);?></td><td><?php echo esc_html($row->message);?></td><td><?php echo esc_html($row->created_at);?></td></tr>
<?php endforeach; ?>
</tbody></table>
