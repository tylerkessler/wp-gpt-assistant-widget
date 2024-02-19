(function($) {

  // Initialize Variables
  let threadId = null;

// Inject HTML
  function injectHTML() {

    // Use the widgetHtmlPath from the localized chatGPT object
    var widgetHtmlPath = chatGPT.widgetHtmlPath || '';

    $.get(widgetHtmlPath, function(data) {
      $("body").append(data);
    }).fail(function() {
      console.error('Error loading widget.html');
    });
  }

// User Interactions Handler
  function attachEventListeners() {
  // Event delegation for dynamically loaded elements
  $('body').on('click', '#gpt-chat-toggle', function() {
    var container = $('#gpt-chat-container');
    container.toggle();
  });

  // Using event delegation for keypress event on dynamically loaded input
  $('body').on('keypress', '#gpt-chat-input', async function(e) {
    if (e.key === 'Enter' && this.value.trim() !== '') {
      const userMessage = this.value;
      displayMessage('User', userMessage);
      this.value = ''; // Clear input after sending
      
      const gptResponse = await fetchGPTResponse(userMessage);
      displayMessage('GPT', gptResponse);
    }
  });
}

  // GPT: Create New Thread
  async function initializeThread() {
	  const ajaxurl = chatGPT.endpoint; // Use the endpoint for thread creation
	  const data = {
	    'action': 'gpt_create_thread', // A new WordPress action for thread creation
	    '_ajax_nonce': chatGPT.nonce // Nonce for security
	  };

	  try {
	    const response = await $.post(ajaxurl, data);
	    // Check if the response has the property 'success'
	    if (response.hasOwnProperty('success') && response.success && response.data.threadId) {
	      threadId = response.data.threadId; // Store the thread ID for later use
	      console.log('threadId: ', threadId)
	    } else {
	      // Handle the case where 'success' is false or undefined
	      console.error('Failed to initialize thread. Response:', response);
	    }
	  } catch (error) {
	    // Catch any errors that occurred during the AJAX request
	    console.error('Error initializing thread:', error);
	  }
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

 // Initialize
  function init() {
    injectHTML();
    attachEventListeners();
    initializeThread(); // Initialize a new thread when the widget loads
  }

  $(window).on('load', init);

})(jQuery);
