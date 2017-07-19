<?php

/*
    Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
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

namespace OSS_SNMP\MIBS\Asterisk;

/**
 * A class for performing SNMP V2 queries on Asterisk
 *
 * @see https://wiki.asterisk.org/wiki/display/AST/Asterisk+MIB+Definitions
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Indications extends \OSS_SNMP\MIB
{

    const OID_ASTERISK_INDICATIONS_COUNT   = '.1.3.6.1.4.1.22736.1.4.1.0';

    const OID_ASTERISK_DEFAULT_INDICATION  = '.1.3.6.1.4.1.22736.1.4.2.0';

    const OID_ASTERISK_INDICATIONS_COUNTRY     = '.1.3.6.1.4.1.22736.1.4.3.1.2';
    const OID_ASTERISK_INDICATIONS_DESCRIPTION = '.1.3.6.1.4.1.22736.1.4.3.1.4';

    /**
     * Returns the number of indications defined in Asterisk
     *
     * > Number of indications currently defined in Asterisk.
     *
     * @return int The number of indications defined in Asterisk
     */
    public function number()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_INDICATIONS_COUNT );
    }


    /**
     * Returns the default indication zone to use.
     *
     * > Default indication zone to use.
     *
     * @return string The default indication zone to use
     */
    public function defaultZone()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_DEFAULT_INDICATION );
    }

    /**
     * Returns an array of ISO country codes for the defined indications zones (indexed by SNMP table entry)
     *
     * > Country for which the indication zone is valid,
     * > typically this is the ISO 2-letter code of the country.
     *
     * @return array An array of ISO country codes for the defined indications zones (indexed by SNMP table entry)
     */
    public function countryCodes()
    {
        return $this->getSNMP()->walk1d( self::OID_ASTERISK_INDICATIONS_COUNTRY );
    }

    /**
     * Returns an array of indications zone descriptions (indexed by SNMP table entry)
     *
     * > Description of the indication zone, usually the full
     * > name of the country it is valid for.
     *
     * @return array An array of indications zone descriptions
     */
    public function descriptions()
    {
        return $this->getSNMP()->walk1d( self::OID_ASTERISK_INDICATIONS_DESCRIPTION );
    }




}
