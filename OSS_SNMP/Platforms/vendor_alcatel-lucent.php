<?php

/*
    Copyright (c) 2012 - 2013, Open Source Solutions Limited, Dublin, Ireland
    Copyright (c) 2013 Jacques Marneweck
    Copyright (c) 2013 Old Bay Industries
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
// 'Alcatel-Lucent OS9600/OS9700-CFM 6.4.3.520.R01 GA, April 08, 2010.'
// 'Alcatel-Lucent OS6850-P24 6.4.3.520.R01 GA, April 08, 2010.'

if( substr( $sysDescr, 0, 14 ) == 'Alcatel-Lucent' )
{
    $this->setVendor( 'Alcatel-Lucent' );
    $this->setOs( 'AOS' );

    if( substr( $sysDescr, 0, 18 ) == 'Alcatel-Lucent OS9' ) {
        preg_match( '/Alcatel-Lucent (OS.+CFM) ([0-9A-Za-z\(\)\.]+) GA,\s([a-zA-Z]+)\s(\d+),\s(\d+)\./',
            $sysDescr, $matches );

        $this->setModel( explode("/", $matches[1])[0] );

        $this->setOsVersion( $matches[2] );
        $this->setOsDate( new \DateTime( "{$matches[5]}-{$matches[3]}-{$matches[4]}" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    } else if ( substr( $sysDescr, 0, 18 ) == 'Alcatel-Lucent OS6' ) {
        preg_match( '/Alcatel-Lucent (OS.+) ([0-9A-Za-z\(\)\.]+) GA,\s([a-zA-Z]+)\s(\d+),\s(\d+)\./',
            $sysDescr, $matches );

        $this->setModel( $matches[1] );

        $this->setOsVersion( $matches[2] );
        $this->setOsDate( new \DateTime( "{$matches[5]}-{$matches[3]}-{$matches[4]}" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    } else {
        $model = $this->getSNMPHost()->get( '.1.3.6.1.2.1.47.1.1.1.1.13.1' );

        if ( !empty ( $model ) ) {
            $this->setModel( $model );

            preg_match( '/Alcatel-Lucent ([0-9A-Za-z\(\)\.]+) GA,\s([a-zA-Z]+)\s(\d+),\s(\d+)\./',
                $sysDescr, $matches );

            $this->setOsVersion( $matches[1] );
            $this->setOsDate( new \DateTime( "{$matches[4]}-{$matches[2]}-{$matches[3]}" ) );
            $this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
        } else {
            $this->setModel( 'Unknown' );
            $this->setOsVersion( 'Unknown' );
            $this->setOsDate( null );
        }
    }
}
