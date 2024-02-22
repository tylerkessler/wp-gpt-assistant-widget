<?php

/**
 * Plugin Name: AAIMEA Member GPT Integration
 * Plugin URI: https://github.com/tylerkessler/wp-gpt-assistant-widget
 * Description: IWP GPT Assistant Widget: A WordPress plugin for embedding OpenAI's GPT chat assistant.
 * Version: 2024.02.22
 * Author: Tyler Kessler
 * Author Email: Tyler.Kessler@gmail.com
 * Author Phone: +1 (407) 415-6101
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Set Variables
set_time_limit(60);

// Include Admin
require_once plugin_dir_path(__FILE__) . 'gpt-admin.php';

// Enqueue Scripts
function enqueue_scripts() {

  // Get Current URL and RegEx
  $url_regex = get_option('url_regular_expression', '.*'); 
  $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

  // Enqueue When URL Matches RegEx
  if (preg_match("#$url_regex#", $current_url)) {
    wp_enqueue_style('chat-gpt-widget-css', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('chat-gpt-widget-js', plugin_dir_url(__FILE__) . 'js/widget.js', array('jquery'), null, true);
    $nonce = wp_create_nonce('gpt_chat_nonce');

    // Localize JS Variables
    wp_localize_script('chat-gpt-widget-js', 'chatGPT', array(
      'apiKey' => get_option('chat_gpt_api_key'),
      'endpoint' => admin_url('admin-ajax.php'),
      'nonce' => $nonce,
      'widgetHtmlPath' => plugin_dir_url(__FILE__) . 'html/widget.html'
    ));
  }
}
add_action('wp_enqueue_scripts', 'enqueue_scripts');

// [ ] Commented Out Till Test in Dev/Test Environments
// Error Handler
// function custom_error_handler($severity, $message, $file, $line) {
//   $errorMessage = "An error occurred: [Severity: $severity] $message in $file on line $line";
//   $to = 'tyler.kessler+wp-gpt-assistant-widget@gmail.com';
//   $subject = '[wp-gpt-assistant] Plugin Error Notification';
//   $body = $errorMessage;
//   $headers = array('Content-Type: text/html; charset=UTF-8');
//   wp_mail($to, $subject, $body, $headers);
//   error_log($errorMessage);
// }
// add_action('plugins_loaded', function() {
//   set_error_handler('custom_error_handler');
// });

// // Error Handler: Ensure that wp_mail() is available
// if (function_exists('wp_mail')) {
//     set_error_handler('custom_error_handler');
// }

// add_action('plugins_loaded', function() {
//     set_error_handler('custom_error_handler');
// });

// Include Messages
require_once plugin_dir_path(__FILE__) . 'gpt-messages.php';

?>