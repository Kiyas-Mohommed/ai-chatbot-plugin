<?php

/**
 * Plugin Name: Custom Chatbot Plugin
 * Description: A custom chatbot for WordPress integrated with OpenAI Assistant.
 * Version: 2.2.2
 * Author: IMARA Software Solutions
 * Author URI: https://www.imarasoft.net
 */


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHATBOT_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once 'includes/curl_request.php';
require_once 'includes/api_request.php';

// Enqueue frontend assets
function chatbot_enqueue_assets()
{
    wp_enqueue_style('chatbot-styles', CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css');
    wp_enqueue_script('chatbot-script', CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js', ['jquery'], null, true);
    wp_localize_script('chatbot-script', 'chatbotConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('chatbot_nonce'),
        'pluginUrl' => CHATBOT_PLUGIN_URL
    ]);
}
add_action('wp_enqueue_scripts', 'chatbot_enqueue_assets');

function enqueue_fontawesome()
{
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_fontawesome');

// Add chatbot HTML to footer
function chatbot_render_ui()
{
    if (is_user_logged_in()) { ?>
        <div id="chatbot-container" class="hidden">
            <div id="chatbot-header">
                <div id="chatbot-logo">🤖</div>
                <h5 style="color: #fff !important; margin: 0 !important;">Need Assistance?</h5>
                <button id="chatbot-close" class="chatbot-close-btn">×</button>
            </div>
            <div id="chatbot-messages"></div>
            <div id="chatbot-input-container">
                <textarea type="text" id="chatbot-input" placeholder="Type your question here..."></textarea>
                <button id="chatbot-send">Send</button>
            </div>
        </div>
        <button id="chatbot-toggle" aria-label="Open Chatbot">
            <img src="<?php echo CHATBOT_PLUGIN_URL; ?>assets/images/chatbot-logo.png" alt="Our logo here">
        </button>
<?php }
}
add_action('wp_footer', 'chatbot_render_ui');

// Handle AJAX request
function chatbot_handle_request()
{
    $makeCall = new makeRequest();
}
add_action('wp_ajax_chatbot_request', 'chatbot_handle_request');
