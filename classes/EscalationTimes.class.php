<?php
/*
	openDCIM

	This is the main class library for the openDCIM application, which
	is a PHP/Web based data center infrastructure management system.

	This application was originally written by Scott A. Milliken while
	employed at Vanderbilt University in Nashville, TN, as the
	Data Center Manager, and released under the GNU GPL.

	Copyright (C) 2011 Scott A. Milliken

	This program is free software:  you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation, version 3.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	For further details on the license, see http://www.gnu.org/licenses
*/

class EscalationTimes {
        var $EscalationTimeID;
        var $TimePeriod;

        function MakeSafe(){
                $this->EscalationTimeID=intval($this->EscalationTimeID);
                $this->TimePeriod=sanitize($this->TimePeriod);
        }

        function MakeDisplay(){
                $this->TimePeriod=stripslashes($this->TimePeriod);
        }

        function query($sql){
                global $dbh;
                return $dbh->query($sql);
        }

        function exec($sql){
                global $dbh;
                return $dbh->exec($sql);
        }

        function CreatePeriod(){
                global $dbh;
                $this->MakeSafe();

                $sql="INSERT INTO fac_EscalationTimes SET TimePeriod=\"$this->TimePeriod\";";

                if($this->exec($sql)){
                        $this->EscalationTimeID=$dbh->lastInsertId();
                        $this->MakeDisplay();
                        (class_exists('LogActions'))?LogActions::LogThis($this):'';
                        return $this->EscalationTimeID;
                }else{
                        return false;
                }
        }

        function DeletePeriod(){
                $this->MakeSafe();

                $sql="DELETE FROM fac_EscalationTimes WHERE EscalationTimeID=$this->EscalationTimeID;";

                (class_exists('LogActions'))?LogActions::LogThis($this):'';
                return $this->exec($sql);
        }

        function GetEscalationTime(){
                $sql="SELECT * FROM fac_EscalationTimes WHERE EscalationTimeID=$this->EscalationTimeID;";

                //if($row=$this->query($sql)->fetch()){
                if($q=$this->query($sql)){
                        $row=$q->fetch();
                        $this->EscalationTimeID=$row["EscalationTimeID"];
                        $this->TimePeriod=$row["TimePeriod"];
                        $this->MakeDisplay();
                        return true;
                }else{
                        return false;
                }
        }

        function GetEscalationTimeList(){
                $sql="SELECT * FROM fac_EscalationTimes ORDER BY TimePeriod ASC;";

                $escList=array();
                foreach($this->query($sql) as $row){
                        $escList[$row["EscalationTimeID"]]=new EscalationTimes();
                        $escList[$row["EscalationTimeID"]]->EscalationTimeID = $row["EscalationTimeID"];
                        $escList[$row["EscalationTimeID"]]->TimePeriod = $row["TimePeriod"];
                        $escList[$row["EscalationTimeID"]]->MakeDisplay();
                }

                return $escList;
        }

        function UpdatePeriod(){
                $this->MakeSafe();

                $oldperiod=new EscalationTimes();
                $oldperiod->EscalationTimeID=$this->EscalationTimeID;
                $oldperiod->GetEscalationTime();

                $sql="UPDATE fac_EscalationTimes SET TimePeriod=\"$this->TimePeriod\" WHERE
                        EscalationTimeID=$this->EscalationTimeID;";

                (class_exists('LogActions'))?LogActions::LogThis($this,$oldperiod):'';
                return $this->query($sql);
        }
}

?>
