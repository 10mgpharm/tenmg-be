<?php

namespace App\Services\Credit;

use App\Models\LenderMatch;
use Gemini\Data\Content;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoCreditWorthinessService
{
    /**
     * Fetch credit history from Mono API
     */
    public function fetchCreditHistory(string $bvn, string $provider = 'crc'): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v3/lookup/credit-history/{$provider}";

            Log::info('Mono Credit History API Request', [
                'url' => $url,
                'provider' => $provider,
                'bvn_masked' => substr($bvn, 0, 3).'*****'.substr($bvn, -3),
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, [
                'bvn' => $bvn,
            ]);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // Try to parse JSON, but handle plain text responses
            $responseData = null;
            $isJson = false;

            try {
                $responseData = $response->json();
                $isJson = true;
            } catch (\Exception $e) {
                // Response is not JSON, might be plain text
                $responseData = $responseBody;
            }

            // Log full response for debugging
            Log::info('Mono Credit History API Response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'is_json' => $isJson,
                'response_data' => $responseData,
            ]);

            // Handle non-JSON responses (like "Mono is Live!" health check)
            if (! $isJson || ! is_array($responseData)) {
                Log::warning('Mono API returned non-JSON response', [
                    'response_type' => gettype($responseData),
                    'raw_body' => $responseBody,
                    'status_code' => $statusCode,
                ]);

                // Check if it's a health check response
                if (is_string($responseBody) && stripos($responseBody, 'mono') !== false) {
                    return [
                        'success' => false,
                        'error' => 'Mono API returned unexpected response. This may indicate an incorrect endpoint URL or API configuration issue.',
                        'status_code' => $statusCode,
                        'raw_response' => $responseBody,
                        'hint' => 'Please verify the API endpoint URL and ensure you are using the correct Mono API version and credentials.',
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Invalid response format from Mono API - expected JSON but received: '.substr($responseBody, 0, 100),
                    'status_code' => $statusCode,
                    'response_type' => gettype($responseData),
                    'raw_response' => $responseBody,
                ];
            }

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to fetch credit history from Mono API';

                Log::error('Mono Credit History API failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'full_response' => $responseData,
                    'raw_body' => $responseBody,
                    'bvn_masked' => substr($bvn, 0, 3).'*****'.substr($bvn, -3),
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                    'response' => $responseData,
                    'raw_response' => $responseBody,
                ];
            }

            // Check for successful status
            if (! isset($responseData['status']) || $responseData['status'] !== 'successful') {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Invalid response from Mono API';

                Log::warning('Mono API returned unsuccessful status', [
                    'status' => $responseData['status'] ?? 'missing',
                    'message' => $errorMessage,
                    'full_response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status' => $responseData['status'] ?? null,
                    'response' => $responseData,
                    'raw_response' => $responseBody,
                ];
            }

            // Validate data exists
            if (! isset($responseData['data'])) {
                Log::warning('Mono API response missing data field', [
                    'response_keys' => array_keys($responseData),
                    'full_response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'error' => 'Mono API response missing data field',
                    'response' => $responseData,
                    'raw_response' => $responseBody,
                ];
            }

            return [
                'success' => true,
                'data' => $responseData['data'],
            ];

        } catch (\Exception $e) {
            Log::error('Mono Credit History API exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
            ];
        }
    }

    /**
     * Analyze Mono credit worthiness data using Gemini AI
     */
    public function analyzeMonoCreditWorthiness(array $monoData, ?string $borrowerReference = null): array
    {
        try {
            // REQUIRED: borrower_reference must be provided
            if (! $borrowerReference) {
                return [
                    'success' => false,
                    'error' => 'borrower_reference is required. Please provide a valid borrower reference.',
                ];
            }

            // Fetch lender match - MANDATORY, process stops if not found
            $lenderMatch = LenderMatch::where('borrower_reference', $borrowerReference)->first();

            if (! $lenderMatch) {
                return [
                    'success' => false,
                    'error' => 'No lender match found for borrower_reference. Please ensure a loan simulation has been completed first.',
                    'borrower_reference' => $borrowerReference,
                ];
            }

            // Load lender and its settings
            $lenderMatch->load('lender.lenderSetting');
            $requestedAmount = (float) $lenderMatch->amount;
            $lenderInstruction = $lenderMatch->lender->lenderSetting->instruction ?? null;

            // Format Mono data for analysis
            $formattedData = $this->formatMonoData($monoData);

            // Generate system prompt with requested amount and lender instruction
            $systemPrompt = $this->generateSystemPrompt($requestedAmount, $lenderInstruction);

            // Build user prompt with Mono data
            $userPrompt = $this->buildUserPrompt($formattedData);

            // Create Content object for system instruction
            $systemInstruction = Content::parse($systemPrompt);

            // Call Gemini API with system prompt
            $response = Gemini::generativeModel(model: 'gemini-2.0-flash')
                ->withSystemInstruction($systemInstruction)
                ->generateContent($userPrompt);

            // Parse Gemini response
            $rawText = $response->text();
            $analysis = $this->parseGeminiResponse($rawText);

            // Clean raw response for better formatting (remove markdown if present)
            $cleanedRawResponse = preg_replace('/```json\s*/i', '', $rawText);
            $cleanedRawResponse = preg_replace('/```\s*/', '', $cleanedRawResponse);
            $cleanedRawResponse = trim($cleanedRawResponse);

            return [
                'success' => true,
                'analysis' => $analysis,
                'raw_response' => $cleanedRawResponse,
                'mono_data' => $formattedData,
                'borrower_reference' => $borrowerReference,
                'requested_amount' => $requestedAmount,
                'lender_instruction' => $lenderInstruction,
            ];

        } catch (\Exception $e) {
            Log::error('Mono credit worthiness Gemini analysis failed', [
                'error' => $e->getMessage(),
                'borrower_reference' => $borrowerReference,
                'mono_data' => $monoData,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to analyze credit worthiness',
                'borrower_reference' => $borrowerReference,
            ];
        }
    }

    /**
     * Format Mono credit history data for analysis
     */
    private function formatMonoData(array $monoData): array
    {
        $data = $monoData['data'] ?? $monoData;

        $profile = $data['profile'] ?? [];
        $creditHistory = $data['credit_history'] ?? [];
        $providers = $data['providers'] ?? [];

        // Calculate metrics from credit history
        $totalLoans = 0;
        $activeLoans = 0;
        $closedLoans = 0;
        $totalLoanAmount = 0;
        $totalRepaymentAmount = 0;
        $performingLoans = 0;
        $nonPerformingLoans = 0;
        $institutions = [];
        $repaymentScheduleStatus = [
            'completed' => 0,
            'paid' => 0,
            'pending' => 0,
            'overdue' => 0,
        ];

        foreach ($creditHistory as $institutionData) {
            $institution = $institutionData['institution'] ?? 'Unknown';
            $history = $institutionData['history'] ?? [];

            foreach ($history as $loan) {
                $totalLoans++;
                $loanStatus = strtolower($loan['loan_status'] ?? '');
                $performanceStatus = strtolower($loan['performance_status'] ?? '');

                if ($loanStatus === 'open') {
                    $activeLoans++;
                } else {
                    $closedLoans++;
                }

                if ($performanceStatus === 'performing') {
                    $performingLoans++;
                } else {
                    $nonPerformingLoans++;
                }

                $openingBalance = $loan['opening_balance'] ?? 0;
                $repaymentAmount = $loan['repayment_amount'] ?? 0;

                $totalLoanAmount += $openingBalance;
                $totalRepaymentAmount += $repaymentAmount;

                // Analyze repayment schedule
                // Mono API returns: [ ["04-2022" => "paid"], ["05-2022" => "paid"] ]
                $repaymentSchedule = $loan['repayment_schedule'] ?? [];
                foreach ($repaymentSchedule as $schedule) {
                    // Handle both formats: ["date" => "status"] or ["04-2022" => "paid"]
                    if (is_array($schedule)) {
                        // If it's an associative array with date as key
                        foreach ($schedule as $date => $status) {
                            $statusLower = strtolower($status ?? '');
                            // Map "paid" to "completed"
                            if ($statusLower === 'paid') {
                                $repaymentScheduleStatus['completed']++;
                            } elseif (isset($repaymentScheduleStatus[$statusLower])) {
                                $repaymentScheduleStatus[$statusLower]++;
                            } else {
                                // Count unknown statuses as pending
                                $repaymentScheduleStatus['pending']++;
                            }
                        }
                    } elseif (isset($schedule['status'])) {
                        // Legacy format: ["date" => "...", "status" => "..."]
                        $status = strtolower($schedule['status'] ?? '');
                        if ($status === 'paid') {
                            $repaymentScheduleStatus['completed']++;
                        } elseif (isset($repaymentScheduleStatus[$status])) {
                            $repaymentScheduleStatus[$status]++;
                        }
                    }
                }

                if (! isset($institutions[$institution])) {
                    $institutions[$institution] = [
                        'name' => $institution,
                        'loans_count' => 0,
                        'active_loans' => 0,
                        'total_amount' => 0,
                    ];
                }

                $institutions[$institution]['loans_count']++;
                if ($loanStatus === 'open') {
                    $institutions[$institution]['active_loans']++;
                }
                $institutions[$institution]['total_amount'] += $openingBalance;
            }
        }

        // Calculate average repayment amount
        $averageRepaymentAmount = $totalLoans > 0 ? ($totalRepaymentAmount / $totalLoans) : 0;

        // Calculate performance ratio
        $performanceRatio = $totalLoans > 0 ? ($performingLoans / $totalLoans) * 100 : 0;

        return [
            'providers' => $providers,
            'profile' => [
                'full_name' => $profile['full_name'] ?? null,
                'first_name' => $profile['first_name'] ?? null,
                'last_name' => $profile['last_name'] ?? null,
                'dob' => $profile['date_of_birth'] ?? $profile['dob'] ?? null,
                'gender' => $profile['gender'] ?? null,
                'email' => $profile['email'] ?? ($profile['email_address'] ?? ($profile['email_addresses'][0] ?? null)),
                'phone' => $profile['phone'] ?? ($profile['phone_number'] ?? ($profile['phone_numbers'][0] ?? null)),
                'address' => $profile['address'] ?? ($profile['address_history'][0]['address'] ?? ($profile['address_history'][0] ?? null)),
                'address_history_count' => count($profile['address_history'] ?? []),
                'email_addresses' => $profile['email_address'] ?? $profile['email_addresses'] ?? [],
                'phone_numbers' => $profile['phone_number'] ?? $profile['phone_numbers'] ?? [],
                'identifications' => $profile['identifications'] ?? [],
            ],
            'credit_history_summary' => [
                'total_loans' => $totalLoans,
                'active_loans' => $activeLoans,
                'closed_loans' => $closedLoans,
                'total_loan_amount' => $totalLoanAmount,
                'total_repayment_amount' => $totalRepaymentAmount,
                'average_repayment_amount' => $averageRepaymentAmount,
                'performing_loans' => $performingLoans,
                'non_performing_loans' => $nonPerformingLoans,
                'performance_ratio' => round($performanceRatio, 2),
                'institutions_count' => count($institutions),
                'repayment_schedule_status' => $repaymentScheduleStatus,
            ],
            'institutions' => array_values($institutions),
            'credit_history' => $creditHistory,
        ];
    }

    /**
     * Generate system prompt for credit history analysis
     */
    private function generateSystemPrompt(float $requestedAmount, ?string $lenderInstruction = null): string
    {
        // Calculate percentage-based amounts for each category
        $categoryBAmount = $requestedAmount * 0.75;
        $categoryCAmount = $requestedAmount * 0.50;
        $categoryDAmount = $requestedAmount * 0.25;

        // Format amounts with thousand separators
        $formattedRequested = number_format($requestedAmount, 2);
        $formattedCategoryB = number_format($categoryBAmount, 2);
        $formattedCategoryC = number_format($categoryCAmount, 2);
        $formattedCategoryD = number_format($categoryDAmount, 2);

        // Lender instruction text
        $instructionText = $lenderInstruction
            ? "LENDER INSTRUCTIONS:\n{$lenderInstruction}\n\n"
            : "LENDER INSTRUCTIONS:\nNo specific lender instructions provided.\n\n";

        return "You are an expert credit risk analyst for a lending platform. Your role is to evaluate customer creditworthiness based on credit history data from Mono Credit History API.

EVALUATION CRITERIA (based on credit history analysis):

1. CREDIT HISTORY ANALYSIS:
   - Total loans: Number of loans the customer has taken across all institutions
   - Active loans: Current open loans (higher active loans = higher risk)
   - Closed loans: Successfully completed loans (higher = positive indicator)
   - Loan performance status: 'performing' loans indicate good repayment behavior
   - Non-performing loans: Indicates repayment issues (critical risk indicator)
   - Performance ratio: Percentage of performing vs non-performing loans

2. REPAYMENT PATTERN ANALYSIS:
   - Repayment schedule status: Analyze completed, pending, and overdue payments
   - Repayment frequency: Monthly, weekly, etc. (consistent frequency = positive)
   - Repayment amount: Average repayment amounts show capacity
   - Loan status: 'open' vs 'closed' loans indicate current debt burden
   - Date opened vs closed: Loan duration shows commitment and completion

3. INSTITUTION DIVERSITY:
   - Number of institutions: Multiple institutions may indicate higher risk or credit shopping
   - Institution concentration: Loans concentrated in one institution vs spread across many
   - Institution reputation: Consider well-known vs lesser-known institutions
   - Active loans per institution: High active loans per institution = higher risk

4. LOAN AMOUNT ANALYSIS:
   - Total loan amount: Sum of all loans taken (higher = potentially higher risk)
   - Average loan amount: Shows typical borrowing pattern
   - Total repayment amount: Shows total repayment capacity
   - Loan amount trends: Increasing amounts may indicate dependency

5. RISK SCORING RULES:
   - High number of active loans = Higher risk
   - Non-performing loans = Critical risk indicator
   - Multiple institutions with active loans = Higher risk
   - High performance ratio (performing loans) = Lower risk
   - Completed repayment schedules = Positive indicator
   - Overdue payments = High risk indicator
   - Closed loans with good performance = Positive indicator

6. LOAN ELIGIBILITY CATEGORIES (based on requested amount: ₦{$formattedRequested}):
   - Category A (75-100% score): Excellent credit history, approve 100% of requested amount (₦{$formattedRequested})
   - Category B (50-74% score): Good credit history, approve 75% of requested amount (₦{$formattedCategoryB})
   - Category C (35-49% score): Fair credit history, approve 50% of requested amount (₦{$formattedCategoryC})
   - Category D (20-34% score): Poor credit history, approve 25% of requested amount (₦{$formattedCategoryD})
   - Category E (0-19% score): Very poor credit history, no loan approved (₦0)

7. {$instructionText}EVALUATION LOGIC:
   - Prioritize performance status (performing vs non-performing)
   - Consider active loan burden (too many active loans = risk)
   - Evaluate repayment track record (completed schedules = positive)
   - Assess institution diversity (moderate diversity = positive, excessive = risk)
   - Factor in loan amounts relative to repayment capacity
   - Consider closed loans as positive indicators of completion

OUTPUT REQUIREMENTS:
CRITICAL: You must respond with ONLY valid JSON. Do NOT wrap the response in markdown code blocks (```json or ```). Return pure JSON only, starting with { and ending with }.

The response must be in this exact structure (pure JSON, no markdown formatting):
{
    \"eligible_for_loan\": boolean,
    \"credit_category\": \"A\" | \"B\" | \"C\" | \"D\" | \"E\",
    \"credit_score_percentage\": number (0-100),
    \"recommended_loan_amount\": number,

    \"requested_amount\": {$requestedAmount},
    \"risk_level\": \"LOW\" | \"MEDIUM\" | \"HIGH\",
    \"affordability_status\": \"CAN_AFFORD\" | \"CANNOT_AFFORD\" | \"BORDERLINE\",
    \"key_factors\": {
        \"positive\": [\"string\"],
        \"negative\": [\"string\"],
        \"warnings\": [\"string\"]
    },
    \"credit_history_analysis\": {
        \"total_loans\": number,
        \"active_loans\": number,
        \"closed_loans\": number,
        \"performance_ratio\": number,
        \"performing_loans\": number,
        \"non_performing_loans\": number,
        \"institutions_count\": number,
        \"repayment_track_record\": \"EXCELLENT\" | \"GOOD\" | \"FAIR\" | \"POOR\",
        \"recommendation\": \"string\"
    },
    \"loan_recommendation\": {
        \"approved\": boolean,
        \"approved_amount\": number,
        \"loan_duration_months\": number,
        \"interest_rate_recommendation\": \"STANDARD\" | \"PREMIUM\" | \"RISK_PREMIUM\",
        \"conditions\": [\"string\"]
    },
    \"reasoning\": \"string\"
}

Be precise, analytical, and base your evaluation strictly on the provided credit history data. Return only valid JSON.";
    }

    /**
     * Build user prompt with credit history data
     */
    private function buildUserPrompt(array $formattedData): string
    {
        $jsonData = json_encode($formattedData, JSON_PRETTY_PRINT);

        return "Analyze the following Mono credit history data and provide a comprehensive credit evaluation:

MONO CREDIT HISTORY DATA:
{$jsonData}

Calculate the credit score percentage (0-100), determine the credit category (A, B, C, or D), assess risk level, and provide loan recommendations based on the evaluation criteria. 

Focus on:
- Loan performance status (performing vs non-performing loans)
- Active loan burden (number of open loans)
- Repayment track record (completed schedules, pending, overdue)
- Institution diversity and concentration
- Total loan amounts and repayment capacity
- Historical loan patterns and trends

IMPORTANT: Return ONLY valid JSON. Do NOT use markdown code blocks (```json or ```). Start your response with { and end with }. Return pure JSON only.";
    }

    /**
     * Parse Gemini response and extract structured JSON
     */
    private function parseGeminiResponse(string $response): array
    {
        try {
            // Clean response - remove markdown code blocks if present
            // Handle various markdown formats: ```json, ```, or just code blocks
            $cleanedResponse = preg_replace('/```json\s*/i', '', $response);
            $cleanedResponse = preg_replace('/```\s*/', '', $cleanedResponse);
            $cleanedResponse = trim($cleanedResponse);

            // Remove any leading/trailing whitespace and newlines
            $cleanedResponse = trim($cleanedResponse, " \t\n\r\0\x0B");

            // Try to find JSON object - match from first { to last }
            if (preg_match('/\{[\s\S]*\}/', $cleanedResponse, $matches)) {
                $jsonString = $matches[0];

                // Try to decode the JSON
                $parsed = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    return $parsed;
                }

                Log::warning('JSON parsing failed after extraction', [
                    'json_error' => json_last_error_msg(),
                    'extracted_text' => substr($jsonString, 0, 500), // Log first 500 chars
                ]);
            }

            // If JSON parsing fails, try direct decode of cleaned response
            $parsed = json_decode($cleanedResponse, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }

            // If JSON parsing fails, return raw response with error flag
            Log::warning('Failed to parse Gemini JSON response', [
                'response_length' => strlen($response),
                'json_error' => json_last_error_msg(),
            ]);

            return [
                'raw_response' => $response,
                'parse_error' => json_last_error_msg(),
                'parsed' => false,
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing Gemini response', [
                'error' => $e->getMessage(),
                'response_length' => strlen($response),
            ]);

            return [
                'raw_response' => $response,
                'parse_error' => $e->getMessage(),
                'parsed' => false,
            ];
        }
    }
}
