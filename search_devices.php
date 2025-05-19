<?php
require 'db.inc.php';
$q   = trim($_GET['q'] ?? '');
$out = [];

if (strlen($q) >= 2) {
    $sql = "
      SELECT
        d.DeviceID,
        d.Label AS Name
      FROM fac_Device d
      JOIN fac_DeviceTemplate t  ON d.TemplateID = t.TemplateID
      JOIN fac_DeviceTemplateHdd dth ON t.TemplateID = dth.TemplateID
      WHERE dth.EnableHDDFeature = 1          -- flag HDD activé
        AND d.Label LIKE ?                    -- on cherche sur d.Label, pas e.Label
      ORDER BY d.Label                        -- idem pour ORDER BY
      LIMIT 20
    ";
    $stmt = $db->prepare($sql);
    $like = "%{$q}%";
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
}

header('Content-Type: application/json');
echo json_encode($out);
