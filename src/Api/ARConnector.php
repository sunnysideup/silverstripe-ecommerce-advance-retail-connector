<?php

namespace Sunnysideup\EcommerceAdvanceRetailConnector\Api;

// use SilverStripe\Core\Config\Config;

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
    public $startTime;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var string
     */
    protected $debugString = false;

    /**
     * @var string
     */
    protected $error = '';

    private static $branches_to_be_excluded_from_stock = [];

    private static $base_path = 'ARESAPI';

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

    protected function makeUrlFromSegments(string ...$links): string
    {
        return Controller::join_links(
            $this->Config()->get('base_url'),
            $this->basePath,
            ...$links
        );
    }

    /**
     * Makes an HTTP request and sends back the response as JSON.
     */
    protected function runRequest(string $uri, ?string $method = 'GET', ?array $data = []): array
    {
        $client = new Client();
        $response = null;

        try {
            $response = $client->request(
                $method,
                $uri,
                [
                    'json' => $data,
                ]
            );
        } catch (ConnectException $connectException) {
            $this->logError('Connection error: ' . $connectException->getMessage());
        } catch (ClientException $clientException) {
            $this->logError('Client error: ' . Message::toString($clientException->getRequest()));
            $this->logError('Client error response: ' . Message::toString($clientException->getResponse()));
        } catch (RequestException $requestException) {
            $this->logError('Request error: ' . Message::toString($requestException->getRequest()));
            if ($requestException->hasResponse()) {
                $this->logError('Request error response: ' . Message::toString($requestException->getResponse()));
            }
        } catch (Exception $exception) {
            $this->logError('Unexpected error: ' . $exception->getMessage());
        }

        if (empty($response)) {
            $this->logError('Empty Response');

            return [];
        }

        $return = json_decode($response->getBody()->getContents(), true);
        if(!is_array($return)) {
            $this->logError('Invalid JSON response: ' . $return);
            return [];
        }
        return $return;
    }

    protected function logError(string $error)
    {
        user_error($error, E_USER_NOTICE);
        $this->error .= '<pre>' . $error . '</pre>';
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
