<?php

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class HDD {
    // Properties
    public int      $HDDID = 0;
    public int      $DeviceID = 0;
    public string   $SerialNo;
    public string   $Status;
    public int      $Size;
    public string   $TypeMedia;
    public string	$DateAdd;
    public ?string	$DateWithdrawn;
    public ?string	$DateDestroyed;
    // Path to proof PDF (e.g. files/hdd/proof_YYYYMMDD-HHMMSS_RANDOM8.pdf)
    public ?string  $ProofFile = null;

    private LoggerInterface $logger;

    // Constructor
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger ?? new NullLogger();
    }

    // Sanitize data before database operations
    public function MakeSafe(): void {
        //$this->HDDID             = intval($this->HDDID);
        $this->DeviceID          = intval($this->DeviceID);
        $this->SerialNo          = sanitize($this->SerialNo);
        $this->Status            = sanitize($this->Status);
        $this->Size              = intval($this->Size);
        $this->TypeMedia         = sanitize($this->TypeMedia);
        if (isset($this->ProofFile) && $this->ProofFile !== null) {
            global $config;
            $pf = sanitize($this->ProofFile);
            $relBase = $config->ParameterArray['hdd_proof_path'] ?? 'assets/files/hdd/';
            $relBase = rtrim($relBase, '/') . '/';
            // If path doesn't start with configured base, collapse to base + basename
            if (strpos($pf, $relBase) !== 0) {
                $pf = $relBase . basename($pf);
            }
            $this->ProofFile = $pf;
        }
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
                  (DeviceID, SerialNo, Status, Size, TypeMedia, DateAdd)
                VALUES
                  (:DeviceID, :SerialNo, :Status, :Size, :TypeMedia, NOW())";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ":DeviceID"          => $this->DeviceID,
            ":SerialNo"          => $this->SerialNo,
            ":Status"            => $this->Status,
            ":Size"              => $this->Size,
            ":TypeMedia"         => $this->TypeMedia,
        ]);
        $this->HDDID = intval($dbh->lastInsertId());
        self::logAction("Created", $this->HDDID);
    }

    // Quick creation from form data
    public static function CreateFromForm(int $deviceID, string $serialNo, string $typeMedia, int $size): int {
        global $dbh;
        $stmt = $dbh->prepare(
            "INSERT INTO fac_HDD
                (DeviceID, SerialNo, Status, TypeMedia, Size, DateAdd)
             VALUES
                (:DeviceID, :SerialNo, 'On', :TypeMedia, :Size, NOW())"
        );
        $stmt->execute([
            ':DeviceID'  => $deviceID,
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
               SerialNo   = :SerialNo,
               Status     = :Status,
               Size       = :Size,
               TypeMedia  = :TypeMedia,
               ProofFile  = :ProofFile
             WHERE HDDID = :HDDID"
        );
        $res = $stmt->execute([
            ":DeviceID"  => $this->DeviceID,
            ":SerialNo"  => $this->SerialNo,
            ":Status"    => $this->Status,
            ":Size"      => $this->Size,
            ":TypeMedia" => $this->TypeMedia,
            ":ProofFile" => $this->ProofFile,
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
	public static function DuplicateToEmptySlots(int $sourceHDDID): array {
		global $dbh;
		$created = [];
		// Récupère le disque source
		$stmt = $dbh->prepare("SELECT * FROM fac_HDD WHERE HDDID = ?");
		$stmt->execute([$sourceHDDID]);
		$hdd = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$hdd) {
			return $created;
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
			return $created;
		}// Combien sont déjà présents ?
		$stmt = $dbh->prepare("SELECT COUNT(*) FROM fac_HDD WHERE DeviceID = ? AND (Status = 'On' OR Status = 'Off')");
		$stmt->execute([$deviceID]);
		$current = intval($stmt->fetchColumn());
	
		$remaining = $max - $current;
		if ($remaining <= 0) {
			return $created;
		} // Insère les duplicata
		$stmt = $dbh->prepare(
			"INSERT INTO fac_HDD
			 (DeviceID, SerialNo, Status, TypeMedia, Size, DateAdd)
			 VALUES
			 (?, ?, ?, ?, ?, NOW())"
		);
		for ($i = 0; $i < $remaining; $i++) {
			$stmt->execute([
				$deviceID,
				uniqid('HDD_', true),
				$hdd['Status'],
				$hdd['TypeMedia'],
				$hdd['Size']
			]);
			$newId = intval($dbh->lastInsertId());
			$created[] = $newId;
			self::logAction("Duplicated from HDDID $sourceHDDID", $newId);
		}
		return $created;
	}

    // Send HDD for destruction
    public function SendForDestruction(string $note = ''): bool {
        global $dbh;
        $this->MakeSafe();
        $stmt = $dbh->prepare(
            "UPDATE fac_HDD SET
               Status            = 'Pending_destruction',
               DateWithdrawn     = NOW()
             WHERE HDDID = :HDDID"
        );
        return $stmt->execute([
            ":HDDID" => $this->HDDID,
        ]);
    }

    // Mark HDD as destroyed
    public static function MarkDestroyed(int $id, ?string $destroyDate = null): bool {
		global $dbh;
		$dateValue = null;
		if ($destroyDate !== null && trim($destroyDate) !== '') {
			$destroyDate = trim($destroyDate);
			$formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];
			foreach ($formats as $format) {
				$dt = DateTime::createFromFormat($format, $destroyDate);
				if ($dt instanceof DateTime) {
					$dateValue = $dt->format('Y-m-d H:i:s');
					break;
				}
			}
		}

		if ($dateValue) {
			$stmt = $dbh->prepare(
				"UPDATE fac_HDD SET
				   Status = 'Destroyed',
				   DateDestroyed   = :DateDestroyed
				 WHERE HDDID = :HDDID"
			);
			$params = [
				':DateDestroyed' => $dateValue,
				':HDDID' => $id
			];
		} else {
			$stmt = $dbh->prepare(
				"UPDATE fac_HDD SET
				   Status = 'Destroyed',
				   DateDestroyed   = NOW()
				 WHERE HDDID = :HDDID"
			);
			$params = [':HDDID' => $id];
		}

		$res = $stmt->execute($params);
		if ($res) {
			self::logAction("Marked as destroyed", $id);
		}
		return $res;
	}
	
	//Reassign a HDD to another device
	public static function ReassignToDevice(int $id, int $deviceID): bool {
		global $dbh;
		$current = self::GetHDDByID($id);
		$currentDevice = ($current instanceof HDD) ? intval($current->DeviceID) : 0;
		if ($currentDevice !== $deviceID && self::GetRemainingSlotCount($deviceID) <= 0) {
			return false;
		}
		$stmt = $dbh->prepare(
			"UPDATE fac_HDD SET
			DeviceID = ?,
			Status    = 'On',
			DateWithdrawn   = NULL,
			DateDestroyed = NULL
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

	public static function GetRemainingSlotCount(int $deviceID): int {
		global $dbh;
		$stmt = $dbh->prepare(
			"SELECT COALESCE(cfg.HDDCount, 0) AS Capacity,
			        COALESCE(u.ActiveCount, 0) AS Used
			   FROM fac_Device d
			   LEFT JOIN fac_DeviceTemplateHdd cfg ON cfg.TemplateID = d.TemplateID AND cfg.EnableHDDFeature = 1
			   LEFT JOIN (
			        SELECT DeviceID, COUNT(*) AS ActiveCount
			          FROM fac_HDD
			         WHERE Status IN ('On','Off')
			         GROUP BY DeviceID
			   ) u ON u.DeviceID = d.DeviceID
			  WHERE d.DeviceID = :DeviceID"
		);
		$stmt->execute([':DeviceID' => $deviceID]);
		if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$capacity = intval($row['Capacity']);
			$used = intval($row['Used']);
			if ($capacity <= 0) {
				return 0;
			}
			$remaining = $capacity - $used;
			return ($remaining > 0) ? $remaining : 0;
		}
		return 0;
	}

	public static function RecordAudit(int $deviceID, string $userID): bool {
		global $dbh;
		$stmt = $dbh->prepare(
			"INSERT INTO fac_GenericLog
			   (UserID, Class, ObjectID, ChildID, Action, Property, OldVal, NewVal)
			 VALUES
			   (:UserID, 'HDD', :ObjectID, NULL, 'HDD_Audit', 'Audit', '', '')"
		);
		return $stmt->execute([
			':UserID'   => $userID,
			':ObjectID' => $deviceID
		]);
	}

	public static function RecordGenericLog(?int $deviceID, string $userID, string $action, string $details = ''): bool {
		global $dbh;
		$objectId = $deviceID ?? 0;
		$stmt = $dbh->prepare(
			"INSERT INTO fac_GenericLog
			   (UserID, Class, ObjectID, ChildID, Action, Property, OldVal, NewVal)
			 VALUES
			   (:UserID, 'HDD', :ObjectID, NULL, :Action, 'Details', '', :NewVal)"
		);
		return $stmt->execute([
			':UserID'   => $userID,
			':ObjectID' => $objectId,
			':Action'   => $action,
			':NewVal'   => $details
		]);
	}

	public static function GetLastAudit(int $deviceID): ?array {
		global $dbh;
		$sql = "SELECT g.Time AS AuditTime, g.UserID,
					   NULLIF(TRIM(CONCAT(COALESCE(p.FirstName,''), ' ', COALESCE(p.LastName,''))), '') AS PersonName
				  FROM fac_GenericLog g
				  LEFT JOIN fac_People p ON p.UserID = g.UserID
				 WHERE g.Class='HDD' AND g.Action='HDD_Audit' AND g.ObjectID = :DeviceID
				 ORDER BY g.Time DESC LIMIT 1";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([':DeviceID' => $deviceID]);
		if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			return [
				'AuditTime'   => $row['AuditTime'],
				'DisplayName' => ($row['PersonName'] ?: $row['UserID'])
			];
		}
		return null;
	}

    // List active HDDs for a device
    public static function GetHDDByDevice(int $DeviceID): array {
        global $dbh;
        $stmt = $dbh->prepare("SELECT * FROM fac_HDD WHERE DeviceID = :DeviceID AND (Status = 'On' OR Status = 'Off') ORDER BY SerialNo ASC");
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
               AND Status = 'Pending_destruction'
             ORDER BY DateWithdrawn DESC"
        );
        $stmt->execute([':DeviceID' => $DeviceID]);

        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = self::RowToObject($row);
        }
        return $list;
    }

    // List destroyed HDDs (destruction completed)
    public static function GetDestroyedHDDByDevice(int $DeviceID): array {
        global $dbh;
        $stmt = $dbh->prepare(
            "SELECT * FROM fac_HDD
             WHERE DeviceID = :DeviceID
               AND Status = 'Destroyed'
             ORDER BY DateDestroyed DESC"
        );
        $stmt->execute([":DeviceID" => $DeviceID]);
        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = self::RowToObject($row);
        }
        return $list;
    }
    
    // Accessor for proof file
    public function GetProofFile(): ?string {
        return $this->ProofFile ?? null;
    }

    // Mutator to set/update proof file for this HDD
    public function SetProofFile(string $filename): bool {
        global $dbh;
        $this->ProofFile = $filename;
        $this->MakeSafe();
        $stmt = $dbh->prepare("UPDATE fac_HDD SET ProofFile = :ProofFile WHERE HDDID = :HDDID");
        $res = $stmt->execute([
            ':ProofFile' => $this->ProofFile,
            ':HDDID'     => $this->HDDID
        ]);
        if ($res) {
            self::logAction("Set proof file", $this->HDDID);
        }
        return $res;
    }

    // Batch update proof file for multiple HDD IDs
    public static function SetProofFileForIds(array $ids, string $filename): int {
        global $dbh;
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) return 0;
        $pf = sanitize($filename);
        if (strpos($pf, 'files/hdd/') !== 0) {
            $pf = 'files/hdd/' . basename($pf);
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE fac_HDD SET ProofFile = ? WHERE HDDID IN ($placeholders)";
        $stmt = $dbh->prepare($sql);
        $params = array_merge([$pf], $ids);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
// List HDDs Spare destruction
	public static function GetSpareHDDByDevice(int $DeviceID): array {
        global $dbh;
        $stmt = $dbh->prepare(
            "SELECT * FROM fac_HDD
             WHERE DeviceID = :DeviceID
               AND Status = 'Spare'
             ORDER BY DateWithdrawn DESC"
        );
        $stmt->execute([':DeviceID' => $DeviceID]);

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
	//- sheet "hdd in prod" for Status = 'On'
    //- sheet "hdd out prod" for Status = 'Off'
	//- sheet "Pending_destruction"   for Status = 'Pending_destruction'
	//- sheet "Destroyed" for Status = 'Destroyed'
    //- sheet "Spare" for Status = 'Spare'
	
	public static function ExportAllToXls(int $deviceID): void {
        global $dbh;
        // Crée le classeur
        $spreadsheet = new Spreadsheet();

        $statuses = [
            'On'      => 'hdd in prod',
            'Off'     => 'hdd out prod',
            'Pending_destruction' => 'Pending_destruction',
            'Destroyed' => 'Destroyed',
            'Spare' => 'Spare'
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
            $headers = ['HDDID','SerialNo','Status','TypeMedia','Size','DateAdd','DateWithdrawn','DateDestroyed'];
            $sheet->fromArray($headers, null, 'A1');

            // Récupère les HDDs pour ce statut
            $stmt = $dbh->prepare(
                "SELECT HDDID, SerialNo, Status, TypeMedia, Size,
                        DateAdd, DateWithdrawn, DateDestroyed
                   FROM fac_HDD
                  WHERE DeviceID = :DeviceID
                    AND Status = :Status
                  ORDER BY SerialNo ASC"
            );
            $stmt->execute([
                ':DeviceID'          => $deviceID,
                ':Status'            => $status,
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
