<?php
/*
Plugin Name: Enable Turnstile (Cloudflare) for Gravity Forms
Plugin URI: https://ss88.us/plugins/cloudflare-turnstile-for-gravity-forms-wordpress-plugin
Description: A lightweight plugin to enable Cloudflare's Turnstile CAPTCHA alternative on your Gravity Forms.
Version: 1.8
Author: SS88 LLC
Author URI: https://ss88.us
Text Domain: enable-turnstile-cloudflare-for-gravity-forms
*/

defined('ABSPATH') || exit;

define('SS88GFFCT_VERSION', '1.8');
define('SS88GFFCT_FILE', __FILE__);

add_action('gform_loaded', ['init_SS88GFFCT', 'load'], 5);

if(is_admin()) {

    add_action('wp_ajax_SS88GFFCT_DISMISS_NOTICE', ['init_SS88GFFCT', 'ajax_dismiss_notice']);
    add_action('admin_enqueue_scripts', ['init_SS88GFFCT', 'admin_enqueue_scripts_notice']);

}

add_action('admin_notices', ['init_SS88GFFCT', 'show_unicornpanel_notice']);

class init_SS88GFFCT {

    public static function load() {

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), ['init_SS88GFFCT', 'plugin_action_links']);

        if (!method_exists('GFForms', 'include_addon_framework')) return;

        require_once __DIR__ . '/class.php';

        GFAddOn::register('SS88GFFCT');

    }

    public static function plugin_action_links($actions) {
        $mylinks = [
            '<a href="https://wordpress.org/support/plugin/enable-turnstile-cloudflare-for-gravity-forms/" target="_blank" rel="noopener noreferrer">' . esc_html__('Need help?', 'enable-turnstile-cloudflare-for-gravity-forms') . '</a>',
        ];
        return array_merge( $actions, $mylinks );
    }

    public static function ajax_dismiss_notice() {

        check_ajax_referer('ss88gffct_dismiss', 'nonce');

        if(!current_user_can('manage_options')) {
            wp_send_json_error('', 403);
        }

        $Type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';

        $AllowedTypes = ['unicornpanel'];

        if(in_array($Type, $AllowedTypes, true)) {

            // Shared with other SS88 plugins so dismissing in one hides it in all
            update_option('SS88_2FAVE_notice_dismissed_' . $Type, true);

        }

        wp_send_json_success();

    }

    public static function show_unicornpanel_notice() {

        if(!current_user_can('manage_options')) return;

        if(get_option('SS88_2FAVE_notice_dismissed_unicornpanel')) return;

        echo '<div class="notice notice-info is-dismissible SS88GFFCT ss88-unicornpanel-notice" data-type="unicornpanel"><p>';
        echo '<span style="display:flex;gap:15px;align-items:center;">';
        echo '<img src="' . esc_url(plugin_dir_url( __FILE__ ) . 'assets/img/unicornpanel-logo.svg') . '" alt="Unicorn Panel" width="60" height="60" style="display:block;flex:0 0 auto;" />';
        echo '<span>';
        echo '<strong>' . esc_html__('We just released Unicorn Panel, our new hosting control panel built for multi-server fleets', 'enable-turnstile-cloudflare-for-gravity-forms') . '</strong><br>';
        echo esc_html__('Provision, manage, and monitor every website, database, mailbox, and DNS zone across your entire fleet from one place. Free for 2 servers. Reselling included.', 'enable-turnstile-cloudflare-for-gravity-forms');
        echo ' <a href="' . esc_url('https://unicornpanel.com?ref=WordPress&utm_campaign=WPdashboard_GFT') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn more', 'enable-turnstile-cloudflare-for-gravity-forms') . ' &rarr;</a>';
        echo '<br><small style="opacity:0.7;">' . sprintf(
            /* translators: %s: linked plugin name */
            esc_html__('Brought to you by the %s plugin', 'enable-turnstile-cloudflare-for-gravity-forms'),
            '<a href="' . esc_url('https://wordpress.org/plugins/enable-turnstile-cloudflare-for-gravity-forms/') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Enable Turnstile (Cloudflare) for Gravity Forms', 'enable-turnstile-cloudflare-for-gravity-forms') . '</a>'
        ) . '</small>';
        echo '</span></span></p></div>';

    }

    public static function admin_enqueue_scripts_notice() {

        if(!current_user_can('manage_options') || get_option('SS88_2FAVE_notice_dismissed_unicornpanel')) return;

        wp_enqueue_script('SS88GFFCT-notice', plugin_dir_url( __FILE__ ) . 'assets/js/notice.js', false, SS88GFFCT_VERSION, true);
        wp_localize_script('SS88GFFCT-notice', 'SS88GFFCT_NoticeData', array('ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('ss88gffct_dismiss')));

    }

}
