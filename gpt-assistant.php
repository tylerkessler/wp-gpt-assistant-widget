<?php

/**
 * Plugin Name: AAIM Member GPT Integration
 * Plugin URI: https://github.com/tylerkessler/wp-gpt-assistant-widget
 * Description: Member Analayst GPT Widget
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
// Call Random Prompt
        

  if (is_singular()) { // Checks if a single post or page is being displayed
  global $post;
      
    // Check if the 'show_ai_chat_widget' ACF field is true for the current post or page
    if (get_field('show_ai_chat_widget', $post->ID) == true) {

        

        // Enqueue your CSS and JS files
        get_random_gpt_prompt();
        wp_enqueue_style('chat-gpt-widget-css', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('chat-gpt-widget-js', plugin_dir_url(__FILE__) . 'js/widget.js', array('jquery'), null, true);
        

        // Create a nonce for security
        $nonce = wp_create_nonce('gpt_chat_nonce');
        
        // Localize the script to pass PHP values to your JS
        wp_localize_script('chat-gpt-widget-js', 'chatGPT', array(
          'apiKey' => get_option('chat_gpt_api_key'),
          'endpoint' => admin_url('admin-ajax.php'),
          'nonce' => $nonce,
          'widgetHtmlPath' => plugin_dir_url(__FILE__) . 'html/widget.html'
        ));
    }
  }
}
add_action('wp_enqueue_scripts', 'enqueue_scripts');

function get_random_gpt_prompt() {
  $suggested_prompts = get_option('suggested_prompts');
  if (!empty($suggested_prompts)) {
    $prompts_array = explode("\n", $suggested_prompts);
    $prompts_array = array_filter($prompts_array, 'trim'); // Remove any empty values
    // error_log(print_r($prompts_array, true));
    // Localize the prompts array to the script
    wp_localize_script('chat-gpt-widget-js', 'gptPrompts', $prompts_array);
  }

}

// Include Messages
require_once plugin_dir_path(__FILE__) . 'gpt-messages.php';

?>