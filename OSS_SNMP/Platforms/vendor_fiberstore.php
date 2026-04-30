<?php

/*
    Copyright (c) 2012 - 2022, Open Source Solutions Limited, Dublin, Ireland
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
// 'FSOS software, S5850 software (S5850 48S6Q), Version 7.2.2
//Copyright (C) 2019 by FS.COM. All rights reserved.
//Compiled Dec 10 09:12:14 2019

if( substr( $sysDescr, 0, 5 ) == 'FSOS ' )
{
    $this->setVendor( 'FiberStore' );
    $this->setOs( 'FSOS' );

    preg_match( '/FSOS\s+software,\s+[\w]+\s+software\s+\(([\w\s]+)\),\s+Version\s+([\w.]+).*/',
        $sysDescr, $matches );

    $this->setModel( $matches[1] );
    $this->setOsVersion( $matches[2] );
    $this->setOsDate( null );
}

