<?php

namespace Irazasyed\Telegram;

use Irazasyed\Telegram\HttpClients\GuzzleHttpClient;
use Irazasyed\Telegram\HttpClients\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Class TelegramClient.
 */
class TelegramClient
{
    /**
     * @const string Telegram Bot API URL.
     */
    const BASE_BOT_URL = 'https://api.telegram.org/bot';

    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60;

    /**
     * @const int The timeout in seconds for a request that contains file uploads.
     */
    const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 3600;

    /**
     * @const int The timeout in seconds for a request that contains video uploads.
     */
    const DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT = 7200;

    /**
     * Instantiates a new TelegramClient object.
     *
     * @param HttpClientInterface|null $httpClientHandler
     */
    public function __construct(HttpClientInterface $httpClientHandler = null)
    {
        $this->httpClientHandler = $httpClientHandler ?: new GuzzleHttpClient();
    }

    /**
     * Sets the HTTP client handler.
     *
     * @param HttpClientInterface $httpClientHandler
     */
    public function setHttpClientHandler(HttpClientInterface $httpClientHandler)
    {
        $this->httpClientHandler = $httpClientHandler;
    }

    /**
     * Returns the HTTP client handler.
     *
     * @return HttpClientInterface
     */
    public function getHttpClientHandler()
    {
        return $this->httpClientHandler;
    }

    /**
     * Returns the base Bot URL.
     *
     * @return string
     */
    public function getBaseBotUrl()
    {
        return static::BASE_BOT_URL;
    }

    /**
     * Prepares the API request for sending to the client handler.
     *
     * @param TelegramRequest $request
     *
     * @return array
     */
    public function prepareRequest(TelegramRequest $request)
    {
        $url = $this->getBaseBotUrl().$request->getAccessToken().'/'.$request->getEndpoint();

        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $request->isAsyncRequest(),
        ];
    }

    /**
     * Send an API request and process the result.
     *
     * @param \Irazasyed\Telegram\TelegramRequest $request
     *
     * @return \Irazasyed\Telegram\TelegramResponse
     *
     * @throws \Irazasyed\Telegram\TelegramSDKException
     */
    public function sendRequest(TelegramRequest $request)
    {
        list($url, $method, $headers, $isAsyncRequest) = $this->prepareRequest($request);

        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;

        $method = $request->getMethod();
        if ($method === 'POST') {
            $options = $request->getPostParams();
        } else {
            $options = ['query' => $request->getParams()];
        }

        $rawResponse = $this->httpClientHandler->send($url, $method, $headers, $options, $timeOut, $isAsyncRequest);

        $returnResponse = $this->getResponse($request, $rawResponse);

        if ($returnResponse->isError()) {
            throw $returnResponse->getThrownException();
        }

        return $returnResponse;
    }

    /**
     * Creates response object.
     *
     * @param \Irazasyed\Telegram\TelegramRequest $request
     * @param ResponseInterface|PromiseInterface  $response
     *
     * @return \Irazasyed\Telegram\TelegramResponse
     */
    protected function getResponse(TelegramRequest $request, $response)
    {
        return new TelegramResponse($request, $response);
    }
}
