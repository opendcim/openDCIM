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


// Works with sysDescr such as:
//
// 'Brocade Communications Systems, Inc. FESX624+2XG, IronWare Version 07.3.00cT3e1 Compiled on Apr 25 2012 at 17:01:00 labeled as SXS07300c'
// 'Brocade Communication Systems, Inc. TurboIron-X24, IronWare Version 04.2.00b Compiled on Oct 22 2010 at 15:15:36 labeled as TIS04200b'
// 'Brocade NetIron CES, IronWare Version V5.2.0cT183 Compiled on Oct 28 2011 at 02:58:44 labeled as V5.2.00c'
// 'Brocade NetIron MLX (System Mode: MLX), IronWare Version V5.4.0cT163 Compiled on Mar 25 2013 at 17:08:16 labeled as V5.4.00c'

if( substr( $sysDescr, 0, 8 ) == 'Brocade ' )
{
    if( preg_match( '/Brocade Communication[s]* Systems, Inc. (.+),\s([a-zA-Z]+)\sVersion\s(.+)\sCompiled\son\s(([a-zA-Z]+)\s(\d+)\s(\d+)\s)at\s((\d\d):(\d\d):(\d\d))\slabeled\sas\s(.+)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Brocade' );
        $this->setModel( $matches[1] );
        $this->setOs( $matches[2] );
        $this->setOsVersion( $matches[3] );
        $this->setOsDate( new \DateTime( "{$matches[6]}/{$matches[5]}/{$matches[7]}:{$matches[8]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    }
    else if( preg_match( '/Brocade (NetIron [a-zA-Z0-9]+).*IronWare\sVersion\s(.+)\s+Compiled\s+on\s+(([a-zA-Z]+)\s+(\d+)\s+(\d+)\s+)at\s+((\d\d):(\d\d):(\d\d))\s+labeled\s+as\s+(.+)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Brocade' );
        $this->setModel( $matches[1] );
        $this->setOs( 'IronWare' );
        $this->setOsVersion( $matches[2] );
        $this->setOsDate( new \DateTime( "{$matches[5]}/{$matches[4]}/{$matches[6]}:{$matches[7]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    }
    else if( preg_match( '/Foundry Networks, Inc. (.+),\sIronWare\sVersion\s(.+)\sCompiled\son\s(([a-zA-Z]+)\s(\d+)\s(\d+)\s)at\s((\d\d):(\d\d):(\d\d))\slabeled\sas\s(.+)/',
            $sysDescr, $matches ) )
    {
        echo "Vendor:   " . 'Foundry Networks' . "\n";
        echo "Model:    " . $matches[1] . "\n";
        echo "OS:       " . 'IronWare' . "\n";
        echo "OS Ver:   " . $matches[2] . "\n";
        $d = new \DateTime( "{$matches[5]}/{$matches[4]}/{$matches[6]}:{$matches[7]} +0000" );
        $d->setTimezone( new \DateTimeZone( 'UTC' ) );
        echo "OS Date:  " . $d->format( 'Y-m-d H:i:s' ) . "\n\n";
    }

    try {
        $this->setSerialNumber( $this->getSNMPHost()->useFoundry_Chassis()->serialNumber() );
    } catch( Exception $e ) {
        $this->setSerialNumber( '(error)' );
    }
}
