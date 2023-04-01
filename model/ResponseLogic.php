<?php

  class Response {

    //Bool setSuccess 
    private $_success;

    public function setSuccess($success) {
      $this->_success = $success;
    }

    //Http status
    private $_httpStatusCode;

    public function setHttpStatusCode($httpStatusCode){
      $this->_httpStatusCode = $httpStatusCode;
    }

    //add message
    private $_messages = array();

    public function addMessage($message){
      $this->_messages[] = $message;
    }

    //set the data
    private $_data;

    public function setData($data){
      $this->_data = $data;
    }

    //set cache
    private $_toCache = false;

    public function toCache($toCache){
      $this->_toCache = $toCache;
    }

    //send respone
    private $_responseData = array();

    public function send() {

      header('Content-type: application/json;charset=utf-8');
 
      if($this->_toCache == true){
        header('Cache-control: max-age=60');
      }
      else{
        header('Cache-control: no-cache, no-store');
      }
 
      if(($this->_success !== false && $this->_success !== true) || !is_numeric($this->_httpStatusCode)) {
        http_response_code(500);
        $this->_responseData['statusCode'] = 500;
        $this->_responseData['success'] = false;
        $this->addMessage("Response creation error");
        $this->_responseData['messages'] = $this->_messages;
      }
      else{
        http_response_code($this->_httpStatusCode);
        $this->_responseData['statusCode'] = $this->_httpStatusCode;
        $this->_responseData['success'] = $this->_success;
        $this->_responseData['messages'] = $this->_messages;
        $this->_responseData['data'] = $this->_data;
      }
 
      echo json_encode($this->_responseData);
    }
  }
   

   

   

   

   

   
