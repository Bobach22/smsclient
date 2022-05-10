## Package to use sms services

## Installation

You can install the package via composer:

```bash
composer require bobach22/smsclient
```
Publishing required files of package:

```bash
php artisan vendor:publish --provider="Bobach22\SmsClient\SmsServiceProvider"
```
In config folder set params for sms.php

## Usage
------

```php
use Bobach22\SmsClient\Facades\Sms;

$sms=Sms::send('any number','your message');
// with array of numbers
// Sms::send(['number1','number2'],'your message')
$res=$sms->response()
