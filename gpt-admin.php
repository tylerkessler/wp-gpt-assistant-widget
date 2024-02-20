<?php
// Open AI Options
function register_options() {

  // Open AI API Key
  add_option('chat_gpt_api_key', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_api_key');

  // Open AI Assistant ID
  add_option('chat_gpt_assistant_id', '');
  register_setting('chat_gpt_options_group', 'chat_gpt_assistant_id');

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

// WP Options Page Content
function chat_gpt_options_page() {
  // Inline CSS for improved layout and readability
  echo '<style>
    .gpt-settings-table th, .gpt-settings-table td {
        padding: 10px;
        vertical-align: top;
    }
    .gpt-settings-table th {
        text-align: right;
        width: 20%;
    }
    .gpt-settings-table td input[type="text"] {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .developer-info {
        margin-top: 20px;
        padding: 15px;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .developer-info ul {
        list-style-type: none;
        padding: 0;
    }
    .developer-info ul li {
        margin-bottom: 10px;
    }
  </style>';

  echo '<div class="wrap">
    <h2>GPT Assistant Settings</h2>
    <form method="post" action="options.php">';
      settings_fields('chat_gpt_options_group');
      echo '<table class="form-table gpt-settings-table">
      <tr>
        <th scope="row"><label for="chat_gpt_api_key">API Key:</label></th>
        <td>
          <input type="text" id="chat_gpt_api_key" name="chat_gpt_api_key" value="' . esc_attr(get_option('chat_gpt_api_key')) . '" />
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="chat_gpt_assistant_id">Assistant ID:</label></th>
        <td>
          <input type="text" id="chat_gpt_assistant_id" name="chat_gpt_assistant_id" value="' . esc_attr(get_option('chat_gpt_assistant_id')) . '" />
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="url_regular_expression">URL Regular Expression:</label></th>
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
}
?>