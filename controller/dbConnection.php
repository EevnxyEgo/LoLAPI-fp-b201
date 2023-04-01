<?php


class DB {
 
//menggunakan 2 jenis koneksi untuk scalabillity
  private static $writeDBConnection;

  private static $readDBConnection;
  
//Database access menggunakan PDO
  public static function connectWriteDB() {
    
    $dbHost ="127.0.0.1";  
    $dbName="loldb";  
    $dbCharset="utf-8";  
    $dbUser="root";          
    $dbPassword=""; 
    if(self::$writeDBConnection === null) {
      self::$writeDBConnection = new PDO("mysql:host=$dbHost;dbname=$dbName;$dbCharset",$dbUser,$dbPassword);
      self::$writeDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$writeDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    return self::$writeDBConnection;
  }

  public static function connectReadDB() {

    $dbHost ="127.0.0.1";  
    $dbName="loldb";  
    $dbCharset="utf-8";  
    $dbUser="root";          
    $dbPassword=""; 
    if(self::$readDBConnection === null) {
      self::$readDBConnection = new PDO("mysql:host=$dbHost;dbname=$dbName;$dbCharset",$dbUser,$dbPassword);
      self::$readDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$readDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    return self::$readDBConnection;
  }

}
