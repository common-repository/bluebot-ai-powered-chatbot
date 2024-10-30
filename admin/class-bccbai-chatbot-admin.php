<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly 

require_once BCCBAI_CHATBOT_PLUGIN_DIR . 'includes/class-bccbai-chatbot-conversation-manager.php';

/**
 * The `BCCBAI_Chatbot_Admin` class handles the admin-specific functionality of the BCCBAI Chatbot plugin.
 * It is responsible for creating the admin menu, enqueuing admin scripts and styles, and rendering the content analysis page.
 * The content analysis page allows users to select pages for content analysis, save OpenAI API keys and edit summaries.
 * It also handles form submissions, verifies nonces, sanitizes input, and updates options in the WordPress database.
 * Additionally, it triggers the content analysis process using the OpenAI API, and saves chatbot CSS values.
 * The class retrieves saved options from the database and includes the content analysis page template.
 *
 * @since 1.0.0
 * 
 * @package BCCBAI_Chatbot
 * @subpackage BCCBAI_Chatbot/admin
 */
class BCCBAI_Chatbot_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));

        // Hook for handling content analysis form submission
        add_action('admin_post_bccbai_start_content_analysis', array($this, 'handle_start_content_analysis'));

        // Trigger auto content analysis if necessary
        add_action('admin_init', array($this, 'maybe_auto_generate_summary'));
    }


    public function add_admin_menu()
    {
        add_menu_page(
            'AI-Powered Chatbot',
            'AI Chatbot',
            'manage_options',
            'bccbai-chatbot',
            array($this, 'render_content_analysis_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'bccbai-chatbot',
            'Conversations',
            'Conversations',
            'manage_options',
            'bccbai-chatbot-conversations',
            array($this, 'render_conversations_page')
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        if ($hook == 'toplevel_page_bccbai-chatbot' || strpos($hook, 'bccbai-chatbot-conversations') !== false) {
            wp_enqueue_script('bccbai-chatbot-admin-script', BCCBAI_CHATBOT_PLUGIN_URL . 'assets/admin/js/bccbai-admin.js', array('jquery'), BCCBAI_CHATBOT_VERSION, true);
            wp_enqueue_style('bccbai-chatbot-admin-style', BCCBAI_CHATBOT_PLUGIN_URL . 'assets/admin/css/bccbai-admin.css', array(), BCCBAI_CHATBOT_VERSION);
            if (strpos($hook, 'bccbai-chatbot-conversations') !== false) {
                wp_enqueue_script('bccbai-chatbot-accordion-script', BCCBAI_CHATBOT_PLUGIN_URL . 'assets/admin/js/bccbai-accordion.js', array('jquery'), BCCBAI_CHATBOT_VERSION, true);
                wp_enqueue_style('bccbai-chatbot-accordion-style', BCCBAI_CHATBOT_PLUGIN_URL . 'assets/admin/css/bccbai-accordion.css', array(), BCCBAI_CHATBOT_VERSION);
            }
        }
    }

    public function register_settings()
    {
        // General Settings
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_openai_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => null
        ));
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_notifications_email', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => null
        ));

        // Chatbot CSS Settings
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_name', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Chatbot'
        ));

        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_css_background_color', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => null
        ));
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_css_text_color', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => null
        ));
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_css_bot_position', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => null
        ));

        // Content Analysis Settings
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_selected_pages', array(
            'type' => 'array',
            'sanitize_callback' => function ($input) {
                return array_map('sanitize_text_field', $input);
            },
            'default' => null
        ));
        register_setting('bccbai_chatbot_settings', 'bccbai_chatbot_summary', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => null
        ));

        // Add General Settings Section
        add_settings_section('bccbai_chatbot_general_settings', __('General Settings', 'bluebot-ai-powered-chatbot'), null, 'bccbai-chatbot');

        // Add General Settings Fields
        add_settings_field(
            'bccbai_chatbot_openai_api_key',
            __('OpenAI API Key', 'bluebot-ai-powered-chatbot'),
            array($this, 'openai_api_key_field_callback'),
            'bccbai-chatbot',
            'bccbai_chatbot_general_settings'
        );

        add_settings_field(
            'bccbai_chatbot_notifications_email',
            __('Send Notifications to this Email', 'bluebot-ai-powered-chatbot'),
            array($this, 'notifications_email_field_callback'),
            'bccbai-chatbot',
            'bccbai_chatbot_general_settings'
        );

        // Add CSS Settings Section
        add_settings_section('bccbai_chatbot_css_settings', __('Chatbot CSS Settings', 'bluebot-ai-powered-chatbot'), null, 'bccbai-chatbot-css');

        // Add CSS Fields
        add_settings_field(
            'bccbai_chatbot_name',
            __('Chatbot Name', 'bluebot-ai-powered-chatbot'),
            array($this, 'chatbot_name_field_callback'),
            'bccbai-chatbot-css',
            'bccbai_chatbot_css_settings'
        );

        add_settings_field(
            'bccbai_chatbot_css_background_color',
            __('Background Color', 'bluebot-ai-powered-chatbot'),
            array($this, 'css_background_color_field_callback'),
            'bccbai-chatbot-css',
            'bccbai_chatbot_css_settings'
        );

        add_settings_field(
            'bccbai_chatbot_css_text_color',
            __('Text Color', 'bluebot-ai-powered-chatbot'),
            array($this, 'css_text_color_field_callback'),
            'bccbai-chatbot-css',
            'bccbai_chatbot_css_settings'
        );

        add_settings_field(
            'bccbai_chatbot_css_bot_position',
            __('Bot Position', 'bluebot-ai-powered-chatbot'),
            array($this, 'css_bot_position_field_callback'),
            'bccbai-chatbot-css',
            'bccbai_chatbot_css_settings'
        );

        // Add Content Analysis Section
        add_settings_section('bccbai_chatbot_content_analysis', __('Content Analysis', 'bluebot-ai-powered-chatbot'), null, 'bccbai-chatbot-content');

        // Add Content Analysis Fields
        add_settings_field(
            'bccbai_chatbot_selected_pages',
            __('Select Pages to Analyze', 'bluebot-ai-powered-chatbot'),
            array($this, 'selected_pages_field_callback'),
            'bccbai-chatbot-content',
            'bccbai_chatbot_content_analysis'
        );

        add_settings_field(
            'bccbai_chatbot_summary',
            __('Generated Summary', 'bluebot-ai-powered-chatbot'),
            array($this, 'summary_field_callback'),
            'bccbai-chatbot-content',
            'bccbai_chatbot_content_analysis'
        );
    }

    // Field Callback Functions

    public function openai_api_key_field_callback()
    {
        $openai_api_key = get_option('bccbai_chatbot_openai_api_key');
        echo '<input type="password" name="bccbai_chatbot_openai_api_key" value="' . esc_attr($openai_api_key) . '" class="regular-text" />';
    }

    public function notifications_email_field_callback()
    {
        $email = get_option('bccbai_chatbot_notifications_email');
        echo '<input type="email" name="bccbai_chatbot_notifications_email" value="' . esc_attr($email) . '" class="regular-text" />';
    }

    public function selected_pages_field_callback()
    {
        $saved_selected_pages = get_option('bccbai_chatbot_selected_pages', array());
        $pages = get_pages(array('sort_column' => 'post_date', 'sort_order' => 'DESC'));

        if (empty($saved_selected_pages)) {
            $saved_selected_pages = array();
        }

        echo '<div class="bccbai-chatbot-page-columns">';
        $column_count = 0;
        foreach ($pages as $page) {
            $page_id = $page->ID;
            $page_title = $page->post_title;
            $checked = in_array($page_id, $saved_selected_pages) ? 'checked' : '';

            echo '<div class="bccbai-chatbot-page-column">';
            echo '<label>';
            echo '<input type="checkbox" name="bccbai_chatbot_selected_pages[]" value="' . esc_attr($page_id) . '" ' . esc_attr($checked) . '>';
            echo esc_html($page_title);
            echo '</label>';
            echo '</div>';

            $column_count++;
            if ($column_count % 3 === 0) {
                echo '<div class="bccbai-chatbot-page-column-break"></div>';
            }
        }
        echo '</div>';
    }

    public function summary_field_callback()
    {
        $saved_summary = get_option('bccbai_chatbot_summary');
        echo '<textarea name="bccbai_chatbot_summary" rows="10" cols="50" class="large-text">' . esc_textarea($saved_summary) . '</textarea>';
    }

    public function chatbot_name_field_callback()
    {
        $chatbot_name = get_option('bccbai_chatbot_name');
        echo '<input type="text" name="bccbai_chatbot_name" value="' . esc_attr($chatbot_name) . '" class="regular-text" />';
    }

    public function css_background_color_field_callback()
    {
        $background_color = get_option('bccbai_chatbot_css_background_color');
        echo '<input type="text" name="bccbai_chatbot_css_background_color" value="' . esc_attr($background_color) . '" class="regular-text" />';
    }

    public function css_text_color_field_callback()
    {
        $text_color = get_option('bccbai_chatbot_css_text_color');
        echo '<input type="text" name="bccbai_chatbot_css_text_color" value="' . esc_attr($text_color) . '" class="regular-text" />';
    }

    public function css_bot_position_field_callback()
    {
        $bot_position = get_option('bccbai_chatbot_css_bot_position');
        echo '<select name="bccbai_chatbot_css_bot_position" class="regular-text">';
        echo '<option value="bottom_right" ' . selected($bot_position, 'bottom_right', false) . '>Bottom Right</option>';
        echo '<option value="bottom_left" ' . selected($bot_position, 'bottom_left', false) . '>Bottom Left</option>';
        echo '</select>';
    }


    public function render_content_analysis_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

        // Start the form for settings
        echo '<form method="post" action="options.php">';

        // Output the settings fields for General Settings
        settings_fields('bccbai_chatbot_settings');
        do_settings_sections('bccbai-chatbot'); // General Settings

        // Output the settings fields for CSS Settings
        do_settings_sections('bccbai-chatbot-css'); // Chatbot CSS Settings

        // Output the settings fields for Content Analysis
        do_settings_sections('bccbai-chatbot-content'); // Content Analysis Settings

        // Submit button for all sections
        submit_button(__('Save Settings', 'bluebot-ai-powered-chatbot'));

        echo '</form>';

        // Start Content Analysis Form (Separate form to trigger the content analysis process)
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('bccbai_chatbot_content_analysis_start'); // Nonce for security
        echo '<input type="hidden" name="action" value="bccbai_start_content_analysis">'; // Action field to trigger the correct hook
        submit_button(esc_html__('Do Content Analysis', 'bluebot-ai-powered-chatbot'));
        echo '</form>';
        echo '</div>';
    }

    public function handle_start_content_analysis()
    {
        // Check the nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'bccbai_chatbot_content_analysis_start')) {
            wp_die(esc_html__('Invalid nonce specified', 'bluebot-ai-powered-chatbot'), esc_html__('Error', 'bluebot-ai-powered-chatbot'), array('response' => 403));
        }

        // Perform the content analysis process
        $saved_selected_pages = get_option('bccbai_chatbot_selected_pages', array());

        if (!empty($saved_selected_pages)) {
            // Trigger the content analysis process for the selected pages
            $content_analyzer = new BCCBAI_Chatbot_Content_Analyzer();
            $summary = $content_analyzer->analyze_content($saved_selected_pages);

            // Save the generated summary
            update_option('bccbai_chatbot_summary', $summary);

            // Redirect back to the settings page with a success message
            wp_redirect(add_query_arg('message', 'content_analysis_started', wp_get_referer()));
            exit;
        } else {
            // Redirect back with an error message
            wp_redirect(add_query_arg('message', 'no_pages_selected', wp_get_referer()));
            exit;
        }
    }

    public function maybe_auto_generate_summary()
    {
        $openai_api_key = get_option('bccbai_chatbot_openai_api_key');
        $chatbot_summary = get_option('bccbai_chatbot_summary');

        // Check if OpenAI API key is set and summary is empty
        if (!empty($openai_api_key) && empty($chatbot_summary)) {
            $saved_selected_pages = get_option('bccbai_chatbot_selected_pages', array());

            // If no pages selected, default to homepage
            if (empty($saved_selected_pages)) {
                $homepage_id = get_option('page_on_front'); // Get the homepage ID
                if ($homepage_id) {
                    $saved_selected_pages = array($homepage_id);
                }
            }

            // Trigger content analysis process
            if (!empty($saved_selected_pages)) {
                $content_analyzer = new BCCBAI_Chatbot_Content_Analyzer();
                $summary = $content_analyzer->analyze_content($saved_selected_pages);

                // Save the generated summary
                update_option('bccbai_chatbot_summary', $summary);
            }
        }
    }


    public function render_conversations_page()
    {
        $conversation_manager = new BCCBAI_Chatbot_Conversation_Manager();
        $conversations = $conversation_manager->get_all_conversations();
        include_once BCCBAI_CHATBOT_PLUGIN_DIR . 'admin/templates/conversations-listing.php';
    }
}

new BCCBAI_Chatbot_Admin();
