=== Enable Turnstile (Cloudflare) for Gravity Forms ===
Contributors: SS88_UK
Donate link: https://paypal.me/SS88/
Tags: cloudflare, turnstile, gravity forms, captcha, spam protection
Requires at least: 4.7
Tested up to: 7.0
Stable tag: 1.8
Requires PHP: 5.6
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight plugin to enable Cloudflare's Turnstile alternative CAPTCHA on your Gravity Forms.

== Description ==

If you are looking for a secure way to protect your Gravity Forms from spam and malicious attacks, then our Cloudflare Turnstile plugin for WordPress may be the perfect solution for you. It’s the latest CAPTCHA advancement since Google reCAPTCHA!

https://youtu.be/gIJsLD98zu0

The Cloudflare Turnstile plugin provides an additional layer of security for your Gravity Forms. Turnstile is a part of the Cloudflare firewall, which means that it uses the same powerful security features that Cloudflare provides to millions of websites around the world. With Turnstile in place, your Gravity Forms are protected against spam and other malicious attacks, which can save you a lot of time and effort in dealing with fraudulent submissions.

* Simple settings page
* Drag-and-drop Turnstile captcha on a per-form basis
* Protection from spam and malicious attacks

**Check out our other plugins:**

* 🦄 [Unicorn Panel](https://unicornpanel.com?ref=WordPress&utm_campaign=WPReadme_GFT) - the new web hosting control panel built for multi-server fleets; FREE!
* 🎉 [Media Library File Size](https://wordpress.org/plugins/media-library-file-size/)
* ✨ [Export Single Post Page](https://wordpress.org/plugins/single-post-page-export/)
* 🔒 [Two Factor (2FA) Authentication via Email](https://wordpress.org/plugins/two-factor-2fa-via-email/)
* 🙍‍♂️ [View User Metadata](https://wordpress.org/plugins/view-user-metadata/)
* ⭐️⭐️⭐️⭐️⭐️ [Gravity Forms to FreeScout](https://ss88.us/plugins/gravity-forms-freescout?utm_campaign=OtherPlugins)

== Installation ==

Use the automatic installer via WordPress or download the plugin and:

1. Upload the plugin files to the `/wp-content/plugins/enable-turnstile-cloudflare-for-gravity-forms` directory.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Navigate to Gravity Forms -> Settings -> Turnstile.

== Screenshots ==

1. Adding the Turnstile element while editing a Gravity Form.
2. The settings page to enter your Cloudflare Turnstile API keys.
3. 'Theme' and 'Size' settings for the Turnstile field.

== Changelog ==

= 1.8 =
* Security: Turnstile can no longer be bypassed by hiding the field with conditional logic, visibility settings or a hidden page
* Security: Escaped and validated all field settings and output
* Fixed a fatal error when Cloudflare could not be reached
* Fixed multi-page forms failing when Turnstile was not on the last page
* Fixed forms asking to solve Turnstile again after another field failed validation
* Fixed the "Auto" theme always showing the light theme
* Fixed timed-out Turnstile challenges not resetting
* Visitors now see a generic error message; admins also see the Cloudflare error, and details are written to the Gravity Forms log
* The Secret Key is now a password field
* Better compatibility with script optimisation plugins
* Removed the conditional logic and visibility settings from the Turnstile field
* Translation ready
* Added Unicorn Panel notice

= 1.7.1 =
* Fixed PHP warnings

= 1.7 =
* Recoded logic of front-end script to remove duplicate Turnstile boxes

= 1.6.1 =
* Removing references to depreciated get_conditional_logic_event()

= 1.6 =
* Turnstile visibility fix in Admin Area for first-time add of the field.

= 1.5 =
* Handle (correctly) multiple forms on the same page.
* Fix: Loading GF via Ajax didn't use 'real' Turnstile

= 1.4 =
* A more lightweight front-end experience
* Better form and conditional logic handling on the front-end
* Auto/Light/Dark option now available
* Size option now available
* Ability to hide the label on the front-end
* Added Gravity Form's description setting
* WordPress 6.2 ready!

= 1.3 =
* Ajax fix on GForm error

= 1.2 =
* Tooltip fix.

= 1.1 =
* Updated to work with WordPress 4.1 and Gravity Forms 2.4+

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.8 =
Security and reliability update. The Turnstile field no longer supports conditional logic or visibility settings, so it always shows on the form.
