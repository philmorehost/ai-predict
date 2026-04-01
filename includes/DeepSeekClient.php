<?php

class DeepSeekClient {
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $baseUrl = "https://api.deepseek.com/") {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function listModels() {
        $ch = curl_init($this->baseUrl . "models");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($response, true);

        // Map DeepSeek models to a format similar to Gemini for the UI
        $models = [];
        if (isset($decoded['data'])) {
            foreach ($decoded['data'] as $model) {
                $models[] = [
                    'name' => $model['id'],
                    'displayName' => strtoupper($model['id'])
                ];
            }
        }
        return $models;
    }

    private function callApi($payload) {
        $ch = curl_init($this->baseUrl . "chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new Exception("CURL Error: " . $err);
        }

        $decoded = json_decode($response, true);
        if ($httpCode !== 200) {
            $errorMsg = $decoded['error']['message'] ?? 'Unknown DeepSeek API Error';
            throw new Exception("DeepSeek API Error ($httpCode): " . $errorMsg);
        }

        return $decoded;
    }

    public function getTeamSuggestions($input, $model = "deepseek-chat") {
        $season = getCurrentSeasonRange();
        $payload = [
            "model" => $model,
            "messages" => [
                ["role" => "system", "content" => "You are a football data expert. Return only JSON."],
                ["role" => "user", "content" => "Predict the professional football team the user is typing: \"$input\".
                    Return the top 5 most likely matches for the $season season.
                    Format: [{\"name\": \"...\", \"league\": \"...\", \"country\": \"...\"}]"]
            ],
            "response_format" => ["type" => "json_object"]
        ];

        try {
            $response = $this->callApi($payload);
            $content = $response['choices'][0]['message']['content'];
            $data = json_decode($content, true);
            return $data['teams'] ?? $data; // Handle various JSON structures
        } catch (Exception $e) {
            error_log("DeepSeek Suggestion Error: " . $e->getMessage());
        }
        return [];
    }

    public function getPrediction($home, $away, $model = "deepseek-chat") {
        $season = getCurrentSeasonRange();
        $payload = [
            "model" => $model,
            "messages" => [
                ["role" => "system", "content" => "You are a high-precision football tactical analyst. Your objective is 100% analytical accuracy using standard betting notation (1, X, 2). Return only JSON."],
                ["role" => "user", "content" => "Perform an extremely detailed tactical analysis for $home (Home) vs $away (Away) for the $season season.

                    GUIDELINES:
                    - mainPrediction must use notation: Home (1), Draw (X), or Away (2).
                    - mainProbability should reflect peak analytical confidence (1-100).
                    - reasoning must be a multi-paragraph deep dive into squad depth, tactical setups, and seasonal trends.

                    Provide JSON with these keys:
                    - mainPrediction: STRING (e.g., 'Home (1)')
                    - mainProbability: NUMBER
                    - overUnderPrediction: STRING (Over/Under 2.5)
                    - overUnderProbability: NUMBER
                    - reasoning: STRING (Highly detailed)
                    - expectedScore: STRING (e.g., '2-1')
                    - keyStats: ARRAY of STRINGS (At least 5)"]
            ],
            "response_format" => ["type" => "json_object"]
        ];

        $response = $this->callApi($payload);
        $content = $response['choices'][0]['message']['content'];
        return json_decode($content, true);
    }
}
?>
