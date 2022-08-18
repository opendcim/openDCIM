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

// Cumulus
// Linux switch.ixleeds.net 3.2.46-1+deb7u1+cl1 #3.2.46-1+deb7u1+cl1 SMP Fri Feb 7 13:15:34 PST 2014 ppc

// Ubuntu
// Linux ixleeds1 3.11.0-23-generic #40-Ubuntu SMP Wed Jun 4 21:05:23 UTC 2014 x86_64

if( substr( $sysDescr, 0, 6 ) == 'Linux ' )
{
    if( preg_match( '/Linux ([^ ]+) ([^ ]+)\+([^ ]+) #([^ ]+) SMP ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( 'Cumulus Networks' );
        $this->setModel( 'Generic' );
        $this->setOs( 'Linux' );
        $this->setOsVersion( $matches[2] );
        $this->setOsDate( new \DateTime( "{$matches[7]}/{$matches[6]}/{$matches[10]}:{$matches[8]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( $matches[9] ));
    }
    else if( preg_match( '/Linux ([^ ]+) ([^ ]+)-([^ ]+) #[^ ]+-([^ ]+) SMP ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) ([^ ]+) (.*)/',
            $sysDescr, $matches ) )
    {
        $this->setVendor( $matches[4] );
        $this->setModel( 'Generic' );
        $this->setOs( 'Linux' );
        $this->setOsVersion( $matches[2] );
        $this->setOsDate( new \DateTime( "{$matches[7]}/{$matches[6]}/{$matches[10]}:{$matches[8]} +0000" ) );
        $this->getOsDate()->setTimezone( new \DateTimeZone( $matches[9] ));
    }
}
