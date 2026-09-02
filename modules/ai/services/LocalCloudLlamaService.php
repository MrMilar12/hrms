<?php

class LocalCloudLlamaService
{
    public function chat(string $message, string $context = '', array $history = []): string
    {
        $config = is_file(CONFIG_PATH . '/llama.php') ? (require CONFIG_PATH . '/llama.php') : [];
        $url = trim((string) getenv('OLLAMA_API_URL')) ?: trim((string) getenv('HRMS_LLAMA_API_URL')) ?: trim((string) ($config['api_url'] ?? '')) ?: 'https://ollama.com/api/chat';
        $key = trim((string) getenv('OLLAMA_API_KEY')) ?: trim((string) getenv('HRMS_LLAMA_API_KEY')) ?: trim((string) ($config['api_key'] ?? ''));
        $model = trim((string) getenv('OLLAMA_MODEL')) ?: trim((string) getenv('HRMS_LLAMA_MODEL')) ?: trim((string) ($config['model'] ?? '')) ?: 'gpt-oss:20b';
        if ($key === '') throw new RuntimeException('Ollama Cloud is not configured. Add OLLAMA_API_KEY to config/llama.php.');
        $system = 'You are a helpful general-purpose AI agent built into HRMS. Understand natural-language commands, explain your reasoning briefly, and provide actionable answers. You may answer questions about technology, education, science, writing, travel, daily life, and other safe topics. For HRMS questions, use only the supplied authorized context. Never invent records, reveal secrets, or provide another user\'s private information. Never claim an action was completed unless the system confirms it. Changes to records require explicit confirmation. Clearly label live weather data and say when information may be outdated.';
        if ($context !== '') $system .= "\n\nAuthorized HRMS context (use only for HRMS questions):\n" . $context;
        $messages = [['role'=>'system','content'=>$system]];
        foreach (array_slice($history, -12) as $item) {
            if (in_array($item['role'] ?? '', ['user', 'assistant'], true) && is_string($item['content'] ?? null)) $messages[] = ['role'=>$item['role'], 'content'=>mb_substr($item['content'], 0, 4000)];
        }
        $messages[] = ['role'=>'user','content'=>$message];
        $payload = json_encode(['model'=>$model,'messages'=>$messages,'temperature'=>.35,'stream'=>false]);
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>45]);
        $raw = curl_exec($ch); $error = curl_error($ch); $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
        if ($raw === false || $error) throw new RuntimeException('Unable to connect to Llama Cloud.');
        $data = json_decode($raw, true);
        if ($status >= 400 || !is_array($data)) throw new RuntimeException($data['error']['message'] ?? 'Llama Cloud returned an invalid response.');
        $reply = $data['message']['content']
            ?? $data['choices'][0]['message']['content']
            ?? $data['completion_message']['content']
            ?? $data['response']
            ?? $data['content']
            ?? '';
        if (!is_string($reply) || trim($reply) === '') {
            $keys = implode(', ', array_keys($data));
            throw new RuntimeException('Ollama returned no message text. Response fields: ' . ($keys ?: 'none') . '. Check that the configured model is available to your Ollama account.');
        }
        return trim($reply);
    }
}
