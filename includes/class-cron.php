<?php
namespace WPFPP;

if (!defined('ABSPATH')) {
    exit;
}

class Cron {
    const HOOK = 'wpfpp_cron';
    const AS_GROUP = 'wpfpp';

    /**
     * Schedule recurring job using Action Scheduler if available, else WP-Cron.
     */
    public static function install() {
        // Prefer Action Scheduler (AS)
        if (function_exists('as_schedule_recurring_action')) {
            // Check if an action with this hook and args is already scheduled
            $next = function_exists('as_next_scheduled_action')
                ? as_next_scheduled_action(self::HOOK, [], self::AS_GROUP)
                : null;

            if (!$next) {
                // Run immediately and repeat every hour
                as_schedule_recurring_action(time(), HOUR_IN_SECONDS, self::HOOK, [], self::AS_GROUP);
            }
            return;
        }

        // Fallback to WP-Cron
        if (!wp_next_scheduled(self::HOOK)) {
            // Ensure there is at least an 'hourly' schedule (WP default)
            wp_schedule_event(time(), 'hourly', self::HOOK);
        }
    }

    /**
     * Unschedule all jobs for both AS and WP-Cron.
     */
    public static function deactivate() {
        // Cancel AS actions
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(self::HOOK, [], self::AS_GROUP);
        }

        // Cancel WP-Cron events (loop until none)
        $ts = wp_next_scheduled(self::HOOK);
        while ($ts) {
            wp_unschedule_event($ts, self::HOOK);
            $ts = wp_next_scheduled(self::HOOK);
        }
    }

    /**
     * Register workers and ensure scheduling.
     */
    public static function init() {
        // Register worker callback for both AS and WP-Cron (same hook name)
        add_action(self::HOOK, [__CLASS__, 'run']);

        // Hook publish transition for "auto on publish"
        add_action('transition_post_status', [__CLASS__, 'maybe_post_on_publish'], 10, 3);

        // Try to ensure something is scheduled (idempotent)
        self::install();
    }

    /**
     * Worker: pick one eligible post and autopost to the configured page.
     */
    public static function run() {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[WPFPP] Cron run at ' . current_time('mysql'));
        }

        $s = get_option(WPFPP_OPTION, []);
        if (!empty($s['force_oauth'])) return;
        if (empty($s['autopost_enable'])) return;

        // Determine time window from settings
        $since = strtotime('-24 hours');
        $f = isset($s['autopost_frequency']) ? $s['autopost_frequency'] : '24h';
        if ($f === '6h') {
            $since = strtotime('-6 hours');
        } elseif ($f === '12h') {
            $since = strtotime('-12 hours');
        } elseif ($f === '24h') {
            $since = strtotime('-24 hours');
        } elseif ($f === 'days') {
            $days = max(1, intval($s['autopost_days'] ?? 1));
            $since = strtotime('-' . $days . ' days');
        }

        // Allowed post types
        $pt = ['post', 'page'];
        if (Helpers::is_wc()) {
            $pt[] = 'product';
        }

        $q = new \WP_Query([
            'post_type'      => $pt,
            'post_status'    => 'publish',
            'date_query'     => [[
                'after'     => gmdate('Y-m-d H:i:s', $since),
                'inclusive' => true
            ]],
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [[
                'key'     => '_wpfpp_posted',
                'compare' => 'NOT EXISTS'
            ]],
            'no_found_rows'  => true,
        ]);

        if (!$q->have_posts()) return;

        $p = $q->posts[0];

        // Only in-stock products if requested
        if ($p->post_type === 'product' && !empty($s['autopost_only_instock']) && Helpers::is_wc()) {
            if (get_post_meta($p->ID, '_stock_status', true) !== 'instock') return;
        }

        // Compose message
        $title   = get_the_title($p);
        $url     = Helpers::utm_url(get_permalink($p));
        $message = $title . ' ' . $url;

        // Images (up to 3). Smart crop products to square.
        $imgs  = array_slice(Helpers::img_from_post($p), 0, 3);
        $final = [];
        foreach ($imgs as $id) {
            $final[] = ($p->post_type === 'product')
                ? Helpers::smart_center_crop($id, 'square')
                : $id;
        }

        // Post
        if (empty($s['page_id']) || empty($s['page_access_token'])) return;

        $client = new FB();
        $res    = $client->post_to_page($s['page_id'], $message, $final);

        if (!is_array($res)) {
            // Defensive logging if client returned unexpected type
            Helpers::log('Cron client returned non-array response');
            return;
        }

        if (!empty($res['ok'])) {
            $post_id = $res['id'] ?? '';
            Helpers::set_posted($p->ID, $post_id);
            Logger::log($p->ID, 'success', $post_id, 'cron');
            Helpers::log('Cron posted ID ' . $p->ID . ' => ' . $post_id);
        } else {
            $err = $res['error'] ?? '';
            Logger::log($p->ID, 'error', '', 'cron', $err); // unified signature: (post_id, status, ref_id, context, message)
            Helpers::log('Cron failed: ' . $err);
        }
    }

    /**
     * Auto-post when a post is published (if enabled per post).
     */
    public static function maybe_post_on_publish($new_status, $old_status, $post) {
        if ($new_status !== 'publish' || $old_status === 'publish') return;
        if (!in_array($post->post_type, ['post', 'page', 'product'], true)) return;

        $auto = get_post_meta($post->ID, '_wpfpp_auto_on_publish', true);
        if ($auto !== '1') return;

        $s = get_option(WPFPP_OPTION, []);
        if (empty($s['page_id']) || empty($s['page_access_token'])) return;

        $custom  = get_post_meta($post->ID, '_wpfpp_auto_custom_text', true);
        $title   = get_the_title($post);
        $url     = Helpers::utm_url(get_permalink($post));
        $message = $custom ? $custom : ($title . ' ' . $url);

        $imgs  = array_slice(Helpers::img_from_post($post), 0, 3);
        $final = [];
        foreach ($imgs as $id) {
            $final[] = ($post->post_type === 'product')
                ? Helpers::smart_center_crop($id, 'square')
                : $id;
        }

        $client = new FB();
        $res    = $client->post_to_page($s['page_id'], $message, $final);

        if (!empty($res['ok'])) {
            $post_id = $res['id'] ?? '';
            Helpers::set_posted($post->ID, $post_id);
            Logger::log($post->ID, 'success', $post_id, 'auto-on-publish');
        } else {
            $err = $res['error'] ?? '';
            Logger::log($post->ID, 'error', '', 'auto-on-publish', $err);
            Helpers::log('Auto publish failed: ' . $err);
        }
    }
}

