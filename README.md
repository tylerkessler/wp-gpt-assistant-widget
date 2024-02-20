# WP GPT Assistant Widget

Built for AAIMEA.

The WP GPT Assistant Widget is a WordPress plugin designed to integrate a "custom GPT" like experience using OpenAI's GPT assistant in a chat widget for wordpress.

## Tasks
- [x] Write backend code to get MVP / Beta working
- [x] Optimize for security and server performance
- [ ] Design Front End (Currently it's bare bones functionality)
- [ ] Improve UX with server feedback, prompt suggestions, etc.
- [ ] Test Assistant Responses with Brian
- [X] Add ability to load only on AAIMEA website (loads on index/homepage current)

## Features

- Seamless integration of GPT-powered chat into WordPress sites.
- Secure management of API keys through the WordPress admin interface.
- Real-time communication with OpenAI's GPT assistant.

## Installation

1. Download the plugin zip file.
2. Navigate to your WordPress dashboard, go to the Plugins section, and click on 'Add New'.
3. Click on the 'Upload Plugin' button at the top of the page.
4. Choose the downloaded zip file and click 'Install Now'.
5. After the installation is complete, activate the plugin through the 'Plugins' menu in WordPress.

## API Keys Configuration

1. Obtain an API key from OpenAI/Tyler.
2. Obtain an Assistant ID from OpenAI/Tyler.
3. In the WordPress admin dashboard, go to Settings > GPT Assistant Widget.
4. Enter your OpenAI API key and Assistant ID and save the changes.
* NOTE: Open AI Threads must be activated for your orgination for this widget to work with Assistant.

## HTML Styling Guidelines
* HTML can be edited in /html/widget.html
* CSS can be edited in /css/styles.css

## Usage

After activation and configuration:

1. Add the widget to any page or post using the built-in WordPress widgets feature.
2. Customize the appearance and settings of the chat widget as needed.
3. The widget will automatically initialize a chat thread on page load.

## Customization

You can customize the styles of the chat interface by editing the CSS files located in the `css` directory of the plugin.

## Development

Contributions are welcome. To contribute:

1. Fork the repository on GitHub.
2. Create a new branch for your feature or fix.
3. Commit your changes with a descriptive message.
4. Push your branch and submit a pull request.

## Support

If you encounter any issues or have questions, please file an issue on the GitHub repository issue tracker.

## License

This plugin is licensed under the [GPLv2](http://www.gnu.org/licenses/gpl-2.0.html) or later.
