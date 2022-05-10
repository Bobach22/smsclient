<?php

namespace Bobach22\SmsClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 
 * @method static \Bobach22\SmsClient\Sms  send(string|array $to,string $message,string|int $dispatch_id = null)
 * 
 */

class Sms extends Facade{
    
    protected static function getFacadeAccessor()
    {
        return 'sms';
    }
}