# xAI SEO Enhancer

A WordPress plugin that leverages xAI to enhance posts and pages for superior SEO performance. Compatible with Rank Math, Yoast SEO, or as a standalone tool with its own scoring system.

## Features
- **SEO Analysis**: Uses xAI to analyze content and suggest improvements for focus keywords, SEO titles, meta descriptions, and content structure.
- **SEO Scoring**: Provides a persistent score (X/100) with detailed checks for keyword frequency, density, title/meta presence, and content length, visible on page load.
- **Image Generation**: Integrates xAI’s image generation API with a popup for creating featured or inline images, triggered manually with user prompt options.
- **Auto-Fix**: Checkbox option to automatically apply non-image suggestions after analysis.
- **Compatibility**: Seamlessly integrates with Rank Math, Yoast SEO, or operates standalone with custom meta fields.
- **Dashboard**: Dedicated settings page with styled API key status and xAI service status updates, featuring clear visual indicators.

## Installation
1. Download the plugin files:
   - `xai-seo-enhancer.php`
   - `xai-optimizer.js`
   - `settings.php`
   - `settings.css`
2. Upload the `xai-seo-enhancer` folder to `wp-content/plugins/`.
3. Activate the plugin via the WordPress admin panel under "Plugins".
4. Configure the xAI API key in the "xAI SEO Enhancer" menu in the WordPress sidebar.

## Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher
- An xAI API key with access to chat completions and image generation (see [xAI Docs](https://docs.x.ai))

## Usage
1. **Settings**:
   - Navigate to "xAI SEO Enhancer" in the sidebar.
   - Enter your xAI API key and save.
   - View API key status and xAI service status on the dashboard.

2. **Post/Page Editing**:
   - Open a post or page in the editor.
   - Find the "xAI SEO Enhancer" metabox in the sidebar.
   - View the initial SEO score and checks on page load.
   - Click "Analyze with xAI" to fetch suggestions without saving or reloading.
   - Apply individual suggestions via buttons or enable "Auto-Fix" checkbox for non-image changes.
   - Click "Generate Image" to create an image, preview it in a popup, and choose to set as featured or insert into the post.

3. **Retesting**:
   - After applying changes, click "Retest" to refresh the score and suggestions.

## Troubleshooting
- **No Initial Score**: Ensure PHP debug is enabled (`WP_DEBUG` in `wp-config.php`) and check logs.
- **Image Creation Fails**: Verify your xAI API key has image generation permissions; check `debug.log` for errors.
- **Suggestions Not Applying**: Clear caches (browser and server) and ensure admin permissions.

## Changelog

### Version 2.0 (March 30, 2025)
- **Renamed**: Plugin renamed from "xAI Rank Math Optimizer" to "xAI SEO Enhancer" for broader applicability.
- **Major Update**: Incremented to 2.0 due to significant feature enhancements, structural refactoring, and renaming.
- **Fixed**: Post title now correctly updates to SEO title instead of full content.
- **Fixed**: "Analyze with xAI" no longer triggers save or page reload, updates UI only.
- **Added**: Initial SEO score and detailed checks displayed on page load in the metabox.
- **Added**: "Auto-Fix" implemented as a checkbox to apply non-image suggestions post-analysis.
- **Enhanced**: Image generation popup redesigned, triggered by "Generate Image" button, fully functional with xAI API.
- **Refactored**: Split JavaScript into `xai-optimizer.js` for maintainability.
- **Added**: Created `settings.php` and `settings.css` for a styled dashboard with larger, color-coded status indicators.
- **Improved**: Persistent SEO score with real-time check visibility and suggestion application workflow.

### Version 1.3 (March 30, 2025)
- **Added**: Compatibility with Rank Math and Yoast SEO, with standalone mode using custom meta fields.
- **Added**: Sidebar menu with xAI-like icon and API key setup instructions.
- **Added**: Initial dashboard with API key status and raw xAI service status feed.
- **Fixed**: Initial caching and sync issues for post updates.
- **Improved**: Enhanced image handling with placeholder generation (later replaced).

### Version 1.2 (March 2025)
- **Fixed**: API timeout issues with increased `max_tokens` and retry logic.
- **Added**: Basic SEO scoring system for standalone mode.
- **Improved**: JavaScript spinner visibility and UI feedback.

### Version 1.1 (March 2025)
- **Fixed**: Initial AJAX and caching issues.
- **Added**: Basic xAI API integration for content analysis.
- **Improved**: Metabox layout and button functionality.

### Version 1.0 (March 2025)
- **Initial Release**: Basic plugin structure with xAI API calls and Rank Math integration.

## Contributing
Feel free to fork this repository, submit pull requests, or report issues on GitHub (if hosted). Ensure you test changes with a valid xAI API key.

## License
This plugin is licensed under the GPL-2.0 License. See the [LICENSE](LICENSE) file for details.

## Credits
- Developed by Rick Hayes with assistance from Grok (xAI).
- Uses xAI APIs for content analysis and image generation.