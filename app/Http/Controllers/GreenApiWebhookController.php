<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GreenApiWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Green API
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            Log::info('Green API webhook received', [
                'raw_data' => $data,
                'headers' => $request->headers->all(),
                'ip' => $request->ip()
            ]);
            
            // Validate webhook (optional but recommended)
            if (!$this->validateWebhook($request)) {
                Log::warning('Invalid webhook signature or source');
                return response()->json(['status' => 'error', 'message' => 'Invalid webhook'], 401);
            }
            
            // Extract message data based on Green API format
            $messageData = $this->extractMessageData($data);
            
            if ($messageData) {
                // Save to database
                $message = WhatsAppMessage::create([
                    'message_id' => $messageData['message_id'],
                    'user_phone' => $messageData['user_phone'],
                    'message_text' => $messageData['message_text'],
                    'message_type' => $messageData['message_type'],
                    'timestamp' => $messageData['timestamp'],
                    'webhook_data' => $data,
                    'created_at' => now()
                ]);
                
                Log::info('WhatsApp message saved', [
                    'id' => $message->id,
                    'message_id' => $messageData['message_id'],
                    'user_phone' => $messageData['user_phone'],
                    'message_type' => $messageData['message_type'],
                    'message_text' => substr($messageData['message_text'], 0, 100) . '...'
                ]);
                
                // Check if this is a verification code message
                $this->processVerificationMessage($message);
            } else {
                Log::info('No extractable message data from webhook', ['data' => $data]);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('Error processing Green API webhook', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->all()
            ]);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Extract message data from Green API webhook payload
     * Adjust this method based on your actual Green API webhook format
     */
    private function extractMessageData(array $data): ?array
    {
        // Green API webhook format example 1: Direct format
        if (isset($data['typeWebhook']) && $data['typeWebhook'] === 'incomingMessageReceived') {
            $messageData = $data['messageData'] ?? [];
            $senderData = $data['senderData'] ?? [];
            
            return [
                'message_id' => $messageData['idMessage'] ?? null,
                'user_phone' => $this->cleanPhoneNumber($senderData['chatId'] ?? ''),
                'message_text' => $this->extractTextMessage($messageData),
                'message_type' => 'incoming',
                'timestamp' => $messageData['timestamp'] ?? time()
            ];
        }
        
        // Green API webhook format example 2: Nested format
        if (isset($data['body']['typeWebhook']) && $data['body']['typeWebhook'] === 'incomingMessageReceived') {
            $body = $data['body'];
            $messageData = $body['messageData'] ?? [];
            $senderData = $body['senderData'] ?? [];
            
            return [
                'message_id' => $messageData['idMessage'] ?? null,
                'user_phone' => $this->cleanPhoneNumber($senderData['chatId'] ?? ''),
                'message_text' => $this->extractTextMessage($messageData),
                'message_type' => 'incoming',
                'timestamp' => $messageData['timestamp'] ?? time()
            ];
        }
        
        // Green API webhook format example 3: Alternative format
        if (isset($data['webhookType']) && $data['webhookType'] === 'incomingMessageReceived') {
            return [
                'message_id' => $data['idMessage'] ?? null,
                'user_phone' => $this->cleanPhoneNumber($data['senderData']['chatId'] ?? ''),
                'message_text' => $this->extractTextMessage($data['messageData'] ?? []),
                'message_type' => 'incoming',
                'timestamp' => $data['timestamp'] ?? time()
            ];
        }
        
        // If webhook is for outgoing message status
        if (isset($data['typeWebhook']) && $data['typeWebhook'] === 'outgoingMessageStatus') {
            return [
                'message_id' => $data['idMessage'] ?? null,
                'user_phone' => $this->cleanPhoneNumber($data['chatId'] ?? ''),
                'message_text' => '', // Usually not included in status webhooks
                'message_type' => 'outgoing',
                'timestamp' => $data['timestamp'] ?? time()
            ];
        }
        
        return null;
    }
    
    /**
     * Extract text message from different message types
     */
    private function extractTextMessage(array $messageData): string
    {
        // Text message
        if (isset($messageData['textMessageData']['textMessage'])) {
            return $messageData['textMessageData']['textMessage'];
        }
        
        // Extended text message
        if (isset($messageData['extendedTextMessageData']['text'])) {
            return $messageData['extendedTextMessageData']['text'];
        }
        
        // Document message with caption
        if (isset($messageData['documentMessageData']['caption'])) {
            return $messageData['documentMessageData']['caption'];
        }
        
        // Image message with caption
        if (isset($messageData['imageMessageData']['caption'])) {
            return $messageData['imageMessageData']['caption'];
        }
        
        // Video message with caption
        if (isset($messageData['videoMessageData']['caption'])) {
            return $messageData['videoMessageData']['caption'];
        }
        
        return '';
    }
    
    /**
     * Clean phone number but preserve @c.us for WhatsApp format storage
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Keep the original format if it has @c.us (WhatsApp format)
        if (str_contains($phone, '@c.us')) {
            return $phone;
        }
        
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Add + if not present
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }
        
        // For Pakistani numbers, add @c.us format
        if (str_starts_with($cleaned, '+92') || str_starts_with($cleaned, '92')) {
            // Remove + and add @c.us
            $number = str_replace('+', '', $cleaned);
            if (!str_starts_with($number, '92')) {
                $number = '92' . $number;
            }
            return $number . '@c.us';
        }
        
        return $cleaned . '@c.us';
    }
    
    /**
     * Validate webhook authenticity (implement based on your security needs)
     */
    private function validateWebhook(Request $request): bool
    {
        // Option 1: IP whitelist (check Green API documentation for their IPs)
        $allowedIPs = [
            '185.178.208.0/24', // Example Green API IP range
            '45.67.229.0/24',   // Example Green API IP range
            // Add actual Green API IPs here
        ];
        
        $clientIP = $request->ip();
        foreach ($allowedIPs as $allowedIP) {
            if ($this->ipInRange($clientIP, $allowedIP)) {
                return true;
            }
        }
        
        // Option 2: User-Agent check
        $userAgent = $request->header('User-Agent');
        if (str_contains($userAgent, 'Green-API')) {
            return true;
        }
        
        // Option 3: Webhook signature validation (if Green API provides it)
        // $signature = $request->header('X-Green-Signature');
        // return $this->validateSignature($request->getContent(), $signature);
        
        // For development, allow all
        if (config('app.debug')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if IP is in range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$range, $netmask] = explode('/', $range, 2);
            $rangeDecimal = ip2long($range);
            $ipDecimal = ip2long($ip);
            $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
            $netmaskDecimal = ~$wildcardDecimal;
            return (($ipDecimal & $netmaskDecimal) == ($rangeDecimal & $netmaskDecimal));
        }
        return $ip === $range;
    }
    
    /**
     * Process potential verification code messages
     */
    private function processVerificationMessage(WhatsAppMessage $message): void
    {
        // Only process incoming text messages
        if ($message->message_type !== 'incoming' || empty($message->message_text)) {
            return;
        }
        
        // Extract potential verification codes (6-digit numbers)
        preg_match_all('/\b\d{6}\b/', $message->message_text, $matches);
        
        if (!empty($matches[0])) {
            foreach ($matches[0] as $code) {
                Log::info('Potential verification code found', [
                    'message_id' => $message->id,
                    'user_phone' => $message->user_phone,
                    'code' => $code,
                    'message_text' => $message->message_text
                ]);
                
                // Here you could trigger additional processing if needed
                // For example, notify a queue job to process verification
            }
        }
    }
}