<?php

return [
    'headers'=>[
        'Authorization'=>'Token '.env('YOUR_TOKEN'),
        'Content-Type'=>'application/json'
    ],
    'params'=>[
        'url'=>'https://rapidpro.ilhasoft.mobi/api/v2/broadcasts.json',
        'country_code'=>''
    ]
];