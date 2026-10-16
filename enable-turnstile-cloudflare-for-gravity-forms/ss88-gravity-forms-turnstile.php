<?php
/*
Plugin Name: Enable Turnstile (Cloudflare) for Gravity Forms
Plugin URI: https://ss88.us/plugins/cloudflare-turnstile-for-gravity-forms-wordpress-plugin
Description: A lightweight plugin to enable Cloudflare's Turnstile CAPTCHA alternative on your Gravity Forms.
Version: 1.7.1
Author: SS88 LLC
Author URI: https://ss88.us
*/

define('SS88GFFCT_VERSION', '1.7.1');

add_action('gform_loaded', ['init_SS88GFFCT', 'load'], 5);

class init_SS88GFFCT {

    public static function load() {

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), ['init_SS88GFFCT', 'plugin_action_links']);

        if (!method_exists('GFForms', 'include_addon_framework')) return;

        require_once('class.php');

        GFAddOn::register('SS88GFFCT');

    }

    public static function plugin_action_links($actions) {
        $mylinks = [
            '<a href="https://wordpress.org/support/plugin/enable-turnstile-cloudflare-for-gravity-forms/" target="_blank">Need help?</a>',
        ];
        return array_merge( $actions, $mylinks );
    }

}