<?php
$post_id=intval($_GET['post_id']??0); $post=$post_id?get_post($post_id):null;
if(!$post){ echo '<div class="notice notice-info"><p>Διάλεξε περιεχόμενο από <a href="'.esc_url(admin_url('admin.php?page=wpfpp-app&tab=content')).'">All Content</a> για να ανοίξει ο Composer.</p></div>'; return; }
$s=get_option(WPFPP_OPTION,[]); $page_id=$s['page_id']??''; $imgs=\WPFPP\Helpers::img_from_post($post);
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
  <input type="hidden" name="action" value="wpfpp_send" />
  <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id);?>" />
  <?php wp_nonce_field('wpfpp_send'); ?>
  <table class="form-table"><tbody>
    <tr><th>Facebook Page ID</th><td><input type="text" name="page_id" class="regular-text" value="<?php echo esc_attr($page_id);?>"></td></tr>
    <tr><th>Text</th><td>
      <label><input type="radio" name="text_mode" value="title" checked> Use title + link</label><br/>
      <label><input type="radio" name="text_mode" value="custom"> Custom</label><br/>
      <textarea name="custom_text" rows="3" class="large-text" placeholder="<?php echo esc_attr(get_the_title($post).' '.get_permalink($post)); ?>"></textarea>
    </td></tr>
    <tr><th>Images (max 3)</th><td>
      <p><em>Drag & drop για αλλαγή σειράς. Crop για καλύτερη εμφάνιση.</em></p>
      <div id="wpfpp-image-picker" class="wpfpp-sortable" data-max="3">
        <?php foreach ($imgs as $id): ?>
          <div class="wpfpp-img" data-id="<?php echo esc_attr($id);?>">
            <span class="wpfpp-drag">⋮⋮</span>
            <?php echo wp_get_attachment_image($id,'medium'); ?>
            <div class="wpfpp-actions">
              <label>Ratio:
                <select class="wpfpp-ratio" data-id="<?php echo esc_attr($id);?>">
                  <option value="landscape" selected>Landscape 1.91:1</option>
                  <option value="square">Square 1:1</option>
                  <option value="portrait">Portrait 4:5</option>
                  <option value="free">Free</option>
                </select>
              </label>
              <button type="button" class="button wpfpp-crop-btn" data-id="<?php echo esc_attr($id);?>">Crop</button>
              <button type="button" class="button wpfpp-remove" data-id="<?php echo esc_attr($id);?>">&times;</button>
            </div>
            <input type="hidden" name="images[]" value="<?php echo esc_attr($id);?>">
            <input type="hidden" name="crop[<?php echo esc_attr($id);?>]" value="">
            <input type="hidden" name="cropmeta[<?php echo esc_attr($id);?>][target]" class="wpfpp-crop-target" value="1200x628">
          </div>
        <?php endforeach; ?>
      </div>
      <p><button type="button" class="button" id="wpfpp-add-image">Add images</button></p>
      <div id="wpfpp-cropper-modal" style="display:none">
        <div class="wpfpp-cropper-viewport"><img id="wpfpp-cropper-img" src="" /></div>
        <p>
          <label>Aspect ratio:
            <select id="wpfpp-aspect">
              <option value="landscape" selected>Landscape 1.91:1 (1200x628)</option>
              <option value="square">Square 1:1 (1200x1200)</option>
              <option value="portrait">Portrait 4:5 (1080x1350)</option>
              <option value="free">Free</option>
            </select>
          </label>
        </p>
        <p><button type="button" class="button button-primary" id="wpfpp-save-crop">Save crop</button>
        <button type="button" class="button" id="wpfpp-cancel-crop">Cancel</button></p>
      </div>
    </td></tr>
    <tr><th>Preview</th><td><div class="wpfpp-fb-preview"><div class="fb-header">Facebook Page</div><div class="fb-text"></div><div class="fb-images"></div></div></td></tr>
  </tbody></table>
  <?php submit_button('Publish Now'); ?>
</form>
