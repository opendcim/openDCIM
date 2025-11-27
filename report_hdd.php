<?php
// report_hdd.php (refactored dynamic table with DataTables)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.inc.php';
require_once __DIR__ . '/facilities.inc.php';
require_once __DIR__ . '/classes/hdd.class.php';

// Access control
if (!($person->ManageHDD || $person->SiteAdmin || $person->ReadAccess)) {
    echo __('This report requires global read access.');
    header('Refresh: 3; url=' . redirect());
    exit;
}

// Subheader for template
$subheader = __('HDD Management Report');

// 1) Build SQL: HDD en attente de destruction (avec contexte site)
$sql = "
    SELECT
      h.HDDID,
      h.DeviceID,
      d.Label AS DeviceLabel,
      dc.Name AS SiteName,
      h.SerialNo,
      h.Status,
      h.Size,
      h.TypeMedia,
      h.DateAdd,
      h.DateWithdrawn,
      h.DateDestroyed,
      h.ProofFile
    FROM fac_HDD h
    LEFT JOIN fac_Device d ON d.DeviceID = h.DeviceID
    LEFT JOIN fac_Cabinet c ON c.CabinetID = d.Cabinet
    LEFT JOIN fac_DataCenter dc ON dc.DataCenterID = c.DataCenterID
";

// Execute query
$stmt = $dbh->prepare($sql);
$stmt->execute();
$hddList = $stmt->fetchAll(PDO::FETCH_OBJ);
$proofPathSetting = $config->ParameterArray['hdd_proof_path'] ?? 'assets/files/hdd/';
$proofWebBase = rtrim($proofPathSetting, '/') . '/';

if (!function_exists('build_hdd_proof_url')) {
    /**
     * Build a public URL for a stored proof file value.
     */
    function build_hdd_proof_url($storedValue, $webBase) {
        $value = trim((string)$storedValue);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^(?:[a-z]+:)?//#i', $value) === 1 || strpos($value, '/') === 0) {
            return $value;
        }
        if (preg_match('#^[A-Za-z]:\\\\#', $value) === 1 || strpos($value, '/') !== false || strpos($value, '\\') !== false) {
            return $value;
        }
        return $webBase . $value;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($subheader, ENT_QUOTES); ?></title>
  <!-- Include DataTables CSS -->
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
    <!-- Include jQuery and DataTables JS -->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/pdfmake.min.js"></script>
  <script type="text/javascript" src="scripts/vfs_fonts.js"></script>
  <style type="text/css">
    div.dt-buttons { float: left; }
    #export_filter { float: left; margin-left: 25px; }
    .toolbar { margin: 10px 0; }
  </style>
  <script type="text/javascript">
    $(document).ready(function(){
      var table = $('#hdds').DataTable({
        "drawCallback": function( settings ) {
					redraw();resize();
				},
        dom: 'B<"clear">lfrtip',
        buttons: [
          {
            extend: 'excel',
            title: '<?php echo addslashes($subheader); ?>'
          }

        ]
      });
      // Simple filters
      $('#filterStatus').on('change', function(){
        table.column(6).search(this.value).draw();
      });
      $('#filterSite').on('keyup change', function(){
        table.column(1).search(this.value).draw();
      });

      // Selection helpers
      $('#select_all').on('change', function(){
        var checked = this.checked;
        $('input.hdd-select').prop('checked', checked);
      });
      $('#btnUploadProof').on('click', function(){
        var ids = $('input.hdd-select:checked').map(function(){return this.value;}).get();
        if(ids.length === 0){
          alert('<?php echo addslashes(__('Please select at least one HDD')); ?>');
          return;
        }
        // Build and submit modal
        $('#uploadForm input[name="hdd_ids[]"]').remove();
        ids.forEach(function(id){
          $('<input>').attr({type:'hidden', name:'hdd_ids[]', value:id}).appendTo('#uploadForm');
        });
        $('#apply_destroy_status').prop('checked', false);
        $('#destroy_date').val('');
        $('#destroy_date_wrapper').hide();
        $('#uploadModal').show();
      });
      $('#closeUploadModal').on('click', function(){ $('#uploadModal').hide(); });
      $('#apply_destroy_status').on('change', function(){
        if(this.checked){
          $('#destroy_date_wrapper').slideDown(150);
        }else{
          $('#destroy_date_wrapper').slideUp(150);
          $('#destroy_date').val('');
        }
      });
      $('#uploadForm').on('submit', function(){
        if($('#apply_destroy_status').is(':checked')){
          var dateVal = $('#destroy_date').val();
          if(!dateVal){
            alert('<?php echo addslashes(__('Please select a destruction date.')); ?>');
            return false;
          }
        }
        return true;
      });

      function resetBatchCsvModal(){
        $('#batchCsvForm')[0].reset();
        $('#csv_column').prop('disabled', true).empty().append('<option value=""><?php echo addslashes(__('Select column')); ?></option>');
        $('#batchCsvLog').hide().val('');
        $('#downloadBatchLog').hide();
        $('#batch_destroy_date_wrapper').hide();
      }

      function detectDelimiter(line){
        var delimiters = [',',';','\t','|'];
        var best = ',';
        var max = 0;
        delimiters.forEach(function(delim){
          var count = line.split(delim).length - 1;
          if(count > max){
            max = count;
            best = delim;
          }
        });
        return best;
      }

      function parseCsvLine(line, delimiter){
        var result = [];
        var current = '';
        var inQuotes = false;
        for (var i = 0; i < line.length; i++){
          var char = line[i];
          if(char === '"'){
            if(inQuotes && line[i+1] === '"'){
              current += '"';
              i++;
            }else{
              inQuotes = !inQuotes;
            }
          }else if(char === delimiter && !inQuotes){
            result.push(current);
            current = '';
          }else{
            current += char;
          }
        }
        result.push(current);
        return result;
      }

      function parseCsvHeader(content){
        var lines = content.split(/\r?\n/).filter(function(line){ return line.trim().length > 0; });
        if(!lines.length){ return []; }
        var delimiter = detectDelimiter(lines[0]);
        return parseCsvLine(lines[0], delimiter).map(function(item){ return item.trim(); });
      }

      function populateCsvColumns(headers){
        var select = $('#csv_column');
        select.empty();
        if(!headers.length){
          select.append('<option value=""><?php echo addslashes(__('Select column')); ?></option>');
          select.prop('disabled', true);
          return;
        }
        select.prop('disabled', false);
        headers.forEach(function(header){
          if(header){
            $('<option>').val(header).text(header).appendTo(select);
          }
        });
      }

      function showBatchLog(missing){
        var logBox = $('#batchCsvLog');
        var downloadBtn = $('#downloadBatchLog');
        if(missing && missing.length){
          var text = missing.join('\n');
          logBox.val(text).show();
          downloadBtn.show().off('click').on('click', function(){
            var blob = new Blob([text], {type:'text/plain'});
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'hdd_csv_missing_'+Date.now()+'.txt';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
          });
        }else{
          logBox.hide().val('');
          downloadBtn.hide();
        }
      }

      function downloadLogFile(text){
        if(!text){
          return;
        }
        var blob = new Blob([text], {type:'text/plain'});
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'hdd_batch_'+Date.now()+'.txt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
      }

      function submitBatchCsv(force){
        var formEl = document.getElementById('batchCsvForm');
        var fd = new FormData(formEl);
        fd.append('force', force ? '1' : '0');
        $.ajax({
          url: formEl.action,
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json'
        }).done(function(resp){
          if(resp.require_confirm){
            showBatchLog(resp.missing || []);
            var proceed = confirm(resp.message || '<?php echo addslashes(__('Some serial numbers were not recognized. Continue processing the others?')); ?>');
            if(proceed){
              submitBatchCsv(true);
            }
          }else{
            if(resp.already_message){
              alert(resp.already_message);
            }
            if(resp.log_text){
              $('#batchCsvLog').val(resp.log_text).show();
              downloadLogFile(resp.log_text);
            }else{
              showBatchLog(resp.missing || []);
            }
            alert(resp.message || '<?php echo addslashes(__('Batch processed.')); ?>');
            if(resp.reload){
              window.location.reload();
            }
          }
        }).fail(function(jqXHR){
          var message = '<?php echo addslashes(__('An error occurred while processing the CSV.')); ?>';
          if(jqXHR.responseJSON && jqXHR.responseJSON.error){
            message = jqXHR.responseJSON.error;
          }
          alert(message);
        });
      }

      $('#btnImportCsv').on('click', function(){
        resetBatchCsvModal();
        $('#batchCsvModal').show();
      });
      $('#closeBatchCsvModal').on('click', function(){ $('#batchCsvModal').hide(); });
      $('#batch_apply_destroy_status').on('change', function(){
        if(this.checked){
          $('#batch_destroy_date_wrapper').slideDown(150);
        }else{
          $('#batch_destroy_date_wrapper').slideUp(150);
          $('#batch_destroy_date').val('');
        }
      });
      $('#batch_csv').on('change', function(event){
        var file = event.target.files[0];
        if(!file){
          populateCsvColumns([]);
          return;
        }
        var reader = new FileReader();
        reader.onload = function(e){
          var headers = parseCsvHeader(e.target.result || '');
          populateCsvColumns(headers);
        };
        reader.onerror = function(){
          alert('<?php echo addslashes(__('Unable to read the CSV file.')); ?>');
        };
        reader.readAsText(file);
      });
      $('#batchCsvForm').on('submit', function(e){
        e.preventDefault();
        if($('#csv_column').prop('disabled') || !$('#csv_column').val()){
          alert('<?php echo addslashes(__('Please select the CSV column containing serial numbers')); ?>');
          return;
        }
        if($('#batch_apply_destroy_status').is(':checked') && !$('#batch_destroy_date').val()){
          alert('<?php echo addslashes(__('Please select a destruction date.')); ?>');
          return;
        }
        submitBatchCsv(false);
      });
    });
    function redraw(){
			if(($('#hdds').outerWidth()+$('#sidebar').outerWidth()+10)<$('.page').innerWidth()){
				$('.main').width($('#header').innerWidth()-$('#sidebar').outerWidth()-16);
			}else{
				$('.main').width($('#hdds').outerWidth()+40);
			}
			$('.page').width($('.main').outerWidth()+$('#sidebar').outerWidth()+10);
		}
  </script>
  <?php include 'header.inc.php'; ?>
</head>
<body>
  <div class="page managehdd">
    <?php include 'sidebar.inc.php'; ?>
    <div class="main">
      <div class="center">
        <h2><?php echo htmlspecialchars($subheader, ENT_QUOTES); ?></h2>
        <?php if (!empty($_SESSION['LastError'])) { echo '<div class="error">'.htmlspecialchars($_SESSION['LastError'], ENT_QUOTES, 'UTF-8').'</div>'; unset($_SESSION['LastError']); } ?>
        <?php if (!empty($_SESSION['Message']))   { echo '<div class="message">'.htmlspecialchars($_SESSION['Message'],   ENT_QUOTES, 'UTF-8').'</div>'; unset($_SESSION['Message']); } ?>

        <div class="toolbar">
          <label><?php echo __('Filter by status'); ?>:
            <select id="filterStatus">
              <option value="">-- <?php echo __('All'); ?> --</option>
              <option value="Pending_destruction">Pending_destruction</option>
              <option value="Destroyed">Destroyed</option>
              <option value="On">On</option>
              <option value="Off">Off</option>
              <option value="Spare">Spare</option>
            </select>
          </label>
          <label style="margin-left:15px;"><?php echo __('DC'); ?>:
            <input type="text" id="filterSite" placeholder="<?php echo __('DC'); ?>">
          </label>
          <button id="btnUploadProof" class="button"><?php echo __('Add destruction proof and destroy'); ?></button>
          <button id="btnImportCsv" class="button" style="margin-left:10px;"><?php echo __('Process CSV Batch'); ?></button>
        </div>

        <table id="hdds" class="display stripe hover" style="width:100%">
          <thead>
            <tr>
              <th><input type="checkbox" id="select_all"></th>
              <th><?php echo __('DC'); ?></th>
              <th><?php echo __('Device ID'); ?></th>
              <th><?php echo __('Device Label'); ?></th>
              <th><?php echo __('HDDID'); ?></th>
              <th><?php echo __('Serial No HDD'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Size'); ?></th>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Date Added'); ?></th>
              <th><?php echo __('Date Withdrawn'); ?></th>
              <th><?php echo __('Date Destroyed'); ?></th>
              <th><?php echo __('Proof'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hddList as $h): ?>
              <tr>
                <td><input type="checkbox" class="hdd-select" value="<?= (int)$h->HDDID ?>"></td>
                <td><?= htmlspecialchars($h->SiteName ?? '', ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DeviceID, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DeviceLabel ?? '',ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->HDDID, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->SerialNo, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->Status, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->Size, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->TypeMedia, ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DateAdd ?? '', ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DateWithdrawn ?? '',ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($h->DateDestroyed ?? '',ENT_QUOTES) ?></td>
                <td>
                  <?php
                    $proofUrl = build_hdd_proof_url($h->ProofFile ?? '', $proofWebBase);
                    if ($proofUrl !== ''):
                  ?>
                    <a href="<?= htmlspecialchars($proofUrl, ENT_QUOTES) ?>" target="_blank"><?php echo __('View proof'); ?></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Upload modal -->
        <div id="uploadModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
          <div style="background-color:#fff; margin:10% auto; padding:20px; border:1px solid #888; width:400px; position:relative;">
            <span id="closeUploadModal" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
            <h3><?php echo __('Add Destruction Proof'); ?></h3>
            <form id="uploadForm" method="post" action="upload_hdd_proof.php" enctype="multipart/form-data">
              <input type="hidden" name="return" value="report_hdd.php">
              <div>
                <label for="proof_pdf"><?php echo __('PDF / Excel / ODS file (max 5 MB)'); ?></label>
                <input type="file" id="proof_pdf" name="proof_pdf" accept=".pdf,.xls,.xlsx,.ods,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.spreadsheet" required>
              </div>
              <div style="margin-top:15px;">
                <label>
                  <input type="checkbox" id="apply_destroy_status" name="apply_destroy_status" value="1">
                  <?php echo __('Apply destroyed status and date to selected HDDs'); ?>
                </label>
              </div>
              <div id="destroy_date_wrapper" style="margin-top:10px; display:none;">
                <label for="destroy_date"><?php echo __('Destruction date (applied to all selected HDDs)'); ?></label>
                <input type="date" id="destroy_date" name="destroy_date">
              </div>
              <div style="margin-top:15px; text-align:right;">
                <button type="submit" class="button"><?php echo __('Upload'); ?></button>
              </div>
            </form>
          </div>
        </div>

        <!-- CSV batch modal -->
        <div id="batchCsvModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
          <div style="background-color:#fff; margin:6% auto; padding:20px; border:1px solid #888; width:460px; position:relative;">
            <span id="closeBatchCsvModal" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
            <h3><?php echo __('Import CSV for Automated Destruction'); ?></h3>
            <form id="batchCsvForm" method="post" action="import_hdd_csv.php" enctype="multipart/form-data">
              <input type="hidden" name="return" value="report_hdd.php">
              <div>
                <label for="batch_csv"><?php echo __('CSV file (UTF-8, max 2 MB)'); ?></label>
                <input type="file" id="batch_csv" name="batch_csv" accept=".csv,text/csv" required>
              </div>
              <div style="margin-top:10px;">
                <label for="csv_column"><?php echo __('CSV column containing Serial Numbers'); ?></label>
                <select id="csv_column" name="csv_column" disabled required>
                  <option value=""><?php echo __('Select column'); ?></option>
                </select>
                <small><?php echo __('The column list appears after choosing a CSV file.'); ?></small>
              </div>
              <div style="margin-top:15px;">
                <label for="batch_proof"><?php echo __('Destruction proof file (PDF / Excel / ODS, max 5 MB)'); ?></label>
                <input type="file" id="batch_proof" name="batch_proof" accept=".pdf,.xls,.xlsx,.ods,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.spreadsheet">
              </div>
              <div style="margin-top:15px;">
                <label>
                  <input type="checkbox" id="batch_apply_destroy_status" name="apply_destroy_status" value="1">
                  <?php echo __('Apply destroyed status and date to matching HDDs'); ?>
                </label>
              </div>
              <div id="batch_destroy_date_wrapper" style="margin-top:10px; display:none;">
                <label for="batch_destroy_date"><?php echo __('Destruction date (applied to all matching HDDs)'); ?></label>
                <input type="date" id="batch_destroy_date" name="destroy_date">
              </div>
              <div style="margin-top:15px;">
                <label for="batch_notes"><?php echo __('Optional note / reference'); ?></label>
                <input type="text" id="batch_notes" name="notes" placeholder="<?php echo __('Reference or ticket number'); ?>">
              </div>
              <div style="margin-top:15px;">
                <textarea id="batchCsvLog" readonly style="width:100%; height:120px; display:none;"></textarea>
                <button type="button" id="downloadBatchLog" class="button" style="margin-top:5px; display:none;"><?php echo __('Download log'); ?></button>
              </div>
              <div style="margin-top:15px; text-align:right;">
                <button type="submit" class="button" id="submitBatchCsv"><?php echo __('Process CSV Batch'); ?></button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  
</body>
</html>
