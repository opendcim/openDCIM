<?php
//	header( "content-type: text/xml" );
	header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
	header("Content-Disposition: inline; filename=\"realtime.xls\"");
	header("Pragma: no-cache"); 
	header("Expires: 0"); 


  include( "db.inc.php" );
  include( "facilities.inc.php" );
  
  printf( "<?xml version=\"1.0\"?>\n" );
  printf( "<?mso-application progid=\"Excel.Sheet\"?>\n" );
  
  $data = array();

  // This needs to be all generated from the db someplace.

  // ITS Statistics
  $sql="select count(*) as Devices, sum(Height) as Size, sum(NominalWatts) as Power, (select count(*) as VMcount from fac_VMInventory a, fac_Department b where a.Owner=b.DeptID and b.Classification='ITS') as VMcount from fac_Device a, fac_Department b where a.Owner=b.DeptID and b.Classification='ITS'";
  $row=$dbh->query($sql)->fetch();
  $ITSdevices = $row["Devices"];
  $ITSsize = $row["Size"];
  $ITSpower = $row["Power"];
  $ITSheat = $ITSpower * 3.412 / 12000;
  $ITSVM = $row["VMcount"];
  
  $data["ITS Managed Services"] = array( $ITSdevices, $ITSsize, $ITSVM, $ITSpower, $ITSheat );
    
  // Administrative Statistics
  $sql="select count(*) as Devices, sum(Height) as Size, sum(NominalWatts) as Power, (select count(*) from fac_VMInventory a, fac_Department b where a.Owner=b.DeptID and b.Classification='Administrative') as VMcount  from fac_Device a, fac_Department b where a.Owner=b.DeptID and b.Classification='Administrative'";
  $row=$dbh->query($sql)->fetch();
  $Admindevices = $row["Devices"];
  $Adminsize = $row["Size"];
  $AdminVM = $row["VMcount"];
  $Adminpower = $row["Power"];
  $Adminheat = $Adminpower * 3.412 / 12000;
  
  $data["Non-ITS Administrative Colocations"] = array( $Admindevices, $Adminsize, $AdminVM, $Adminpower, $Adminheat );
  
  // Academic (non-research) Statistics
  $sql="select count(*) as Devices, sum(Height) as Size, sum(NominalWatts) as Power, (select count(*) from fac_VMInventory a, fac_Department b where a.Owner=b.DeptID and b.Classification='Academic') as VMcount  from fac_Device a, fac_Department b where a.Owner=b.DeptID and b.Classification='Academic'";
  $row=$dbh->query($sql)->fetch();
  $Academicdevices = $row["Devices"];
  $Academicsize = $row["Size"];
  $AcademicVM = $row["VMcount"];
  $Academicpower = $row["Power"];
  $Academicheat = $Academicpower * 3.412 / 12000;
  
  $data["Academic (Non-Research) Colocations"] = array( $Academicdevices, $Academicsize, $AcademicVM, $Academicpower, $Academicheat );
  // Research Computing Statistics
  $sql="select count(*) as Devices, sum(Height) as Size, sum(NominalWatts) as Power, (select count(*) from fac_VMInventory a, fac_Department b where a.Owner=b.DeptID and b.Classification='Research') as VMcount  from fac_Device a, fac_Department b where a.Owner=b.DeptID and b.Classification='Research'";
  $row=$dbh->query($sql)->fetch();
  $Researchdevices = $row["Devices"];
  $Researchsize = $row["Size"];
  $ResearchVM = $row["VMcount"];
  $Researchpower = $row["Power"];
  $Researchheat = $Researchpower * 3.412 / 12000;
  
  $data["Research Computing Colocations"] = array( $Researchdevices, $Researchsize, $ResearchVM, $Researchpower, $Researchheat );
    
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Author>openDCIM</Author>
  <LastAuthor>openDCIM</LastAuthor>
  <Created>2012-04-03T06:38:53Z</Created>
  <Company>openDCIM</Company>
  <Version>14.00</Version>
 </DocumentProperties>
 <OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office">
  <AllowPNG/>
 </OfficeDocumentSettings>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>15930</WindowHeight>
  <WindowWidth>20025</WindowWidth>
  <WindowTopX>480</WindowTopX>
  <WindowTopY>105</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="s62">
   <NumberFormat ss:Format="Fixed"/>
  </Style>
  <Style ss:ID="s63">
   <NumberFormat ss:Format="#,##0"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Data_Center_Metrics">
  <Table ss:ExpandedColumnCount="6" ss:ExpandedRowCount="<? echo( count( $data ) + 1 ) ?>" x:FullColumns="1"
   x:FullRows="1">
   <Column ss:Width="186"/>
   <Column ss:Width="66.75"/>
   <Column ss:Width="135.75"/>
   <Column ss:Width="84"/>
   <Column ss:Width="128.25"/>
   <Column ss:Width="106.5"/>
   <Row ss:AutoFitHeight="0">
    <Cell><Data ss:Type="String">Department</Data></Cell>
<?php
  foreach(array("Device Count","Space Occupied (1U = 1.75\")","Virtual Machines","Power Consumed (kW/hr)","Heat Produced (Tons)",) as $key=>$val){
    print "    <Cell><Data ss:Type=\"String\">$val</Data></Cell>\n";
  }
?>
   </Row>
<?php
  foreach($data as $key=>$row){
    print "   <Row ss:AutoFitHeight=\"0\">\n";
    print "    <Cell><Data ss:Type=\"String\">$key</Data></Cell>\n";
    foreach($row as $key=>$val){
      if(next($row)==false){
        print "    <Cell ss:StyleID=\"s62\"><Data ss:Type=\"Number\">$val</Data></Cell>\n";
	  }else{
        print "    <Cell ss:StyleID=\"s63\"><Data ss:Type=\"Number\">$val</Data></Cell>\n";
	  }
    }
    print "   </Row>\n";
  }
?>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <PageSetup>
    <Header x:Margin="0.3"/>
    <Footer x:Margin="0.3"/>
    <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
   </PageSetup>
   <Unsynced/>
   <Print>
    <ValidPrinterInfo/>
    <HorizontalResolution>600</HorizontalResolution>
    <VerticalResolution>600</VerticalResolution>
   </Print>
   <Selected/>
   <Panes>
    <Pane>
     <Number>3</Number>
     <ActiveRow>1</ActiveRow>
	 <ActiveCol>1</ActiveCol>
    </Pane>
   </Panes>
   <ProtectObjects>False</ProtectObjects>
   <ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>
