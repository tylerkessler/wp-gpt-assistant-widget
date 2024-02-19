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

    // Click to Toggle Widget Container
    $('body').on('click','#gpt-chat-toggle',function(){$('#gpt-chat-container').toggle();});

    // Push Enter to Prompt GPT
    $('body').on('keypress', '#gpt-chat-input', async function(e) {
      if (e.key === 'Enter' && this.value.trim() !== '') {
        const userMessage = this.value;

        // Post Submitted Message to Display Area
        displayMessage('User', userMessage);

        // Clear Text Field
        this.value = '';

        // Waiting For a Response...
        const gptResponse = await fetchGPTResponse(userMessage);
        displayMessage('GPT', gptResponse);
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
        console.log('New Thread Created', threadId)
      } else {console.error('Failed to initialize thread. Response:', response);}
    } catch (error) {console.error('Error initializing thread:', error);}
  }

  // Modified function to handle API requests via WordPress AJAX
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
    var messagesContainer = $('#gpt-chat-messages');
    messagesContainer.append(`<div>${sender}: ${message}</div>`);
    messagesContainer.scrollTop(messagesContainer.prop("scrollHeight"));
  }

  // Initialize View
  function init() {injectHTML(); attachEventListeners(); initializeThread();}
  
  $(window).on('load', init);

})(jQuery);