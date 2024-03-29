<?php

  // [ ] Add File Upload
  //     https://chat.openai.com/c/21806951-9318-43c5-be45-de631aeb14d0

// Enqueue Styles
function gpt_enqueue_admin_styles() {
  wp_register_style('gpt-admin-css', plugins_url('/css/admin.css', __FILE__), array(), 'all');
  wp_enqueue_style('gpt-admin-css');
}
add_action('admin_enqueue_scripts', 'gpt_enqueue_admin_styles');

// Open AI Options
function register_options() {

  // Open AI API Key
  add_option('chat_gpt_api_key', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_api_key');

  // Open AI Assistant ID
  add_option('chat_gpt_assistant_id', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_assistant_id');

  // Open AI Organization ID
  add_option('chat_gpt_organization_id', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_organization_id');

  // URL Regular Expression
  add_option('url_regular_expression', '');
  register_setting('chat_gpt_options_group', 'url_regular_expression');

  // Suggested Prompts
  add_option('suggested_prompts', '');
  register_setting('chat_gpt_options_group', 'suggested_prompts');
  
  // GPT Assistant Instructions
  add_option('assistant_instructions', '');
  register_setting('chat_gpt_options_group', 'assistant_instructions');

}
add_action('admin_init', 'register_options');

// WP Options Page Registration
function chat_gpt_register_options_page() {
  add_options_page('GPT Assistant Settings', 'GPT Assistant', 'manage_options', 'gpt-assistant-settings', 'chat_gpt_options_page');
}
add_action('admin_menu', 'chat_gpt_register_options_page');

function get_current_assistant_instructions() {
  $api_key = get_option('chat_gpt_api_key');
  $assistant_id = get_option('chat_gpt_assistant_id');
  $url = 'https://api.openai.com/v1/assistants/' . $assistant_id;
  $response = wp_remote_get($url, array(
    'headers' => array(
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
      'OpenAI-Beta' => 'assistants=v1'
    )
  ));
  if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
  } else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body); 
    if (!empty($data->instructions)) {
      $instructions = $data->instructions;
      update_option('assistant_instructions', $instructions);
    } 
  }
}

// function update_openai_assistant_instructions($new_instructions) {
//   error_log("Updating Open AI Instructions!");
//   $api_key = get_option('chat_gpt_api_key');
//   $assistant_id = get_option('chat_gpt_assistant_id');
//   $url = "https://api.openai.com/v1/assistants/{$assistant_id}";
//   $body = json_encode([
//       'instructions' => $new_instructions,
//   ]);

//   $response = wp_remote_post($url, array(
//       'method'    => 'PATCH',
//       'headers'   => array(
//           'Authorization' => 'Bearer ' . $api_key,
//           'Content-Type'  => 'application/json',
//           'OpenAI-Beta' => 'assistants=v1'
//       ),
//       'body' => $body,
//   ));

//   if (is_wp_error($response)) {
//       $error_message = $response->get_error_message();
//       error_log("Failed to update assistant instructions: $error_message");
//   } else {
//       error_log('Successfully updated assistant instructions.');
//   }
// }


// WP Options Page Content
function chat_gpt_options_page() {

  // // Update Open AI Instructions
  // if (isset($_POST['submit']) && check_admin_referer('update-assistant-instructions', 'assistant-instructions-nonce')) {
  //   if (!empty($_POST['assistant_instructions'])) {
  //     $new_instructions = sanitize_textarea_field($_POST['assistant_instructions']);
  //     update_openai_assistant_instructions($new_instructions);
  //     wp_nonce_field('update-assistant-instructions', 'assistant-instructions-nonce');
  //   }
  // }
  
  // Get Assistant Instructions
  if (!empty(get_option('chat_gpt_api_key')) && !empty(get_option('chat_gpt_assistant_id'))) get_current_assistant_instructions();

  echo '<div class="wrap">

    <h2>GPT Assistant Settings</h2>

    <form method="post" action="options.php">';

      settings_fields('chat_gpt_options_group');
      echo '<table class="form-table gpt-settings-table">
      
      <tr>
        <th scope="row"><label for="chat_gpt_api_key">API Key:</label></th>
        <td>
          <input type="text" col="75" id="chat_gpt_api_key" name="chat_gpt_api_key" value="' . esc_attr(get_option('chat_gpt_api_key')) . '" />
            <p class="description"><a href="https://platform.openai.com/api-keys" target="_blank">Open AI Keys</a></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="chat_gpt_assistant_id">Assistant ID:</label></th>
        <td>
          <input type="text" col="50" id="chat_gpt_assistant_id" name="chat_gpt_assistant_id" value="' . esc_attr(get_option('chat_gpt_assistant_id')) . '" />
          <p class="description"><a href="https://platform.openai.com/assistants" target="_blank">Open AI Assistants</a></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="chat_gpt_organization_id">Organization ID:</label></th>
        <td>
          <input type="text" col="50" id="chat_gpt_assistant_id" name="chat_gpt_organization_id" value="' . esc_attr(get_option('chat_gpt_organization_id')) . '" />
          <p class="description"><a href="https://platform.openai.com/account/organization" target="_blank">Open AI Threads</a> must be set to <code>Visible to organization owners</code>.</p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="url_regular_expression">URL Matching:</label></th>
        <td>
          <input type="text" id="url_regular_expression" name="url_regular_expression" value="' . esc_attr(get_option('url_regular_expression')) . '" />
          <p class="description">Define a regular expression to match the URLs where the widget should be displayed. For example, to match all member pages, use something like <code>.*/members/.*</code>.</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="suggested_prompts">Suggested Prompts:</label></th>
        <td>
          <textarea id="suggested_prompts" name="suggested_prompts" rows="4" cols="100" rows="50">' . esc_attr(get_option('suggested_prompts')) . '</textarea>
          <p class="description">Randomly selected prompts for users.</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="assistant_instructions">Assistant Instructions:</label></th>
        <td>
          <textarea id="assistant_instructions" name="assistant_instructions" rows="4" cols="100" rows="50">' . esc_attr(get_option('assistant_instructions')) . '</textarea>
          <p class="description">GPT Assitant instructions set inside of Open AI API (does not sync as of yet)</p>
        </td>
      </tr>
      </table>';

      submit_button();

      // Developer Info Section
      echo '<div class="developer-info">
        <h3>Developer Information & Resources</h3>
        <p>For additional support, feature requests, or to contribute to the development of this plugin, please explore the following resources or contact the developer directly:</p>
        <ul>
          <li><strong>Developer:</strong> Tyler Kessler</li>
          <li><strong>Email:</strong> <a href="mailto:Tyler.Kessler@gmail.com">Tyler.Kessler@gmail.com</a></li>
          <li><strong>Phone:</strong> +1 (407) 415-6101</li>
          <li><strong>GitHub Repository:</strong> <a href="https://github.com/yourrepo" target="_blank">WP GPT Assistant Widget on GitHub</a></li>
          <li><strong>LinkedIn Profile:</strong> <a href="https://www.linkedin.com/in/tylerkessler" target="_blank">Visit LinkedIn</a></li>
        </ul>
      </div>
    </form>
  </div>';
  wp_nonce_field('update-assistant-instructions', 'assistant-instructions-nonce');
}
?>