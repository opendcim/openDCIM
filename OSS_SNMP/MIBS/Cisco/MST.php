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

namespace OSS_SNMP\MIBS\Cisco;

/**
 * A class for performing SNMP V2 queries on Cisco devices
 *
 * @copyright Copyright (c) 2012 - 2013, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class MST extends \OSS_SNMP\MIBS\Cisco
{

    const OID_STP_X_MST_MAX_INSTANCE_NUMBER = '.1.3.6.1.4.1.9.9.82.1.11.1.0'; 
    const OID_STP_X_MST_REGION_NAME         = '.1.3.6.1.4.1.9.9.82.1.11.2.0'; 
    const OID_STP_X_MST_REGION_REVISION     = '.1.3.6.1.4.1.9.9.82.1.11.3.0'; 
    
    
    /**
     * Returns the maximum MST instance number
     *
     * > "The maximum MST (Multiple Spanning Tree) instance id, 
     * > that can be supported by the device for Cisco proprietary
     * > implementation of the MST Protocol.
     * > 
     * > This object is deprecated and replaced by stpxSMSTMaxInstanceID."
     *
     * @deprecated Use \OSS_SNMP\MIBS\Cisco\SMST::maxInstanceID()
     * @return string The maximum MST instance number
     */
    public function maxInstanceNumber()
    {
        return $this->getSNMP()->get( self::OID_STP_X_MST_MAX_INSTANCE_NUMBER );
    }
    
    /**
     * Returns the operational MST region name.
     *
     * @return string The operational MST region name
     */
    public function regionName()
    {
        return $this->getSNMP()->get( self::OID_STP_X_MST_REGION_NAME );
    }
    
    /**
     * Returns the operational MST region revision.
     *
     * @deprecated Use \OSS_SNMP\MIBS\Cisco\SMST::regionRevision()
     * @return string The operational MST region revision
     */
    public function regionRevision()
    {
        return $this->getSNMP()->get( self::OID_STP_X_MST_REGION_REVISION );
    }
    
    
    /**
     * Get the device's MST port roles (by given instance id)
     *
     * Only ports participating in MST for the given instance id are returned.
     *
     * > "An entry containing the port role information for the RSTP
     * > protocol on a port for a particular Spanning Tree instance."
     *
     * The original OIDs for this are deprecated:
     *
     * > stpxMSTPortRoleTable - 1.3.6.1.4.1.9.9.82.1.11.12
     * > 
     * > "A table containing a list of the bridge ports for a 
     * > particular MST instance. This table is only instantiated 
     * > when the stpxSpanningTreeType is mst(4). 
     * > 
     * > This table is deprecated and replaced with 
     * > stpxRSTPPortRoleTable."
     *
     *
     * @see RSTP::portRoles()
     * @param int $iid The MST instance ID to query port roles for
     * @param boolean $translate If true, return the string representation via RSTP::$STP_X_RSTP_PORT_ROLES
     * @return array The device's MST port roles (by given instance id)
     */
    public function portRoles( $iid, $translate = false )
    {
        return $this->getSNMP()->useCisco_RSTP()->portRoles( $iid, $translate );
    }



}
