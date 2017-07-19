<?php

/*
    Copyright (c) 2013 - 2014, Open Source Solutions Limited, Dublin, Ireland
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

namespace OSS_SNMP\MIBS\Extreme\SwMonitor;

/**
 * A class for performing SNMP V2 queries on Extreme devices
 *
 * These OIDs are from the private.extremenetworks.extremeAgent.extremeSwMonitor.extremeSwMonitorMemory tree
 *
 * @copyright Copyright (c) 2013 - 2014, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Memory extends \OSS_SNMP\MIBS\Extreme\SwMonitor
{

    const OID_SYSTEM_SLOT_ID      = '.1.3.6.1.4.1.1916.1.32.2.2.1.1';
    const OID_SYSTEM_TOTAL        = '.1.3.6.1.4.1.1916.1.32.2.2.1.2';
    const OID_SYSTEM_FREE         = '.1.3.6.1.4.1.1916.1.32.2.2.1.3';
    const OID_SYSTEM_USAGE        = '.1.3.6.1.4.1.1916.1.32.2.2.1.4';
    const OID_USER_USAGE          = '.1.3.6.1.4.1.1916.1.32.2.2.1.5';


    /**
     * Slot Id of the memory monitored.
     *
     * @return array Slot Id of the memory monitored.
     */
    public function slotIds()
    {
        return $this->getSNMP()->walk1d( self::OID_SYSTEM_SLOT_ID );
    }

    /**
     * Total amount of DRAM in Kbytes in the system.
     *
     * @return array Total amount of DRAM in Kbytes in the system. Indexed by slot ID.
     */
    public function systemTotal()
    {
        return $this->getSNMP()->walk1d( self::OID_SYSTEM_TOTAL );
    }

    /**
     * Total amount of free memory in Kbytes in the system.
     *
     * @return array Total amount of free memory in Kbytes in the system. Indexed by slot ID.
     */
    public function systemFree()
    {
        return $this->getSNMP()->walk1d( self::OID_SYSTEM_FREE );
    }

    /**
     * Total amount of memory used by system services in Kbytes in the system.
     *
     * @return array Total amount of memory used by system services in Kbytes in the system. Indexed by slot ID.
     */
    public function systemUsage()
    {
        return $this->getSNMP()->walk1d( self::OID_SYSTEM_USAGE );
    }

    /**
     * Total amount of memory used by applications in Kbytes in the system.
     *
     * @return array Total amount of memory used by applications in Kbytes in the system.
     */
    public function userUsage()
    {
        return $this->getSNMP()->walk1d( self::OID_USER_USAGE );
    }


    /**
     * Percentage of memory used per slot
     *
     * @return array Integer percentage of memory used
     */
    public function percentUsage()
    {
        $total = $this->systemTotal();
        $free  = $this->systemFree();

        $usage = [];

        foreach( $total as $slotId => $amount ) {
            $usage[ $slotId ] = intval( ceil( ( ( $amount - $free[ $slotId ] ) * 100 ) / $amount ) );
        }

        return $usage;
    }

}
