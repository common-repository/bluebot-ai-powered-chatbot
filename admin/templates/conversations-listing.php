<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 
?>
<div class="wrap">
    <h1><?php esc_html_e('Conversations', 'bluebot-ai-powered-chatbot'); ?></h1>

    <?php if (!empty($conversations)) : ?>
        <div id="conversation-accordion">
            <?php foreach ($conversations as $conversation) :
                if (is_object($conversation)) {
                    $conversation = json_decode(wp_json_encode($conversation), true);
                } ?>
                <div class="conversation-item">
                    <div class="conversation-header">
                        <?php
                        // Translators: %s is the conversation ID.
                        echo esc_html(sprintf(__('Conversation ID: %s', 'bluebot-ai-powered-chatbot'), $conversation['conversation_id']));
                        ?>
                    </div>

                    <div class="conversation-body">
                        <div class="conversation-content">
                            <div class="conversation-messages" style="width: 70%; float: left;">
                                <h4><?php esc_html_e('Messages:', 'bluebot-ai-powered-chatbot'); ?></h4>
                                <?php
                                $messages = json_decode($conversation['conversation'], true);
                                $user_info = json_decode($conversation['user_data'], true);
                                $conversation_summary = json_decode($conversation['conversation_summary'], true);
                                if (!empty($messages)) {
                                    foreach ($messages as $message) {
                                        $message_class = $message['m_type'] === 'user' ? 'user' : 'bot';
                                        echo '<p class="' . esc_attr($message_class) . '"><strong>' . esc_html($message['m_type'] === 'user' ? __('User', 'bluebot-ai-powered-chatbot') : __('Bot', 'bluebot-ai-powered-chatbot')) . ':</strong> ' . esc_html($message['message']) . '</p>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="conversation-info" style="width: 30%; float: right;">
                                <p><strong><?php esc_html_e('Conversation ID:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation['conversation_id']); ?></p>
                                <p><strong><?php esc_html_e('Conversation Summary | User Issue:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation_summary['user_issue']); ?></p>
                                <p><strong><?php esc_html_e('Conversation Summary| Further Action Required:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation_summary['further_action_required']); ?></p>
                                <p><strong><?php esc_html_e('User ID:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation['user_id']); ?></p>
                                <p><strong><?php esc_html_e('User Name:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($user_info['name']); ?></p>
                                <p><strong><?php esc_html_e('User Email:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation['user_email']); ?></p>
                                <p><strong><?php esc_html_e('User Phone:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($user_info['phone_number']); ?></p>
                                <p><strong><?php esc_html_e('User Company Name:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($user_info['company_name']); ?></p>
                                <p><strong><?php esc_html_e('Other Info:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($user_info['other_info']); ?></p>
                                <p><strong><?php esc_html_e('Started At:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation['created_at']); ?></p>
                                <p><strong><?php esc_html_e('Updated At:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation['updated_at']); ?></p>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No conversations found.', 'bluebot-ai-powered-chatbot'); ?></p>
    <?php endif; ?>
</div>