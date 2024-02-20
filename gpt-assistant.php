<?php

/**
 * Plugin Name: AAIMEA Member GPT Integration
 * Plugin URI: https://www.aaimea.org
 * Description: Integrates an AI-driven Chat GPT Widget into the AAIMEA Member Portal, enhancing user engagement with real-time assistance.
 * Version: 2024.02.19
 * Author: Tyler Kessler
 * Author Email: Tyler.Kessler@gmail.com
 * Author Phone: +1 (407) 415-6101
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// WP Plugin Registration
function chat_gpt_register_settings() {

  // API KEY:
  add_option('chat_gpt_api_key', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_api_key');

  // ASSISTANT ID:
  add_option('chat_gpt_assistant_id', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_assistant_id');
}
add_action('admin_init', 'chat_gpt_register_settings');

// WP Admin Options Page Registration
function chat_gpt_register_options_page() {
  add_options_page('Chat GPT Widget', 'Chat GPT Widget', 'manage_options', 'chatgpt', 'chat_gpt_options_page');
}
add_action('admin_menu', 'chat_gpt_register_options_page');

// WP Admin Options Page
function chat_gpt_options_page() {
  ?><div><h2>Chat GPT Assistant Widget Settings</h2>
    <form method="post" action="options.php"><?php settings_fields('chat_gpt_options_group'); ?>
      <table>
        <!-- API KEY: -->
        <tr><th scope="row"><label for="chat_gpt_api_key">API Key: </label></th><td style="width: 90%;"><input style="width: 100%; !important" type="text" id="chat_gpt_api_key" name="chat_gpt_api_key" value="<?php echo get_option('chat_gpt_api_key'); ?>" /></td></tr>
        <!-- ASSISTANT ID: -->
        <tr><th scope="row"><label for="chat_gpt_assistant_id">Assistant ID: </label></th><td><input style="width: 100%; !important" type="text" id="chat_gpt_assistant_id" name="chat_gpt_assistant_id" value="<?php echo get_option('chat_gpt_assistant_id'); ?>" /></td></tr>
      </table>
      <?php submit_button(); ?>
    </form>
  </div><?php
}

// Enqueue HTML/Scripts
function chat_gpt_enqueue_scripts() {

  // [ ] Add Custom URLs to Options Page and Change This

  if (is_front_page() || is_home()) {

    // CSS & JS
    wp_enqueue_style('chat-gpt-widget-css', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('chat-gpt-widget-js', plugin_dir_url(__FILE__) . 'js/widget.js', array('jquery'), null, true);
    
    $nonce = wp_create_nonce('gpt_chat_nonce');
    
    // HTML Path & Localized Variables
    wp_localize_script('chat-gpt-widget-js', 'chatGPT', array(
      'apiKey' => get_option('chat_gpt_api_key'),
      'endpoint' => admin_url('admin-ajax.php'),
      'nonce' => $nonce,
      'widgetHtmlPath' => plugin_dir_url(__FILE__) . 'html/widget.html'
    ));
  }
}
add_action('wp_enqueue_scripts', 'chat_gpt_enqueue_scripts');

// Create New Thread (gpt_create_thread')
function gpt_create_thread() {
  check_ajax_referer('gpt_chat_nonce', '_ajax_nonce');
  $apiKey = get_option('chat_gpt_api_key');
  $args=['method'=>'POST','timeout'=>15,'headers'=>['Content-Type'=>'application/json','Authorization'=>'Bearer '.$apiKey,'OpenAI-Beta'=>'assistants=v1']];
 	$response = wp_remote_post("https://api.openai.com/v1/threads", $args);

  if (is_wp_error($response)) {
    wp_send_json_error(['message' => 'Error creating new thread.']);
  } else {

    // Get Thread ID From Response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (isset($data['id'])) {
      wp_send_json_success(['threadId' => $data['id']]);
      error_log('threadId: ' . $threadId); //browser console info

    } else {
    	error_log('GPT response format unexpected: ' . $body);
      wp_send_json_error(['message' => 'Unexpected response format when creating new thread.']);
    }
  }
}
add_action('wp_ajax_gpt_create_thread', 'gpt_create_thread');
add_action('wp_ajax_nopriv_gpt_create_thread', 'gpt_create_thread');

// New User Chat (gpt_chat)
function handle_chat_interaction() {

  //Get Thread ID
  $threadId = sanitize_text_field($_POST['threadId']);

  // Send Message
  $userMessage = sanitize_text_field($_POST['message']);
  $messageResponse = send_message(get_option('chat_gpt_api_key'), $threadId, $userMessage);
  if (is_wp_error($messageResponse)) {wp_send_json_error(['message' => 'Error sending message.']); return;}
  
  // Run Assistant On Sent Message
  $gptResponse = run_assistant(get_option('chat_gpt_api_key'), $threadId);
  wp_send_json_success(['message' => $gptResponse]);
}
add_action('wp_ajax_gpt_chat', 'handle_chat_interaction');
add_action('wp_ajax_nopriv_gpt_chat', 'handle_chat_interaction');

// Send Message
function send_message($apiKey, $threadId, $userMessage) {
  $data = ['role' => 'user', 'content' => $userMessage ];
  $args=['method'=>'POST','timeout'=>15,'headers'=>['Content-Type'=>'application/json','Authorization'=>'Bearer '.$apiKey,'OpenAI-Beta'=>'assistants=v1'],'body'=>json_encode($data)];
  $response = wp_remote_post("https://api.openai.com/v1/threads/$threadId/messages", $args);

  if (is_wp_error($response)) {
    error_log('Error Sending Message to ' . $threadId . ": " . $userMessage . '". Error: ' . $response->get_error_message());
  } else {
    error_log('[GPT-Widget] User: "' . $userMessage . '"');
  }
  return $response;
}

// Run Assistant
function run_assistant($apiKey, $threadId) {
  $data = ['assistant_id' => get_option('chat_gpt_assistant_id')];
  $apiKey = get_option('chat_gpt_api_key');
  $args=['method'=>'POST','timeout'=>15,'headers'=>['Content-Type'=>'application/json','Authorization'=>'Bearer '.$apiKey,'OpenAI-Beta'=>'assistants=v1'],'body'=>json_encode($data)];
  $response = wp_remote_post("https://api.openai.com/v1/threads/$threadId/runs", $args);

 	if (is_wp_error($response)) { 
    wp_send_json_error(['message' => 'Error running assistant.']);
  } else {

    // Get Run ID
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (isset($data['id'])) {

      // Initialize While Loop
      $runId = $data['id']; // retrieve Run ID
      $status = 'in_progress'; // initializing variable

      // While AI is Processing Reguest...
      while ($status !== 'completed') {

        // Wait
        sleep(0.3);

        // Check Run Status
        $statusResponse = check_run_status($apiKey, $threadId, $runId);

        // Error Reporting
        if (is_wp_error($statusResponse)) {
          wp_send_json_error(['message' => 'Error checking run status.']);
          return false;
        }

        // Update Run Status
        $status = json_decode(wp_remote_retrieve_body($statusResponse), true)['status'] ?? 'unknown';

      } // Do Until Complete

      // Get Latest Message
      $latestMessage = get_latest_message($apiKey, $threadId);
      return $latestMessage;

    } else {

      error_log('GPT Run Assistant response format unexpected: ' . $body);
      wp_send_json_error(['message' => 'Unexpected response format when creating new assistant run.']);
      return false;

    } 

  }
}

// Check Run Status
function check_run_status($apiKey, $threadId, $runId) {
  $args=['method'=>'GET','headers'=>['Authorization'=>'Bearer '.$apiKey,'OpenAI-Beta'=>'assistants=v1']];
  $url = "https://api.openai.com/v1/threads/$threadId/runs/$runId";
  return wp_remote_get($url, $args);
}

// Get Latest Message
function get_latest_message($apiKey, $threadId) {
  $args=['timeout'=>15,'headers'=>['Content-Type'=>'application/json','Authorization'=>'Bearer '.$apiKey,'OpenAI-Beta'=>'assistants=v1']];
  $response = wp_remote_get("https://api.openai.com/v1/threads/$threadId/messages", $args);

  if (is_wp_error($response)) { return 'Error retrieving messages.'; }
  
  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  // Return Latest Message
  if (isset($data['data']) && !empty($data['data'])) {
    $latestMessage = reset($data['data']);
    error_log('[GPT-Widget] GPT: "' . $latestMessage['content'][0]['text']['value'] . '"');
    return $latestMessage['content'][0]['text']['value'] ?? 'No content available';
  }
  return 'No messages found.';
}

?>