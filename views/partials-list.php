<?php
$s=sanitize_text_field($_GET['s']??''); $type=sanitize_text_field($_GET['type']??''); $posted=sanitize_text_field($_GET['posted']??'');
?>
<form method="get" class="wpfpp-filters">
  <input type="hidden" name="page" value="wpfpp-app" />
  <input type="hidden" name="tab" value="content" />
  <input type="search" name="s" value="<?php echo esc_attr($s);?>" placeholder="Search..." />
  <select name="type">
    <option value="">All types</option>
    <option value="post" <?php selected($type,'post');?>>Post (Articles)</option>
    <option value="page" <?php selected($type,'page');?>>Page</option>
    <?php if (class_exists('WooCommerce')): ?><option value="product" <?php selected($type,'product');?>>Product</option><?php endif; ?>
  </select>
  <select name="posted">
    <option value="">Any</option><option value="yes" <?php selected($posted,'yes');?>>Posted</option><option value="no" <?php selected($posted,'no');?>>Not posted</option>
  </select>
  <button class="button">Filter</button>
  <?php if (class_exists('WooCommerce')): ?>
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=content&type=post'));?>">Μόνο Άρθρα</a>
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=content&type=product'));?>">Μόνο Προϊόντα</a>
  <?php endif; ?>
</form>
<table class="widefat striped"><thead><tr><th>Title</th><th>Type</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
<?php
$pt=['post','page']; if(class_exists('WooCommerce')) $pt[]='product';
$args=['post_type'=>$pt,'post_status'=>'publish','posts_per_page'=>20,'s'=>$s];
if($type) $args['post_type']=[$type];
if($posted==='yes') $args['meta_query']=[['key'=>'_wpfpp_posted','compare'=>'EXISTS']];
if($posted==='no')  $args['meta_query']=[['key'=>'_wpfpp_posted','compare'=>'NOT EXISTS']];
$q=new WP_Query($args);
if($q->have_posts()): while($q->have_posts()): $q->the_post(); $p=get_post();
  $flag=get_post_meta($p->ID,'_wpfpp_posted',true)?'Posted':'Not posted';
  printf('<tr><td><a href="%s" target="_blank">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td><a class="button button-primary" href="%s">POST TO FACEBOOK</a></td></tr>',
    esc_url(get_permalink($p)), esc_html(get_the_title($p)), esc_html($p->post_type), esc_html(get_the_date('', $p)), esc_html($flag),
    esc_url(admin_url('admin-post.php?action=wpfpp_compose&post_id='.$p->ID)));
endwhile; else: echo '<tr><td colspan="5">No items found.</td></tr>'; endif; wp_reset_postdata();
?>
</tbody></table>
