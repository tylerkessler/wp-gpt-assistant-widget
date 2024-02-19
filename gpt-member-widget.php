<?php

/**
 * Plugin Name: AAIMEA Member GPT Integration
 * Plugin URI: https://www.aaimea.org
 * Description: Integrates an AI-driven Chat GPT Widget into the AAIMEA Member Portal, enhancing user engagement with real-time assistance.
 * Version: 1.0.0
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
        <!-- API KEY -->
        <tr><th scope="row"><label for="chat_gpt_api_key">API Key: </label></th><td style="width: 90%;"><input style="width: 100%; !important" type="text" id="chat_gpt_api_key" name="chat_gpt_api_key" value="<?php echo get_option('chat_gpt_api_key'); ?>" /></td></tr>
        <!-- ASSISTANT ID -->
        <tr><th scope="row"><label for="chat_gpt_assistant_id">Assistant ID: </label></th><td><input style="width: 100%; !important" type="text" id="chat_gpt_assistant_id" name="chat_gpt_assistant_id" value="<?php echo get_option('chat_gpt_assistant_id'); ?>" /></td></tr>
      </table>
      <?php submit_button(); ?>
    </form>
  </div><?php
}


// HTML Widget Enqueue on Page
function chat_gpt_enqueue_scripts() {
  if (is_front_page() || is_home()) {
      wp_enqueue_style('chat-gpt-widget-css', plugin_dir_url(__FILE__) . 'css/style.css');
      wp_enqueue_script('chat-gpt-widget-js', plugin_dir_url(__FILE__) . 'js/chat-widget.js', array('jquery'), null, true);
      
      $nonce = wp_create_nonce('gpt_chat_nonce');
      
      // Pass API key, endpoint, nonce, and the path to the widget HTML to JavaScript
      wp_localize_script('chat-gpt-widget-js', 'chatGPT', array(
          'apiKey' => get_option('chat_gpt_api_key'),
          'endpoint' => admin_url('admin-ajax.php'),
          'nonce' => $nonce,
          'widgetHtmlPath' => plugin_dir_url(__FILE__) . 'html/widget.html'
      ));
  }
}
add_action('wp_enqueue_scripts', 'chat_gpt_enqueue_scripts');



// Create New GPT Thread on Page Load
function gpt_create_thread() {
    check_ajax_referer('gpt_chat_nonce', '_ajax_nonce');
    $apiKey = get_option('chat_gpt_api_key');
    $args = [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v1'
        ],
        'method'  => 'POST',
        'timeout' => 15
    ];
 	$response = wp_remote_post("https://api.openai.com/v1/threads", $args);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error creating new thread.']);
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['id'])) { // OpenAI typically returns the thread ID directly in the 'id' field
            wp_send_json_success(['threadId' => $data['id']]);
            error_log('threadId: ' . $threadId);
        } else {
        	error_log('GPT response format unexpected: ' . $body);
            wp_send_json_error(['message' => 'Unexpected response format when creating new thread.']);
        }
    }
}
add_action('wp_ajax_gpt_create_thread', 'gpt_create_thread');
add_action('wp_ajax_nopriv_gpt_create_thread', 'gpt_create_thread');


// Create New Message on Thread
function handle_chat_interaction() {
    // Initial setup and nonce check...
    
    $threadId = sanitize_text_field($_POST['threadId']);
    $userMessage = sanitize_text_field($_POST['message']);
    
    // Send the initial message to the thread
    $messageResponse = send_message_to_thread(get_option('chat_gpt_api_key'), $threadId, $userMessage);
    if (is_wp_error($messageResponse)) {
        wp_send_json_error(['message' => 'Error sending message.']);
        return;
    }
    
    // Run The Assistant
    $assistantResponse = run_assistant(get_option('chat_gpt_api_key'), $threadId);
    
    // Retrieve and send the latest message from the thread
    $latestMessageContent = retrieve_and_send_latest_message(get_option('chat_gpt_api_key'), $threadId);
    wp_send_json_success(['message' => $latestMessageContent]);
}


// Create New Assistant Run > Then Check To See If It's Done
function run_assistant($apiKey, $threadId) {
    // error_log('Preparing to run assistant with thread_id:' . $threadId);

    $data = [
    'assistant_id' => get_option('chat_gpt_assistant_id')
	];
    $apiKey = get_option('chat_gpt_api_key');
    $args = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v1'
        ],
        'method' => 'POST',
        'timeout' => 15,
        'body' => json_encode($data) // Include the request body
    ];

    $response = wp_remote_post("https://api.openai.com/v1/threads/$threadId/runs", $args);

 	if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error running assistant.']);
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['id'])) {
            $runId = $data['id'];
            error_log('Assistant Run Created: ' . $runId);
            // Start checking for completion status
            $status = 'in_progress';
            while ($status !== 'completed') {
                sleep(0.5); // Wait for 0.5 seconds before checking again
                $statusResponse = check_run_status($apiKey, $threadId, $runId);
                if (is_wp_error($statusResponse)) {
                    wp_send_json_error(['message' => 'Error checking run status.']);
                    return false;
                }
                $statusBody = wp_remote_retrieve_body($statusResponse);
                $statusData = json_decode($statusBody, true);
                $status = $statusData['status'] ?? 'unknown';
            }
            // Once completed, retrieve and send the latest message
            $latestMessage = retrieve_and_send_latest_message($apiKey, $threadId);
            return $latestMessage;
        } else {
            error_log('GPT Run Assistant response format unexpected: ' . $body);
            wp_send_json_error(['message' => 'Unexpected response format when creating new assistant run.']);
            return false;
        }
    }
 }


// Check Assistant Run Status
function check_run_status($apiKey, $threadId, $runId) {
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v1'
        ],
        'method' => 'GET',
    ];
    $url = "https://api.openai.com/v1/threads/$threadId/runs/$runId";
    return wp_remote_get($url, $args);
}


// Get Latest Message From Thread (AKA Assistant Response)
function retrieve_and_send_latest_message($apiKey, $threadId) {
    $latestMessage = get_latest_message_from_thread($apiKey, $threadId);
    if ($latestMessage === 'Error retrieving messages.' || $latestMessage === 'No messages found.') {
        return 'No response content'; // Fallback message if no latest message is found or on error
    }
    return $latestMessage; // Return the content of the latest message
}

// Create New Message on Thread
function send_message_to_thread($apiKey, $threadId, $userMessage) {
    error_log('Preparing to send message to thread.');
    $data = [
        'role' => 'user',
        'content' => $userMessage,
    ];

    $args = [
        'body'    => json_encode($data),
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v1'
        ],
        'method'  => 'POST',
        'timeout' => 15,
    ];

    $response = wp_remote_post("https://api.openai.com/v1/threads/$threadId/messages", $args);
    if (is_wp_error($response)) {
        error_log('Error in wp_remote_post: ' . $response->get_error_message());
    } else {
        error_log('Message sent successfully to thread.');
    }
    return $response;
}

// Updated function to retrieve the latest message from a thread, ensuring it matches the expected workflow
function get_latest_message_from_thread($apiKey, $threadId) {

    $args = [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'OpenAI-Beta' => 'assistants=v1'
        ],
        
        'timeout' => 15,
    ];


    $response = wp_remote_get("https://api.openai.com/v1/threads/$threadId/messages", $args);
    if (is_wp_error($response)) {
        return 'Error retrieving messages.';
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (isset($data['data']) && !empty($data['data'])) { // Assuming 'data' contains messages
        $latestMessage = reset($data['data']); // Get the last message assuming it's the latest
       error_log(var_export($latestMessage, true));
        return $latestMessage['content'][0]['text']['value'] ?? 'No content available'; // Adjust based on actual structure
    }

    return 'No messages found.';
}

add_action('wp_ajax_gpt_chat', 'handle_chat_interaction');
add_action('wp_ajax_nopriv_gpt_chat', 'handle_chat_interaction');

?>