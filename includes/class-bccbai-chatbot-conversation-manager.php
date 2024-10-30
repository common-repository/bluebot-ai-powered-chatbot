<?php

class BCCBAI_Chatbot_Conversation_Manager
{
    private $conversation_table;

    public function __construct()
    {
        global $wpdb;
        $this->conversation_table = esc_sql($wpdb->prefix . 'bccbai_chatbot_conversations');
    }

    public static function create_conversation_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = esc_sql($wpdb->prefix . 'bccbai_chatbot_conversations');
        // Check if the table already exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `conversation_id` VARCHAR(255) NOT NULL,
            `user_id` BIGINT(20) UNSIGNED,
            `user_email` VARCHAR(255),
            `user_data` TEXT,
            `conversation` LONGTEXT,
            `conversation_summary` TEXT,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            `last_email_sent` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `conversation_id` (`conversation_id`),
            INDEX `user_id` (`user_id`),
            INDEX `user_email` (`user_email`)
        ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }


    public function create_conversation()
    {
        global $wpdb;

        $conversation_id = wp_generate_uuid4();
        $current_time = current_time('mysql');
        $table_name = $this->conversation_table;
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'conversation_id' => $conversation_id,
                'created_at' => $current_time,
                'updated_at' => $current_time,
            )
        );

        if ($inserted === false) {
            // Log error or handle it accordingly
            error_log('Failed to insert conversation: ' . $wpdb->last_error);
        } else {
            // Schedule WP-Cron event for 1 hour and 20 minutes (4800 seconds) later
            if (!wp_next_scheduled('bccbai_check_inactive_conversation', array($conversation_id))) {
                wp_schedule_single_event(time() + 4800, 'bccbai_check_inactive_conversation', array($conversation_id));
            }
        }

        return $conversation_id;
    }



    public function get_conversation_history($conversation_id)
    {
        global $wpdb;
        $table_name = $this->conversation_table;
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT conversation FROM $table_name WHERE conversation_id = %s",
                $conversation_id
            ),
            ARRAY_A
        );

        return $result ? json_decode($result['conversation'], true) : array();
    }

    public function update_conversation_history($conversation_id, $new_entries)
    {
        global $wpdb;

        // Retrieve the current conversation history
        $conversation_history = $this->get_conversation_history($conversation_id);

        // Ensure $new_entries is an array of arrays
        if (is_array($new_entries) && !empty($new_entries)) {
            foreach ($new_entries as $entry) {
                if (isset($entry['m_type']) && isset($entry['message'])) {
                    $conversation_history[] = $entry;
                }
            }
        }

        // Update the conversation in the database
        $table_name = $this->conversation_table;
        $wpdb->update(
            $table_name,
            array(
                'conversation' => wp_json_encode($conversation_history),
                'updated_at' => current_time('mysql'),
            ),
            array('conversation_id' => $conversation_id)
        );
    }

    public function get_all_conversations()
    {
        global $wpdb;
        $table_name = $this->conversation_table;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table_name` ORDER BY created_at DESC", ARRAY_A));
    }


    public function check_and_send_email($conversation_id)
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'bccbai_chatbot_conversations');
        $conversation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE conversation_id = %s", $conversation_id));

        if ($conversation) {
            $last_interaction_time = strtotime($conversation->updated_at);
            $last_email_sent_time = $conversation->last_email_sent ? strtotime($conversation->last_email_sent) : 0;
            $current_time = time();
            // If conversation inactive for more than 5 minutes and no email sent in this inactive period
            if (($current_time - $last_interaction_time) > 300 && $last_interaction_time > $last_email_sent_time) {
                $this->extract_user_data($conversation_id);
                $this->send_conversation_email($conversation_id);
            }
        }
    }

    public function send_conversation_email($conversation_id)
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'bccbai_chatbot_conversations');

        // Retrieve conversation details
        $conversation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE conversation_id = %s", $conversation_id));

        if ($conversation) {
            $admin_email = empty(get_option('bccbai_chatbot_notifications_email')) ? get_option('admin_email') : get_option('bccbai_chatbot_notifications_email');
            $subject = 'New Chatbot Conversation';
            $conversation_content = json_decode($conversation->conversation, true);

            // Start output buffering to capture the email template
            ob_start();
            include(plugin_dir_path(__FILE__) . '../frontend/templates/admin-conversation-email.php');
            $message = ob_get_clean();

            // Set content type to HTML
            add_filter('wp_mail_content_type', function () {
                return 'text/html';
            });

            // Send email
            // do a filter that will return the funtion name which will send the email
            $send_email_function = apply_filters('bccbai_send_email_function', 'bccbai_wp_mail');

            if ($send_email_function($admin_email, $subject, $message)) {
                // Update conversation record to mark email as sent
                $wpdb->update($table_name, ['last_email_sent' => current_time('mysql')], ['conversation_id' => $conversation_id]);
            }

            // Reset content type to avoid conflicts
            remove_filter('wp_mail_content_type', function () {
                return 'text/html';
            });
        }
    }


    public function extract_user_data($conversation_id)
    {
        global $wpdb;
        $conversation_history = $this->get_conversation_history($conversation_id);
        $openai_class_name = apply_filters('bccbai_openai_class_name', 'BCCBAI_Chatbot_OpenAI');
        $openai = new $openai_class_name();
        $response = $openai->find_contact_info_from_conversation_history($conversation_history);

        $user_info = array();
        $email = '';
        $conversation_summary = '';

        if (isset($response['email'])) {
            $email = $response['email'];
        }
        if (isset($response['conversation_summary'])) {
            $conversation_summary = wp_json_encode($response['conversation_summary']);
        }
        if (isset($response['name'])) {
            $user_info['name'] = $response['name'];
        }
        if (isset($response['phone_number'])) {
            $user_info['phone_number'] = $response['phone_number'];
        }
        if (isset($response['company_name'])) {
            $user_info['company_name'] = $response['company_name'];
        }
        if (isset($response['other_info'])) {
            $user_info['other_info'] = $response['other_info'];
        }

        // Update the conversation in the database
        $table_name = $this->conversation_table;
        $wpdb->update(
            $table_name,
            array(
                'user_email' => $email,
                'user_data' => wp_json_encode($user_info),
                'conversation_summary' => $conversation_summary,
            ),
            array('conversation_id' => $conversation_id)
        );
    }
}
