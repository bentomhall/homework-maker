<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require "repository.php";
$credentials = getCredentials();
$post_data = file_get_contents("php://input");
$data = json_decode($post_data,true);
if (!isset($data["subject"])|| !isset($data["api"]) || $data["api"] != $credentials["apiToken"]) {
    http_response_code(400);
    die();
}
$repo = new Repository($credentials);
$subject = $data["subject"];
try {
    $repo->addSubject($subject);
} catch (Exception $exc) {
    http_response_code(400);
    echo $exc->getTraceAsString();
    die();
}
http_response_code(200);




