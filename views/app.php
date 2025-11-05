<?php
if (!defined('ABSPATH')) exit;
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
?>
<div class="wrap wpfpp-app">
  <h1 class="wpfpp-title">WP FB Page Poster</h1>
  <h2 class="nav-tab-wrapper wpfpp-tabs">
    <a href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=settings'));?>" class="nav-tab <?php echo $tab==='settings'?'nav-tab-active':'';?>">Settings</a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=autopost'));?>" class="nav-tab <?php echo $tab==='autopost'?'nav-tab-active':'';?>">Autopost</a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=content'));?>" class="nav-tab <?php echo $tab==='content'?'nav-tab-active':'';?>">All Content</a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=compose'));?>" class="nav-tab <?php echo $tab==='compose'?'nav-tab-active':'';?>">Composer</a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wpfpp-app&tab=logs'));?>" class="nav-tab <?php echo $tab==='logs'?'nav-tab-active':'';?>">Logs</a>
  </h2>

  <?php if ($tab==='settings' || $tab==='autopost'): ?>
    <form method="post" action="options.php" class="wpfpp-settings-form card">
      <?php settings_fields('wpfpp'); ?>
      <table class="form-table">
        <?php if ($tab==='settings'): ?>
          <?php do_settings_fields('wpfpp','wpfpp_main'); ?>
        <?php else: ?>
          <tr><th>Autopost options</th><td><?php do_settings_fields('wpfpp','wpfpp_main'); ?></td></tr>
        <?php endif; ?>
      </table>
      <?php submit_button(); ?>
    </form>

    <?php if ($tab==='settings' && !empty($_GET['pick_page'])): ?>
      <div class="card" style="padding:12px; margin-top:12px;">
        <h2>Choose Facebook Page</h2>
        <?php $pages = get_option('wpfpp_oauth_pages_cache', []); ?>
        <?php if (!$pages): ?>
          <div class="notice notice-error"><p>No Pages found (check permissions on Facebook login).</p></div>
        <?php else: ?>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
            <input type="hidden" name="action" value="wpfpp_pick_page">
            <?php wp_nonce_field('wpfpp_pick_page'); ?>
            <select name="page_choice">
              <?php foreach ($pages as $p): ?>
                <option value="<?php echo esc_attr($p['id'].'|'.$p['access_token']); ?>">
                  <?php echo esc_html($p['name'].' (#'.$p['id'].')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php submit_button('Save Page'); ?>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php elseif ($tab==='content'): ?>
    <div class="card" style="padding:12px;"><?php include WPFPP_DIR.'views/partials-list.php'; ?></div>
  <?php elseif ($tab==='compose'): ?>
    <div class="card" style="padding:12px;"><?php include WPFPP_DIR.'views/partials-compose.php'; ?></div>
  <?php else: ?>
    <div class="card" style="padding:12px;"><?php include WPFPP_DIR.'views/partials-logs.php'; ?></div>
  <?php endif; ?>
</div>
