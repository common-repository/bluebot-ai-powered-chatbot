<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 
?>
<div class="bccbai-chatbot-toggler">
    <div class="bccbai-chatbot-status" id="bccbai-fl-tip" role="tooltip">
        <div class="bccbai-status-title" data-bccbai="title">We Are Online</div>
        <div class="bccbai-status-subtitle" data-bccbai="subtitle">How may I help you today?</div>
        <em id="bccbai-tip-close" class="bccbai-tooltip-close" role="button"></em>
    </div>
    <span class=""><img class="bccbai-chat-icons" src="<?php echo esc_url(BCCBAI_CHATBOT_PLUGIN_URL . 'assets/img/message-1.png'); ?>"></span>
    <span class=""><img class="bccbai-chat-icons" src="<?php echo esc_url(BCCBAI_CHATBOT_PLUGIN_URL . 'assets/img/cross-1.png'); ?>"></span>
</div>
<div class="bccbai-chatbot">
    <header>
        <div class="bccbai-header-chatbot-text"><?php echo esc_html($chatbot_name); ?></div>
        <span class="close-btn material-symbols-outlined">close</span>
    </header>
    <ul class="bccbai-chatbox">
        <li class="bccbai-chat incoming">
            <span class=""><img class="bccbai-bot-icon" src="<?php echo esc_url(BCCBAI_CHATBOT_PLUGIN_URL . 'assets/img/customer_service.png'); ?>"></span>
            <p>Hi there 👋<br>How can I help you?</p>
        </li>
    </ul>
    <div class="bccbai-chat-input">
        <textarea placeholder="Enter a message..." spellcheck="false" required></textarea>
        <span id="send-btn" class="material-symbols-rounded"><img style="height:25px; width:30px;" src="<?php echo esc_url(BCCBAI_CHATBOT_PLUGIN_URL . 'assets/img/send.png'); ?>"></span>
    </div>
</div>