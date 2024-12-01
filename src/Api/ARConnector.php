<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api;

// use SilverStripe\Core\Config\Config;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Message;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Flush\FlushNow;

class ARConnector
{
    use Extensible;
    use Injectable;
    use Configurable;
    use FlushNow;

    public const ALL_BRANCH_ID = 0;

    /**
     * ARESAPI|ARESAPITest.
     *
     * @var string
     */
    public $basePath = '';

    /**
     * @var float|mixed
     */
    public float $startTime;

    /**
     * @var bool
     */
    protected bool $debug = false;

    /**
     * @var string
     */
    protected string $debugString = '';

    protected array|null $lastPagingData = null;

    protected int|null $lastTotalRecords = null;

    /**
     * @var array
     */
    protected array $errors = [];

    private static array $branches_to_be_excluded_from_stock = [];

    private static string $base_path = 'ARESAPI';

    private static string $class_name_for_product = Product::class;
    private static string $class_name_for_product_groups = ProductGroup::class;
    private static int $time_out = 2;


    public function __construct()
    {
        $this->basePath = $this->Config()->get('base_path');
    }

    public function convertTsToArDate(int $ts): string
    {
        return self::convert_ts_to_ar_date($ts);
    }

    public static function convert_ts_to_ar_date($ts)
    {
        return date('Y-m-d\\TH:i:s.000\\Z', $ts);
    }

    public static function convert_silverstripe_to_ar_date($silverstripeDate, int $adjustment = 0)
    {
        return self::convert_ts_to_ar_date(strtotime((string) $silverstripeDate) + $adjustment);
    }

    public static function convert_ar_to_ts_date(string $isoDate): int
    {
        // Create a DateTime object from the ISO date string
        $date = new DateTime($isoDate);

        // Convert to Unix timestamp
        return $date->getTimestamp();
    }

    public static function convert_ar_to_silverstripe_date(string $isoDate): string
    {
        // Create a DateTime object from the ISO date string
        $date = new DateTime($isoDate);

        // Format the date as 'Y-m-d'
        return $date->format('Y-m-d');
    }

    /**
     * ARESAPI|ARESAPITest.
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function setDebug(bool $bool): self
    {
        $this->debug = $bool;

        return $this;
    }

    public function getDebugString(): string
    {
        return $this->debugString;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function makeUrlFromSegments(string ...$links): string
    {
        return Controller::join_links(
            $this->Config()->get('base_url'),
            $this->basePath,
            ...$links
        );
    }

    public function getLastPagingData(): ?array
    {
        return $this->lastPagingData;
    }

    public function getLastTotalRecords(): ?int
    {
        return $this->lastTotalRecords;
    }

    /**
     * Makes an HTTP request and sends back the response as JSON.
     */
    protected function runRequest(string $uri, ?string $method = 'GET', ?array $data = [], ?bool $showErrors = false, ?int $timeoutInSeconds = 2): ?array
    {
        $client = new Client();
        $response = null;
        if (!$timeoutInSeconds) {
            $timeoutInSeconds = 2;
        }
        $error = false;
        try {
            $response = $client->request(
                $method,
                $uri,
                [
                    'json' => $data,
                    'timeout' => $timeoutInSeconds,
                ]
            );
        } catch (ConnectException $connectException) {
            $this->logError('Connection error: ' . $connectException->getMessage());
            $error = true;
        } catch (ClientException $clientException) {
            $this->logError('Client error: ' . Message::toString($clientException->getRequest()));
            $this->logError('Client error response: ' . Message::toString($clientException->getResponse()));
            $error = true;
        } catch (RequestException $requestException) {
            $this->logError('Request error: ' . Message::toString($requestException->getRequest()));
            if ($requestException->hasResponse()) {
                $this->logError('Request error response: ' . Message::toString($requestException->getResponse()));
            }
            $error = true;
        } catch (Exception $exception) {
            $this->logError('Unexpected error: ' . $exception->getMessage());
            $error = true;
        }
        if ($error) {
            $this->logError('Empty Response');
            $this->showErrors($uri, $showErrors, 'no response');
            return null;
        }
        $return = json_decode($response->getBody()->getContents(), true);

        if (!is_array($return)) {
            $this->logError('Invalid JSON response');
            $this->showErrors($uri, $showErrors, $response);
            return null;
        }
        $this->showErrors($uri, $showErrors, $response);
        if (isset($return['paging'])) {
            $this->lastPagingData = $return['paging'];
        } else {
            $this->lastPagingData = null;
        }
        if (isset($this->lastPagingData['totalRecords'])) {
            $this->lastTotalRecords = $this->lastPagingData['totalRecords'];
        } else {
            $this->lastTotalRecords = null;
        }
        if (isset($return['data']) && is_array($return['data'])) {
            return $return['data'];
        } elseif (is_array($return)) {
            return $return;
        }
        return null;
    }

    protected function showErrors(string $uri, bool $showErrors, $data = null)
    {
        if ($showErrors) {
            print_r($uri);
            if (! empty($this->getErrors())) {
                print_r($this->getErrors());
            }
            if (! empty($data)) {
                print_r($data);
            }
        }
    }

    protected function logError(string $error)
    {
        // user_error($error, E_USER_NOTICE);
        $this->errors[] = $error;
    }

    //#################################################
    // helpers OUTPUT
    //#################################################

    protected function output($v)
    {
        if ($this->debug) {
            if (is_string($v)) {
                $this->debugString .= self::flush_return($v);
            } else {
                $this->debugString .= self::flush_return('<pre>' . print_r($v, 1) . '</pre>');
            }
        }
    }
}
