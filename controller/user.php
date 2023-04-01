<?php

require_once('dbConnection.php');
require_once('../model/ResponseLogic.php');

try{

    $writeDB = DB::connectWriteDB();
}
catch (PDOException $trials){
    error_log("Connection error -".$trials, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database connection error");
    $response->send();
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $response = new Response();
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request method not allowed");
    $response->send();
    exit();
}

$contentType = isset($_SERVER['CONTENT_TYPE']);
if($contentType && $_SERVER['CONTENT_TYPE'] !== 'application/json'){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("Content Type header not set to JSON");
    $response->send();
    exit;
}

$rawPostData = file_get_contents('php://input');

if(!$jsonData = json_decode($rawPostData)){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("Request body is not valid JSON");
    $response->send();
    exit;
}

if(!isset($jsonData->username)){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("username tidak diberikan");
    $response->send();
    exit;
}

if(!isset($jsonData->password)){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("password tidak diberikan");
    $response->send();
    exit;
}

if(strlen($jsonData->username ) < 1){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("username is blank");
    $response->send();
    exit;
}

if(strlen($jsonData->password) < 1){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("password is blank");
    $response->send();
    exit;
}

$username = $jsonData->username;
$password = $jsonData->password;

try{
   
    $query = $writeDB->prepare('select id from datauser where username = :username');
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    $rowCount = $query->rowCount();

    if($rowCount !== 0){
        $response = new Response();
        $response->setHttpStatusCode(409);
        $response->setSuccess(false);
        $response->addMessage("username sudah terpakai");
        $response->send();
        exit();
    }

    $hashed_pwd=password_hash($password, PASSWORD_DEFAULT);

    $query = $writeDB->prepare('insert into datauser (username, password) values (:username, :password)');
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $hashed_pwd, PDO::PARAM_STR);
    $query->execute();

    $rowCount = $query->rowCount();

    if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("aww, issue while making user account");
        $response->send();
        exit();
    }

    $lastUserID = $writeDB->lastInsertId();

    $returnData = array();
    $returnData['user_id'] = $lastUserID;
    $returnData['username'] = $username;

    $response = new Response();
    $response->setHttpStatusCode(201);
    $response->setSuccess(true);
    $response->addMessage("user account created");
    $response->setData($returnData);
    $response->send();
    exit();

}
catch(PDOException $trials){
    error_log("Database query error -".$trials, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Issue while making the account");
    $response->send();
    exit();
}

