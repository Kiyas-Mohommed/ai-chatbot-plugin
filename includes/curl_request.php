<?php

class curlRequest
{
    private $apiKey = '';
    private $endpoint;
    private $method;
    private $data;

    public function __construct($endpoint, $method = 'POST', $data = [])
    {
        $this->endpoint = $endpoint;
        $this->method = $method;
        $this->data = $data;
    }

    public function doCall()
    {
        return $this->openai_request();
    }

    private function openai_request()
    {
        $apiKey = $this->apiKey;

        if (!$apiKey) {
            die("Error: Missing API Key.");
        }

        $curl = curl_init();

        $headers = [
            'OpenAI-Beta: assistants=v2',
            "Authorization: Bearer $apiKey",
            'Content-Type: application/json'
        ];

        $options = [
            CURLOPT_URL => "https://api.openai.com/v1/$this->endpoint",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_HTTPHEADER => $headers
        ];

        if (!empty($this->data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($this->data);
        }

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            die('Curl error: ' . curl_error($curl));
        }

        curl_close($curl);
        return json_decode($response, true);
    }
}
