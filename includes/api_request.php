<?php

class makeRequest
{
    public function __construct()
    {
        $this->call();
    }

    private function call()
    {
        check_ajax_referer('chatbot_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Unauthorized access. Please log in.']);
        }

        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $threadId = isset($_POST['thread_id']) ? sanitize_text_field($_POST['thread_id']) : '';

        if (empty($question)) {
            wp_send_json_error(['message' => 'Question cannot be empty.']);
        }

        $userId = get_current_user_id(); // Get the current user's ID

        // Use thread ID from POST if available; otherwise, check user meta (you can use user_id as part of the thread ID)
        if (!$threadId) {
            $threadId = get_user_meta($userId, 'chatbot_thread_id', true);
        }

        if (!$threadId) {
            // Create a new thread for the user
            $makeCall = new curlRequest('threads', 'POST', []);
            $response = $makeCall->doCall();

            if (!isset($response['id'])) {
                wp_send_json_error(['message' => 'Failed to create thread.']);
            }

            $threadId = $response['id'];
            update_user_meta($userId, 'chatbot_thread_id', $threadId); // Store the thread ID in user meta
        }

        // Send Message
        $makeCall = new curlRequest("threads/$threadId/messages", 'POST', [
            'role' => 'user',
            'content' => $question
        ]);
        $messageResponse = $makeCall->doCall();

        if (!isset($messageResponse['id'])) {
            wp_send_json_error(['message' => 'Failed to send message.']);
        }

        // Run Assistant
        $makeCall = new curlRequest("threads/$threadId/runs", 'POST', [
            'assistant_id' => ''
        ]);
        $runResponse = $makeCall->doCall();

        if (!isset($runResponse['id'])) {
            wp_send_json_error(['message' => 'Failed to start assistant.']);
        }
        $runId = $runResponse['id'];

        // Poll for Completion with Timeout
        $attempts = 0;
        $maxAttempts = 10; // Prevent infinite loops

        do {
            sleep(2); // Wait for 2 second before polling again
            $makeCall = new curlRequest("threads/$threadId/runs/$runId", 'GET');
            $runStatus = $makeCall->doCall();
            $attempts++;
        } while (!in_array($runStatus['status'], ['completed', 'failed']) && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts || $runStatus['status'] === 'failed') {
            wp_send_json_error(['message' => 'Assistant run failed or timed out.']);
        }

        // Retrieve Messages
        $makeCall = new curlRequest("threads/$threadId/messages", 'GET');

        $finalResponse = $makeCall->doCall();
        if (!isset($finalResponse['data'][0]['content'][0]['text']['value'])) {
            wp_send_json_error(['message' => 'Invalid response from Assistant.']);
        }

        $answer = $finalResponse['data'][0]['content'][0]['text']['value'];
        wp_send_json_success(['answer' => $answer, 'thread_id' => $threadId]);
    }
}
