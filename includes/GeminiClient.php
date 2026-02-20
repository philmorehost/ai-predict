<?php

class GeminiClient {
    private $apiKey;
    private $baseUrl = "https://generativelanguage.googleapis.com/v1beta/";

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function listModels() {
        $endpoint = $this->baseUrl . "models?key=" . $this->apiKey;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($response, true);
        return $decoded['models'] ?? [];
    }

    private function callApi($model, $payload, $stream = false) {
        // Ensure model name is properly formatted (models/name)
        if (strpos($model, 'models/') !== 0) {
            $model = 'models/' . $model;
        }
        $endpoint = $this->baseUrl . $model . ":generateContent?key=" . $this->apiKey;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For compatibility on some servers

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new Exception("CURL Error: " . $err);
        }

        $decoded = json_decode($response, true);
        if ($httpCode !== 200) {
            $errorMsg = $decoded['error']['message'] ?? 'Unknown API Error';
            throw new Exception("Gemini API Error ($httpCode): " . $errorMsg);
        }

        return $decoded;
    }

    public function getTeamSuggestions($input, $model = "gemini-1.5-flash") {
        $season = getCurrentSeasonRange();

        $payload = [
            "contents" => [[
                "parts" => [[
                    "text" => "Predict the professional football team the user is typing: \"$input\".
                    Return the top 5 most likely matches.
                    DATA ACCURACY: Return the league the team is playing in for the $season season.
                    CRITICAL: For $season, verify promotion/relegation.
                    Example: If a team was promoted to the Premier League for $season, list 'Premier League'.
                    Return results as JSON."
                ]]
            ]],
            "generationConfig" => [
                "responseMimeType" => "application/json",
                "responseSchema" => [
                    "type" => "array",
                    "items" => [
                        "type" => "object",
                        "properties" => [
                            "name" => ["type" => "string"],
                            "league" => ["type" => "string"],
                            "country" => ["type" => "string"]
                        ],
                        "required" => ["name", "league", "country"]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->callApi($model, $payload);

            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                return json_decode($response['candidates'][0]['content']['parts'][0]['text'], true);
            }
        } catch (Exception $e) {
            error_log("Gemini Suggestion Error: " . $e->getMessage());
        }

        return [];
    }

    public function getPrediction($home, $away, $model = "gemini-1.5-pro") {
        $season = getCurrentSeasonRange();

        $payload = [
            "contents" => [[
                "parts" => [[
                    "text" => "Perform a high-precision tactical analysis for $home (Home) vs $away (Away) for the $season season.
                    Your goal is maximum accuracy based on current squad data, recent form, and historical trends.

                    OUTPUT FORMATTING:
                    - Use standard betting notation for the main outcome: Home (1), Draw (X), or Away (2).
                    - Example: 'Home (1)' or 'Draw (X)' or 'Away (2)'.

                    CRITICAL PROBABILITY GUIDELINES:
                    - Provide a probability percentage from 1 to 100.
                    - 90%+ indicates extreme confidence.
                    - Use whole numbers.

                    Provide a comprehensive report as JSON:
                    1. mainPrediction: The outcome using (1), (X), or (2) notation.
                    2. mainProbability: Confidence level (1-100).
                    3. overUnderPrediction: Over/Under 2.5 goals forecast.
                    4. overUnderProbability: Confidence level (1-100).
                    5. reasoning: Extremely detailed tactical breakdown, including key player match-ups, expected formations, and managerial strategies for $season.
                    6. expectedScore: The most likely final scoreline.
                    7. keyStats: At least 5 critical match statistics supporting your analysis."
                ]]
            ]],
            "generationConfig" => [
                "responseMimeType" => "application/json",
                "responseSchema" => [
                    "type" => "object",
                    "properties" => [
                        "mainPrediction" => ["type" => "string"],
                        "mainProbability" => ["type" => "number"],
                        "overUnderPrediction" => ["type" => "string"],
                        "overUnderProbability" => ["type" => "number"],
                        "reasoning" => ["type" => "string"],
                        "expectedScore" => ["type" => "string"],
                        "keyStats" => [
                            "type" => "array",
                            "items" => ["type" => "string"]
                        ]
                    ],
                    "required" => ["mainPrediction", "mainProbability", "overUnderPrediction", "overUnderProbability", "reasoning", "expectedScore", "keyStats"]
                ]
            ]
        ];

        $response = $this->callApi($model, $payload);

        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return json_decode($response['candidates'][0]['content']['parts'][0]['text'], true);
        }

        if (isset($response['promptFeedback']['blockReason'])) {
            throw new Exception("Prediction blocked by AI Safety filters: " . $response['promptFeedback']['blockReason']);
        }

        throw new Exception("Failed to generate prediction. The AI did not return the expected format.");
    }
}
?>
