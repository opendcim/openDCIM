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

namespace OSS_SNMP\MIBS;

/**
 * A class for performing SNMP V2 queries on generic devices
 *
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class LAG extends \OSS_SNMP\MIB
{
    /**
     * The identifier value (port ID) of the Aggregator that this Aggregation Port is currently attached
     * to. Zero indicates that the Aggregation Port is not currently attached to an Aggregator.
     */
    const OID_LAG_PORT_ATTACHED_ID = '.1.2.840.10006.300.43.1.2.1.1.13';

    /**
     *  Boolean value indicating whether the Aggregator represents an Aggregate (`TRUE') or an Individual link (`FALSE')
     */
    const OID_LAG_AGGREGATE_OR_INDIVIDUAL = '.1.2.840.10006.300.43.1.2.1.1.24';

    /**
     * Returns an associate array of port IDs with a boolean value to indicate if it's an aggregate port (true)
     * or an individual port (false).
     *
     * @return array Associate array of port IDs with a boolean value to indicate if it's an aggregate port (true) or not
     */
    public function isAggregatePorts()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_LAG_AGGREGATE_OR_INDIVIDUAL ) );
    }

    /**
     * Returns an associate array of port IDs with the ID of the aggregate port that
     * they are a member of (else 0 if not a LAG port)
     *
     *
     * @return array Associate array of port IDs with the ID of the aggregate port that they are a member of
     */
    public function portAttachedIds()
    {
        return $this->getSNMP()->walk1d( self::OID_LAG_PORT_ATTACHED_ID );
    }

    /**
     * Gets an associate array of LAG ports with the [id] => name of it's constituent ports
     *
     * E.g.:
     *    [5048] => Array
     *        (
     *            [10111] => GigabitEthernet1/0/11
     *            [10112] => GigabitEthernet1/0/12
     *        )
     *
     * @return array Associate array of LAG ports with the [id] => name of it's constituent ports
     */
    public function getLAGPorts()
    {
        $ports = array();

        foreach( $this->portAttachedIds() as $portId => $aggPortId )
            if( $aggPortId != 0 )
                $ports[ $aggPortId ][$portId] = $this->getSNMP()->useIface()->names()[$portId];

        return $ports;
    }


    /**
     * Utility function to identify configured but unattached LAG ports
     *
     * @return array Array of indexed port ids (array index, not value) of configured but unattached LAG ports
     */
    public function findFailedLAGPorts()
    {
        // find all configured LAG ports
        $lagPorts = $this->isAggregatePorts();

        // find all attached ports
        $attachedPorts = $this->portAttachedIds();

        foreach( $lagPorts as $portId => $isLAG )
        {
            if( !$isLAG )
            {
                unset( $lagPorts[ $portId ] );
                continue;
            }

            if( $attachedPorts[ $portId ] != 0 )
                unset( $lagPorts[ $portId ] );
        }

        // we should be left with configured but unattached LAG ports
        return( $lagPorts );
    }

}


