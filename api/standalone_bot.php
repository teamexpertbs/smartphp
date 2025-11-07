<?php
/**
 * Standalone Telegram Bot - Single File Version
 * Developer: Smarty Sunny
 *
 * Instructions:
 * 1. Upload this file to any PHP hosting
 * 2. Set BOT_TOKEN in environment or edit below
 * 3. Set webhook: https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/standalone_bot.php
 */

// ============ CONFIGURATION ============
$BOT_TOKEN = getenv('BOT_TOKEN') ?: '8490946201:AAHZDvj72UCTrh1oB-6GWlKQT0HlXawIMVo'; // Edit if not using environment variable
$PHONE_API_URL = "https://demon.taitanx.workers.dev/?mobile=";
$AADHAAR_API_URL = "https://family-members-n5um.vercel.app/fetch";
// --- YEH LINE FIX KAR DI GAYI HAI ---
$SESSION_DIR = '/tmp/bot_sessions'; // Was: __DIR__ . '/bot_sessions'

// ============ ERROR HANDLING ============
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============ SESSION MANAGEMENT ============
if (!file_exists($SESSION_DIR)) {
    mkdir($SESSION_DIR, 0777, true);
}

function getState($chatId) {
    global $SESSION_DIR;
    $file = $SESSION_DIR . "/{$chatId}.json";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        return $data['state'] ?? 0;
    }
    return 0;
}

function setState($chatId, $state) {
    global $SESSION_DIR;
    $file = $SESSION_DIR . "/{$chatId}.json";
    file_put_contents($file, json_encode(['state' => $state]));
}

// ============ BOT FUNCTIONS ============
function sendTelegramMessage($chatId, $text, $parseMode = 'Markdown', $replyMarkup = null) {
    global $BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => $parseMode
    ];
    
    if ($replyMarkup !== null) {
        $data['reply_markup'] = json_encode($replyMarkup);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function editTelegramMessage($chatId, $messageId, $text, $parseMode = 'Markdown') {
    global $BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/editMessageText";
    
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => $parseMode
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function getMainKeyboard() {
    return [
        'keyboard' => [
            [
                ['text' => '📱 Phone Lookup'],
                ['text' => '🆔 Aadhaar Lookup']
            ],
            [
                ['text' => 'ℹ️ Help'],
                ['text' => '🚀 Quick Start']
            ]
        ],
        'resize_keyboard' => true
    ];
}

function getCancelKeyboard() {
    return [
        'keyboard' => [
            [['text' => 'Cancel']]
        ],
        'resize_keyboard' => true
    ];
}

function validatePhone($number) {
    return preg_match('/^[6-9]\d{9}$/', $number);
}

function validateAadhaar($number) {
    return preg_match('/^\d{12}$/', $number);
}

// ============ COMMAND HANDLERS ============
function handleStart($chatId, $firstName) {
    $text = "👋 Welcome {$firstName}!\n\n";
    $text .= "I'm *Multi-Info Bot* — Get information from multiple sources.\n\n";
    $text .= "🧑‍💻 *Developer:* Smarty Sunny\n";
    $text .= "⚠️ *Note:* This bot is made for educational purposes only.\n";
    $text .= "Misuse of the bot or its data sources is strictly prohibited.\n";
    $text .= "The developer is *not responsible* for any illegal use.\n\n";
    $text .= "Choose an option below or send:\n";
    $text .= "- 📱 10-digit phone number\n";
    $text .= "- 🆔 12-digit Aadhaar number\n\n";
    $text .= "Commands:\n";
    $text .= "/phone - Phone number lookup\n";
    $text .= "/aadhaar - Aadhaar number lookup\n";
    $text .= "/help - Help guide";
    
    sendTelegramMessage($chatId, $text, 'Markdown', getMainKeyboard());
    setState($chatId, 0);
}

function handleHelp($chatId) {
    $text = "📘 *Help Guide - Multi-Info Bot*\n\n";
    $text .= "📱 *Phone Lookup:*\n";
    $text .= "- Send 10-digit mobile number\n";
    $text .= "  Example: 9876543210\n\n";
    $text .= "🆔 *Aadhaar Lookup:*\n";
    $text .= "- Send 12-digit Aadhaar number\n";
    $text .= "  Example: 123456789012\n\n";
    $text .= "Quick Commands:\n";
    $text .= "/phone - Phone lookup\n";
    $text .= "/aadhaar - Aadhaar lookup\n";
    $text .= "/help - This message\n\n";
    $text .= "Or use the buttons below!";
    
    sendTelegramMessage($chatId, $text, 'Markdown', getMainKeyboard());
    setState($chatId, 0);
}

function handlePhoneCommand($chatId) {
    $text = "📱 Enter 10-digit mobile number:\n";
    $text .= "Example: 9945789124\n\n";
    $text .= "Type /cancel to stop.";
    
    sendTelegramMessage($chatId, $text, 'Markdown', getCancelKeyboard());
    setState($chatId, 1); // STATE_AWAITING_PHONE
}

function handleAadhaarCommand($chatId) {
    $text = "🆔 Enter 12-digit Aadhaar number:\n";
    $text .= "Example: 123456789012\n\n";
    $text .= "Type /cancel to stop.";
    
    sendTelegramMessage($chatId, $text, 'Markdown', getCancelKeyboard());
    setState($chatId, 2); // STATE_AWAITING_AADHAAR
}

function handleQuickStart($chatId) {
    $text = "🚀 Quick Start:\n\n";
    $text .= "For Phone Lookup:\n• Send: 9876543210\n• Use: /phone\n\n";
    $text .= "For Aadhaar Lookup:\n• Send: 123456789012\n• Use: /aadhaar\n\n";
    $text .= "Or use the buttons!";
    
    sendTelegramMessage($chatId, $text, 'Markdown', getMainKeyboard());
    setState($chatId, 0);
}

function handleCancel($chatId) {
    sendTelegramMessage($chatId, "❌ Operation cancelled.", 'Markdown', getMainKeyboard());
    setState($chatId, 0);
}

function processPhoneLookup($chatId, $phoneNumber) {
    global $PHONE_API_URL;
    
    if (!validatePhone($phoneNumber)) {
        sendTelegramMessage($chatId, "❌ Invalid phone number. Enter 10-digit number like 9876543210", 'Markdown', getMainKeyboard());
        setState($chatId, 0);
        return;
    }
    
    $processingMsg = sendTelegramMessage($chatId, "🔍 Searching phone: {$phoneNumber}...");
    $messageId = $processingMsg['result']['message_id'];
    
    try {
        $url = $PHONE_API_URL . $phoneNumber;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $apiData = json_decode($response, true);
            $jsonResponse = json_encode($apiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            editTelegramMessage($chatId, $messageId, "✅ Phone data found: {$phoneNumber}");
            sendTelegramMessage($chatId, "```json\n{$jsonResponse}\n```", 'Markdown', getMainKeyboard());
        } else {
            editTelegramMessage($chatId, $messageId, "❌ API Error - Status: {$httpCode}");
        }
    } catch (Exception $e) {
        editTelegramMessage($chatId, $messageId, "❌ Error - " . $e->getMessage());
    }
    
    setState($chatId, 0);
}

function processAadhaarLookup($chatId, $aadhaarNumber) {
    global $AADHAAR_API_URL;
    
    if (!validateAadhaar($aadhaarNumber)) {
        sendTelegramMessage($chatId, "❌ Invalid Aadhaar number. Enter 12-digit number like 123456789012", 'Markdown', getMainKeyboard());
        setState($chatId, 0);
        return;
    }
    
    $processingMsg = sendTelegramMessage($chatId, "🔍 Searching Aadhaar: {$aadhaarNumber}...");
    $messageId = $processingMsg['result']['message_id'];
    
    try {
        $url = $AADHAAR_API_URL . "?aadhaar={$aadhaarNumber}&key=paidchx";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $apiData = json_decode($response, true);
            $jsonResponse = json_encode($apiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            editTelegramMessage($chatId, $messageId, "✅ Aadhaar data found: {$aadhaarNumber}");
            sendTelegramMessage($chatId, "```json\n{$jsonResponse}\n```", 'Markdown', getMainKeyboard());
        } else {
            editTelegramMessage($chatId, $messageId, "❌ Aadhaar API Error - Please try again later");
        }
    } catch (Exception $e) {
        editTelegramMessage($chatId, $messageId, "❌ Aadhaar API Error - Please try again later");
    }
    
    setState($chatId, 0);
}

// ============ WEBHOOK HANDLER ============
$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$firstName = $message['from']['first_name'] ?? 'User';
$currentState = getState($chatId);

// Route messages
if (strpos($text, '/start') === 0) {
    handleStart($chatId, $firstName);
}
elseif (strpos($text, '/help') === 0) {
    handleHelp($chatId);
}
elseif (strpos($text, '/phone') === 0) {
    handlePhoneCommand($chatId);
}
elseif (strpos($text, '/aadhaar') === 0) {
    handleAadhaarCommand($chatId);
}
elseif (strpos($text, '/cancel') === 0 || strtolower($text) === 'cancel') {
    handleCancel($chatId);
}
elseif ($text === '📱 Phone Lookup') {
    handlePhoneCommand($chatId);
}
elseif ($text === '🆔 Aadhaar Lookup') {
    handleAadhaarCommand($chatId);
}
elseif ($text === 'ℹ️ Help') {
    handleHelp($chatId);
}
elseif ($text === '🚀 Quick Start') {
    handleQuickStart($chatId);
}
elseif ($currentState === 1) { // AWAITING_PHONE
    if (strtolower($text) === 'cancel') {
        handleCancel($chatId);
    } else {
        processPhoneLookup($chatId, trim($text));
    }
}
elseif ($currentState === 2) { // AWAITING_AADHAAR
    if (strtolower($text) === 'cancel') {
        handleCancel($chatId);
    } else {
        processAadhaarLookup($chatId, trim($text));
    }
}
else {
    if (validatePhone(trim($text))) {
        processPhoneLookup($chatId, trim($text));
    }
    elseif (validateAadhaar(trim($text))) {
        processAadhaarLookup($chatId, trim($text));
    }
    else {
        $text = "Please send:\n• 10-digit Phone number\n• 12-digit Aadhaar number\n\nOr use the buttons below!";
        sendTelegramMessage($chatId, $text, 'Markdown', getMainKeyboard());
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
