<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$IS_DEBUG = true;

function log_error(string $message, string $error) {
    $log_message = $message .": ".$error . "\n";
    error_log($log_message, 3, __DIR__ . "\error_log.txt");
    return;
}

function debug_log(string $message) {
    global $IS_DEBUG;
    if ($IS_DEBUG) {
        file_put_contents(__DIR__."\debug_log.txt", $message."\n", FILE_APPEND);
    }
    return;
}
