<?php
/*
file downloaded from: http://www.phpclasses.org/package/377-PHP-Allow-to-read-ModbusTCP-compatible-devices-direct-with-PHP-without-third-package-.html#view_files

 -------------- VERSION 1.4 du 28/11/2007 ---------
	Fiabilisation fonction ecriture des registres (Fonctions 16)

 -------------- VERSION 1.3 du 17/04/2003 ---------
	Rajout Ecriture d'une Bobine  ( Fct 5 )

 -------------- VERSION 1.2 du 17/11/2002 ---------
	Rajout d'un mode Simulation ( retourne des valeurs aleatoires sans connexions )
	
 -------------- VERSION 1.1 du 01/07/2002 ---------
	Rajout du routage dynamique avec passerelle 174 CEV 200 30 MB+ / ModBusTcp

 -------------- VERSION 1.0 du 30/10/2001 ---------
	Creation de la classe

------------------------------------------------------------------
--                       EXEMPLES                               --
------------------------------------------------------------------

------ Lecture d'un tableau de registres ou bits contigus  --------

	...
	include "class_ModbusTcp.inc";

	$Plc = new ModbusTcp;
	$Plc->SetAdIpPLC ("xx.xx.xx.xx");

	$Plc->Unit = 5;  // Sans routage dynamique
	$Plc->BridgeRoute = array( 52, 11, 0, 0, 0 );  // Avec routage dynamique si passerelle 174CEV20030
	
	$valeurs = $Plc->ReadModbus( "400001", 50 ); // Lecture de 50 mots a partir de 400001
	print_r ($valeurs); echo "<br>";

	$valeurs = $Plc->ReadModbus( "300001", 15 ); // Lecture de 15 mots d'entree a partir de 300001
	print_r ($valeurs); echo "<br>";

	$valeurs = $Plc->ReadModbus( "000001", 200 ); // Lecture de 200 bits de sortie a partir de 000001
	print_r ($valeurs); echo "<br>";

	$valeurs = $Plc->ReadModbus( "100001", 125 ); // Lecture de 125 bits d'entrée a partir de 100001
	print_r ($valeurs); echo "<br>";
	...

	$Plc->ModClose();


------- Lecture d'un tableau de registres aleatoires -------------

	...
	include "class_ModbusTcp.inc";

	$Plc = new ModbusArray;
	$Plc->SetAdIpPLC ("xx.xx.xx.xx");

	$Plc->Unit = 5;  // Sans routage dynamique
	$Plc->SetBridgeRoute( 52, 11, 0, 0, 0 );  // Avec routage dynamique si passerelle 174CEV20030
	
	$Registre = array (400001, 400250, 400625, 400002, 400050, 300001, 000005, 100010, 100035 )
	$arrValeurs = $Plc->ReadArrRegs( $Registre );
	print_r ($arrValeurs); echo "<br>";
	...

	$Plc->ModClose();

	A noter que dans ce cas, les trames envoyees sont optimisees au niveau du reseau de 
	facon a lire des tableaux de mots contigus.
	A voir avec $Plc->Debug = true.

------ Utilisation du mode Simulation -----------------------------

	...
	include "class_ModbusTcp.inc";

	$Plc = new ModbusTcp;
	$Plc->SetSimulation();
	
	$valeurs = $Plc->ReadModbus( "400001", 50 ); 
	print_r ($valeurs); echo "<br>";

	$valeurs = $Plc->ReadModbus( "300001", 63 ); 
	print_r ($valeurs); echo "<br>";

	$valeurs = $Plc->ReadModbus( "000001", 2000 );
	print_r ($valeurs); echo "<br>";
	...

	$Plc->ModClose();


------ Ecriture(write) d'un tableau de registres contigus --------

	...
	include "class_ModbusTcp.inc";

	$Plc = new ModbusTcp;
	$Plc->SetAdIpPLC ("xx.xx.xx.xx");

	$Plc->Unit = 1;  // Sans routage dynamique
	//$Plc->BridgeRoute = array( 52, 11, 0, 0, 0 );  // Avec routage dynamique si passerelle 174CEV20030
	
	$Writebuffer = array(1234, 1111, 2222); 
	$DebutArrAdresse = "400200";

	if ( !$Plc->WriteModbus($DebutArrAdresse, $Writebuffer ) ){
		echo "<br>PROBLEME D'ECRITURE<BR>";
	}
	...

	$Plc->ModClose();

------ Ecriture(write) d'une bobine(coil)  --------

	...
	include "class_ModbusTcp.inc";

	$Plc = new ModbusTcp;
	$Plc->SetAdIpPLC ("xx.xx.xx.xx");

	$Plc->Unit = 1;  // Sans routage dynamique
	//$Plc->BridgeRoute = array( 52, 11, 0, 0, 0 );  // Avec routage dynamique si passerelle 174CEV20030
	
	$Writebuffer = "1"; 
	$DebutArrAdresse = "000200";

	if ( !$Plc->WriteModbus($DebutArrAdresse, $Writebuffer ) ){
		echo "<br>PROBLEME D'ECRITURE<BR>";
	}
	...

	$Plc->ModClose();

*/

class ModbusTcp {

	var $AdIpPLC;
	var $PortIpPLC;
	var $Unit;
	var $DebutAdresse;
	var $Nbre;
	var $WriteValues;
	var $Erreur;
	var $Fp;
	var $BridgeRoute;
	var $tmpBridgeRoute;
	var $Debug;
	var $Simulation;
	var $MemoConn;
	var $TypeDouble;
	var $TypeFloat;
	var $TimeOut;

	function ModbusTcp () { // Constructeur 
		$this->AdIpPLC = "10.9.14.201";
		$this->PortIpPLC = 502;
		$this->Unit = 0;
		$this->DebutAdresse = 0;
		$this->Nbre = 1;
		$this->WriteValues = array(0);
		$this->Erreur = "";
		$this->BridgeRoute = array();
		$this->tmpBridgeRoute = array();
		$this->Debug = false;
		$this->Simulation = False;
		$this->MemoConn = False;
		$this->TypeDouble = false;
		$this->TypeFloat = false;
		$this->TimeOut = 1;
		srand( (float) microtime()*1000000 );
	}

	function SetAdIpPLC( $Ip = "10.9.14.201" ) {
		$this->AdIpPLC = $Ip; 
		$this->ModConn();
		$this->BridgeRoute = array();
		$this->tmpBridgeRoute = array();
		if ( $this->Fp ) $this->MemoConn = True;
	}

	function ModConn() {
		if ( !$this->Simulation ) {
			//$this->Fp = @fsockopen( "$this->AdIpPLC", $this->PortIpPLC, $errno, $errstr, 5 ) or die("Pas de connexion a l'Adresse $this->AdIpPLC");
			$this->Fp = @fsockopen( "$this->AdIpPLC", $this->PortIpPLC, $errno, $errstr, $this->TimeOut );
		}
	}

	function ModClose() {
		if ( $this->Fp) @fclose($this->Fp); 
	}

	function WriteSocket( $OutBuf ) {
		fwrite( $this->Fp, implode( "", $OutBuf ));
		return true;
	}

	function ReadSocket () {
		while ( ! $InBuf = fgetc($this->Fp) ); //Lire le 1er octet du socket pour utiliser aprÃ¨s socket_get_status()
			$status = socket_get_status($this->Fp);
			$InBuf .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants
			if ( $this->Debug ) { //Affichage des octets recu si mode Debug
				for ( $i=0; $i<strlen($InBuf); $i++ ) {
				echo "OctRecu[$i] =". ord($InBuf[$i])."<br>";
			}
		}
		return $InBuf;
	}

	function SetTypeFloat() {
		$this->TypeFloat = true; //
	}

	function SetTypeDouble() {
		$this->TypeDouble = true; //
	}

	function SetSimulation() {
		$this->Simulation = True; // 
		// initialise avec les microsecondes depuis la derni? seconde enti?  
		srand( (float) microtime()*1000000 );  //Pour Simulation
		echo "<br><font color='#FF9900' size=3><b>Attention: valeurs en Mode SIMULATION ! ! ! ! </b></font><br>";
	}

	function SetDebug() {
		$this->Debug = True; // 
	}

	function WordToBytes( $word = 0 ) {
		if ( $word > 65535 ) $word = 65535;
		return ( array( chr( $word % 256 ), chr( ( $word - $word % 256 ) / 256 ) ) );
	}

	function BytesToWord( $byte1 = 0, $byte2 = 0 ) { 
		return( ord($byte1) * 256 + ord($byte2) );
	}

	function ByteToBits( $byte1 = 0) { // converti un octet en string format binaire inverse
		return( strrev( sprintf( "%08d", decbin( ord( $byte1 ) ) ) ) );
	}

	function BytesToDouble( $byte1, $byte2, $byte3, $byte4 ) {
		return ( ($byte1 & 0x000000FF) << 24) + (($byte2 & 0x000000FF) << 16) + (($byte3 & 0x000000FF) << 8) + (($byte4 & 0x000000FF) );
	}

	function WordToDouble( $Word1, $Word2 ) {
		return ( ($Word1 & 0x0000FFFF) << 16) + (($Word2 & 0x0000FFFF) );
	}

	function WordToFloat( $Word1, $Word2 ) {
		/* Conversion selon presentation Standard IEEE 754 
		/    seeeeeeeemmmmmmmmmmmmmmmmmmmmmmm   
		/    31                             0  
		/    s = sign bit, e = exponent, m = mantissa
		*/
		define ("DBL_MAX", 99999999999999999);

		$src = ( ($Word1 & 0x0000FFFF) << 16) + (($Word2 & 0x0000FFFF) );

		$s = (bool)($src >> 31);
		$e = ($src & 0x7F800000) >> 23;
		$f = ($src & 0x007FFFFF);
		
		//var_dump($s);
		//echo "<br>";
		//var_dump($e);
		//echo "<br>";
		//var_dump($f);
		//echo "<br>";

		if ($e == 255 && $f != 0) {
			 /* NaN - Not a number */
			 $value = DBL_MAX;
		} elseif ($e == 255 && $f == 0 && $s) {
			/* Negative infinity */
			$value = -DBL_MAX;
		} elseif ($e == 255 && $f == 0 && !$s) {
			/* Positive infinity */
			$value = DBL_MAX;
	   } elseif ($e > 0 && $e < 255) {
			/* Normal number */
			$f += 0x00800000;
			if ($s) $f = -$f;
			$value = $f * pow(2, $e - 127 - 23);
		} elseif ($e == 0 && $f != 0) {
			/* Denormal number */
			if ($s) $f = -$f;
			$value = $f * pow(2, $e - 126 - 23);
		} elseif ($e == 0 && $f == 0 && $s) {
			/* Negative zero */
			$value = 0;
		} elseif ($e == 0 && $f == 0 && !$s) {
			/* Positive zero */
			$value = 0;
		} else {
			/* Never happens */
		}

	   return $value;
	}

	function BytesToFloat( $byte1, $byte2, $byte3, $byte4 ) {
		// Conversion selon presentation Standard IEEE 754 

		define ("DBL_MAX", 99999999999999999);

		$src = ( ($byte1 & 0x000000FF) << 24) + (($byte2 & 0x000000FF) << 16) + (($byte3 & 0x000000FF) << 8) + (($byte4 & 0x000000FF) );

		$s = (bool)($src >> 31);
		$e = ($src & 0x7F800000) >> 23;
		$f = ($src & 0x007FFFFF);
		
		//var_dump($s);
		//echo "<br>";
		//var_dump($e);
		//echo "<br>";
		//var_dump($f);
		//echo "<br>";

		if ($e == 255 && $f != 0) {
			 /* NaN - Not a number */
			 $value = DBL_MAX;
		} elseif ($e == 255 && $f == 0 && $s) {
			/* Negative infinity */
			$value = -DBL_MAX;
		} elseif ($e == 255 && $f == 0 && !$s) {
			/* Positive infinity */
			$value = DBL_MAX;
	   } elseif ($e > 0 && $e < 255) {
			/* Normal number */
			$f += 0x00800000;
			if ($s) $f = -$f;
			$value = $f * pow(2, $e - 127 - 23);
		} elseif ($e == 0 && $f != 0) {
			/* Denormal number */
			if ($s) $f = -$f;
			$value = $f * pow(2, $e - 126 - 23);
		} elseif ($e == 0 && $f == 0 && $s) {
			/* Negative zero */
			$value = 0;
		} elseif ($e == 0 && $f == 0 && !$s) {
			/* Positive zero */
			$value = 0;
		} else {
			/* Never happens */
		}

	   return $value;
	}

	function print_r_log($var) { 
		echo "<pre><font color='#000000' size='1' face='Verdana'>";
		print_r($var);
		echo "</pre><br>";
	}

	
	// --------------------------------------------------------------------------------------------
	//            FONCTION PRINCIPALE LECTURE D'UN TABLEAU DE 0/1/3/4xxxxx
	//					( retourne un tableau [0/1/3/4xxxxx] = valeur )
	// --------------------------------------------------------------------------------------------
	function ReadModbus( $AdrDebut = "400001", $NbreReg = 1 ) {
		$this->Nbre = (int)$NbreReg;
		//$AdrDebut = (string)$AdrDebut;
		if ( $this->Nbre < 1 ) $this->Nbre = 1;
		// LECTURE REELLE -----------------------		
		switch ( substr( sprintf("%06d", $AdrDebut), 0, 1) ) {	// Formatage ? caract?s
			case "4":
				$this->DebutAdresse = $AdrDebut - 400001;
				//echo "debut-adresse = $this->DebutAdresse";
				return ( $this->ReadHoldRegisters() );
				break;
			case "3":
				$this->DebutAdresse = $AdrDebut - 300001;
				return ( $this->ReadInputRegisters() );
				break;
			case "1":
				$this->DebutAdresse = $AdrDebut - 100001;
				return ( $this->ReadDiscretInputs() );
				break;
			case "0":
				$this->DebutAdresse = $AdrDebut - 1;
				return ( $this->ReadCoils() );
				break;
			default:
				return array();
		}
	}

	// -------------------------------------------------------
	//						LECTURE DES 4xxxxx          
	//			( retourne un tableau [4xxxxx] = valeur )
	// -------------------------------------------------------

	function ReadHoldRegisters() {

		//Retourne des valeurs aleatoires entre 0 et 4095 sans ouvrir les sockets	
		if ( $this->Simulation ) {
			if ( $this->Nbre > 125 ) $this->Nbre = 125;
			for ( $i=0; $i<=$this->Nbre; $i++ ) {
				$buffer[400000 + $this->DebutAdresse + $i] = rand(0, 4095); //Simulation ANA
//				$buffer[400000 + $this->DebutAdresse + $i] = ( 256*rand(65, 90) + rand(65, 90) ) ;//Simulation ASCII
			}
			return $buffer;
		} // FIN Simulation

		if ( !$this->MemoConn ) return array();

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		if ( $this->Nbre > 125 ) $this->Nbre = 125;
		$obuf = array ( 0=>chr(0), 1=>chr(0), 2=>chr(0), 3=>chr(0), 4=>chr(0), 5=>chr(6), 6=>chr($this->Unit), 7=>chr(3) ) ;
		list( $obuf[9], $obuf[8] ) = $this->WordToBytes( (int)$this->DebutAdresse );
		list( $obuf[11], $obuf[10] ) = $this->WordToBytes( (int)$this->Nbre );

		if ( $this->Debug ) { //Affichage des octets ?s si en mode Debug
			echo "<b>ReadHoldRegisters</b><br>"; 
			for ($i=0;$i<count($obuf);$i++ ) {
				echo "OctEmis[$i] =". ord($obuf[$i])."<br>";
			}
		}		

		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( "", $obuf ) );

		//--------- LECTURE DU SOCKET ---------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser apr?socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $OctetRecu[7] != $obuf[7] ) { 
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>";
			echo "ERREUR DE LECTURE des REGISTRES de SORTIE de ".sprintf("4%05d", $this->DebutAdresse)." ".sprintf("4%05d", ($this->DebutAdresse + $this->Nbre))."<br>"; 
			for ( $i=0; $i<count($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
			echo "</b></FONT>\n";
			$this->MemoConn = False;
			return array();
		}
		//Recupération des DATAs
		$i = 1;
		$buffer = array();
		if ($this->TypeFloat) {
			for ($j=0; $j < ord($OctetRecu[8]); $j=$j+4) {
				$buffer[400000 + $this->DebutAdresse + $i] = $this->BytesToFloat( ord($OctetRecu[$j+9]), ord($OctetRecu[$j+10]), ord($OctetRecu[$j+11]), ord($OctetRecu[$j+12]) );
				$i++;
				$i++;
			}
		} elseif ($this->TypeDouble) {
			for ($j=0; $j < ord($OctetRecu[8]); $j=$j+4) {
				$buffer[400000 + $this->DebutAdresse + $i] = $this->BytesToDouble( ord($OctetRecu[$j+9]), ord($OctetRecu[$j+10]), ord($OctetRecu[$j+11]), ord($OctetRecu[$j+12]) );
				//$buffer[400000 + $this->DebutAdresse + $i] = $this->BytesToDouble( ord($OctetRecu[$j+11]), ord($OctetRecu[$j+12]), ord($OctetRecu[$j+9]), ord($OctetRecu[$j+10]) );
				$i++;
				$i++;
			}
		} else {
			for ($j=0; $j < ord($OctetRecu[8]); $j=$j+2) {
				$buffer[400000 + $this->DebutAdresse + $i] = $this->BytesToWord( $OctetRecu[$j+9], $OctetRecu[$j+10] );
				$i++;
			}
		}
		
		//Affichage des octets recu si mode Debug
		if ( $this->Debug ) {
			for ( $i=0; $i<strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
		}
		
		return $buffer ;
		
	}

	// --------------------------------------------------------------
	//					LECTURE DES 3xxxxx          
	//			( retourne un tableau [3xxxxx] = valeur )
	// --------------------------------------------------------------
	function ReadInputRegisters() {
		
		//Retourne des valeurs aleatoires entre 0 et 4095 sans ouvrir les sockets	
		if ( $this->Simulation ) {
			if ( $this->Nbre > 125 ) $this->Nbre = 125;
			for ( $i=0; $i<=$this->Nbre; $i++ ) {
				$buffer[300000 + $this->DebutAdresse + $i] = rand(0, 4095);
			}
			return $buffer;
		} // FIN Simulation

		if ( !$this->MemoConn ) return array();

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		if ( $this->Nbre > 125 ) $this->Nbre = 125;
		$obuf = array ( 0=>chr(0), 1=>chr(0), 2=>chr(0), 3=>chr(0), 4=>chr(0), 5=>chr(6), 6=>chr($this->Unit), 7=>chr(4) ) ;
		list( $obuf[9], $obuf[8] ) = $this->WordToBytes( (int)$this->DebutAdresse );
		list( $obuf[11], $obuf[10] ) = $this->WordToBytes( (int)$this->Nbre );

		if ( $this->Debug ) { //Affichage des octets emis si en mode Debug
			echo "<b>ReadInputRegisters</b><br>"; 
			for ($i=0;$i<count($obuf);$i++ ) {
				echo "OctEmis[$i] =". ord($obuf[$i])."<br>";
			}
		}		

		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( "", $obuf ) );

		//--------- LECTURE DU SOCKET ---------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser apr?socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $OctetRecu[7] != $obuf[7] ) { 
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>";
			echo "ERREUR DE LECTURE des REGISTRES d'ENTREE de ".sprintf("3%05d", $this->DebutAdresse)." ".sprintf("3%05d", ($this->DebutAdresse + $this->Nbre))."<br>"; 
			for ( $i=0; $i<count($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
			echo "</b></FONT>\n";
			$this->MemoConn = False;
			return array();
		}
		//Recupération des DATAs
		$i = 1;
		$buffer = array();
		if ( $this->TypeFloat ) {
			for ($j=0; $j < ord($OctetRecu[8]); $j=$j+4) {
				$buffer[300000 + $this->DebutAdresse + $i] = $this->BytesToFloat( ord($OctetRecu[$j+9]), ord($OctetRecu[$j+10]), ord($OctetRecu[$j+11]), ord($OctetRecu[$j+12]) );
				$i++;
				$i++;
			}
		} elseif ( $this->TypeDouble ) {
			for ($j=0; $j < ord($OctetRecu[8]); $j=$j+4) {
				$buffer[300000 + $this->DebutAdresse + $i] = $this->BytesToDouble( ord($OctetRecu[$j+9]), ord($OctetRecu[$j+10]), ord($OctetRecu[$j+11]), ord($OctetRecu[$j+12]) );
				$i++;
				$i++;
			}
		} else {
			for ($j=0; $j < ord($OctetRecu[8]); $j=$j+2) {
				$buffer[300000 + $this->DebutAdresse + $i] = $this->BytesToWord( $OctetRecu[$j+9], $OctetRecu[$j+10] );
				$i++;
			}
		}
		
		//Affichage des octets recu si mode Debug
		if ( $this->Debug ) {
			for ( $i=0; $i<strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
		}
	
		if ( $this->Debug ) { //Affichage des octets recu si mode Debug
			for ( $i=0; $i<strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
		}

		return $buffer ;
	}

	// -------------------------------------------------------------
	//						LECTURE DES 1xxxxx          
	//			( retourne un tableau [1xxxxx] = valeur )
	// -------------------------------------------------------------
	function ReadDiscretInputs() {

		//Retourne des valeurs aleatoires entre 0 et 1 sans ouvrir les sockets	
		if ( $this->Simulation ) {
			if ( $this->Nbre > 2000 ) $this->Nbre = 2000;
			for ( $i=0; $i<=$this->Nbre; $i++ ) {
				$buffer[100000 + $this->DebutAdresse + $i] = rand(0, 1);
			}
			return $buffer;
		} // FIN Simulation

		if ( !$this->MemoConn ) return array();

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		if ( $this->Nbre > 2000 ) $this->Nbre = 2000;
		$obuf = array ( 0=>chr(0), chr(0), chr(0), chr(0), chr(0), 5=>chr(6), 6=>chr($this->Unit), 7=>chr(2) ) ;
		list( $obuf[9], $obuf[8] ) = $this->WordToBytes( (int)$this->DebutAdresse );
		list( $obuf[11], $obuf[10] ) = $this->WordToBytes( (int)$this->Nbre );

		if ( $this->Debug ) { //Affichage des octets emis si en mode Debug
			echo "<b>ReadDiscretInputs</b><br>"; 
			for ( $i=0; $i<count($obuf); $i++ ) {
				echo "OctEmis[$i] =". ord($obuf[$i])."<br>";
			}
		}

		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( "", $obuf) );
		
		//--------- LECTURE DU SOCKET --------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser apr?socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants
		
		if ( $OctetRecu[7] != $obuf[7] ) { // Lecture du 8eme octet = Code function renvoy?ar destinataire
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>";
			echo "ERREUR DE LECTURE des Bits d'ENTREES de ".sprintf("1%05d", $this->DebutAdresse)." ".sprintf("1%05d", ($this->DebutAdresse + $this->Nbre))."<br>"; 
			echo "</b></FONT>\n";
			$this->MemoConn = False;
			return array();
		}
		//$OctetRecu[8] 9eme octet = nbre d'octets (mots) de donn?
		$i = 0;
		$buffer = array();
		$debut = 100000 + $this->DebutAdresse + 1;
		while( $i < ord($OctetRecu[8]) ) {
			$tmp = $this->ByteToBits( $OctetRecu[$i+9] );
			for ( $j=0; $j<8; $j++ ) { //extraction des bits de mots
				$bit = $i*8 + $j;
				if ( $bit >= $this->Nbre ) break;
				$buffer[$debut + $j + $i*8] = $tmp[$j];
			}
			$i++;
		}
		
		if ( $this->Debug ) { //Affichage des octets recu si mode Debug
			for ( $i=0; $i < strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
		}

		return $buffer;
	}

	// -------------------------------------------------------------
	//						LECTURE DES 0xxxxx          
	//			( retourne un tableau ['0xxxxx'] = valeur )
	// -------------------------------------------------------------
	function ReadCoils() {
		
		//Retourne des valeurs aleatoires entre 0 et 1 sans ouvrir les sockets	
		if ( $this->Simulation ) {
			if ( $this->Nbre > 2000 ) $this->Nbre = 2000;
			for ( $i=0; $i<=$this->Nbre; $i++ ) {
				$buffer[sprintf( "%06d", $this->DebutAdresse + $i)] = rand(0, 1);
			}
			return $buffer;
		} // FIN Simulation

		if ( !$this->MemoConn ) return array();

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		if ( $this->Nbre > 2000 ) $this->Nbre = 2000;
		$obuf = array ( 0=>chr(0), chr(1), chr(0), chr(0), chr(0), 5=>chr(6), 6=>chr($this->Unit), 7=>chr(1) ) ;
		list( $obuf[9], $obuf[8] ) = $this->WordToBytes( (int)$this->DebutAdresse );
		list( $obuf[11], $obuf[10] ) = $this->WordToBytes( (int)$this->Nbre );

		if ( $this->Debug ) { //Affichage des octets emis si en mode Debug
			echo "<b>ReadCoils</b><br>"; 
			for ( $i=0; $i<count($obuf); $i++ ) {
				echo "OctEmis[$i] =". ord($obuf[$i])."<br>";
			}
		}

		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( "", $obuf) );
		
		//--------- LECTURE DU SOCKET --------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser apr?socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $OctetRecu[7] != $obuf[7] ) {
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>";
			echo "ERREUR DE LECTURE des Bits de SORTIES de ".sprintf("%06d", $this->DebutAdresse)." ".sprintf("%06d", ($this->DebutAdresse + $this->Nbre))."<br>\n"; 
			echo "</b></FONT>\n";
			$this->MemoConn = False;
			return array();
		}
		//$OctetRecu[8] 9eme octet = nbre mots(2octets) de donnees
		$i = 0;
		$buffer = array();
		$debut = $this->DebutAdresse + 1;
		while( $i < ord($OctetRecu[8]) ) {
			$tmp = $this->ByteToBits( $OctetRecu[$i+9] );
			for ( $j=0; $j<8; $j++ ) { 
				$bit = $i*8 + $j;
				if ( $bit >= $this->Nbre ) break;
				$buffer[ sprintf( "%06d", $debut + $j + $i*8 )] = $tmp[$j];
			}
			$i++;
		}
		if ( $this->Debug ) { //Affichage des octets recu si mode Debug
			for ( $i=0; $i<strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =".ord($OctetRecu[$i])."<br>";
			}
		}
		return $buffer;
	}

	// --------------------------------------------------------------------------------------------
	//            FONCTION PRINCIPALE ECRITURE D'UN TABLEAU DE 0xxxx/4xxxxx
	//					( retourne True ou False )
	// --------------------------------------------------------------------------------------------
	function WriteModbus( $AdrDebut = "400001", $Values = array() ) {
		$this->Nbre = Sizeof( $Values );
		if ( $this->Nbre < 1 ) $this->Nbre = 1;
		$this->WriteValues = $Values;
		// ECRITURE REELLE -----------------------		
		switch ( substr( sprintf("%06d", $AdrDebut), 0, 1) ) {	// Formatage ? caract?s
			case "4":
				$this->DebutAdresse = $AdrDebut - 400001;
				//echo "debut-adresse = $this->DebutAdresse";
				return ( $this->WriteHoldRegisters() );
				break;
			case "0":
				$this->DebutAdresse = $AdrDebut - 1;
				return ( $this->WriteCoil() );
				break;
			default:
				return False;
		}
	}

	// -----------------------------------------------
	//                ECRITURE DES 400000
	// -----------------------------------------------
	function WriteHoldRegisters() {

		if ( !$this->MemoConn ) return false;

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		$CodeFunction = 16;	// 16 = Ecriture d'un tableau de registres 400001
		if ($this->Nbre > 100 ) $this->Nbre = 100;

		$obuf[0] = chr(0);
		$obuf[1] = chr(0);
		$obuf[2] = chr(0);
		$obuf[3] = chr(0);
		list( $obuf[5], $obuf[4] ) = $this->WordToBytes( $this->Nbre * 2 + 7 ); //Nbre de mots
		$obuf[6] = chr( $this->Unit );
		$obuf[7] = chr( $CodeFunction );
		list( $obuf[9], $obuf[8] ) = $this->WordToBytes( (int)$this->DebutAdresse ); //Adresse de debut
		list( $obuf[11], $obuf[10] ) = $this->WordToBytes( $this->Nbre ); //Nbre de mots
		$obuf[12] = chr( $this->Nbre * 2 ); //Nbre d'octets
		for ( $i=0; $i < $this->Nbre*2; $i += 2 ) {
			list( $obuf[13+$i], $obuf[13+$i+1] ) = $this->WordToBytes( (int)$this->WriteValues[$i/2] ); //Valeurs
		}
		if ( $this->Debug ) { 
			echo "<b>WriteHoldRegisters</b><br>"; 
			for ($i=0;$i<count($obuf);$i++ ) {
				echo "OctEmis[$i] =". ord($obuf[$i])."<br>";
			}
		}		
		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( "", $obuf ) );

		//--------- LECTURE DU SOCKET ---------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser apres socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $OctetRecu[7] != $obuf[7] ) { 
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>ERREUR D'ECRITURE</b></FONT><br>\n";
			$this->MemoConn = False;
			return False;
		}
		//$OctetRecu[8] //Confirmation Debut tableau d'adresse ecrite (octet Haut)
		//$OctetRecu[9] //Confirmation Debut tableau d'adresse ecrite (octet Bas) adresse = Oct.Haut*256 + Oct.Bas
		//$OctetRecu[10] //Nbre d'octets suivent

		if ( $this->Debug ) { //Affichage des octets recu si mode Debug
			for ( $i=0; $i<strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
		}
		return True ;
	}


	// -----------------------------------------------
	//                ECRITURE D'UNE BOBINE
	// -----------------------------------------------
	function WriteCoil() {

		if ( !$this->MemoConn ) return False;

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		$CodeFunction = 5;	// 5 = Ecriture d'une Bobine

		$obuf[0] = chr(0);
		$obuf[1] = chr(0);
		$obuf[2] = chr(0);
		$obuf[3] = chr(0);
		list( $obuf[5], $obuf[4] ) = $this->WordToBytes( 6 ); //Nbre de mots qui suivent
		$obuf[6] = chr( $this->Unit );
		$obuf[7] = chr( $CodeFunction );
		list( $obuf[9], $obuf[8] ) = $this->WordToBytes( (int)$this->DebutAdresse ); //Adresse de la bobine
		if ( $this->WriteValues[0] ) { $obuf[10] = chr(255); } else { $obuf[10] = chr(0); }
		$obuf[11] = chr(0);

		if ( $this->Debug ) { 
			echo "<b>WriteCoil</b><br>"; 
			for ($i=0;$i<count($obuf);$i++ ) {
				echo "OctEmis[$i] =". ord($obuf[$i])."<br>";
			}
		}		

		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( "", $obuf ) );

		//--------- LECTURE DU SOCKET ---------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser apres socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $OctetRecu[7] != $obuf[7] ) { 
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>ERREUR D'ECRITURE de la BOBINE ".sprintf("%06d", $Adresse)."</b></FONT><br>\n";
			$this->MemoConn = False;
			return False;
		}

		if ( $this->Debug ) { //Affichage des octets recu si mode Debug
			for ( $i=0; $i<strlen($OctetRecu); $i++ ) {
				echo "OctRecu[$i] =". ord($OctetRecu[$i])."<br>";
			}
		}
		return True ;
	}

	// --------------------------------------------------------
	//            SETUP BRIDGE FOR DYNAMIC ROUTING
	// --------------------------------------------------------
	function SetBridgeRoute() {
		
		//--------------------------------------------------------------------------------------------------------
		//Consiste a ecrire a l'adresse 255 du device 255 de la passerelle, le routage MB+ avec la fonction 16
		//Une fois la route etablie, on peut lire ou ecrire dans le device 254 pour atteindre l'automate concerne
		//---------------------------------------------------------------------------------------------------------

		//Test si la nouvelle route est égale à l'ancienne.
		//Si pas de changement de routage MB+, on n'envoie pas de nouvelle route a la passerelle MB+/Ethernet

		//echo "BridgeRoute tab = "; 
		//$this->print_r_log ($this->BridgeRoute);
		//echo "tmpBridgeRoute tab = ";
		//$this->print_r_log ($this->tmpBridgeRoute);

		if ( strcmp( implode(".", $this->BridgeRoute), implode(".", $this->tmpBridgeRoute) ) == 0 )	return true;

		$this->tmpBridgeRoute = $this->BridgeRoute;

		$obuf1[0] = chr(0);
		$obuf1[1] = chr(0);
		$obuf1[2] = chr(0);
		$obuf1[3] = chr(0);
		$obuf1[4] = chr(0);

		$obuf1[5] = chr(13);	//Nbre d'octets qui suivent
		$obuf1[6] = chr(255);	// 1=Host-based routing  255=Socket-based routing
		//CODE FONCTION MODBUS
		$obuf1[7] = chr(16);	//Code fonction Modbus 16 = Ecriture d'un tableau de registres 4xxxxx
		//EN TETE MODBUS
		$obuf1[8] = chr(0);		//Adresse de debut octet 1
		$obuf1[9] = chr(255-1);	//Adresse de debut octet 2
		$obuf1[10] = chr(0);	//Nbre de mots octet 1
		$obuf1[11] = chr(3);	//Nbre de mots octet 2
		$obuf1[12] = chr(6);	//Nbre d'octets qui suivent
		//DATA
		$obuf1[13] = chr(5);	//Nbre d'octets qui suivent = Cte => Attention: fait parti des champs data's
		$obuf1[14] = chr($this->BridgeRoute[0]);	//Routage 1
		$obuf1[15] = chr($this->BridgeRoute[1]);	//Routage 2
		$obuf1[16] = chr($this->BridgeRoute[2]);	//Routage 3
		$obuf1[17] = chr($this->BridgeRoute[3]);	//Routage 4
		$obuf1[18] = chr($this->BridgeRoute[4]);	//Routage 5

		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( '', $obuf1 ) );

		if ( $this->Debug == true ) {
			echo "<b>BridgeRouting</b>";
			for( $i=0; $i<sizeof($obuf1); $i++ ) {
				echo "<br>Emission - Octet $i=".ord($obuf1[$i]); 
			}
			echo "<br>";
		}

		//--------- LECTURE DU SOCKET --------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $this->Debug == true ) echo "Reception - Adresse Unite = ".ord($OctetRecu[6])." <br>"; // 7eme octet = adresse du device
		if ( $OctetRecu[7] != $obuf1[7] ) { //8eme octet = Code function renvoye par destinataire
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>";
			echo "ERREUR D'ECRITURE dans la fonction de routage dynamique de la passerelle (SetBridgeRoute) a l'adr. IP ".$this->AdIpPLC."<br>\n";
			echo "</b></FONT>\n";
			$this->MemoConn = False;
			fclose($this->Fp);
			
			exit;
		}
		if ( $this->Debug == true ) echo "Reception - Code function = ".ord($obuf1[7])."<br>";
		$adresse = $this->BytesToWord( ord($obuf1[8]), ord($obuf1[9]) );
		if ( $this->Debug == true ) echo "Reception - Adresse debut = $adresse <br>";
		$nbMots = $this->BytesToWord( ord($obuf1[10]), ord($obuf1[11]) );
		if ( $this->Debug == true ) echo "Reception - Nbre de mots = $nbMots <br>";

		//Ne pas fermer la connection !! car la fonction Modbus suivante suivra !
		//fclose($this->Fp); 
		
		$this->Unit = 254; //Preparation pour la fonction Modbus suivante.
		
		return True ;
	}

} //Fin de la class 'ModbusTcp'



class ModbusSingle extends ModbusTcp {

	// ---------------------------------------------------
	//				LECTURE D'UN 3/4xxxxx     
	//				( retourne la valeur )
	// ---------------------------------------------------
	function ReadReg( $Adr = "400001" ) {

		if ( !$this->Fp ) $this->Fp = fsockopen( $this->AdIpPLC, $this->PortIpPLC, $errno, $errstr, 10 ) or die("$errstr ($errno)<br>\nPas de connexion a l'Adresse $this->AdIpPLC a function 'ReadReg'");
		
		if ( !$this->MemoConn ) return array();

		if ( $this->BridgeRoute ) $this->SetBridgeRoute();

		$obuf = array ( 0=>chr(0), chr(0), chr(0), chr(0), chr(0), 5=>chr(6), 6=>chr($this->Unit), 7=>chr(3) ) ;
		list( $obuf[8], $obuf[9] ) = $this->WordToBytes( $Adr-400001 );

		if ( substr($Adr, 0, 1) == "3" ) {  // Test si INPUT REGISTER ( 3xxxxx )
			$obuf[7] = chr(4);
			list( $obuf[8], $obuf[9] ) = $this->WordToBytes( $Adr-300001 );
		}

		$obuf[10] = chr(0);
		$obuf[11] = chr(1);
		
		//--------- ECRITURE DU SOCKET --------------
		fwrite( $this->Fp, implode( '', $obuf ) );

		//--------- LECTURE DU SOCKET --------------
		$OctetRecu = fgetc($this->Fp); //Lire le 1er octet du socket pour utiliser socket_get_status()
		$status = socket_get_status($this->Fp);
		$OctetRecu .= fread($this->Fp, $status["unread_bytes"]); //Lire les octets restants

		if ( $OctetRecu[7] != $obuf[7] ) { // Lecture du 8eme octet = Code function renvoye par destinataire
			echo "<FONT SIZE='3' COLOR='#FFFF00'><b>";
			echo "ERREUR DE LECTURE du REGISTRE<br>";
			echo "</b></FONT>\n";
			$this->MemoConn = False;
			return array();
		}
		$nbByte = ord( $OctetRecu[8] ); // Lecture du 9eme octet = nbre d'octets (mots) de donnees
		return $this->BytesToWord( $OctetRecu[9],$OctetRecu[10] ) ;
	}
} //Fin de la class 'ModbusSingle'



class ModbusArray extends ModbusTcp {

	// -------------------------------------------------------------------------
	//					DECOUPAGE D'UN TABLEAU de 0/1/3/4xxxxx   
	//			( retourne un tableau [debut_adres] = Nbre de mots/bits )
	// -------------------------------------------------------------------------
	function DecoupeTrame( $arr ) {
		asort($arr);
//		$this->print_r_log ($arr); echo "<br>";
		
		$tmp = $arr[key($arr)]; //init a la premiere valeur du tableau
		foreach ( $arr as $i => $value) {
			ereg( "^3|^4", $arr[$i]) ? $max = 125 : $max = 500;
			if ( $arr[$i] - $tmp >= $max ) {
				$resultat[$tmp] = $arr[$i_1] - $tmp + 1;
				$tmp = $arr[$i];
			}
			$i_1 = $i;
		}

		$resultat[$tmp] = $arr[$i] - $tmp + 1; //traitement de la derniere adresse
//		$this->print_r_log ($resultat); echo "<br>";

		return $resultat;
	}

	// ------------------------------------------------------------------------------------------
	//			LECTURE D'UN TABLEAU HETEROGENE TRIE ou NON TRIE 0/1/3/4xxxxx POUR UN DEVICE   
	//				( retourne un tableau [0/1/3/4xxxxx] = valeur )
	// ------------------------------------------------------------------------------------------
	function ReadArrRegs( $arrAdr = array ("400001") ) {
		
		$buf = $this->DecoupeTrame( $arrAdr );
		
		// Fait apparaitre les tableau de bits ou de Mots utilises pour les trames 
		if ( $this->Debug ) { 
			echo "TRAMES: DEVICE => ".$this->Unit." => "; 
			$this->print_r_log ($buf); 
			echo "<br>\n"; 
		}

		$buffer = array();
		foreach ( $buf as $debut => $nbre ) {
			$buffer += $this->ReadModbus( $debut, $nbre );
		}

		return $buffer ;
	}
}

?>
