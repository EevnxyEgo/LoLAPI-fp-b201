<?php

require_once('dbConnection.php');
require_once('../model/ResponseLogic.php');

try{
    $writeDB = DB::connectWriteDB();
}
catch(PDOException $trials){
    error_log("Connection error :".$trials, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database Connection Error");
    $response->send();
    exit;
}

if(array_key_exists("sessionid", $_GET)){

    $sessionid = $_GET['sessionid'];

    if($sessionid === '' || !is_numeric($sessionid)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Session ID tidak boleh kosong dan harus dalam bentuk angka");
        $response->send();
        exit;
    }

    if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION'])<1){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("Access token tidak ada");
        $response->send();
        exit;  
    }

    $ValidAccessToken = $_SERVER['HTTP_AUTHORIZATION'];

    if($_SERVER['REQUEST_METHOD'] === 'DELETE'){

        try{

            $query = $writeDB->prepare('delete from datasession where id = :sessionid and accesstoken = :accesstoken');
            $query->bindParam(':sessionid', $sessionid, PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $ValidAccessToken, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("kendala saat log off menggunakan access token yang diberikan");
                $response->send();
                exit;
            }

            $returnData = array();
            $returnData['sessionid'] = intval($sessionid);

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("berhasil log out");
            $response->setData($returnData);
            $response->send();
            exit;

        }
        catch(PDOException $trials){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("kendala saat log off, coba lagi");
            $response->send();
            exit;
        }

    }
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){

        $contentType = isset($_SERVER['CONTENT_TYPE']);
        if($contentType && $_SERVER['CONTENT_TYPE'] !== 'application/json'){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Content Type header not set to JSON");
            $response->send();
            exit;
        }

        $rawPatchData = file_get_contents('php://input');

        if(!$jsonData = json_decode($rawPatchData)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Request body is not valid JSON");
            $response->send();
            exit;
        }

        if(!isset($jsonData->refresh_token) || strlen($jsonData->refresh_token) < 1){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Refresh token tidak diberikan");
            $response->send();
            exit;
        }

        try{
            $ValidRefreshAccessToken = $jsonData->refresh_token;

            $query = $writeDB->prepare('select datasession.id as sessionid, datasession.userid as userid, accesstoken, refreshtoken, activeStatus, loginAttempts, accesstokenexpired, refreshtokenexpired from datasession, datauser where datauser.id = datasession.userid and datasession.id = :sessionid and datasession.accesstoken = :accesstoken and datasession.refreshtoken = :refreshtoken');
            $query->bindParam(':sessionid', $sessionid, PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $ValidAccessToken, PDO::PARAM_STR);
            $query->bindParam(':refreshtoken', $ValidRefreshAccessToken, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage("access token atau refresh token salah");
                $response->send();
                exit;      
            }

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $returned_sessionid = $row['sessionid'];
            $returned_userid = $row['userid'];
            $returned_accesstoken = $row['accesstoken'];
            $returned_refreshtoken = $row['refreshtoken'];
            $returned_activeStatus = $row['activeStatus'];
            $returned_loginAttempts = $row['loginAttempts'];
            $returned_accesstokenexpired= $row['accesstokenexpired'];
            $returned_refreshtokenexpired= $row['refreshtokenexpired'];
            
            if($returned_activeStatus !== 'Y'){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage("Akun tidak aktif");
                $response->send();
                exit;
            }

            if($returned_loginAttempts >= 5){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage("Akun telah terkunci");
                $response->send();
                exit;
            }

            if(strtotime($returned_refreshtokenexpired)<time()){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage("Refresh token expired");
                $response->send();
                exit;
            }

            $newAccessToken1 = openssl_random_pseudo_bytes(30);
            $newAccessToken2 = openssl_random_pseudo_bytes(30);

            $rawAccessToken1 = bin2hex($newAccessToken1).time();
            $rawAccessToken2 = bin2hex($newAccessToken2).time();

            $ValidAccessToken = base64_encode($rawAccessToken1);
            $ValidRefreshAccessToken = base64_encode($rawAccessToken2);

            $AccessTokenExpired = 1800;
            $RefreshTokenExpired = 604800;

            $query = $writeDB->prepare('update datasession set accesstoken = :accesstoken, accesstokenexpired = date_add(NOW(), INTERVAL :accesstokenexpired SECOND), refreshtoken = :refreshtoken, refreshtokenexpired = date_add(NOW(), INTERVAL :refreshtokenexpired SECOND) where id = :sessionid and userid = :userid and accesstoken = :returnedaccesstoken and refreshtoken = :returnedrefreshtoken');
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->bindParam(':sessionid', $returned_sessionid, PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $ValidAccessToken, PDO::PARAM_STR);
            $query->bindParam(':accesstokenexpired', $AccessTokenExpired, PDO::PARAM_INT);
            $query->bindParam(':refreshtoken', $ValidRefreshAccessToken, PDO::PARAM_STR);
            $query->bindParam(':refreshtokenexpired', $RefreshTokenExpired, PDO::PARAM_INT);
            $query->bindParam(':returnedaccesstoken',$returned_accesstoken,PDO::PARAM_STR);
            $query->bindParam(':returnedrefreshtoken',$returned_refreshtoken,PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->addMessage("access token tidak bisa diperbaharui, coba login kembali");
                $response->send();
                exit;    
            }

            $returnData = array();
            $returnData['sessionid']=$returned_sessionid;
            $returnData['accesstoken']=$ValidAccessToken;
            $returnData['accesstokenexpired']=$AccessTokenExpired;
            $returnData['refreshtoken']=$ValidRefreshAccessToken;
            $returnData['refreshtokenexpired']=$RefreshTokenExpired;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("token telah diperbaharui");
            $response->setData($returnData);
            $response->send();
            exit;


        }
        catch(PDOException $trials){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Kendala mendapatkan refresh token");
            $response->send();
            exit;
        }



    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }




}
elseif(empty($_GET)){

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }

    sleep(1);

    $contentType = isset($_SERVER['CONTENT_TYPE']);
    if($contentType && $_SERVER['CONTENT_TYPE'] !== 'application/json'){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Content type header is not set to JSON");
        $response->send();
        exit;
    }

    $rawPOSTData = file_get_contents('php://input');

    if(!$jsonData = json_decode($rawPOSTData)){
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
    elseif(!isset($jsonData->password)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("password tidak diberikan");
        $response->send();
        exit;
    }

    if(strlen($jsonData->username) < 1 || strlen($jsonData->username) > 255 ||
    strlen($jsonData->password) < 1 || strlen($jsonData->password) > 255){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (strlen($jsonData->username) < 1 ? $response->addMessage("Username tidak boleh kosong") : false);
        (strlen($jsonData->password) < 1 ? $response->addMessage("Password tidak boleh kosong") : false);
        (strlen($jsonData->username) > 255 ? $response->addMessage("Username tidak boleh melebihi 255 karakter") : false);
        (strlen($jsonData->password) > 255 ? $response->addMessage("Password tidak boleh melebihi 255 karakter") : false);
        $response->send();
        exit;
    }

    try{

        $username = $jsonData->username;
        $password = $jsonData->password;

        $query = $writeDB->prepare('select id, username, password, activeStatus, loginAttempts from datauser where username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("username atau password yang diberikan salah, coba lagi!");
            $response->send();
            exit;
        }

        $row = $query->fetch(PDO::FETCH_ASSOC);

        $returned_id = $row['id'];
        $returned_username = $row['username'];
        $returned_password = $row['password'];
        $returned_activeStatus = $row['activeStatus'];
        $returned_loginAttempts = $row['loginAttempts'];

        if($returned_activeStatus !== 'Y'){
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("akun tidak aktif");
            $response->send();
            exit;
        }

        if($returned_loginAttempts >= 5){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("percobaan login telah melebihi batas, akun telah diamankan");
            $response->send();
            exit;
        }

        if(!password_verify($password, $returned_password)){
            
            $query = $writeDB->prepare('update datauser set loginAttempts = loginAttempts+1 where id= :id');
            $query->bindParam(':id',$returned_id, PDO::PARAM_STR);
            $query->execute();
            
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("username atau password yang diberikan salah");
            $response->send();
            exit;
        }

        $newAccessToken1 = openssl_random_pseudo_bytes(30);
        $newAccessToken2 = openssl_random_pseudo_bytes(30);

        $rawAccessToken1 = bin2hex($newAccessToken1).time();
        $rawAccessToken2 = bin2hex($newAccessToken2).time();

        $ValidAccessToken = base64_encode($rawAccessToken1);
        $ValidRefreshAccessToken = base64_encode($rawAccessToken2);

        $AccessTokenExpired = 1800;
        $RefreshTokenExpired = 604800;

    }
    catch(PDOException $trials){
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("terjadi kendala saat log in");
        $response->send();
        exit;
    }

    try{
        $writeDB->beginTransaction();
        $query = $writeDB->prepare('update datauser set loginAttempts = 0 where id= :id');
        $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
        $query->execute();

        $query = $writeDB->prepare('insert into datasession (userid, accesstoken, accesstokenexpired, refreshtoken, refreshtokenexpired) values (:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpired SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpired SECOND))');
        $query->bindParam(':userid', $returned_id, PDO::PARAM_INT);
        $query->bindParam(':accesstoken', $ValidAccessToken, PDO::PARAM_STR);
        $query->bindParam(':accesstokenexpired', $AccessTokenExpired, PDO::PARAM_INT);
        $query->bindParam(':refreshtoken', $ValidRefreshAccessToken, PDO::PARAM_STR);
        $query->bindParam(':refreshtokenexpired', $RefreshTokenExpired, PDO::PARAM_INT);
        $query->execute();

        $lastSessionID = $writeDB->lastInsertId();

        $writeDB->commit();

        $returnData = array();
        $returnData['sessionID'] = intval($lastSessionID);
        $returnData['accessToken'] = $ValidAccessToken;
        $returnData['accessTokenExpired'] = $AccessTokenExpired;
        $returnData['refreshToken'] = $ValidRefreshAccessToken;
        $returnData['refreshTokenExpired'] = $RefreshTokenExpired;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->setData($returnData);
        $response->send();
        exit;
    }
    catch(PDOException $trials){
        $writeDB->rollBack();
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("terjadi kendala saat proses log in, coba lagi");
        $response->send();
        exit;
    }
}
else{
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint not found");
    $response->send();
    exit;
}