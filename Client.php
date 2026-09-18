<?php

namespace Zelenin\Telegram\Bot;

use GuzzleHttp\Exception\ClientException;

class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $baseUrl = 'https://api.telegram.org/bot{token}/{method}';

    /**
     * @var string
     */
    private $token;

    /** @var \GuzzleHttp\ClientInterface */
    private $httpClient;

    /** @param string $token */
    public function __construct($token, ?\GuzzleHttp\ClientInterface $httpClient = null)
    {
        $this->token = $token;
        $this->httpClient = $httpClient ?: new \GuzzleHttp\Client();
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return Response
     */
    public function request($method, $params = [])
    {
        $multipartParams = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value) || (is_object($value) && !($value instanceof \Psr\Http\Message\StreamInterface))) {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $multipartParams[] = [
                'name' => $key,
                'contents' => is_scalar($value) ? (string)$value : $value
            ];
        }

        try {
            $response = $this->httpClient->request('POST', $this->getUrl($method), [
                'verify' => true,
                'allow_redirects' => false,
                'multipart' => $multipartParams ?: null
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }
        $payload = json_decode((string)$response->getBody());
        if (!is_object($payload) || !isset($payload->ok) || !is_bool($payload->ok)) {
            throw new \UnexpectedValueException('Invalid Telegram API response.');
        }
        if ($payload->ok) {
            if (!property_exists($payload, 'result')) {
                throw new \UnexpectedValueException('Telegram API response has no result.');
            }
            return new Response(true, $payload->result);
        }
        if (!isset($payload->error_code, $payload->description)
            || !is_int($payload->error_code) || !is_string($payload->description)) {
            throw new \UnexpectedValueException('Invalid Telegram API error response.');
        }
        return new Response(false, null, $payload->error_code, $payload->description);
    }

    /**
     * @param string $method
     *
     * @return string
     */
    private function getUrl($method)
    {
        return strtr($this->baseUrl, [
            '{token}' => $this->token,
            '{method}' => $method
        ]);
    }
}
