<?php

use Psr\Log\NullLogger;
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
		// Placeholder pour formatage si nécessaire
	}

	static function RowToObject($row) {
		$hdd = new HDD();
		foreach ($row as $prop => $val) {
			$hdd->$prop = $val;
		}
		$hdd->MakeDisplay();
		return $hdd;
	}

	public static function GetHDDByID($id) {
		global $dbh;
		$id = intval($id);
		$sql = "SELECT * FROM fac_HDD WHERE HDDID = ?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			return self::RowToObject($row);
		}
		return null;
	}

	function Create() {
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
		self::logAction("Created", $this->HDDID);
	}

	public static function CreateFromForm($deviceID, $label, $serialNo, $typeMedia, $size) {
		global $dbh;
		$sql = "INSERT INTO fac_HDD (DeviceID, Label, SerialNo, Status, TypeMedia, Size, DateAdd)
				VALUES (:DeviceID, :Label, :SerialNo, 'On', :TypeMedia, :Size, NOW())";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':DeviceID' => intval($deviceID),
			':Label' => sanitize($label),
			':SerialNo' => sanitize($serialNo),
			':TypeMedia' => sanitize($typeMedia),
			':Size' => intval($size)
		]);
		$id = $dbh->lastInsertId();
		self::logAction("Created from form", $id);
		return $id;
	}

	function Update() {
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
		$result = $stmt->execute([
			":DeviceID" => $this->DeviceID,
			":Label" => $this->Label,
			":SerialNo" => $this->SerialNo,
			":Status" => $this->Status,
			":Size" => $this->Size,
			":TypeMedia" => $this->TypeMedia,
			":Note" => $this->Note,
			":HDDID" => $this->HDDID
		]);

		if ($result) {
			self::logAction("Updated", $this->HDDID);
		}
		return $result;
	}

	function Delete() {
		global $dbh;
		$this->MakeSafe();

		$sql = "DELETE FROM fac_HDD WHERE HDDID = :HDDID";
		$stmt = $dbh->prepare($sql);
		$result = $stmt->execute([":HDDID" => $this->HDDID]);
		if ($result) {
			self::logAction("Deleted", $this->HDDID);
		}
		return $result;
	}

	function SendForDestruction($note = '') {
		global $dbh;
		$this->MakeSafe();
		$note = sanitize($note);

		$sql = "UPDATE fac_HDD SET
			Status = 'Pending_destruction',
			StatusDestruction = 'Pending',
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
			Status = 'Destroyed_h2',
			StatusDestruction = 'Destroyed',
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

		$list = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$list[] = self::RowToObject($row);
		}
		return $list;
	}

	static function SearchBySerial($SerialNo) {
		global $dbh;
		$SerialNo = "%" . sanitize($SerialNo) . "%";

		$sql = "SELECT * FROM fac_HDD WHERE SerialNo LIKE :SerialNo";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([":SerialNo" => $SerialNo]);

		$list = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$list[] = self::RowToObject($row);
		}
		return $list;
	}

	static function GetPendingDestruction() {
		global $dbh;
		$sql = "SELECT * FROM fac_HDD WHERE StatusDestruction = 'Pending'";
		$stmt = $dbh->query($sql);

		$list = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$list[] = self::RowToObject($row);
		}
		return $list;
	}

	// LOGGING
	private static function logAction($action, $HDDID) {
		//global $person, $dbh;
		//$sql = "INSERT INTO fac_GenericLog (UserID, Class, ObjectID, ChildID, Property, Action, OldVal, NewVal, Time)
		//        VALUES (:UserID, 'HDD', :ObjectID, :ChildID, :Property, :Action, :OldVal, :NewVal, CURRENT_TIMESTAMP)";
		//$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':UserID' => $person->UserID,
			':ObjectID' => $HDDID,
			':ChildID' => null,
			':Property' => null,
			':Action' => $action,
			':OldVal' => null,
			':NewVal' => null
		]);
	}

	// STATIC ACTIONS UTILITAIRES
	public static function WithdrawByID($id) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET Status='Pending_destruction', DateWithdrawn=NOW() WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Withdrawn (Pending destruction)", $id);
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
		$sql = "UPDATE fac_HDD SET StatusDestruction='Destroyed', DateDestruction=NOW() WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Marked as destroyed", $id);
	}

	public static function ReassignToDevice($id, $deviceID) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET Status='On', DeviceID=?, DateWithdrawn=NULL, DateDestruction=NULL, StatusDestruction=NULL WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$deviceID, $id]);
		self::logAction("Reassigned to DeviceID $deviceID", $id);
	}

	public static function MarkAsSpare($id) {
		global $dbh;
		$sql = "UPDATE fac_HDD SET Status='Spare' WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$id]);
		self::logAction("Marked as Spare", $id);
	}

	public static function CreateEmpty($deviceID) {
		global $dbh;
	
		$SerialNo = uniqid('HDD_', true); // Génère un identifiant unique
		$sql = "INSERT INTO fac_HDD (DeviceID, Label, SerialNo, Status, TypeMedia, Size, DateAdd)
				VALUES (:DeviceID, '', :SerialNo, 'On', 'SATA', 0, NOW())";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':DeviceID' => intval($deviceID),
			':SerialNo' => $SerialNo
		]);
		$id = $dbh->lastInsertId();
		self::logAction("Created new empty HDD", $id);
		return $id;
	}
	

	public static function DuplicateToEmptySlots($sourceHDDID) {
		global $dbh;

		$sql = "SELECT * FROM fac_HDD WHERE HDDID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$sourceHDDID]);
		if (!$hdd = $stmt->fetch(PDO::FETCH_ASSOC)) return;

		$DeviceID = $hdd['DeviceID'];

		$sql = "SELECT dt.HDDCount FROM fac_DeviceTemplateHdd dt
		        INNER JOIN fac_Device d ON d.TemplateID = dt.TemplateID
		        WHERE d.DeviceID = ?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$DeviceID]);
		$max = $stmt->fetchColumn();

		if (!$max || !$DeviceID) return;

		$sql = "SELECT COUNT(*) FROM fac_HDD WHERE DeviceID=?";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$DeviceID]);
		$current = $stmt->fetchColumn();

		$remaining = $max - $current;
		if ($remaining <= 0) return;

		$sql = "INSERT INTO fac_HDD (DeviceID, Label, SerialNo, Status, TypeMedia, Size, DateAdd)
		        VALUES (?, ?, '', ?, ?, ?, NOW())";
		$stmt = $dbh->prepare($sql);
		for ($i = 0; $i < $remaining; $i++) {
			$stmt->execute([$DeviceID, $hdd['Label'], $hdd['Status'], $hdd['TypeMedia'], $hdd['Size']]);
			$id = $dbh->lastInsertId();
			self::logAction("Duplicated from HDDID $sourceHDDID", $id);
		}
	}

	public static function ExportPendingDestruction($deviceID) {
		global $dbh;
		$sql = "SELECT Label, SerialNo, DateWithdrawn FROM fac_HDD
		        WHERE DeviceID = ? AND Status = 'Pending_destruction'";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([$deviceID]);

		echo "Label\tSerial Number\tDate Withdrawn\n";
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			echo "{$row['Label']}\t{$row['SerialNo']}\t{$row['DateWithdrawn']}\n";
		}
	}
	public static function GetRetiredHDDByDevice($DeviceID) {
	global $dbh;
	$DeviceID = intval($DeviceID);
	$sql = "SELECT * FROM fac_HDD WHERE DeviceID = :DeviceID AND Status = 'Retired'";
	$stmt = $dbh->prepare($sql);
	$stmt->execute([':DeviceID' => $DeviceID]);
	
	$hddList = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$hdd = new HDD();
		foreach ($row as $key => $value) {
			$hdd->$key = $value;
		}
		$hddList[] = $hdd;
	}
	return $hddList;
	}
}
?>
