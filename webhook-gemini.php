<?php
$GEMINI_KEY = "***"; // sesuaikan dengan API Key Google Gemini
$FONNTE_KEY = "***"; // Sesuaikan dengan Token API Fonnte

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!$data) exit;

$from = $data["sender"] ?? "";
$text = trim($data["message"] ?? "");
if ($from === "" || $text === "") exit;

// 1. Siapkan Prompt
$systemPrompt = <<<PROMPT
Kamu adalah asisten WhatsApp yang profesional, ringkas, dan membantu.

Aturan WAJIB:
- Gunakan teks polos (tanpa markdown)
- Langsung ke inti pembahasan, JANGAN menggunakan sapaan di setiap jawaban
- Maksimal 3 paragraf pendek
- Jika menggunakan poin, WAJIB memakai tanda "-" (dash)
- DILARANG menggunakan simbol "*", "**", "•", atau format markdown apa pun
- Akhiri jawaban dengan satu kalimat ajakan bertanya dan emoticon 🙂 saja

PROMPT;

$payload = [
    "contents" => [[
        "role" => "user",
        "parts" => [[
            "text" => $systemPrompt . "\n\nPertanyaan:\n" . $text
        ]]
    ]]
];


// Endpoint Gemini
$url =
  "https://generativelanguage.googleapis.com/v1beta/models/"
. "gemini-2.5-flash:generateContent"
. "?key=$GEMINI_KEY";

// Kirim ke Gemini
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);
$res = curl_exec($ch);
curl_close($ch);


// Ambil jawaban
$r = json_decode($res, true);
$reply = $r["candidates"][0]["content"]["parts"][0]["text"]
      ?? "Mohon Maaf, saya belum bisa menjawab saat ini.";

// 5. Kirim ke WhatsApp 
$send = curl_init("https://api.fonnte.com/send");
curl_setopt_array($send, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_KEY"],
  CURLOPT_POSTFIELDS => [
      "target" => $from,
      "message" => $reply
  ]
]);
curl_exec($send);
curl_close($send);
