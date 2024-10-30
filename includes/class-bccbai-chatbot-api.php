<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 

require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-conversation-manager.php';

/**
 * The `BCCBAI_Chatbot_API` class is responsible for handling the REST API endpoints of the BCCBAI Chatbot plugin.
 * It registers custom REST API routes for the chatbot and handles POST and GET requests to these routes.
 * The class also provides a method for authenticating requests to the chatbot endpoint.
 *
 * The `register_routes` method registers routes at 'bccbai/v1/chatbot' for POST and 'bccbai/v1/chatbot/history' for GET.
 * The `handle_chatbot_request` method is the callback for the POST route. It processes the user's message, generates a response, and updates the conversation history.
 * The `get_conversation_history` method is the callback for the GET route. It retrieves the conversation history based on the provided conversation ID.
 * The `authenticate_request` method is used to verify the nonce sent with the request for security purposes.
 *
 * @since 1.0.0
 * 
 * @package BCCBAI_Chatbot
 * @subpackage BCCBAI_Chatbot/api
 */
class BCCBAI_Chatbot_API
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route('bccbai/v1', '/chatbot', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chatbot_request'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        register_rest_route('bccbai/v1', '/chatbot/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_conversation_history'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
    }

    public function authenticate_request($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Invalid nonce.', array('status' => 403));
        }
        return true;
    }

    public function handle_chatbot_request($request)
    {
        $message = $request->get_param('message');
        $conversation_id = $request->get_param('conversation_id');

        // Initialize conversation manager
        $conversation_manager = new BCCBAI_Chatbot_Conversation_Manager();

        // Check if it's a new conversation or an existing one
        if (empty($conversation_id)) {
            // Start a new conversation
            $conversation_id = $conversation_manager->create_conversation();
        }

        // Retrieve conversation history
        $conversation_history = $conversation_manager->get_conversation_history($conversation_id);

        // Process user message
        if (!empty($message)) {
            // Generate response using OpenAI API
            $openai = new BCCBAI_Chatbot_OpenAI();
            $saved_summary = get_option('bccbai_chatbot_summary');
            $response = $openai->generate_chat_response($message, $conversation_history, $saved_summary);

            // Update conversation history
            $conversation_manager->update_conversation_history($conversation_id, array(
                array('m_type' => 'user', 'message' => $message),
                array('m_type' => 'bot', 'message' => $response, 'api_log_id' => $openai->api_log_id)
            ));
        } else {
            // If no message is provided, return an error response
            return new WP_Error('invalid_request', 'No message provided.', array('status' => 400));
        }

        return wp_send_json_success(array(
            'conversation_id' => $conversation_id,
            'response' => $response,
        ));
    }

    public function get_conversation_history($request)
    {
        $conversation_id = $request->get_param('conversation_id');

        // Initialize conversation manager
        $conversation_manager = new BCCBAI_Chatbot_Conversation_Manager();

        // Retrieve conversation history
        $conversation_history = $conversation_manager->get_conversation_history($conversation_id);

        // Return an empty array if no conversation history is found
        if (empty($conversation_history)) {
            $conversation_history = array();
        }

        return array(
            'conversation_id' => $conversation_id,
            'conversation_history' => $conversation_history,
        );
    }
}
