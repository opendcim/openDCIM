<?php
class HDD {
    public $HDDID;
    public $DeviceID;
    public $Label;
    public $SerialNo;
    public $Status;
    public $Size;
    public $TypeMedia;
    public $DateAdd;
    public $DateWithdrawn;
    public $DateDestruction;
    public $StatusDestruction;
    public $Note;

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

	private static function logAction($action, $HDDID) {
		global $person, $dbh;
		$sql = "INSERT INTO fac_GenericLog (UserID, Time, ItemType, ItemID, LogText) VALUES (?, NOW(), 'HDD', ?, ?)";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$person->UserID, $HDDID, $action]);
	}

	public static function WithdrawByID($id) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET Status='pending_destruction', dateWithdrawn=NOW() WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Withdrawn (pending destruction)", $id);
	}

	public static function DeleteByID($id) {
		global $dbh;
		$sql = "DELETE FROM fac_HDD WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Deleted", $id);
	}

	public static function MarkDestroyed($id) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET StatusDestruction='destroyed', dateDestruction=NOW() WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Marked as destroyed", $id);
	}

	public static function ReassignToDevice($id, $deviceID) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET Status='on', DeviceID=?, dateWithdrawn=NULL, dateDestruction=NULL, StatusDestruction=NULL WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$deviceID, $id]);
		self::logAction("Reassigned to DeviceID $deviceID", $id);
	}

	public static function MarkAsSpare($id) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET Status='spare' WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Marked as spare", $id);
	}

	public static function CreateEmpty($deviceID) {
		global $dbh;
		$sql = "INSERT INTO fac_HDD (DeviceID, Label, SerialNo, Status, TypeMedia, Size, dateAdd) VALUES (?, '', '', 'on', 'SATA', 0, NOW())";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$deviceID]);
		$id = $dbh->lastInsertId();
		self::logAction("Created new empty HDD", $id);
	}

	public static function DuplicateToEmptySlots($sourceHDDID) {
		global $dbh;
		$sql = "SELECT * FROM fac_HDD WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$sourceHDDID]);
		if (!$hdd = $stmt->fetch(PDO::FETCH_ASSOC)) return;

		$sql = "SELECT DeviceID FROM fac_HDD WHERE HDDID=?";
		$devID = $dbh->prepare($sql);
		$devID->execute([$sourceHDDID]);
		$DeviceID = $devID->fetchColumn();

		$sql = "SELECT HDDCount FROM fac_DeviceTemplateHdd dt INNER JOIN fac_Device d ON d.TemplateID=dt.TemplateID WHERE d.DeviceID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$DeviceID]);
		$max = $stmt->fetchColumn();

		$sql = "SELECT COUNT(*) FROM fac_HDD WHERE DeviceID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$DeviceID]);
		$current = $stmt->fetchColumn();

		$remaining = $max - $current;
		if ($remaining <= 0) return;

		$sql = "INSERT INTO fac_HDD (DeviceID, Label, SerialNo, Status, TypeMedia, Size, dateAdd) VALUES (?, ?, '', ?, ?, ?, NOW())";
		$stmt = $dbh->prepare($sql);
		for ($i = 0; $i < $remaining; $i++) {
			$stmt->execute([$DeviceID, $hdd['Label'], $hdd['Status'], $hdd['TypeMedia'], $hdd['Size']]);
			$id = $dbh->lastInsertId();
			self::logAction("Duplicated from HDDID $sourceHDDID", $id);
		}
	}

	public static function ExportPendingDestruction($deviceID) {
		global $dbh;
		$sql = "SELECT Label, SerialNo, dateWithdrawn FROM fac_HDD WHERE DeviceID=? AND Status='pending_destruction'";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$deviceID]);

		echo "Label\tSerial Number\tDate Withdrawn\n";
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			echo "{$row['Label']}\t{$row['SerialNo']}\t{$row['dateWithdrawn']}\n";
		}
	}
}
?>
