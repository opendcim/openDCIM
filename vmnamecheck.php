<?php
    require_once( 'db.inc.php' );
    require_once( 'facilities.inc.php' );
    
    $esx = new ESX();
    $dev = new Device();
    $dept = new Department();

    $esxList = $esx->GetOrphanVMList( $facDB );
    if ( count( $esxList ) > 0 ) {
      $esxCount = count( $esxList );
      
      $headers = 'MIME-Version: 1.0\r\n';
      $headers .= 'Content-Type: Text/HTML; charset=iso-8559-1\r\n';
      $headers .= 'From: ' . $config->ParameterArray["MailFromAddr"];
      $headers .= 'Reply-To:  ' . $config->ParameterArray["MailToAddr"];
  
      $message = '<html>
<head>
   <title>Virtual Machine Inventory Exception Report</title>
</head>
<body>
<p>This is an automated message from the ' . $config->ParameterArray["OrgName"] . 'Inventory
Process.  This process is scheduled to run once each business day.</p>
<p>The following $esxCount Virtual Machines were detected in the environment
and do not have an associated owner record.  It is assumed that
these are new Virtual Machines.  Please click on the links below to update
ownership information.</p>
<p>If the appropriate department is not listed as an option for ownership, please
send an email to ' . $config->ParameterArray["FacMgrMail"] . ' to have it added.</p>
<p>
<table width=\'100%\' border=\'1\' padding=\'0\' bgcolor=white>
<tr><td>Server Name</td><td>VM Name</td><td>Status</td><td>Last Updated</td></tr>';

      foreach( $esxList as $esxRow ) {
        $dev->DeviceID = $esxRow->DeviceID;
        $dev->GetDevice( $facDB );
        
        $dept->DeptID = $esxRow->Owner;
        if ( $dept->DeptID > 0 )
          $dept->GetDeptByID( $facDB );
        else
          $dept->Name = 'Unknown';
          
        $message .= '<tr><td>' . $dev->Label . '</td><td><a href=\'https://its.vanderbilt.edu/noc/inventory/updatevmowner.php?vmindex=' . $esxRow->VMIndex . '\'>' . $esxRow->vmName . '</a></td><td>' . $esxRow->vmState . '</td><td>' . $esxRow->LastUpdated . '</td></tr>\n';
      }
      
      $message .= '</table></body></html>';
	// Add recipients for this report
    //  mail( 'user@your.domain', 'Virtual Machine Inventory Exceptions', $message, $headers );

    }
    
    $esxList = $esx->GetExpiredVMList( 7, $facDB );
    if ( count( $esxList ) > 0 ) {
      $esxCount = count( $esxList );
      
      $headers = 'MIME-Version: 1.0\r\n';
      $headers .= 'Content-Type: Text/HTML; charset=iso-8559-1\r\n';
      $headers .= 'From: ' . $config->ParameterArray["MailFromAddr"];
      $headers .= 'Reply-To:  ' . $config->ParameterArray["MailToAddr"];
  
      $message = '<html>
<head>
   <title>Virtual Machine Inventory Expiration Report</title>
</head>
<body>
<p>This is an automated message from the ' . $config->ParameterArray["OrgName"] . ' Virtual Machine Inventory
Process.  This process is scheduled to run once each business day.</p>
<p>The following $esxCount Virtual Machines have not been detected within the
past 7 days and are assumed to be expired.  They are being removed from the
inventory system.</p>
<table width=\'100%\' border=\'1\' padding=\'0\' bgcolor=white>
<tr><td>Server Name</td><td>VM Name</td><td>Status</td><td>Last Updated</td></tr>';

      foreach( $esxList as $esxRow ) {
        $dev->DeviceID = $esxRow->DeviceID;
        $dev->GetDevice( $facDB );
        
        $dept->DeptID = $esxRow->Owner;
        if ( $dept->DeptID > 0 )
          $dept->GetDeptByID( $facDB );
        else
          $dept->Name = 'Unknown';
          
        $message .= '<tr><td>' . $dev->Label . '</td><td>' . $esxRow->vmName . '</td><td>' . $esxRow->vmState . '</td><td>' . $esxRow->LastUpdated . '</td></tr>\n';
      }
      
      $message .= '</table></body></html>';

	// Add recipients for this daily report
    // mail( 'user@your.domain', 'Virtual Machine Inventory Exceptions', $message, $headers );
      
      // Delete 'em
      $esx->ExpireVMs( 7, $facDB );
    }
    

?>
