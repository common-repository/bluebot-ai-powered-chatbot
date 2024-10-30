<?php

/**
 * The `BCCBAI_Chatbot_OpenAI` class is responsible for interacting with the OpenAI API.
 * It uses the API key and API URL to make requests to the OpenAI API and generate summaries based on the content provided.
 *
 * The `make_api_request` method is a private method used to make requests to the OpenAI API. It accepts a prompt string as input, creates a request body, and sends a POST request to the OpenAI API. The response from the API is then returned.
 *
 * @since 1.0.0
 * 
 * @package BCCBAI_Chatbot
 * @subpackage BCCBAI_Chatbot/openai
 */
class BCCBAI_Chatbot_OpenAI
{
    private $api_key;
    private $api_url;
    public $api_log_id;

    public function __construct()
    {
        $this->api_key = get_option('bccbai_chatbot_openai_api_key');
        $this->api_url = 'https://api.openai.com/v1/chat/completions';
    }


    public function improve_website_content($preprocessed_content)
    {
        $system_prompt = "
            You are an AI assistant specialized in content optimization for further processing.
            ### Instructions:
            1. Read the provided preprocessed website content.
            2. Enhance the content by removing repeated information while preserving all key details.
            3. Ensure the content is well-structured and suitable for further AI processing.
            4. Always capture contact information, key services/products details, business timings and pricing details in the improved content.
            5. Must add all information/points from the preprocessed website content into improved content.
        ";


        $user_prompt = "
            Here is the preprocessed website content delimited by ### :

            ###
            {$preprocessed_content}
            ###

            Based on this content, enhance it by removing repeated information and preserving all key details. Ensure that generated content is well-structured and suitable for further AI processing.
            Include contact information, all services/products details, business timings and pricing details in the improved content. Add all details/facts from the preprocessed website content into improved content.
            Improved Content:
        ";

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            ),
            array(
                'role' => 'user',
                'content' => $user_prompt,
            ),
        );

        $response = $this->make_api_request($messages, 'improve_website_content', 'gpt-3.5-turbo-0125', 0.3);
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }
        throw new Exception('Invalid API response - ' . wp_json_encode($response));
    }


    public function generate_chat_response($message, $conversation_history, $website_summary)
    {
        if ($this->api_key === '' || strlen($this->api_key) < 6) {
            return 'Please enter a valid OpenAI API key in the plugin settings.';
        }

        $system_prompt = "
        You are an agent for the website that respond to user messages live on website. Your task is to generate appropriate responses based on conversation history and the website summary.
            ### Instructions:
            1. Read the provided website summary thoroughly.
            2. Generate responses to user queries based on the website summary and the conversation history.
            3. Ensure the responses are concise, clear, helpful and in chat format.
            4. If the answer is not available, request the user's contact information for follow-up.
            5. Ask user questions to clarify their queries and provide accurate information.
            6. Ask user their name early in the conversation and use it to personalize responses.
            7. Always prompt the user to provide their contact information like email, phone number and other info for further assistance where applicable.
            8. If the user asks for specific details, provide accurate and concise information based on the website summary.
            ";

        $user_prompt = "The following is the summary of the website content delimited by ### :
                ###
                {$website_summary}
                ###

                Based on this summary, generate appropriate responses to the user's queries.
                ";

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            ),
            array(
                'role' => 'user',
                'content' => $user_prompt,
            ),
        );

        // Add the conversation history to the messages array
        foreach ($conversation_history as $entry) {
            if ($entry['m_type'] === 'user') {
                $messages[] = array(
                    'role' => 'user',
                    'content' => $entry['message'],
                );
            } elseif ($entry['m_type'] === 'bot') {
                $messages[] = array(
                    'role' => 'assistant',
                    'content' => $entry['message'],
                );
            }
        }

        // Add the current user message
        $messages[] = array(
            'role' => 'user',
            'content' => $message,
        );

        // Make API request
        $response = $this->make_api_request($messages, 'generate_chat_response');

        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }

        return '';
    }


    public function find_contact_info_from_conversation_history($conversation_history)
    {
        if ($this->api_key === '' || strlen($this->api_key) < 6) {
            return wp_json_encode(['error' => 'Please enter a valid OpenAI API key in the plugin settings.']);
        }

        // Create a single string from conversation history
        $history_string = '';
        foreach ($conversation_history as $entry) {
            if ($entry['m_type'] === 'user') {
                $history_string .= "User: {$entry['message']}\n";
            } elseif ($entry['m_type'] === 'bot') {
                $history_string .= "Bot: {$entry['message']}\n";
            }
        }

        // OpenAI prompt
        $system_prompt = "
        You are an intelligent assistant. Your task is to extract personal contact information and generate a summary of the conversation from the provided conversation history.
        Extract the following information if available:
        - Name
        - Email address
        - Phone number
        - Company name
        - Other personal information
        
        Additionally, generate a small summary of the conversation, including:
        - What is the user's issue?
        - Was the issue resolved?
        - What further action is required?
        
        Provide the output in JSON format with the keys: 'name', 'email', 'phone_number', 'company_name', 'other_info', and 'conversation_summary'. 
        conversation_summary keys - 'user_issue', 'issue_resolved', 'further_action_required'.
        ";

        $user_prompt = "
        The following is the conversation history delimited by ### :
        ###
        {$history_string}
        ###
        
        Please extract the required information and generate the summary as instructed.
        ";

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            ),
            array(
                'role' => 'user',
                'content' => $user_prompt,
            ),
        );

        // Make API request
        $response = $this->make_api_request($messages, 'find_contact_info_from_conversation_history');

        // Parse and return response
        if (isset($response['choices'][0]['message']['content'])) {
            $contact_info_summary = trim($response['choices'][0]['message']['content']);
            return json_decode($contact_info_summary, true);
        }

        return wp_json_encode(['error' => 'No contact information or summary found.']);
    }



    private function make_api_request($messages, $function_name, $model = 'gpt-3.5-turbo-0125', $temperature = 0.7, $n = 1)
    {
        $request_body = array(
            'model' => $model,
            'messages' => $messages,
            'temperature' =>  $temperature,
            'n' => $n,
        );

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => wp_json_encode($request_body),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            // Handle error
            return array();
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        // Extract text response if available
        $response_text = isset($response_data['choices'][0]['message']['content']) ? $response_data['choices'][0]['message']['content'] : '';

        // Log the interaction
        $this->log_api_interaction($function_name, $messages, $response_data, $response_text, $model, $temperature, $n);

        return $response_data;
    }

    private function log_api_interaction($function_name, $request_messages, $response_data, $response_text, $model, $temperature, $n)
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'bccbai_chatbot_api_logs');

        $data = array(
            'function_name' => $function_name,
            'request' => wp_json_encode($request_messages),
            'response' => wp_json_encode($response_data),
            'response_text' => $response_text,
            'time' => current_time('mysql'),
            'tokens_used' => isset($response_data['usage']['total_tokens']) ? $response_data['usage']['total_tokens'] : 0,
            'model' => $model,
            'temperature' => $temperature,
            'n' => $n,
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%d');

        // how to insert data into the table and get row id
        $wpdb->insert($table_name, $data, $format);
        $this->api_log_id = $wpdb->insert_id;
    }


    public static function bccbai_chatbot_create_log_table()
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'bccbai_chatbot_api_logs');

        // Check if the table already exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                function_name varchar(255) NOT NULL,
                request longtext NOT NULL,
                response longtext NOT NULL,
                response_text longtext NOT NULL,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                tokens_used int NOT NULL,
                model varchar(50) NOT NULL,
                temperature float NOT NULL,
                n int NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}
