<?php
/*
  OpenDCIM - Feature Project Maitrise Link
  Author: Alexandre Oliveira
*/

class MaitriseType {
  var $MaitriseTypeID;
  var $MaitriseName;

  static function GetAll(){
    global $dbh;
    $stmt=$dbh->prepare("SELECT * FROM fac_MaitriseType ORDER BY MaitriseName ASC;");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_CLASS,"MaitriseType");
  }

  static function Insert($name){
    global $dbh;
    $stmt=$dbh->prepare("INSERT INTO fac_MaitriseType SET MaitriseName=:name;");
    return $stmt->execute(array(":name"=>$name));
  }

  static function Delete($id){
    global $dbh;
    $stmt=$dbh->prepare("DELETE FROM fac_MaitriseType WHERE MaitriseTypeID=:id;");
    return $stmt->execute(array(":id"=>$id));
  }

  static function Update($id,$name){
    global $dbh;
    $stmt=$dbh->prepare("UPDATE fac_MaitriseType SET MaitriseName=:name WHERE MaitriseTypeID=:id;");
    return $stmt->execute(array(":id"=>intval($id), ":name"=>$name));
  }
}
?>
