<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 

require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-conversation-manager.php';

function bccbai_check_inactive_conversation_fn($conversation_id)
{
    $conversation_manager = new BCCBAI_Chatbot_Conversation_Manager();
    $conversation_manager->check_and_send_email($conversation_id);
}


// send email to user
function bccbai_wp_mail($to_email, $subject, $message)
{
    $response = wp_mail($to_email, $subject, $message);
    return $response;
}
