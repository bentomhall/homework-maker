<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require "repository.php";

$post_data = file_get_contents("php://input");
$subject = json_decode($post_data,true)["subject"];
if (!isset($subject)) {
    http_response_code(400);
    echo "Malformed JSON: missing subject";
}
$credentials = getCredentials();
$repo = new Repository($credentials);
try {
    $repo->addSubject($subject);
} catch (Exception $exc) {
    http_response_code(400);
    echo $exc->getTraceAsString();
    die();
}
http_response_code(200);




