<?php

/*
    Copyright (c) 2012 - 2015, Open Source Solutions Limited, Dublin, Ireland
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


// Works with sysDescr such as:
//
// 'Dell Force10 OS Operating System Version: 1.0 Application Software Version: 8.3.12.1 Series: S4810 Copyright (c) 1999-2012 by Dell Inc. All Rights Reserved. Build Time: Sun Nov 18 11:05:15 2012'
// 'Dell Force10 OS Operating System Version: 2.0 Application Software Version: 9.3(0.0) Series: S4810 Copyright (c) 1999-2014 by Dell Inc. All Rights Reserved. Build Time: Thu Jan 2 02:14:08 2014'
// 'Dell Networking OS Operating System Version: 2.0 Application Software Version: 9.10(0.1P3) Series: S4810 Copyright (c) 1999-2016 by Dell Inc. All Rights Reserved. Build Time: Tue Jun 14 15:00:23 2016'

if( substr( $sysDescr, 0, 5 ) == 'Dell ' )
{
    $sysDescr = preg_replace('/\R/',' ', $sysDescr );
    if( preg_match( '/^Dell (Force10|Networking) OS Operating System Version: ([\d\.]+) Application Software Version:\s([A-Z0-9\(\)\.]+)\sSeries:\s([A-Z0-9]+)\sCopyright \(c\) \d+-\d+ by Dell Inc. All Rights Reserved. Build Time:\s[A-Za-z0-9]+\s(([a-zA-Z]+)\s+(\d+)\s((\d\d):(\d\d):(\d\d))\s(\d+))$/',
           $sysDescr, $matches ) )
    {
        $this->setVendor( "Dell {$matches[1]}" );
        $this->setModel( $matches[4] );
        $this->setOs( "FTOS {$matches[2]}" );
        $this->setOsVersion( $matches[3] );
        $this->setOsDate( new \DateTime( "{$matches[7]}/{$matches[6]}/{$matches[12]}:{$matches[8]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    }

    try {
        $this->setSerialNumber( $this->getSNMPHost()->get( '.1.3.6.1.2.1.47.1.1.1.1.11.2' ) );
    } catch( Exception $e ) {
        $this->setSerialNumber( '(error)' );
    }
}
