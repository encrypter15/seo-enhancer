<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function xai_settings_page() {
    global $xai_optimizer; // Access the main class instance if needed
    $optimizer = new XAI_Rank_Math_Optimizer(); // Create an instance to use methods
    $api_key = get_option('xai_api_key', '');
    if (isset($_POST['xai_api_key'])) {
        update_option('xai_api_key', sanitize_text_field($_POST['xai_api_key']));
        $api_key = get_option('xai_api_key');
        error_log('xAI: API key updated to: ' . $api_key);
    }
    ?>
    <div class="wrap xai-settings-wrap">
        <h1>xAI SEO Enhancer Settings</h1>
        <form method="post" action="">
            <table class="form-table xai-form-table">
                <tr>
                    <th scope="row"><label for="xai_api_key">xAI API Key</label></th>
                    <td>
                        <input type="text" name="xai_api_key" id="xai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                        <p class="description">To obtain an xAI API key:</p>
                        <ol class="xai-instructions">
                            <li>Visit <a href="https://x.ai" target="_blank">x.ai</a> and sign up or log in.</li>
                            <li>Navigate to the developer portal or API section (refer to <a href="https://docs.x.ai" target="_blank">docs.x.ai</a>).</li>
                            <li>Generate a new API key and paste it here.</li>
                            <li>Ensure your account has sufficient credits/quota for API usage.</li>
                        </ol>
                        <p class="description xai-disclaimer"><strong>Disclaimer:</strong> The xAI logo and related trademarks are owned by xAI. We use them under fair use for identification and compatibility purposes. No endorsement by xAI is implied.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Changes', 'primary', 'submit', true, ['class' => 'xai-submit-btn']); ?>
        </form>
        <h2>Dashboard</h2>
        <div class="xai-dashboard">
            <p><strong>API Key Status:</strong> 
                <span class="xai-status-dot" style="background-color: <?php echo $api_key ? ($optimizer->test_api_key() ? '#28a745' : '#dc3545') : '#ffc107'; ?>;"></span>
                <?php echo $api_key ? ($optimizer->test_api_key() ? 'Working' : 'Not Working') : 'Not Set'; ?>
            </p>
            <h3>xAI Service Status</h3>
            <div class="xai-status-container">
                <?php echo $optimizer->display_status(); ?>
            </div>
        </div>
    </div>
    <?php
}

// This function is called by the main class, so we don’t need to call it here