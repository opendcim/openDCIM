<?php

/*
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

// Verified against Apresia15000 series devices
//
// Sample sysDescr:
// "Hitachi Cable Ethernet Switch Apresia15000-32XL-PSR Ver.8.22.03"

if( substr( $sysDescr, 0, 13 ) == 'Hitachi Cable' )
{
    preg_match( '/(Hitachi Cable) Ethernet Switch ([^\s]+) ([^\s]+)/', $sysDescr, $matches );
    $this->setVendor( $matches[1] );
    $this->setModel( $matches[2] );
    $this->setOs( 'hitachi os' );
    $this->setOsVersion( $matches[3] );

    if( preg_match( '/Build date: (\d\d\d\d-\d\d-\d\d) (\d\d:\d\d:\d\d) ([A-Za-z]+)/', $sysDescr, $d ) )
    {
        $this->setOsDate( new \DateTime( "{$d[1]} {$d[2]} +00:00") );
        $this->getOsDate()->setTimezone( new \DateTimeZone( $d[3] ) );
    }
}
