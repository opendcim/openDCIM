<?php

/*
    Copyright (c) 2012 - 2014, Open Source Solutions Limited, Dublin, Ireland
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
// 'ProCurve J4903A Switch 2824, revision I.08.98, ROM I.08.07 (/sw/code/build/mako(ts_08_5))'

if( substr( $sysDescr, 0, 9 ) == 'ProCurve ' )
{
    if( preg_match( '/ProCurve (\w+) Switch (\w+).*, revision ([A-Z0-9\.]+), ROM ([A-Z0-9\.]+ .*)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Hewlett-Packard' );
        $this->setModel( "Procurve Switch {$matches[2]} ({$matches[1]})" );
        $this->setOs( 'ProCurve' );
        $this->setOsVersion( $matches[3] );
        $this->setOsDate( null );
        //$this->getOsDate()->setTimezone( new \DateTimeZone( 'UTC' ) );
    }

    try {
        $this->setSerialNumber( $this->getSNMPHost()->useHP_ProCurve_Chassis()->serialNumber() );
    } catch( Exception $e ) {
        $this->setSerialNumber( '(error)' );
    }
}
