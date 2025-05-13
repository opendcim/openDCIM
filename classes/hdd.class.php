<?php

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class HDD {
    // Properties
    public int      $HDDID = 0;
    public int      $DeviceID = 0;
    public string   $Label;
    public string   $SerialNo;
    public string   $Status;
    public int      $Size;
    public string   $TypeMedia;
    public string	$DateAdd;
    public ?string	$DateWithdrawn;
    public ?string	$DateDestruction;
    public string	$StatusDestruction;
    public ?string  $Note;

    private LoggerInterface $logger;

    // Constructor
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger ?? new NullLogger();
    }

    // Sanitize data before database operations
    public function MakeSafe(): void {
        //$this->HDDID             = intval($this->HDDID);
        $this->DeviceID          = intval($this->DeviceID);
        $this->Label             = sanitize($this->Label);
        $this->SerialNo          = sanitize($this->SerialNo);
        $this->Status            = sanitize($this->Status);
        $this->Size              = intval($this->Size);
        $this->TypeMedia         = sanitize($this->TypeMedia);
        $this->StatusDestruction = sanitize($this->StatusDestruction);
        $this->Note              = sanitize($this->Note);
    }

	public function MakeDisplay(): void {
		// ex. $this->DateAdd = date('d/m/Y', strtotime($this->DateAdd));
	}
	
	// Convert a PDO row into an HDD object
    public static function RowToObject(array $row): self {
        $hdd = new self();
        foreach ($row as $prop => $val) {
            if (property_exists($hdd, $prop)) {
                $hdd->$prop = $val;
            }
        }
        $hdd->MakeDisplay();
        return $hdd;
    }

    // Get a HDD by its ID
    public static function GetHDDByID(int $id): ?self {
        global $dbh;
        $stmt = $dbh->prepare("SELECT * FROM fac_HDD WHERE HDDID = ?");
        $stmt->execute([$id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return self::RowToObject($row);
        }
        return null;
    }

    // Create a HDD (instance method)
    public function Create(): void {
        global $dbh;
        $this->MakeSafe();
        $sql = "INSERT INTO fac_HDD
                  (DeviceID, Label, SerialNo, Status, Size, TypeMedia, DateAdd, StatusDestruction, Note)
                VALUES
                  (:DeviceID, :Label, :SerialNo, :Status, :Size, :TypeMedia, NOW(), :StatusDestruction, :Note)";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ":DeviceID"          => $this->DeviceID,
            ":Label"             => $this->Label,
            ":SerialNo"          => $this->SerialNo,
            ":Status"            => $this->Status,
            ":Size"              => $this->Size,
            ":TypeMedia"         => $this->TypeMedia,
            ":StatusDestruction" => $this->StatusDestruction,
            ":Note"              => $this->Note
        ]);
        $this->HDDID = intval($dbh->lastInsertId());
        self::logAction("Created", $this->HDDID);
    }

    // Quick creation from form data
    public static function CreateFromForm(int $deviceID, string $label, string $serialNo, string $typeMedia, int $size): int {
        global $dbh;
        $stmt = $dbh->prepare(
            "INSERT INTO fac_HDD
                (DeviceID, Label, SerialNo, Status, TypeMedia, Size, DateAdd, StatusDestruction)
             VALUES
                (:DeviceID, :Label, :SerialNo, 'On', :TypeMedia, :Size, NOW(), 'none')"
        );
        $stmt->execute([
            ':DeviceID'  => $deviceID,
            ':Label'     => sanitize($label),
            ':SerialNo'  => sanitize($serialNo),
            ':TypeMedia' => sanitize($typeMedia),
            ':Size'      => $size
        ]);
        $newId = intval($dbh->lastInsertId());
        self::logAction("Created from form", $newId);
        return $newId;
    }

    // Update an existing HDD
    public function Update(): bool {
        global $dbh;
        $this->MakeSafe();
        $stmt = $dbh->prepare(
            "UPDATE fac_HDD SET
               DeviceID   = :DeviceID,
               Label      = :Label,
               SerialNo   = :SerialNo,
               Status     = :Status,
               Size       = :Size,
               TypeMedia  = :TypeMedia,
               Note       = :Note
             WHERE HDDID = :HDDID"
        );
        $res = $stmt->execute([
            ":DeviceID"  => $this->DeviceID,
            ":Label"     => $this->Label,
            ":SerialNo"  => $this->SerialNo,
            ":Status"    => $this->Status,
            ":Size"      => $this->Size,
            ":TypeMedia" => $this->TypeMedia,
            ":Note"      => $this->Note,
            ":HDDID"     => $this->HDDID
        ]);
        if ($res) self::logAction("Updated", $this->HDDID);
        return $res;
    }

    // Delete this HDD
	public static function DeleteByID(int $id): bool {
		global $dbh;
		$stmt = $dbh->prepare("DELETE FROM fac_HDD WHERE HDDID = ?");
		$res  = $stmt->execute([$id]);
		if ($res) {
			self::logAction("Deleted", $id);
		}
		return $res;
	}

	// Duplicate this HDD
	public static function DuplicateToEmptySlots(int $sourceHDDID): void {
		global $dbh;
		// Récupère le disque source
		$stmt = $dbh->prepare("SELECT * FROM fac_HDD WHERE HDDID = ?");
		$stmt->execute([$sourceHDDID]);
		$hdd = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$hdd) {
			return;
		}// Nombre max de HDD permis par template
		$deviceID = intval($hdd['DeviceID']);
		$stmt = $dbh->prepare(
			"SELECT dt.HDDCount
			   FROM fac_DeviceTemplateHdd dt
			   JOIN fac_Device d ON d.TemplateID = dt.TemplateID
			  WHERE d.DeviceID = ?"
		);
		$stmt->execute([$deviceID]);
		$max = intval($stmt->fetchColumn());
	
		if ($max <= 0) {
			return;
		}// Combien sont déjà présents ?
		$stmt = $dbh->prepare("SELECT COUNT(*) FROM fac_HDD WHERE DeviceID = ?");
		$stmt->execute([$deviceID]);
		$current = intval($stmt->fetchColumn());
	
		$remaining = $max - $current;
		if ($remaining <= 0) {
			return;
		} // Insère les duplicata
		$stmt = $dbh->prepare(
			"INSERT INTO fac_HDD
			 (DeviceID, Label, SerialNo, Status, TypeMedia, Size, DateAdd)
			 VALUES
			 (?, ?, ?, ?, ?, ?, NOW())"
		);
		for ($i = 0; $i < $remaining; $i++) {
			$stmt->execute([
				$deviceID,
				$hdd['Label'],
				uniqid('HDD_', true),
				$hdd['Status'],
				$hdd['TypeMedia'],
				$hdd['Size']
			]);
			$newId = intval($dbh->lastInsertId());
			self::logAction("Duplicated from HDDID $sourceHDDID", $newId);
		}
	}

    // Send HDD for destruction
    public function SendForDestruction(string $note = ''): bool {
        global $dbh;
        $this->MakeSafe();
        $stmt = $dbh->prepare(
            "UPDATE fac_HDD SET
               Status            = 'Pending_destruction',
               StatusDestruction = 'pending',
               DateWithdrawn     = NOW(),
               Note              = CONCAT(Note, ' ', :Note)
             WHERE HDDID = :HDDID"
        );
        return $stmt->execute([
            ":HDDID" => $this->HDDID,
            ":Note"  => sanitize($note)
        ]);
    }

    // Mark HDD as destroyed
    public static function MarkDestroyed(int $id): bool {
		global $dbh;
		$stmt = $dbh->prepare(
			"UPDATE fac_HDD SET
			   StatusDestruction = 'destroyed',
			   DateDestruction   = NOW()
			 WHERE HDDID = ?"
		);
		$res = $stmt->execute([$id]);
		if ($res) {
			self::logAction("Marked as destroyed", $id);
		}
		return $res;
	}
	
	//Reassign a HDD to another device
	public static function ReassignToDevice(int $id, int $deviceID): bool {
		global $dbh;
		$stmt = $dbh->prepare(
			"UPDATE fac_HDD SET
			DeviceID = ?,
			Status    = 'On',
			DateWithdrawn   = NULL,
			DateDestruction = NULL,
			StatusDestruction = 'none'
			WHERE HDDID = ?"
		);
		$res = $stmt->execute([$deviceID, $id]);
		if ($res) {
			self::logAction("Reassigned to DeviceID $deviceID", $id);
		}
		return $res;
	}

	//Mark a HDD as spare by its ID
	public static function MarkAsSpare(int $id): bool {
		global $dbh;
		$stmt = $dbh->prepare(
			"UPDATE fac_HDD SET
			Status = 'Spare'
			WHERE HDDID = ?"
		);
		$res = $stmt->execute([$id]);
		if ($res) {
			self::logAction("Marked as Spare", $id);
		}
		return $res;
	}

    // List active HDDs for a device
    public static function GetHDDByDevice(int $DeviceID): array {
        global $dbh;
        $stmt = $dbh->prepare("SELECT * FROM fac_HDD WHERE DeviceID = :DeviceID AND StatusDestruction = 'none' ORDER BY Label ASC");
        $stmt->execute([":DeviceID" => $DeviceID]);
        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = self::RowToObject($row);
        }
        return $list;
    }

    // List HDDs pending destruction
	public static function GetPendingByDevice(int $DeviceID): array {
        global $dbh;
        $stmt = $dbh->prepare(
            "SELECT * FROM fac_HDD
             WHERE DeviceID = :DeviceID
               AND StatusDestruction = 'pending'
             ORDER BY DateWithdrawn DESC"
        );
        $stmt->execute([':DeviceID' => $DeviceID]);

        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = self::RowToObject($row);
        }
        return $list;
    }

    // List retired HDDs (destruction completed)
    public static function GetRetiredHDDByDevice(int $DeviceID): array {
        global $dbh;
        $stmt = $dbh->prepare(
            "SELECT * FROM fac_HDD
             WHERE DeviceID = :DeviceID
               AND StatusDestruction = 'destroyed'
             ORDER BY DateDestruction DESC"
        );
        $stmt->execute([":DeviceID" => $DeviceID]);
        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = self::RowToObject($row);
        }
        return $list;
    }

    // Search HDDs by serial number
    public static function SearchBySerial(string $SerialNo): array {
        global $dbh;
        $stmt = $dbh->prepare("SELECT * FROM fac_HDD WHERE SerialNo LIKE :SerialNo");
        $stmt->execute([":SerialNo" => "%" . sanitize($SerialNo) . "%"]);
        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = self::RowToObject($row);
        }
        return $list;
    }

	//Export all HDDs of a device into a 3-sheet XLS
	//- sheet "hdd en prod" for StatusDestruction = 'none'
	//- sheet "pending"   for StatusDestruction = 'pending'
	//- sheet "destroyed" for StatusDestruction = 'destroyed'

	
	public static function ExportAllToXls(int $deviceID): void {
        global $dbh;
        // Crée le classeur
        $spreadsheet = new Spreadsheet();

        $statuses = [
            'none'      => 'hdd en prod',
            'pending'   => 'pending',
            'destroyed' => 'destroyed',
        ];

        $first = true;
        foreach ($statuses as $status => $title) {
            // Feuille active ou création
            $sheet = $first
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();
            $first = false;
            $sheet->setTitle($title);

            // En-têtes colonnes
            $headers = ['HDDID','Label','SerialNo','Status','TypeMedia','Size','DateAdd','DateWithdrawn','DateDestruction','StatusDestruction','Note'];
            $sheet->fromArray($headers, null, 'A1');

            // Récupère les HDDs pour ce statut
            $stmt = $dbh->prepare(
                "SELECT HDDID, Label, SerialNo, Status, TypeMedia, Size,
                        DateAdd, DateWithdrawn, DateDestruction, StatusDestruction, Note
                   FROM fac_HDD
                  WHERE DeviceID = :DeviceID
                    AND StatusDestruction = :StatusDestruction
                  ORDER BY Label ASC"
            );
            $stmt->execute([
                ':DeviceID'          => $deviceID,
                ':StatusDestruction' => $status,
            ]);

            // Remplit les lignes
            $row = 2;
            while ($h = $stmt->fetch(PDO::FETCH_NUM)) {
                // $h est un array indexé de 0 à 10, dans le même ordre que les headers
                $sheet->fromArray($h, null, "A{$row}");
                $row++;
            }
        }

        // En-têtes HTTP & envoi du fichier
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="HDD_List_Device_' . $deviceID . '.xls"');

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
        exit;
    }


    // Generic logging to fac_GenericLog
    private static function logAction(string $action, int $HDDID): void {
        global $person, $dbh;
        $stmt = $dbh->prepare(
            "INSERT INTO fac_GenericLog
             (UserID, Class, ObjectID, Action, Time)
             VALUES
             (:UserID, 'HDD', :ObjectID, :Action, CURRENT_TIMESTAMP)"
        );
        $stmt->execute([
            ":UserID"   => $person->UserID,
            ":ObjectID" => $HDDID,
            ":Action"   => $action
        ]);
    }
}
?>