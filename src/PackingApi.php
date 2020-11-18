<?php declare(strict_types=1);


namespace demo\BinPackingApi;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use demo\BinPackingApi\Exception\ApiError;
use demo\BinPackingApi\Exception\ItemsTooBig;

class PackingApi implements LoggerAwareInterface, PackingApiInterface
{
    private string $username;

    private string $api_key;

    private LoggerInterface $logger;

    private ClientInterface $client;

    public function __construct(string $username, string $api_key, ClientInterface $client)
    {
        $this->client = $client;
        $this->username = $username;
        $this->api_key = $api_key;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function calcBestBin(array $bins, array $items): string
    {
        $bins_packed = $this->getBinsPacked($bins, $items);

        $usable_bins = array_filter($bins_packed, fn($bin) => count($bin['not_packed_items']) === 0);

        if (count($usable_bins) === 0) {
            throw new ItemsTooBig();
        }

        usort($usable_bins, fn(array $a, array $b) => $a['bin_data']['used_space'] <=> $b['bin_data']['used_space']);

        $best_bin = end($usable_bins);
        return $best_bin['bin_data']['id'];
    }

    private function getBinsPacked(array $bins, array $items): array
    {
        $response = $this->callApi($bins, $items);

        foreach ($response['errors'] as $error) {
            if ($error['level']  === 'warning') {
                $this->logger->warning($error['message']);
            }
            if ($error['level'] === 'critical') {
                $this->handleApiError($error['message']);
            }
        }

        if ($response['status'] !== 1) { // in case errors doesnt contain anything
            $this->handleApiError('API responded with critical error');
        }

        return $response['bins_packed'];
    }

    private function callApi(array $bins, array $items): array
    {
        $request_data = [
            'username' => $this->username,
            'api_key' => $this->api_key,
            'bins' => $bins,
            'items' => $items,
        ];

        try {
            $response = $this->client->request('POST', 'pack', [
                'body' => json_encode($request_data, JSON_THROW_ON_ERROR),
            ]);
        } catch (GuzzleException $e) {
            $this->handleApiError('Failed to contact API', $e);
        }

        if ($response->getStatusCode() !== 200) {
            $this->handleApiError('API responded' . $response->getStatusCode() . $response->getReasonPhrase());
        }

        $contents_string = $response->getBody()->getContents();
        try {
            $decoded_response = json_decode($contents_string, true, 512,  JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->handleApiError('Response not a JSON', $e);
        }

        if (!isset($decoded_response['response'])) {
            $this->handleApiError('Invalid response from API');
        }

        return $decoded_response['response'];
    }

    private function handleApiError(string $message, \Throwable $exception = null): void
    {
        $context = [];
        if ($exception !== null) {
            $context['exception'] = $exception;
        }
        $this->logger->error($message, $context);
        throw new ApiError($message, $exception);
    }
}