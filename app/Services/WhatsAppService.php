<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $phoneNumberId;

    public function __construct()
    {
        // Usa la versione v25.0 come nel tuo comando cURL
        $this->apiUrl = "https://graph.facebook.com/v25.0/";
        $this->accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $this->phoneNumberId = '1086670847867616'; // L'ID che hai usato nel cURL
    }

    public function sendPreventiveMessage(string $to, string $templateName, array $variables = [])
    {
        $url = $this->apiUrl . $this->phoneNumberId . "/messages";

        // Mappatura per il template "hello_world" che hai testato
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName, // Es: "hello_world"
                'language' => ['code' => 'en_US'] // Deve corrispondere a quello del cURL
            ]
        ];

        $response = Http::withToken($this->accessToken)
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Errore cURL WhatsApp: ' . $response->body());
            return false;
        }

        return $response->json();
    }
}