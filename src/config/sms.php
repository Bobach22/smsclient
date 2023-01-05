<?php

return [
    'headers'=>[
        'Content-Type'=>'application/json'
    ],
    'params'=>[
        'country_code'           => env('SMS_COUNTRY_CODE'),
        'number_key'             => env('SMS_NUMBER_KEY'),
        'message_key'            => env('SMS_MESSAGE_KEY'),
        'service_url'            => env('SMS_SERVICE_URL'),
        'eskiz_auth'             => env('ESKIZ_AUTH'),
        'service_bulk_send_url'  => env('SMS_SERVICE_BULK_SEND_URL'),
        'method'                 => env('SMS_METHOD'),
        'from'                   => env('SMS_FROM'),
        'email'                  => env('SMS_EMAIL'),
        'password'               => env('SMS_PASSWORD'),
    ],
    'cache_expired_time' => env('SMS_TOKEN_EXPIRED_IN')
];