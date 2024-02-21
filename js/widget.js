(function($) {

  // Initialize Variables
  let threadId = null;

  // Inject HTML
  function injectHTML() {
    var widgetHtmlPath = chatGPT.widgetHtmlPath || '';
    $.get(widgetHtmlPath,function(data){$("body").append(data);}).fail(function(){console.error('GPT Widget: Error loading html/widget.html');});
  }

  // User Interactions Handler
  function attachEventListeners() {

    // User Clicks to Toggle Widget Container
    $('body').on('click','#gpt-chat-toggle',function(){$('#gpt-chat-container').toggle();});

    // User Pushes Enter to Prompt GPT
    $('body').on('keypress', '#gpt-chat-input', async function(e) {
      if (e.key === 'Enter' && this.value.trim() !== '') {
        const userMessage = this.value;
        displayMessage('User', userMessage);
        this.value = '';
        this.disabled = true; // d
        document.getElementById('gpt-chat-input').placeholder = "...";

        // [ ] Add Processing Animation

        const gptResponse = await fetchGPTResponse(userMessage);
        displayMessage('GPT', gptResponse);
        this.disabled = false; //enable text field on response
        document.getElementById('gpt-chat-input').placeholder = "Ask me anything..."
      }
    });
  }

  // Create New GPT Thread
  async function initializeThread() {
    const ajaxurl = chatGPT.endpoint;
    const data = {'action': 'gpt_create_thread','_ajax_nonce': chatGPT.nonce};
    try {
      const response = await $.post(ajaxurl, data);
      if (response.hasOwnProperty('success') && response.success && response.data.threadId) {
        threadId = response.data.threadId;
        console.log('New Thread Created:', threadId)
        displayMessage('GPT', "Hello! How can I assist you today?");
      } else {console.error('Failed to initialize thread. Response:', response);}
    } catch (error) {
      console.error('Error initializing thread:', error);
      document.getElementById('gpt-chat-ui').style.display = 'none'; // hide if API fails
    }
  }

  // AJAX: Fetch GPT Response From User Initiated Prompt
  async function fetchGPTResponse(message) {
    if (!threadId) {
      console.error('GPT Thread ID is not initialized.');
      return 'GPT Thread not initialized.';
    }

    const ajaxurl = chatGPT.endpoint; // Your existing endpoint
    const data = {
      'action': 'gpt_chat', // Your existing WordPress action
      'message': message,
      'threadId': threadId, // Pass the thread ID with each message
      '_ajax_nonce': chatGPT.nonce // Nonce for security
    };

    try {
      const response = await $.post(ajaxurl, data);
      if (response.success) {
        return response.data.message; // Adjust based on your actual AJAX handler response
      } else {
        throw new Error('Response was not successful.');
      }
    } catch (error) {
      console.error('Error:', error);
      return 'Sorry, there was an error processing your request.';
    }
  }

  // Display messages in the UI
  function displayMessage(sender, message) {

    // [ ] Remove Processing Animation

    var messagesContainer = $('#gpt-chat-messages');
    messagesContainer.append(`<div>${sender}: ${message}</div>`);
    messagesContainer.scrollTop(messagesContainer.prop("scrollHeight"));
  }

  // Initialize View
  function init() {injectHTML(); attachEventListeners(); initializeThread();}

  $(window).on('load', init);

})(jQuery);