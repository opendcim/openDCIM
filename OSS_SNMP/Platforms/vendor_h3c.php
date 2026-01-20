<?php

if( substr( $sysDescr, 0, 20 ) == 'H3C Comware software' )
{

    //H3C Comware software. H3C S9512E Product Version S9500E-CMW520-R1238P08. Copyright (c) 2004-2010 Hangzhou H3C Tech. Co., Ltd. All rights reserved.
    //H3C Switch E528 Software Version 5.20, Release 1103P01 Copyright(c) 2004-2010 Hangzhou H3C Tech. Co., Ltd. All rights reserved.
	//H3C Comware Platform Software, Software Version 5.20 Release 2202P06 H3C S5120-28C-EI Copyright (c) 2004-2010 Hangzhou H3C Tech. Co., Ltd. All rights reserved.

    preg_match( '/H3C Comware software. (.+) Product Version\s(.+)\. Copyright/',
            $sysDescr, $matches );

    $this->setVendor( 'H3C' );
    try {
        $this->setModel( $this->getSNMPHost()->useEntity()->physicalName()[1] );
    } catch( \OSS_SNMP\Exception $e ) {
        $this->setModel( 'Unknown' );
    }
    $this->setOs( 'Comware' );
    $this->setOsVersion( $matches[2] );
    $this->setOsDate( null );
}
else if( substr( $sysDescr, 0, 10 ) == 'H3C Switch' )
{
    $sysDescr = trim( preg_replace( '/\s+/', ' ', $sysDescr ) );
    preg_match( '/H3C Switch (.+) Software Version (.+)Copyright/',
            $sysDescr, $matches );

    $this->setVendor( 'H3C' );
    $this->setModel( $matches[1] );
    $this->setOs( null );
    $this->setOsVersion( $matches[2] );
    $this->setOsDate( null );
} 
else if( substr( $sysDescr, 0, 29 ) == 'H3C Comware Platform Software' )
{
    $sysDescr = trim( preg_replace( '/\s+/', ' ', $sysDescr ) );
    preg_match( '/H3C Comware Platform Software, Software Version (.+) H3C (.+)Copyright/',
            $sysDescr, $matches );

    $this->setVendor( 'H3C' );
    $this->setModel( $matches[2] );
    $this->setOs( 'Comware' );
    $this->setOsVersion( $matches[1] );
    $this->setOsDate( null );
} 
