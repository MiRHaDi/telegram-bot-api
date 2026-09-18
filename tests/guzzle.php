<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Zelenin\Telegram\Bot\Client;

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function makeClient($responses, &$history) {
    $history = [];
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));
    return new Client('fixture-token', new HttpClient(['handler' => $stack]));
}
function expectInvalid($body) {
    $client = makeClient([new HttpResponse(200, [], $body)], $history);
    try { $client->request('getMe'); } catch (UnexpectedValueException $e) { return; }
    throw new RuntimeException('Malformed response was accepted');
}
$client = makeClient([new HttpResponse(200, [], '{"ok":true,"result":{"message_id":7}}')], $history);
$file = fopen('php://temp', 'r+');
fwrite($file, 'fixture upload'); rewind($file);
$response = $client->request('sendDocument', [
    'chat_id' => 12, 'reply_markup' => ['inline_keyboard' => []],
    'disable_notification' => false, 'protect_content' => true,
    'unused' => null, 'document' => $file,
]);
check($response->getResult()->message_id === 7, 'Success result');
$options = $history[0]['options'];
check($options['verify'] === true && $options['allow_redirects'] === false, 'Transport security');
$body = (string)$history[0]['request']->getBody();
foreach (['{"inline_keyboard":[]}', "\r\n\r\nfalse\r\n", "\r\n\r\ntrue\r\n", 'fixture upload'] as $expected) {
    check(str_contains($body, $expected), 'Multipart encoding: ' . $expected);
}
check(!str_contains($body, 'name="unused"'), 'Null field must be omitted');
fclose($file);
foreach ([200, 400, 429] as $status) {
    $client = makeClient([new HttpResponse($status, [], '{"ok":false,"error_code":429,"description":"Retry later"}')], $history);
    $response = $client->request('getMe');
    check(!$response->getOk() && $response->getErrorCode() === 429 && $response->getDescription() === 'Retry later', 'API error parsing');
}
foreach (['<html>bad gateway</html>', '{}', '{"ok":1,"result":true}', '{"ok":true}', '{"ok":false}', 'null'] as $body) {
    expectInvalid($body);
}
$client = makeClient([new HttpResponse(302, ['Location' => 'https://example.invalid/'])], $history);
try { $client->request('getMe'); throw new RuntimeException('Redirect accepted'); }
catch (UnexpectedValueException $e) { check(count($history) === 1, 'Redirect must not be followed'); }
$client = makeClient([new HttpResponse(200, [], '{"ok":true,"result":false}')], $history);
check($client->request('getMe')->getResult() === false, 'False result preserved');
$client = makeClient([], $history);
try { $client->request('sendMessage', ['reply_markup' => ["\xB1"]]); throw new RuntimeException('Invalid UTF-8 accepted'); }
catch (JsonException $e) { check(count($history) === 0, 'Invalid JSON must not be sent'); }
$api = new \Zelenin\Telegram\Bot\Api('fixture-token');
$api->setClient(makeClient([new HttpResponse(200, [], '{"ok":true,"result":{"id":12,"first_name":"Fixture","is_bot":true,"new_api_field":"preserved"}}')], $history));
$user = $api->getMe();
check($user->id === 12 && $user->is_bot === true && $user->new_api_field === 'preserved', 'Modern API fields must survive typed wrappers');
echo "PASS: Guzzle multipart, TLS, redirects, API errors, malformed responses, and JSON failure\n";
