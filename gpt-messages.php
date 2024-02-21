<?php

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

  // Chat Interaction Handler
  function handle_chat_interaction() {

    //Send Message
    $threadId = sanitize_text_field($_POST['threadId']);
    $userMessage = sanitize_text_field($_POST['message']);
    $messageResponse = send_message(get_option('chat_gpt_api_key'), $threadId, $userMessage);
    if (is_wp_error($messageResponse)) return wp_send_json_error(['message' => 'Error sending message.']);

    // Open AI API Sluggishness Handler
    $retryCount = 0; $maxRetries = 3; $retryDelay = 2;

    while ($retryCount < $maxRetries) {

      // Run Assistant On Sent Message
      $gptResponse = run_assistant(get_option('chat_gpt_api_key'), $threadId);
      
      // Check if response indicates an active run conflict
      if (isset($gptResponse['error']) && strpos($gptResponse['error']['message'], "already has an active run") !== false) {

        // If Assistant Is Still Running
        error_log('Active run conflict detected for thread ' . $threadId . '. Retrying after delay...');
        sleep($retryDelay); // Wait before retrying
        $retryCount++; // Increment the retry counter

      } else {
        wp_send_json_success(['message' => $gptResponse]);
        return;
      }
    }
    wp_send_json_error(['message' => 'The assistant is currently busy. Please try again in a few moments.']);
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

      // Broad regex pattern to ensure capturing all variations
      $filteredMessage = preg_replace('/【.*?†source】/', '', $latestMessage['content'][0]['text']['value']);
      
      // Additionally remove any potential whitespace around the removed citations
      $filteredMessage = preg_replace('/\s+/', ' ', $filteredMessage);

      error_log('[GPT-Widget] GPT: "' . $filteredMessage . '"');
      return trim($filteredMessage) ?? 'No content available';
    }
    return 'No messages found.';
  }

?>