<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly 

require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-content-analyzer.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-openai.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-api.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'frontend/class-bccbai-chatbot-frontend.php';
require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-conversation-manager.php';


class BCCBAI_Chatbot
{

    public function __construct()
    {
        // Constructor code
    }

    public function init()
    {
        // Register scripts and styles
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Register AJAX handlers
        //add_action('wp_ajax_bccbai_chatbot_request', array($this, 'handle_ajax_request'));
        //add_action('wp_ajax_nopriv_bccbai_chatbot_request', array($this, 'handle_ajax_request'));

        // Other actions and hooks
        add_action('init', array($this, 'render_chatbot'));
    }

    public function enqueue_scripts()
    {
        // Enqueue scripts and styles
        // ...
    }

    public function handle_ajax_request()
    {
        // Handle AJAX requests
        // ...
    }

    public static function activate()
    {
        // Activation code        
        BCCBAI_Chatbot_OpenAI::bccbai_chatbot_create_log_table();
        BCCBAI_Chatbot_Conversation_Manager::create_conversation_table();
    }

    public static function deactivate()
    {
        // Deactivation code
        // ...
    }

    public function render_chatbot()
    {
        new BCCBAI_Chatbot_API();
        $chatbot_frontend = new BCCBAI_Chatbot_Frontend();
        $chatbot_frontend->init();
        add_action('wp_footer', array($chatbot_frontend, 'render_chatbot'));
    }
}
