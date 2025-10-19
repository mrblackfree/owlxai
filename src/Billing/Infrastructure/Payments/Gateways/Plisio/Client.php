<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Plisio;

use Easy\Container\Attributes\Inject;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;

class Client
{
    private GuzzleClient $httpClient;
    private string $baseUrl = 'https://api.plisio.net/api/v1';

    public function __construct(
        #[Inject('option.plisio.api_key')]
        private ?string $apiKey = null,
    ) {
        $this->httpClient = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Create a new transaction/invoice
     */
    public function createTransaction(array $data): array
    {
        try {
            $data['api_key'] = $this->apiKey;
            
            $response = $this->httpClient->request('GET', '/invoices/new', [
                'query' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new PaymentException(
                    'Plisio API error: ' . ($body['data']['message'] ?? 'Unknown error'),
                    $e->getCode(),
                    $e
                );
            }
            
            throw new PaymentException(
                'Plisio API connection failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get shop information
     */
    public function getShopInfo(): array
    {
        try {
            $response = $this->httpClient->request('GET', '/shops/info', [
                'query' => ['api_key' => $this->apiKey],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new PaymentException(
                'Failed to get shop info: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get enabled cryptocurrencies
     */
    public function getCurrencies(string $sourceCurrency = 'USD'): array
    {
        try {
            $response = $this->httpClient->request('GET', '/currencies', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'source_currency' => $sourceCurrency,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new PaymentException(
                'Failed to get currencies: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Verify callback data
     */
    public function verifyCallbackData(array $data): bool
    {
        if (!isset($data['verify_hash'])) {
            return false;
        }

        // Plisio callback verification
        $verifyHash = $data['verify_hash'];
        unset($data['verify_hash']);
        
        // Sort the data by keys
        ksort($data);
        
        // Create the string to hash
        $hashString = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $hashString .= $key . '=' . $value;
        }
        
        // Add the API key
        $hashString .= $this->apiKey;
        
        // Calculate the hash
        $calculatedHash = hash('sha1', $hashString);
        
        return hash_equals($calculatedHash, $verifyHash);
    }
}
