<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?php esc_html_e('New Chatbot Conversation', 'bluebot-ai-powered-chatbot'); ?></title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; background-color: #f9f9f9; margin: 0; padding: 20px;">
    <div class="container" style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div class="header" style="background-color: #0073aa; color: #ffffff; padding: 10px; text-align: center; border-bottom: 1px solid #005177; border-radius: 5px 5px 0 0;">
            <h2><?php esc_html_e('New Chatbot Conversation', 'bluebot-ai-powered-chatbot'); ?></h2>
        </div>
        <div class="content" style="margin: 20px 0;">
            <?php if (!empty($conversation->user_data)) : ?>
                <p><strong><?php esc_html_e('User Info:', 'bluebot-ai-powered-chatbot'); ?></strong></p>
                <ul style="list-style-type: none; padding: 0;">
                    <?php foreach (json_decode($conversation->user_data, true) as $key => $value) : ?>
                        <li style="background-color: #f4f4f4; margin: 5px 0; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                            <strong style="color: #0073aa;"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo esc_html($value); ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!empty($conversation->user_email)) : ?>
                        <li style="background-color: #f4f4f4; margin: 5px 0; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                            <strong style="color: #0073aa;"><?php esc_html_e('Email:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation->user_email); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($conversation->conversation_summary)) : ?>
                <?php $summary = json_decode($conversation->conversation_summary, true); ?>
                <p><strong><?php esc_html_e('Conversation Summary:', 'bluebot-ai-powered-chatbot'); ?></strong></p>
                <ul style="list-style-type: none; padding: 0;">
                    <li style="background-color: #f4f4f4; margin: 5px 0; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                        <strong style="color: #0073aa;"><?php esc_html_e('User Issue:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($summary['user_issue']); ?>
                    </li>
                    <li style="background-color: #f4f4f4; margin: 5px 0; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                        <strong style="color: #0073aa;"><?php esc_html_e('Issue Resolved:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($summary['issue_resolved']); ?>
                    </li>
                    <li style="background-color: #f4f4f4; margin: 5px 0; padding: 10px; border-radius: 3px; border: 1px solid #ddd;">
                        <strong style="color: #0073aa;"><?php esc_html_e('Further Action Required:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($summary['further_action_required']); ?>
                    </li>
                </ul>
            <?php endif; ?>
            <p><strong><?php esc_html_e('Conversation:', 'bluebot-ai-powered-chatbot'); ?></strong></p>
            <ul style="list-style-type: none; padding: 0;">
                <?php foreach ($conversation_content as $message) : ?>
                    <li class="<?php echo esc_attr($message['m_type'] == 'user' ? 'message user' : 'message'); ?>" style="background-color: <?php echo esc_attr($message['m_type'] === 'user' ? '#e0f7fa' : '#f4f4f4'); ?>; margin: 5px 0; padding: 10px; border-radius: 3px; border: 1px solid #ddd; text-align: <?php echo esc_attr($message['m_type'] === 'user' ? 'right' : 'left'); ?>;">
                        <strong><?php echo esc_html($message['m_type'] === 'user' ? __('User', 'bluebot-ai-powered-chatbot') : __('Bot', 'bluebot-ai-powered-chatbot')); ?>:</strong> <?php echo esc_html($message['message']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="meta" style="margin-top: 20px; font-size: 14px; color: #555;">
            <p><strong><?php esc_html_e('Conversation ID:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html($conversation->conversation_id); ?></p>
            <p><strong><?php esc_html_e('Created At:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html(date_i18n('j F, Y h:i:s', strtotime(get_date_from_gmt($conversation->created_at)), true)); ?></p>
            <p><strong><?php esc_html_e('Updated At:', 'bluebot-ai-powered-chatbot'); ?></strong> <?php echo esc_html(date_i18n('j F, Y h:i:s', strtotime(get_date_from_gmt($conversation->updated_at)), true)); ?></p>
        </div>
        <div class="footer" style="text-align: center; font-size: 12px; color: #777; margin-top: 20px;">
            <p><?php esc_html_e('This email was generated by the Chatbot plugin.', 'bluebot-ai-powered-chatbot'); ?></p>
        </div>
    </div>
</body>

</html>