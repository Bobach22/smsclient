<?php

/*
 * Add helper function
 */

if (!function_exists('sms')) {

    /**
     * @param string $to
     * @param string $message
     * @return mixed
     */
    function sms($to = null, $message = null)
    {
        $sms = app('sms');
        if (!(is_null($to) || is_null($message))) {
            return $sms->send($to, $message);
        }
        return $sms;
    }
}