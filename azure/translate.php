<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$req = json_decode(file_get_contents('php://input'), true) ?: [];
$to    = $req['to']   ?? 'en';
$from  = $req['from'] ?? null;
$texts = $req['texts'] ?? [];

if (!$texts || !is_array($texts)) {
  http_response_code(400); echo json_encode(['error'=>'No texts provided']); exit;
}

$params = ['api-version'=>'3.0','to'=>$to] + ($from ? ['from'=>$from] : []);
$body = array_map(fn($t)=>['Text'=>$t], $texts);

$ch = curl_init(AZURE_TRANSLATOR_ENDPOINT.'translate?'.http_build_query($params));
curl_setopt_array($ch, [
  CURLOPT_POST=>true,
  CURLOPT_HTTPHEADER=>[
    'Content-Type: application/json',
    'Ocp-Apim-Subscription-Key: '.AZURE_TRANSLATOR_KEY,
    'Ocp-Apim-Subscription-Region: '.AZURE_TRANSLATOR_REGION,
  ],
  CURLOPT_POSTFIELDS=>json_encode($body, JSON_UNESCAPED_UNICODE),
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_TIMEOUT=>10,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($err || $code >= 400) {
  http_response_code(500);
  echo json_encode(['error'=>'Translator error','http'=>$code]); exit;
}

$out = array_map(fn($it)=>$it['translations'][0]['text'] ?? '', json_decode($res, true));
echo json_encode(['translated'=>$out], JSON_UNESCAPED_UNICODE);
