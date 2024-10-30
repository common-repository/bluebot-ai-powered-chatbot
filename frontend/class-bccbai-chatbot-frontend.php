<?php

class BCCBAI_Chatbot_Frontend
{

    public function init()
    {
        // Register shortcode
        add_shortcode('bccbai_chatbot', array($this, 'render_chatbot'));
    }

    public function render_chatbot()
    {
        // Render chatbot widget
        $chatbot_name = get_option('bccbai_chatbot_name');
        if (empty($chatbot_name)) {
            $chatbot_name = 'Chatbot';
        }

        include BCCBAI_CHATBOT_PLUGIN_DIR . 'frontend/templates/chatbot-widget.php';
        $this->enqueue_scripts();
        $this->custom_css();
    }


    public function enqueue_scripts()
    {
        // Enqueue scripts and styles
        if (apply_filters('bccbai_chatbot_load_js', true)) {
            wp_enqueue_script('bccbai-chatbot-script', BCCBAI_CHATBOT_PLUGIN_URL . 'assets/js/bccbai-chatbot.js', array('jquery'), '1728240727940', true);
        }
        wp_enqueue_style('bccbai-chatbot-style', BCCBAI_CHATBOT_PLUGIN_URL . 'assets/css/bccbai-chatbot.css', array(), '1728240727940');

        wp_nonce_tick();

        // Localize script with variables
        $localized_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            //'nonce' => wp_create_nonce('wp_rest'),
            'api_url' => get_site_url() . '/wp-json',
            'site_url' => get_site_url(),
            'plugin_url' => BCCBAI_CHATBOT_PLUGIN_URL,
            // Add more variables as needed
        );
        wp_localize_script('bccbai-chatbot-script', 'bccbai_chatbot_vars', $localized_data);
    }

    // write function to output custom CSS from the plugin settings
    private function custom_css()
    {
        $chatbot_css_background_color = get_option('bccbai_chatbot_css_background_color');
        if (empty($chatbot_css_background_color)) {
            $chatbot_css_background_color = '#724ae8';
        }
        $chatbot_css_background_color_hover = $chatbot_css_background_color . 'cc';

        $chatbot_css_text_color = get_option('bccbai_chatbot_css_text_color');
        if (empty($chatbot_css_text_color)) {
            $chatbot_css_text_color = '#ffffff';
        }

        $chatbot_css_bot_position = get_option('bccbai_chatbot_css_bot_position');
        if (empty($chatbot_css_bot_position)) {
            $chatbot_css_bot_position = 'bottom_right';
        }
        if ($chatbot_css_bot_position == 'bottom_right') {
            $chatbot_css_bot_position = 'right';
        } else {
            $chatbot_css_bot_position = 'left';
        }


        $custom_css = "
            :root {
                --bccbai-chatbot-background-color: {$chatbot_css_background_color};
                --bccbai-chatbot-background-color-hover: {$chatbot_css_background_color_hover};
                --bccbai-chatbot-text-color: {$chatbot_css_text_color};
            }
            .bccbai-chatbot-toggler {
                {$chatbot_css_bot_position} : 35px;
            }
            .bccbai-chatbot {
                {$chatbot_css_bot_position} : 35px;
            }
            .bccbai-chatbot-status{
                {$chatbot_css_bot_position} : calc(100% + 10px);
            }
            .bccbai-chatbot-status:before{
                {$chatbot_css_bot_position} : -5px;
            }
            @media (max-width: 490px) {
                .bccbai-chatbot {
                right: 10px;
                left: 10px;
                } 
                .bccbai-chatbot-toggler {
                {$chatbot_css_bot_position} : 20px;
                }
            }
        ";
        wp_add_inline_style('bccbai-chatbot-style', esc_html($custom_css));
    }

    // Other frontend-related functionality
    // ...
}
