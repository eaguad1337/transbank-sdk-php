<?php

namespace Transbank\Webpay\WebpayPlus;

use Transbank\Webpay\Exceptions\TransactionCommitException;
use Transbank\Webpay\Exceptions\TransactionCreateException;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;

class Transaction
{

    /**
     * Path used for the 'create' endpoint
     */
    const CREATE_TRANSACTION_ENDPOINT = 'rswebpaytransaction/api/webpay/v1.0/transactions';

    const COMMIT_TRANSACTION_ENDPPOINT = 'rswebpaytransaction/api/webpay/v1.0/transactions';


    /**
     * @param string $buyOrder
     * @param string $sessionId
     * @param integer $amount
     * @param string $returnUrl
     * @param Options|null $options
     *
     * @return TransactionCreateResponse
     * @throws TransactionCreateException
     **
     */
    public static function create(
        $buyOrder,
        $sessionId,
        $amount,
        $returnUrl,
        $options = null
    ) {
        if ($options == null) {
            $commerceCode = WebpayPlus::getCommerceCode();
            $apiKey = WebpayPlus::getApiKey();
            $baseUrl = WebpayPlus::getIntegrationTypeUrl();
        } else {
            $commerceCode = $options->getCommerceCode();
            $apiKey = $options->getApiKey();
            $baseUrl = WebpayPlus::getIntegrationTypeUrl($options->getIntegrationType());
        }

        $headers = [
            "Tbk-Api-Key-Id" => $commerceCode,
            "Tbk-Api-Key-Secret" => $apiKey
        ];

        $payload = json_encode([
            "buy_order" => $buyOrder,
            "session_id" => $sessionId,
            "amount" => $amount,
            "return_url" => $returnUrl
        ]);

        $http = WebpayPlus::getHttpClient();

        $httpResponse = $http->post($baseUrl,
            self::CREATE_TRANSACTION_ENDPOINT,
            $payload,
            ['headers' => $headers]
        );

        if (!$httpResponse) {
            throw new TransactionCreateException('Could not obtain a response from the service', -1);
        }

        $responseJson = json_decode($httpResponse, true);
        if (!$responseJson["token"] || !$responseJson['url']) {
            throw new TransactionCreateException($responseJson['error_message']);
        }

        $json = json_decode($httpResponse, true);

        $transactionCreateResponse = new TransactionCreateResponse($json);

        return $transactionCreateResponse;
    }

    public static function commit($token, $options = null)
    {
        if ($options == null) {
            $commerceCode = WebpayPlus::getCommerceCode();
            $apiKey = WebpayPlus::getApiKey();
            $baseUrl = WebpayPlus::getIntegrationTypeUrl();
        } else {
            $commerceCode = $options->getCommerceCode();
            $apiKey = $options->getApiKey();
            $baseUrl = WebpayPlus::getIntegrationTypeUrl($options->getIntegrationType());
        }

        $headers = [
            "Tbk-Api-Key-Id" => $commerceCode,
            "Tbk-Api-Key-Secret" => $apiKey
        ];

        $http = WebpayPlus::getHttpClient();
        $httpResponse = $http->put($baseUrl,
            self::COMMIT_TRANSACTION_ENDPPOINT . "/" . $token,
            [],
            ['headers' => $headers]
        );

        if (!$httpResponse) {
            throw new TransactionCommitException('Could not obtain a response from the service', -1);
        }

        $responseJson = json_decode($httpResponse, true);

        #dd($responseJson);

        if (array_key_exists("error_message", $responseJson)) {
            throw new TransactionCommitException($responseJson['error_message']);
        }

        $transactionCommitResponse = new TransactionCommitResponse($responseJson);

        return $transactionCommitResponse;
}
}
