<?php
class LoLException extends Exception{}
class LoL{

  //LoL id
  private $_id;

  public function getID(){
    return $this->_id;
  }

  public function setID($id){
    if(($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 999999999999 || $this->_id !== null)){
      throw new LoLException("LoL ID Error");
    }
    $this->_id = $id;
  }

  //LoL name
  private $_name;

  public function getName(){
    return $this->_name;
  }

  public function setName($name){
    if(strlen($name)<0 || strlen($name) > 255){
      throw new LoLException("LoL Name Error");
    }
    $this->_name = $name;
  }


  //Lol Class
  private $_class;

  public function getClass(){
    return $this->_class;
  }

  public function setClass($class){
    if(strlen($class)<0 || strlen($class) > 255){
      throw new LoLException("LoL Class Error");
    }
    $this->_class = $class;
  }


//LoL role
  private $_role;
  
  public function getRole(){
    return $this->_role;
  }

  public function setRole($role){
    if(strlen($role)<0 || strlen($role) > 255){
      throw new LoLException("LoL Role Error");
    }
    $this->_role = $role;
  }
  

  //LoL tier
  private $_tier;
  
  public function getTier(){
    return $this->_tier;
  }

  public function setTier($tier){
    if(strlen($tier)<0 || strlen($tier) > 255){
      throw new LoLException("LoL Tier Error");
    }
    $this->_tier = $tier;
  }
  

  //LoL winPct
  private $_winPct;
  
  public function getWinPct(){
    return $this->_winPct;
  }

  public function setWinPct($winPct){
    if(($winPct !== null) && (!is_numeric($winPct) || $winPct <= 0 || $winPct > 999999999999 )){
      throw new LoLException("LoL Win Pct Error");
    }
    $this->_winPct = $winPct;
  }


  //LoL pickPct
  private $_pickPct;
  
  public function getPickPct(){
    return $this->_pickPct;
  }

  public function setPickPct($pickPct){
    if(($pickPct !== null) && (!is_numeric($pickPct) || $pickPct <= 0 || $pickPct > 999999999999)){
      throw new LoLException("LoL Pick Pct Error");
    }
    $this->_pickPct = $pickPct;
  }


//ban Percentage  
  private $_banPct;
  
  public function getBanPct(){
    return $this->_banPct;
  }

  public function setBanPct($banPct){
    if(($banPct !== null) && (!is_numeric($banPct) || $banPct <= 0 || $banPct > 999999999999 )){
      throw new LoLException("LoL Ban Pct Error");
    }
    $this->_banPct = $banPct;
  }


//KDA
  private $_kda;

  public function getKDA(){
    return $this->_kda;
  }

  public function setKDA($kda){
    if(($kda !== null) && (!is_numeric($kda) || $kda <= 0 || $kda > 999999999999)){
      throw new LoLException("LoL Win Pct Error");
    }
    $this->_kda = $kda;
  }

  //Constructor
  public function __construct($id,$name,$class,$role,$tier,$winPct,$pickPct,$banPct,$kda)
  {
    $this->setID($id);
    $this->setName($name);
    $this->setClass($class);
    $this->setRole($role);
    $this->setTier($tier);
    $this->setWinPct($winPct);
    $this->setPickPct($pickPct);
    $this->setBanPct($banPct);
    $this->setKDA($kda);

  }

   //simpan dalam array
  public function LoLDataJSON(){
    $LoLdata = array();
    $LoLdata['id'] = $this->getID();
    $LoLdata['name'] = $this->getName();
    $LoLdata['class'] = $this->getClass();
    $LoLdata['role'] = $this->getRole();
    $LoLdata['tier'] = $this->getTier();
    $LoLdata['winPct'] = $this->getWinPct();
    $LoLdata['pickPct'] = $this->getPickPct();
    $LoLdata['banPct'] = $this->getBanPct();
    $LoLdata['kda'] = $this->getKDA();
    return $LoLdata;
  }  

}
