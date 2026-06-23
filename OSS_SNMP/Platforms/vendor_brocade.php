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
// 'Brocade Communications Systems, Inc. Stacking System ICX7450-48, IronWare Version 08.0.30dT213 Compiled on Nov  3 2015 at 22:16:04 labeled as SPR08030d'
// 'Brocade NetIron CES, IronWare Version V5.2.0cT183 Compiled on Oct 28 2011 at 02:58:44 labeled as V5.2.00c'
// 'Brocade NetIron MLX (System Mode: MLX), IronWare Version V5.4.0cT163 Compiled on Mar 25 2013 at 17:08:16 labeled as V5.4.00c'
// 'Brocade MLXe (System Mode: MLX), IronWare Version V5.7.0dT163 Compiled on Sep 23 2015 at 09:35:50 labeled as V5.7.00db'
// 'Brocade VDX Switch, BR-VDX6720-24, Network Operating System Software Version 4.1.3b.'

if( substr( $sysDescr, 0, 8 ) == 'Brocade ' || substr( $sysDescr, 0, 23 ) == 'Foundry Networks, Inc. ' )
{
    if( preg_match( '/Brocade Communication[s]* Systems, Inc. [(Stacking System)]*(.+),\s([a-zA-Z]+)\sVersion\s(.+)\sCompiled\son\s(([a-zA-Z]+)\s+(\d+)\s(\d+)\s)at\s((\d\d):(\d\d):(\d\d))\slabeled\sas\s(.+)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Brocade' );
        $this->setModel( $matches[1] );
        $this->setOs( $matches[2] );
        $this->setOsVersion( $matches[3] );
        $this->setOsDate( new \DateTime( "{$matches[6]}/{$matches[5]}/{$matches[7]}:{$matches[8]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    }
    else if( preg_match( '/Brocade ((NetIron )?[a-zA-Z0-9]+).*IronWare\sVersion\s(.+)\s+Compiled\s+on\s+(([a-zA-Z]+)\s+(\d+)\s+(\d+)\s+)at\s+((\d\d):(\d\d):(\d\d))\s+labeled\s+as\s+(.+)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Brocade' );
        $this->setModel( $matches[1] );
        $this->setOs( 'IronWare' );
        $this->setOsVersion( $matches[3] );
        $this->setOsDate( new \DateTime( "{$matches[6]}/{$matches[5]}/{$matches[7]}:{$matches[8]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    }
    // Foundry Networks, Inc. FES12GCF, IronWare Version 04.1.01eTc1 Compiled on Mar 06 2011 at 17:05:36 labeled as FES04101e
    // Foundry Networks, Inc. BigIron RX, IronWare Version V2.7.2aT143 Compiled on Sep 29 2009 at 17:15:24 labeled as V2.7.02a
    else if( preg_match( '/^Foundry Networks, Inc\. ([A-Za-z0-9\s]+), IronWare Version ([0-9a-zA-Z\.]+) Compiled on (([a-zA-Z]+) (\d+) (\d+) )at ((\d\d):(\d\d):(\d\d)) labeled as ([A-Za-z0-9\.]+)$/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Foundry Networks' );
        $this->setModel( $matches[1] );
        $this->setOs( 'IronWare' );
        $this->setOsVersion( $matches[2] );
        $d = new \DateTime( "{$matches[5]}/{$matches[4]}/{$matches[6]}:{$matches[7]} +0000" );
        $d->setTimezone( new \DateTimeZone( 'UTC' ) );
        $this->setOsDate( $d );
    }
    else if( preg_match( '/Brocade VDX Switch,\s(.+), Network Operating System Software Version\s(.+)\./',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Brocade' );
        $this->setModel( $matches[1] );
        $this->setOs( 'Network Operating System Software' );
        $this->setOsVersion( $matches[2] );
    }

    try {
        $this->setSerialNumber( $this->getSNMPHost()->useFoundry_Chassis()->serialNumber() );
    } catch( Exception $e ) {
        $this->setSerialNumber( '(error)' );
    }
}

