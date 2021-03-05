<?php

namespace Bobach22\RapidProSms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 
 * @method static \Bobach22\RapidProSms\Sms  send(string|array $to,string $message)
 * 
 */

class Sms extends Facade{
    
    protected static function getFacadeAccessor()
    {
        return 'sms';
    }
}