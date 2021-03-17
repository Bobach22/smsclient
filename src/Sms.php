<?php

namespace Bobach22\RapidProSms;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Illuminate\Http\Response;

class Sms{

    private static $client=null;
    private $config=array();
    private $response=null;
    private $response_code=null;
    private $country_code=null;
    

     /**
     * Sms constructor.
     */
    public function __construct()
    {   
        $this->loadConfig();
        $this->createClient();
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

    public function countryCode(string $country_code){
        $this->country_code=$country_code;
        return $this;
    }

    /**
     * Send message
     * @param string|array $to
     * @param string $message
     * 
     * @return $this
     * @throws RequestException
     */

    public function send($to,$message){
        $this->config['params']['country_code']?$this->addCountryCode($to):$to;
        $headers=$this->config['headers'];
        
        foreach($headers as $key=>$value){
            if($key==='Authorization'){
                $headers[$key]='Token '.$value;
            }
        }

        $phone=$this->format($to);
        $payload=json_encode([
            'urns'=>$phone,
            'text'=>$message
        ]);

        try {
            $request=new Request('POST','https://rapidpro.ilhasoft.mobi/api/v2/broadcasts.json',$headers,$payload);
            $promise=$this->getClient()->sendAsync(
                $request,
            );


            $res=$promise->wait();
            $this->response_code=$res->getStatusCode();
            $this->response=new Response($res->getBody(),$res->getStatusCode(),$res->getHeaders());

        }catch(RequestException $e){
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $this->response=new Response($response->getBody(),$response->getStatusCode(),$response->getHeaders());
            }
        }
        return $this;
    }

    /**
     * Get Client
     *
     * @return GuzzleHttp\Client
     */
    public function getClient():Client
    {
        return self::$client;
    }



    /**
     * Load config
     *
     * @return $this
     */

    protected function loadConfig(){
        $this->config=config('sms');
        return $this;
    }

    /**
     * Add country code to mobile
     *
     * @param  string|array $mobile
     * @return string|array
     */

    private function addCountryCode($mobile){
        if(!$this->country_code){
            $this->country_code=config('sms.params.country_code');
        }
        if(is_array($mobile)){
            array_walk($mobile,function(&$value,$key){
                if(!$this->hasCountryCode($value)){
                    $value=$this->country_code . $value;
                }

            });
            return $mobile;
        }
        return $this->hasCountryCode($mobile)?$mobile:$this->country_code . $mobile;
    }

    /**
     * Check phone number(s) for country code
     * 
     * @param  string $mobile
     * @return boolean
     */

    private function hasCountryCode($mobile){
    
                if(strpos($mobile,'+')!==false){
                    return true;
                }
                return false;
           
    }

    /**
     * Format phone numbers
     * 
     * @param string|array $mobile
     * @return string|array
     */
    
    private function format($mobile){
        $prefix='tel:';
         if(is_array($mobile)){
             
            array_walk($mobile,function(&$value,$key) use($prefix) {                
                if(!(strpos($value,$prefix)!==false)){
                $value=$prefix.$value;
                }

            });

         }else{
             $mobile=[$prefix.$mobile];
         }

        return $mobile;
     }

     /**
     * Return Response
     *
     * @return Response
     */
    public function response():Response
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
}