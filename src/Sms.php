<?php

namespace Bobach22\SmsClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class Sms
{

    const TYPE_REGULAR = 'regular';
    const TYPE_BULK = 'bulk';
    private static $client = null;
    private $config = array();
    private $response = null;
    private $response_code = null;

    /**
     * Sms constructor.
     */
    public function __construct()
    {
        $this->loadConfig();
        $this->createClient();
    }

    /**
     * Load config
     *
     * @return $this
     */

    protected function loadConfig()
    {
        $this->config = config('sms');
        return $this;
    }

    /**
     * Create new Guzzle Client
     *
     * @return $this
     */
    protected function createClient()
    {
        if (!self::$client) {
            self::$client = new Client();
        }
        return $this;
    }

    /**
     * Set country code
     *
     * @param string $country_code
     * @return $this
     */

    public function countryCode(string $country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * Send message
     * @param string|array $to
     * @param string $message
     * @param string|int $dispatch_id
     *
     * @return $this
     * @throws RequestException
     */

    public function send($to, $message, $dispatch_id = null)
    {

        $headers = $this->config['headers'];
        $headers["Authorization"] = "Bearer " . $this->getToken();
        $countryCode = $this->config['params']['country_code'];
        $from = $this->config['params']['from'];

        $to = $this->addCountryCode($to, $countryCode);

        $numberKey = isset($this->config['params']['number_key']) && !empty($this->config['params']['number_key']) ? $this->config['params']['number_key'] : 'phone_number';
        $messageKey = isset($this->config['params']['number_key']) && !empty($this->config['params']['message_key']) ? $this->config['params']['message_key'] : 'message';

        $type = NULL;
        $payload = NULL;

        if (is_string($to)) {

            $payload = json_encode([
                $numberKey => $to,
                $messageKey => $message
            ]);

            $type = Sms::TYPE_REGULAR;

        } elseif (is_array($to)) {
            $messages = [];

            foreach ($to as $key => $mobile) {
                $messages[] = ['to' => $mobile, 'text' => $message];
            }

            $payload = json_encode([
                'messages' => $messages,
                'from' => $from,
                'dispatch_id' => $dispatch_id
            ]);

            $type = Sms::TYPE_BULK;
        }

        $method = isset($this->config['params']['method']) && in_array($this->config['params']['method'], ['POST', 'GET']) ? $this->config['params']['method'] : 'POST';
        $url_regular = isset($this->config['params']['service_url']) && !empty($this->config['params']['service_url']) ? $this->config['params']['service_url'] : null;
        $url_bulk = isset($this->config['params']['service_bulk_send_url']) && !empty($this->config['params']['service_bulk_send_url']) ? $this->config['params']['service_bulk_send_url'] : null;

        $service_url = $type === Sms::TYPE_REGULAR ? $url_regular : $url_bulk;

        try {


            if (!$countryCode) {
                throw new Exception('Country code not provided');
            }


            if (!$service_url) {
                throw new Exception('Missing service provider url');
            }

            $request = new Request($method, $service_url, $headers, $payload);
            $promise = $this->getClient()->sendAsync(
                $request
            );


            $res = $promise->wait();
            $this->response_code = $res->getStatusCode();
            $this->response = new Response($res->getBody(), $res->getStatusCode(), $res->getHeaders());

        } catch (RequestException $e) {

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $this->response = new Response($response->getBody(), $response->getStatusCode(), $response->getHeaders());
            } else {
                $response = $e->getHandlerContext();
                if (isset($response['error'])) {
                    $this->response = new Response(['error' => $response['error']], 500);
                }
            }
        } catch (Exception $e) {
            $this->response = new Response(['error' => $e->getMessage()], 500);
        }
        return $this;
    }

    /**
     * Add country code to mobile
     *
     * @param string|array $mobile
     * @return string|array
     */

    private function addCountryCode($mobile, $country_code)
    {

        if (is_array($mobile)) {
            array_walk($mobile, function (&$value, $key) use ($country_code) {
                if (!$this->hasCountryCode($value, $country_code)) {
                    $value = $country_code . $value;
                }

            });
            return $mobile;
        }
        return $this->hasCountryCode($mobile, $country_code) ? $mobile : $country_code . $mobile;
    }

    /**
     * Check phone number(s) for country code
     *
     * @param string $mobile
     * @return boolean
     */

    private function hasCountryCode($mobile, $country_code)
    {

        if (strlen($mobile) === 12 && strpos($mobile, $country_code) !== false) {
            return true;
        }
        return false;

    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return self::$client;
    }


    /**
     * @return void
     * @throws Exception
     */
    public function getToken(): string
    {
        try {
            return Cache::remember('sms-token', $this->config['cache_expired_time'] - 3600, function () {
                return $this->auth();
            });
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function auth(): string
    {
        try {
            if (!$this->config['params']['email'] || !$this->config['params']['password']) {
                throw new Exception("credentials not set up");
            }
            $response = (new Client(['Content-Type' => 'application/json']))->post(
                $this->config['params']['eskiz_auth'],
                [
                    'form_params' => [
                        'email' => $this->config['params']['email'],
                        'password' => $this->config['params']['password']
                    ]
                ]
            );

            $body = json_decode($response->getBody()->getContents());

            switch ($response->getStatusCode()) {
                case Response::HTTP_BAD_REQUEST:
                    throw new Exception($body->message);
                case Response::HTTP_OK:
                    return $body->data->token;
                default:
                    throw new Exception("response status code is " . $response->getStatusCode());
            }
        } catch (GuzzleException $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Return Response
     *
     * @return Response
     */
    public function response(): Response
    {
        return $this->response;
    }

    /**
     * Return Response Code
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->response_code;
    }

    /**
     * Format phone numbers
     *
     * @param string|array $mobile
     * @return string|array
     */

    private function format($mobile)
    {
        $prefix = 'tel:';
        if (is_array($mobile)) {

            array_walk($mobile, function (&$value, $key) use ($prefix) {
                if (!(strpos($value, $prefix) !== false)) {
                    $value = $prefix . $value;
                }

            });

        } else {
            $mobile = [$prefix . $mobile];
        }

        return $mobile;
    }
}