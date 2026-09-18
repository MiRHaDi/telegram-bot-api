<?php
// Offline transport contract: no bot token or network access is needed.
namespace GuzzleHttp {
    class Client
    {
        public static $lastUrl;
        public static $lastOptions;

        public function post($url, $options)
        {
            self::$lastUrl = $url;
            self::$lastOptions = $options;
            return new TestResponse();
        }
    }

    class TestResponse
    {
        public function getBody()
        {
            return '{"ok":true,"result":{"message_id":123}}';
        }
    }
}

namespace {
    require __DIR__ . '/../ClientInterface.php';
    require __DIR__ . '/../Response.php';
    require __DIR__ . '/../Client.php';

    function check($condition, $message)
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    $client = new \Zelenin\Telegram\Bot\Client('test-token');
    $response = $client->request('sendMessage', ['chat_id' => 42, 'text' => 'hello']);
    check(\GuzzleHttp\Client::$lastOptions['verify'] === true, 'TLS certificate verification must be enabled');
    check(\GuzzleHttp\Client::$lastUrl === 'https://api.telegram.org/bottest-token/sendMessage', 'HTTPS endpoint changed');
    check(\GuzzleHttp\Client::$lastOptions['multipart'][0]['contents'] === '42', 'Scalar multipart conversion changed');
    check($response->getOk() && $response->getResult()->message_id === 123, 'Response parsing changed');
    $client->request('getMe');
    check(\GuzzleHttp\Client::$lastOptions['multipart'] === null, 'Empty request changed');
    echo "PASS: TLS verification and request/response contract\n";
}
