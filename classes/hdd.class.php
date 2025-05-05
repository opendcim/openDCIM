<?php
class HDD {
    var $HDDID;
    var $DeviceID;
    var $Label;
    var $SerialNo;
    var $Status;
    var $Size;
    var $TypeMedia;
    var $DateAdd;
    var $DateWithdrawn;
    var $DateDestruction;
    var $StatusDestruction;
    var $Note;

    function MakeSafe() {
        $this->HDDID = intval($this->HDDID);
        $this->DeviceID = intval($this->DeviceID);
        $this->Label = sanitize($this->Label);
        $this->SerialNo = sanitize($this->SerialNo);
        $this->Status = sanitize($this->Status);
        $this->Size = intval($this->Size);
        $this->TypeMedia = sanitize($this->TypeMedia);
        $this->Note = sanitize($this->Note);
    }

    function MakeDisplay() {
        // Si besoin d'afficher des données formatées
    }

    static function RowToObject($row){
        $hdd = new HDD();
        foreach($row as $prop => $val){
            $hdd->$prop = $val;
        }
        $hdd->MakeDisplay();
        return $hdd;
    }

    function CreateHDD() {
        global $dbh;

        $this->MakeSafe();

        $sql = "INSERT INTO fac_HDD (DeviceID, Label, SerialNo, Status, Size, TypeMedia, DateAdd, StatusDestruction, Note)
                VALUES (:DeviceID, :Label, :SerialNo, :Status, :Size, :TypeMedia, NOW(), 'none', :Note)";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ":DeviceID" => $this->DeviceID,
            ":Label" => $this->Label,
            ":SerialNo" => $this->SerialNo,
            ":Status" => $this->Status,
            ":Size" => $this->Size,
            ":TypeMedia" => $this->TypeMedia,
            ":Note" => $this->Note
        ]);

        $this->HDDID = $dbh->lastInsertId();
    }

    function UpdateHDD() {
        global $dbh;

        $this->MakeSafe();

        $sql = "UPDATE fac_HDD SET
                    DeviceID = :DeviceID,
                    Label = :Label,
                    SerialNo = :SerialNo,
                    Status = :Status,
                    Size = :Size,
                    TypeMedia = :TypeMedia,
                    Note = :Note
                WHERE HDDID = :HDDID";

        $stmt = $dbh->prepare($sql);
        return $stmt->execute([
            ":DeviceID" => $this->DeviceID,
            ":Label" => $this->Label,
            ":SerialNo" => $this->SerialNo,
            ":Status" => $this->Status,
            ":Size" => $this->Size,
            ":TypeMedia" => $this->TypeMedia,
            ":Note" => $this->Note,
            ":HDDID" => $this->HDDID
        ]);
    }

    function DeleteHDD() {
        global $dbh;

        $this->MakeSafe();

        $sql = "DELETE FROM fac_HDD WHERE HDDID = :HDDID";

        $stmt = $dbh->prepare($sql);
        return $stmt->execute([":HDDID" => $this->HDDID]);
    }

    function SendForDestruction($note = '') {
        global $dbh;

        $this->MakeSafe();
        $note = sanitize($note);

        $sql = "UPDATE fac_HDD SET
                    Status = 'pending_destruction',
                    StatusDestruction = 'pending',
                    DateWithdrawn = NOW(),
                    Note = CONCAT(Note, ' ', :Note)
                WHERE HDDID = :HDDID";

        $stmt = $dbh->prepare($sql);
        return $stmt->execute([
            ":HDDID" => $this->HDDID,
            ":Note" => $note
        ]);
    }

    function MarkAsDestroyed($note = '') {
        global $dbh;

        $this->MakeSafe();
        $note = sanitize($note);

        $sql = "UPDATE fac_HDD SET
                    Status = 'destroyed_h2',
                    StatusDestruction = 'destroyed',
                    DateDestruction = NOW(),
                    Note = CONCAT(Note, ' ', :Note)
                WHERE HDDID = :HDDID";

        $stmt = $dbh->prepare($sql);
        return $stmt->execute([
            ":HDDID" => $this->HDDID,
            ":Note" => $note
        ]);
    }

    static function GetHDDByDevice($DeviceID) {
        global $dbh;

        $DeviceID = intval($DeviceID);

        $sql = "SELECT * FROM fac_HDD WHERE DeviceID = :DeviceID ORDER BY Label ASC";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([":DeviceID" => $DeviceID]);

        $hddList = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $hddList[] = self::RowToObject($row);
        }

        return $hddList;
    }

    static function SearchBySerial($SerialNo) {
        global $dbh;

        $SerialNo = "%".sanitize($SerialNo)."%";

        $sql = "SELECT * FROM fac_HDD WHERE SerialNo LIKE :SerialNo";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([":SerialNo" => $SerialNo]);

        $hddList = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $hddList[] = self::RowToObject($row);
        }

        return $hddList;
    }

    static function GetPendingDestruction() {
        global $dbh;

        $sql = "SELECT * FROM fac_HDD WHERE StatusDestruction = 'pending'";

        $stmt = $dbh->query($sql);

        $hddList = array();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $hddList[] = self::RowToObject($row);
        }

        return $hddList;
    }
}
?>
