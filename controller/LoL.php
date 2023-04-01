<?php

require_once('dbConnection.php');
require_once('../model/LolDataModel.php');
require_once('../model/ResponseLogic.php');

try{
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
}
catch(PDOException $trials){
    error_log("Connection error -".$trials, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database connection error");
    $response->send();
    exit();
}

//auth

if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION'])<1){
    $response = new Response();
    $response->setHttpStatusCode(401);
    $response->setSuccess(false);
    $response->addMessage("Access token belum diberikan");
    $response->send();
    exit;
}

$accesstoken =$_SERVER['HTTP_AUTHORIZATION'];

try{

    $query = $writeDB->prepare('select userid, accesstokenexpired, activeStatus, loginAttempts from datasession, datauser where datasession.userid = datauser.id and accesstoken = :accesstoken');
    $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();

    if($rowCount === 0){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("Access token salah");
        $response->send();
        exit;
    }

    $row = $query->fetch(PDO::FETCH_ASSOC);

    $returned_userid = $row['userid'];
    $returned_accesstokenexpired = $row['accesstokenexpired'];
    $returned_activeStatus = $row['activeStatus'];
    $returned_loginAttempts = $row['loginAttempts'];

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
        $response->addMessage("Akun terkunci");
        $response->send();
        exit;
    }
    if(strtotime($returned_accesstokenexpired) < time()){
        $response = new Response();
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        $response->addMessage("Access token telah kadaluarsa");
        $response->send();
        exit;
    }
}
catch(PDOException $trials){
    $response = new Response();
    $response->setHttpStatusCode(401);
    $response->setSuccess(false);
    $response->addMessage("kendala saat proses autentikasi");
    $response->send();
    exit;
}
//

if(array_key_exists("lolid",$_GET)) {

    $lolid = $_GET["lolid"];

    if($lolid == '' || !is_numeric($lolid)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("LoL ID must be numeric and not blank");
        $response->send();
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){

        try{

            $query = $readDB->prepare('select id, name, class, role, tier, winPct, pickPct, banPct, kda from datalol where id =:lolid and userid = :userid');
            $query->bindParam(':lolid', $lolid, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();  

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("LoL Data not found");
                $response->send();
                exit;
            }
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $LoLdata = new LoL($row['id'], $row['name'], $row['class'], $row['role'], $row['tier'], $row['winPct'], $row['pickPct'], $row['banPct'], $row['kda']);
                $lolArray[] = $LoLdata->LoLDataJSON();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['lol'] = $lolArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;
            
        }
        catch(LoLException $trials){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($trials->getMessage());
            $response->send();
            exit;
        }

    }
    elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        try{
            $query = $writeDB->prepare('delete from datalol where id = :lolid and userid = :userid');
            $query->bindParam(':lolid', $lolid, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount=$query->rowCount();
            
            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("LoL Data not found");
                $response->send();
                exit;
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage("LoL Data deleted");
            $response->send();
            exit;

        }   
        catch(PDOException $trials){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to delete LoL Data");
            $response->send();
            exit;

        }
    }
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){

        try{
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

            $name_updated = false;
            $class_updated = false;
            $role_updated = false;
            $tier_updated = false;
            $winPct_updated = false;
            $pickPct_updated = false;
            $banPct_updated = false;
            $kda_updated = false;

            $queryFields = "";

            if(isset($jsonData->name)){
                $name_updated = true;
                $queryFields .= "name = :name, ";
            }

            if(isset($jsonData->class)){
                $class_updated = true;
                $queryFields .= "class = :class, ";
            }

            if(isset($jsonData->role)){
                $role_updated = true;
                $queryFields .= "role = :role, ";
            }

            if(isset($jsonData->tier)){
                $tier_updated = true;
                $queryFields .= "tier = :tier, ";
            }

            if(isset($jsonData->winPct)){
                $winPct_updated = true;
                $queryFields .= "winPct = :winPct, ";
            }

            if(isset($jsonData->pickPct)){
                $pickPct_updated = true;
                $queryFields .= "pickPct = :pickPct, ";
            }

            if(isset($jsonData->banPct)){
                $banPct_updated = true;
                $queryFields .= "banPct = :banPct, ";
            }

            if(isset($jsonData->kda)){
                $kda_updated = true;
                $queryFields .= "kda = :kda, ";
            }

            $queryFields = rtrim($queryFields, ", ");

            if($name_updated === false && $class_updated === false && $role_updated === false && $tier_updated === false && $winPct_updated === false && $pickPct_updated === false && $banPct_updated === false && $kda_updated === false){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("No LoL data provided");
                $response->send();        
                exit;  
            }

            $query = $writeDB->prepare('select id, name, class, role, tier, winPct, pickPct, banPct, kda from datalol where id =:lolid and userid = :userid');
            $query->bindParam(':lolid', $lolid, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("No LoL data found to update");
                $response->send();        
                exit;
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $LoLdata = new LoL($row['id'], $row['name'], $row['class'], $row['role'], $row['tier'], $row['winPct'], $row['pickPct'], $row['banPct'], $row['kda']);
            }

            $queryString = "update datalol set ".$queryFields." where id = :lolid and userid = :userid";
            $query = $writeDB->prepare($queryString);

            if($name_updated === true){
                $LoLdata->setName($jsonData->name);
                $up_name = $LoLdata->getName();
                $query->bindParam(':name', $up_name, PDO::PARAM_STR);
            }
            if($class_updated === true){
                $LoLdata->setClass($jsonData->class);
                $up_class = $LoLdata->getClass();
                $query->bindParam(':class', $up_class, PDO::PARAM_STR);
            }
            if($role_updated === true){
                $LoLdata->setRole($jsonData->role);
                $up_role = $LoLdata->getRole();
                $query->bindParam(':role', $up_role, PDO::PARAM_STR);
            }
            if($tier_updated === true){
                $LoLdata->setTier($jsonData->tier);
                $up_tier = $LoLdata->getTier();
                $query->bindParam(':tier', $up_tier, PDO::PARAM_STR);
            }
            if($winPct_updated === true){
                $LoLdata->setWinPct($jsonData->winPct);
                $up_winPct = $LoLdata->getWinPct();
                $query->bindParam(':winPct', $up_winPct, PDO::PARAM_INT);
            }
            if($pickPct_updated === true){
                $LoLdata->setPickPct($jsonData->pickPct);
                $up_pickPct = $LoLdata->getPickPct();
                $query->bindParam(':pickPct', $up_pickPct, PDO::PARAM_STR);
            }
            if($banPct_updated === true){
                $LoLdata->setBanPct($jsonData->banPct);
                $up_banPct = $LoLdata->getBanPct();
                $query->bindParam(':banPct', $up_banPct, PDO::PARAM_STR);
            }
            if($kda_updated === true){
                $LoLdata->setKDA($jsonData->kda);
                $up_kda = $LoLdata->getKDA();
                $query->bindParam(':kda', $up_kda, PDO::PARAM_STR);
            }

            $query->bindParam(':lolid', $lolid, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Lol data not updated");
                $response->send();        
                exit;
            }

            $query = $writeDB->prepare('select id, name, class, role, tier, winPct, pickPct, banPct, kda from datalol where id =:lolid and userid = :userid');
            $query->bindParam(':lolid', $lolid, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();
    
            $rowCount = $query->rowCount();
    
            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to get LoL Data after updated");
                $response->send();
                exit;
            }
        
            $lolArray=array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $LoLdata = new LoL($row['id'], $row['name'], $row['class'], $row['role'], $row['tier'], $row['winPct'], $row['pickPct'], $row['banPct'], $row['kda']);
                $lolArray[]=$LoLdata->LoLDataJSON();
            }
    
            $returnData = array();
            $returnData['row_returned'] = $rowCount;
            $returnData['lol'] = $lolArray;
    
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->addMessage("LoL data updated");
            $response->setData($returnData);
            $response->send();
            exit;

        }
        catch(LoLException $trials){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($trials->getMessage());
            $response->send();        
            exit;
        }
        catch(PDOException $trials){
            error_log("Database query error".$trials, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to update LoL Data");
            $response->send();
        }


    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit();
    }
}
elseif(array_key_exists("page",$_GET)) {

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        $page = $_GET['page'];

        if($page == '' || !is_numeric($page)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("page number tidak boleh kosong dan harus numerik");
            $response->send();
            exit;
        }

        $limitPage = 10;
        
        try{

            $query = $readDB->prepare('select count(id) as totalNoOfLoLDatas from datalol where userid = :userid');
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->execute();

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $loldatasCount = intval($row['totalNoOfLoLDatas']);
            
            $numOfPages = ceil($loldatasCount/$limitPage);

            if($numOfPages == 0){
                $numOfPages = 1;
            }

            if($page > $numOfPages || $page == 0 ){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Page not found");
                $response->send();
                exit;
            }

            $offset = ($page == 1 ? 0 : ($limitPage * ($page-1)));

            $query = $readDB->prepare('select id, name, class, role, tier, winPct, pickPct, banPct, kda from datalol where userid = :userid limit :pglimit offset :offset');
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->bindParam(':pglimit', $limitPage, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            $lolArray = array();
            
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $LoLdata = new LoL($row['id'], $row['name'], $row['class'], $row['role'], $row['tier'], $row['winPct'], $row['pickPct'], $row['banPct'], $row['kda']);
                $lolArray[] = $LoLdata->LoLDataJSON();
            }

            $returnData = array();
            $returnData['row_returned'] = $rowCount;
            $returnData['total_row'] =$loldatasCount;
            $returnData['total_pages'] =$numOfPages;
            if($page<$numOfPages){
                $returnData['has_next_pages'] =true;
            }elseif($page>=$numOfPages){
                $returnData['has_next_pages'] =false;
            }
            $returnData['LoL_data'] = $lolArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit;

        }
        catch(LoLException $trials){
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage($trials->getMessage());
            $response->send();
            exit;
        }
        catch(PDOException $trials){
            $response = new Response();
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->addMessage("Gagal mendapatkan LoL data");
            $response->send();
            exit;
        }
    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("request method not allowed");
        $response->send();
        exit;
    }
    
}
elseif(empty($_GET)){
 
 if($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{
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

        if(!isset($jsonData->name))
        {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("LoL name is missing");
            $response->send();
            exit;
        }

        $newLoLDATA = new LoL(null, $jsonData->name, $jsonData->class, $jsonData->role, $jsonData->tier, $jsonData->winPct,$jsonData->pickPct, $jsonData->banPct, $jsonData->kda);

        $name = $newLoLDATA->getName();
        $class = $newLoLDATA->getClass();
        $role = $newLoLDATA->getRole();
        $tier = $newLoLDATA->getTier();
        $winPct = $newLoLDATA->getWinPct();
        $pickPct = $newLoLDATA->getPickPct();
        $banPct = $newLoLDATA->getBanPct();
        $kda = $newLoLDATA->getKDA();

        $query = $writeDB->prepare('insert into datalol (name, class, role, tier, winPct, pickPct, banPct, kda, userid) values (:name, :class, :role, :tier, :winPct, :pickPct, :banPct, :kda, :userid)');
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->bindParam(':class', $class, PDO::PARAM_STR);
        $query->bindParam(':role', $role, PDO::PARAM_STR);
        $query->bindParam(':tier', $tier, PDO::PARAM_STR);
        $query->bindParam(':winPct', $winPct, PDO::PARAM_STR);
        $query->bindParam(':pickPct', $pickPct, PDO::PARAM_STR);
        $query->bindParam(':banPct', $banPct, PDO::PARAM_STR);
        $query->bindParam(':kda', $kda, PDO::PARAM_STR);
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to create LoL data");
            $response->send();
            exit;
        }

        $lastLoLDataCreated = $writeDB->lastInsertId();

        $query = $writeDB->prepare('select id, name, class, role, tier, winPct, pickPct, banPct, kda from datalol where id = :lolid and userid = :userid');
        $query->bindParam(':lolid', $lastLoLDataCreated, PDO::PARAM_INT);
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get LoL Data after created");
            $response->send();
            exit;
        }

        $lolArray=array();

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $LoLdata = new LoL($row['id'], $row['name'], $row['class'], $row['role'], $row['tier'], $row['winPct'], $row['pickPct'], $row['banPct'], $row['kda']);
            $lolArray[]=$LoLdata->LoLDataJSON();
        }

        $returnData = array();
        $returnData['row_returned'] = $rowCount;
        $returnData['lol'] = $lolArray;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->toCache(true);
        $response->addMessage("LoL data created");
        $response->setData($returnData);
        $response->send();
        exit;


        


    }
    catch(LoLException $trials){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage($trials->getMessage());
        $response->send();
        exit;
    }
    catch(PDOException $trials){
        error_log("Database query error".$trials, 0);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to create LoL Data");
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
else{
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint not found");
    $response->send();
}