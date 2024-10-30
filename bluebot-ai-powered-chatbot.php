<?php
/*
Plugin Name: BlueBot - AI Powered Chatbot
Plugin URI: https://chatbot.blue-cloud.io/
Description: An AI-powered chatbot plugin for WordPress using OpenAI API.
Version: 1.0.1
Author: Salil Agarwal
Author URI: https://blue-cloud.io
License: GPL2
Text Domain: bluebot-ai-powered-chatbot
Domain Path: /languages
Requires at least: 5.0
Requires PHP: 7.4
*/

if (! defined('ABSPATH')) exit; // Exit if accessed directly 

// Define constants
define('BCCBAI_CHATBOT_VERSION', '1.0');
define('BCCBAI_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BCCBAI_CHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load plugin text domain for translations
function bccbai_chatbot_load_textdomain()
{
    load_plugin_textdomain('bluebot-ai-powered-chatbot', false, BCCBAI_CHATBOT_PLUGIN_DIR . '/languages');
}
add_action('plugins_loaded', 'bccbai_chatbot_load_textdomain');

// Include necessary files
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-conversation-manager.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-openai.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'admin/class-bccbai-chatbot-admin.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'frontend/class-bccbai-chatbot-frontend.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'utility_functions/frontend_utility.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'utility_functions/backend_utility.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('BCCBAI_Chatbot', 'activate'));
register_deactivation_hook(__FILE__, array('BCCBAI_Chatbot', 'deactivate'));

// Initialize the plugin
function bccbai_chatbot_init()
{
    $chatbot = new BCCBAI_Chatbot();
    $chatbot->init();

    // Hook to handle the scheduled event
    add_action('bccbai_check_inactive_conversation', 'bccbai_check_inactive_conversation_fn');
}
add_action('plugins_loaded', 'bccbai_chatbot_init');


// Add a settings link to the plugins page for BlueBot
function bccbai_add_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=bccbai-chatbot">Settings</a>';
    array_push($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bccbai_add_settings_link');
