<?php

/*
    Copyright (c) 2013, Open Source Solutions Limited, Dublin, Ireland
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

namespace OSS_SNMP\MIBS\SNMP;

/**
 * A class for performing SNMP V2 queries
 *
 * @copyright Copyright (c) 2013, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Engine extends \OSS_SNMP\MIB
{
    const OID_BOOTS         = '.1.3.6.1.6.3.10.2.1.2.0';
    const OID_TIME          = '.1.3.6.1.6.3.10.2.1.3.0';

    /**
     * Get the SNMP engine boots
     *
     *
     * > "The number of times that the SNMP engine has (re-)initialized itself since snmpEngineID was last configured."
     *
     * @see http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?local=en&translate=Translate&objectInput=1.3.6.1.6.3.10.2.1.2#oidContent
     *
     * @return int The SNMP engine boots
     */
    public function boots()
    {
        return $this->getSNMP()->get( self::OID_BOOTS );
    }

    /**
     * Get the SNMP engine time
     *
     *
     * > "The number of seconds since the value of the snmpEngineBoots object last changed.
     * > When incrementing this objects value would cause it to exceed its maximum, snmpEngineBoots
     * > is incremented as if a re-initialization had occurred, and this objects value consequently
     * > reverts to zero."
     *
     * @see http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?local=en&translate=Translate&objectInput=1.3.6.1.6.3.10.2.1.2#oidContent
     *
     * @return int The SNMP engine time
     */
    public function time()
    {
        return $this->getSNMP()->get( self::OID_TIME );
    }

}
