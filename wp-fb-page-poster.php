<?php
/**
 * Plugin Name: WP FB Page Poster
 * Description: Post manually or automatically from WordPress (posts/pages/products) to Facebook Pages. One-page admin with tabs (Settings, Autopost, All Content, Composer, Logs).
 * Version: 1.4.4
 * Author: ChatGPT
 * Text Domain: wp-fb-page-poster
 * Requires PHP: 8.1
 */
if (!defined('ABSPATH')) exit;
define('WPFPP_VERSION','1.4.4');
define('WPFPP_DIR', plugin_dir_path(__FILE__));
define('WPFPP_URL', plugin_dir_url(__FILE__));
define('WPFPP_OPTION','wpfpp_settings');

require_once WPFPP_DIR.'includes/helpers.php';
require_once WPFPP_DIR.'includes/class-settings.php';
require_once WPFPP_DIR.'includes/class-admin.php';
require_once WPFPP_DIR.'includes/class-ui.php';
require_once WPFPP_DIR.'includes/class-compose.php';
require_once WPFPP_DIR.'includes/class-fb.php';
require_once WPFPP_DIR.'includes/class-logger.php';
require_once WPFPP_DIR.'includes/class-shortlink.php';
require_once WPFPP_DIR.'includes/class-oauth.php';
require_once WPFPP_DIR.'includes/class-cron.php';

register_activation_hook(__FILE__, function(){
    WPFPP\Logger::install();
    WPFPP\Shortlink::install();
    WPFPP\Cron::install();
    if (!wp_next_scheduled('wpfpp_refresh_tokens')) wp_schedule_event(time()+3600, 'weekly', 'wpfpp_refresh_tokens');
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){
    WPFPP\Cron::deactivate();
    $ts = wp_next_scheduled('wpfpp_refresh_tokens'); if ($ts) wp_unschedule_event($ts, 'wpfpp_refresh_tokens');
    flush_rewrite_rules();
});
add_action('plugins_loaded', function(){
    WPFPP\Settings::init();
    WPFPP\Admin::init();
    WPFPP\UI::init();
    WPFPP\Compose::init();
    WPFPP\Cron::init();
    WPFPP\Shortlink::init();
    WPFPP\FB::init();
    WPFPP\OAuth::init();
});
