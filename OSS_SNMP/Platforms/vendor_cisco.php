<?php

/*
    Copyright (c) 2012 - 2013, Open Source Solutions Limited, Dublin, Ireland
    All rights reserved.

    Contact: Barry O'Donovan - barry (at) opensolutions (dot) ie
    http://www.opensolutions.ie/

    This file is part of the OSS_SNMP package.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of Open Source Solutions Limited nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
    DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

if( substr( $sysDescr, 0, 26 ) == 'Cisco IOS Software, IOS-XE' )
{
    // 'Cisco IOS Software, IOS-XE Software, Catalyst 4500 L3 Switch  Software (cat4500es8-UNIVERSAL-M), Version 03.08.02.E RELEASE SOFTWARE (fc2)'
    // 'Cisco IOS Software, IOS-XE Software, Catalyst L3 Switch Software (CAT3K_CAA-UNIVERSALK9-M), Version 03.06.06E RELEASE SOFTWARE (fc1)'

    preg_match( '/Cisco IOS Software, IOS-XE Software,\s([a-zA-Z0-9\s]+[a-zA-Z0-9])\s+Software\s\(([0-9A-Za-z\(\)_\.\-]+)\),\sVersion\s([0-9A-Za-z\.]+)\sRELEASE SOFTWARE.*/',
        $sysDescr, $matches );

    $this->setVendor( 'Cisco Systems' );
    $this->setModel( $matches[1]);
    $this->setOs( 'IOS-XE' );
    $this->setOsVersion( isset( $matches[3] ) ? $matches[3] : '' );
    $this->setOsDate( null );

}
else if( substr( $sysDescr, 0, 18 ) == 'Cisco IOS Software' )
{
    // 'Cisco IOS Software, s72033_rp Software (s72033_rp-ADVENTERPRISE_WAN-VM), Version 12.2(33)SXI5, RELEASE SOFTWARE (fc2)'
    // 'Cisco IOS Software [Everest], Catalyst L3 Switch Software (CAT3K_CAA-UNIVERSALK9-M), Version 16.6.2, RELEASE SOFTWARE (fc2)'

    preg_match( '/Cisco IOS Software(?: \[\w+\])?,\s([\w\s]+)\sSoftware \((.+)\), Version\s([0-9A-Za-z\(\)\.]+), RELEASE SOFTWARE\s\((.+)\)/',
            $sysDescr, $matches );

    $this->setVendor( 'Cisco Systems' );
    try {
        if( $this instanceof \OSS_SNMP\TestPlatform ) {
            $this->setModel('PHPUnit');
        } else {
            $model = $this->getSNMPHost()->useEntity()->physicalName();
            if( isset( $model[1] ) ) {
                $this->setModel( $model[ 1 ] );
            } else {
                $this->setModel( 'Unknown' );
            }
        }
    } catch( \Exception $e ) {
        $this->setModel( 'Unknown' );
    }

    $this->setOs( 'IOS' );
    $this->setOsVersion( isset( $matches[3] ) ? $matches[3] : '' );
    $this->setOsDate( null );
}
else if( substr( $sysDescr, 0, 48 ) == 'Cisco Internetwork Operating System Software IOS' )
{
    // 'Cisco Internetwork Operating System Software IOS (tm) C2950 Software (C2950-I6Q4L2-M), Version 12.1(13)EA1, RELEASE SOFTWARE.*'

    $sysDescr = trim( preg_replace( '/\s+/', ' ', $sysDescr ) );
    preg_match( '/Cisco(.+)C2950 Software(.+)Version\s([0-9A-Za-z\(\)\.]+),\sRELEASE SOFTWARE.*/',
            $sysDescr, $matches );

    $this->setVendor( 'Cisco Systems' );
    $this->setModel( 'C2950' );
    $this->setOs( 'IOS' );
    $this->setOsVersion( isset( $matches[3] ) ? $matches[3] : '' );
    $this->setOsDate( null );
}
else if( substr( $sysDescr, 0, 21 ) == 'Cisco IOS XR Software' )
{
    // 'Cisco IOS XR Software (Cisco ASR9K Series),  Version 4.3.2[Default]\r\nCopyright (c) 2013 by Cisco Systems, Inc., referer: http://10.0.35.20/ixp/switch/add-by-snmp'

    preg_match( '/Cisco IOS XR Software \((.+ Series)\),\s+Version\s([0-9A-Za-z\(\)\.\[\]]+)\s+Copyright \(c\) [0-9]+ by Cisco Systems, Inc.*/',
            $sysDescr, $matches );
    $this->setVendor( 'Cisco Systems' );
    $this->setModel( $matches[1] );
    $this->setOs( 'IOS XR' );
    $this->setOsVersion( isset( $matches[2] ) ? $matches[2] : '' );
    $this->setOsDate( null );
}
else if( substr( $sysDescr, 0, 11 ) == 'Cisco NX-OS' ) {

    // Cisco NX-OS(tm) n9000, Software (n9000-dk9), Version 6.1(2)I2(2b), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc. Compiled 8/7/2014 15:00:00
    // Cisco NX-OS(tm) n3500, Software (n3500-uk9), Version 6.0(2)A1(1d), RELEASE SOFTWARE Copyright (c) 2002-2012 by Cisco Systems, Inc. Device Manager Version nms.sro not found, Compiled 1/30/2014 9:00:00
    // Cisco NX-OS(tm) n3500, Software (n3500-uk9), Version 6.0(2)A6(3), RELEASE SOFTWARE Copyright (c) 2002-2012 by Cisco Systems, Inc. Compiled 7/1/2015 10:00:00
    // Cisco NX-OS(tm) n3500, Software (n3500-uk9), Version 6.0(2)A1(1d), RELEASE SOFTWARE Copyright (c) 2002-2012 by Cisco Systems, Inc. Device Manager Version nms.sro not found,  Compiled 1/30/2014 9:00:00

    if( preg_match( '/^Cisco NX\-OS\(tm\) (n[\d+]+), Software \([a-zA-Z0-9\-]+\), Version ([a-zA-Z0-9\.\(\)]+), RELEASE SOFTWARE Copyright \(c\) (?:\d+)-(?:\d+) by Cisco Systems, Inc\.(?: Device Manager Version nms\.sro not found,)?\s+Compiled (\d+)\/(\d+)\/(\d+) (\d+):(\d+):(\d+)$/',
        $sysDescr, $matches ) )
    {
        $this->setVendor( 'Cisco Systems' );
        $this->setModel( $matches[1] );
        $this->setOs( 'NX-OS' );
        $this->setOsVersion( isset( $matches[2] ) ? $matches[2] : '' );
        $d = new \DateTime( sprintf( "{$matches[5]}/%02d/%02d {$matches[6]}:{$matches[7]}:{$matches[8]} +0000", $matches[3], $matches[4] ) );
        $d->setTimezone( new \DateTimeZone( 'UTC' ) );
        $this->setOsDate( $d );
    }

    // Cisco NX-OS(tm) nxos.7.0.3.I2.3.bin, Software (nxos), Version 7.0(3)I2(3), RELEASE SOFTWARE Copyright (c) 2002-2013 by Cisco Systems, Inc. Compiled 3/19/2016 22:00:00
    else if( preg_match( '/^Cisco NX\-OS\(tm\) ([a-zA-Z0-9\.]+), Software \([a-zA-Z0-9\-]+\), Version ([a-zA-Z0-9\.\(\)]+), RELEASE SOFTWARE Copyright \(c\) (?:\d+)-(?:\d+) by Cisco Systems, Inc\.(?: Device Manager Version nms\.sro not found,)?\s+Compiled (\d+)\/(\d+)\/(\d+) (\d+):(\d+):(\d+)$/',
        $sysDescr, $matches ) )
    {
        $this->setVendor( 'Cisco Systems' );
        $this->setModel( 'nXXXX' );
        $this->setOs( 'NX-OS' );
        $this->setOsVersion( isset( $matches[2] ) ? $matches[2] : '' );
        $d = new \DateTime( sprintf( "{$matches[5]}/%02d/%02d {$matches[6]}:{$matches[7]}:{$matches[8]} +0000", $matches[3], $matches[4] ) );
        $d->setTimezone( new \DateTimeZone( 'UTC' ) );
        $this->setOsDate( $d );
    }

}
