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

namespace OSS_SNMP\MIBS\Extreme;

/**
 * A class for performing SNMP V2 VLAN queries on Extreme devices
 *
 * @see http://www.extremenetworks.com/products/mibs.aspx
 * @copyright Copyright (c) 2012 - 2013, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Vlan extends \OSS_SNMP\MIBS\Extreme
{
    const OID_VLAN_IF_INDEX                   = '.1.3.6.1.4.1.1916.1.2.1.2.1.1';
    const OID_VLAN_IF_DESCRIPTION             = '.1.3.6.1.4.1.1916.1.2.1.2.1.2';
    const OID_VLAN_IF_TYPE                    = '.1.3.6.1.4.1.1916.1.2.1.2.1.3';
    const OID_VLAN_IF_GLOBAL_IDENTIFIER       = '.1.3.6.1.4.1.1916.1.2.1.2.1.4';
    const OID_VLAN_IF_STATUS                  = '.1.3.6.1.4.1.1916.1.2.1.2.1.6';
    const OID_VLAN_IF_LOOPBACK_MODE_FLAG      = '.1.3.6.1.4.1.1916.1.2.1.2.1.9';
    const OID_VLAN_IF_VLAN_ID                 = '.1.3.6.1.4.1.1916.1.2.1.2.1.10';

    const OID_VLAN_OPAQUE_TAGGED_PORTS        = ".1.3.6.1.4.1.1916.1.2.6.1.1.1";
    const OID_VLAN_OPAQUE_UNTAGGED_PORTS      = ".1.3.6.1.4.1.1916.1.2.6.1.1.2";


    /**
     * Get an array of VLAN interface indexes (ifIndex)
     *
     * NB: VLAN indexes return only, not physical interfaces.
     *
     * Queries: EXTREME-VLAN-MIB::extremeVlanIfIndex
     *
     * Example of returned array:
     *
     *     [
     *         [1000004] => 1000004
     *         [1000005] => 1000005
     *         ...
     *     ]
     *
     *
     * @return array An array of VLAN interface indexes (ifIndex)
     */
    public function ifIndexes()
    {
        return $this->getSNMP()->walk1d( self::OID_VLAN_IF_INDEX );
    }


    /**
     * Get the device's VLAN descriptions (indexed by vlanIfIndex)
     *
     * @return array The device's VLAN descriptions (indexed by vlanIfIndex)
     */
    public function ifDescriptions()
    {
        return $this->getSNMP()->walk1d( self::OID_VLAN_IF_DESCRIPTION );
    }



    /**
     * Constant for possible value of VLAN type
     * @see ifTypes()
     */
    const IF_VLAN_TYPE_LAYER2 = 1;

    /**
     * Text representation of VLAN types
     *
     * @see ifTypes()
     * @var array Text representations of VLAN types
     */
    public static $IF_VLAN_TYPES = array(
        self::IF_VLAN_TYPE_LAYER2      => 'vlanLayer2(1)'
    );

    /**
     * Get the device's VLAN types (indexed by vlanIfIndex)
     *
     * > -- Extreme Networks Vlan Type Textual Convention
     * > --
     * > --  vlanLayer2(1) = The globally identified VLAN interface is protocol
     * > --  independent and based on port grouping.  The configuration of
     * > --  port grouping is controlled through the ifStackTable.
     *
     * @see IF_VLAN_TYPES
     * @param boolean $translate If true, return the string representation
     * @return array The device's VLAN types (indexed by vlanIfIndex)
     */
    public function ifTypes( $translate = false )
    {
        $types = $this->getSNMP()->walk1d( self::OID_VLAN_IF_TYPE );

        if( !$translate )
            return $types;

        return $this->getSNMP()->translate( $types, self::$IF_VLAN_TYPES );
    }



    /**
     * Get the device's VLAN global identifiers (indexed by vlanIfIndex)
     *
     * > An administratively assigned global VLAN identifier.  For
     * > VLAN interfaces, on different network devices, which are
     * > part of the same globally identified VLAN, the value of this
     * > object will be the same.
     * >
     * > The binding between a global identifier and a VLAN
     * > interface can be created or removed.  To create a binding
     * > an NMS must write a non-zero value to this object.  To
     * > delete a binding, the NMS must write a zero to this
     * > object. The value 1 is reserved for the default VLAN and
     * > this cannot be deleted or re-assigned.
     *
     * @return array The device's VLAN global identifiers (indexed by vlanIfIndex)
     */
    public function ifGlobalIdentifiers()
    {
        return $this->getSNMP()->walk1d( self::OID_VLAN_IF_GLOBAL_IDENTIFIER );
    }




    /**
     * Constant for possible value of VLAN status
     * @see ifStates()
     */
    const IF_VLAN_STATUS_ACTIVE = 1;

    /**
     * Constant for possible value of VLAN status
     * @see ifStates()
     */
    const IF_VLAN_STATUS_NOTINSERVICE = 2;

    /**
     * Constant for possible value of VLAN status
     * @see ifStates()
     */
    const IF_VLAN_STATUS_NOTREADY = 3;

    /**
     * Text representation of VLAN states
     *
     * @see ifstates()
     * @var array Text representations of VLAN states
     */
    public static $IF_VLAN_STATES = array(
        self::IF_VLAN_STATUS_ACTIVE       => 'active',
        self::IF_VLAN_STATUS_NOTINSERVICE => 'notInService',
        self::IF_VLAN_STATUS_NOTREADY     => 'notReady'
    );

    /**
     * Get the device's VLAN states
     *
     * @see IF_VLAN_STATES
     * @param boolean $translate If true, return the string representation
     * @return array The device's VLAN states (indexed by vlanIfIndex)
     */
    public function ifStates( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_VLAN_IF_STATUS );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$IF_VLAN_STATES );
    }


    /**
     * Constant for possible value of loopback mode flag
     * @see ifLoopbackModeFlags()
     */
    const IF_VLAN_LOOPBACK_MODE_FLAG_TRUE = 1;

    /**
     * Constant for possible value of loopback mode flag
     * @see ifLoopbackModeFlags()
     */
    const IF_VLAN_LOOPBACK_MODE_FLAG_FALSE = 2;

    /**
     * Text representation of loopback mode flags
     *
     * @see ifLoopbackModeFlags()
     * @var array Text representation of loopback mode flags
     */
    public static $IF_VLAN_LOOPBACK_MODE_FLAGS = [
        self::IF_VLAN_LOOPBACK_MODE_FLAG_TRUE  => true,
        self::IF_VLAN_LOOPBACK_MODE_FLAG_FALSE => false
    ];

    /**
     * Get the device's VLAN loopback mode flags
     *
     * @see IF_VLAN_LOOPBACK_MODE_FLAGS
     * @param boolean $translate If true, return boolean values for flag
     * @return array The device's VLAN loopback mode flags (indexed by vlanIfIndex)
     */
    public function ifLoopbackModeFlags( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_VLAN_IF_LOOPBACK_MODE_FLAG );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$IF_VLAN_LOOPBACK_MODE_FLAGS );
    }


    /**
     * Get the device's VLAN IDs / tags  (indexed by vlanIfIndex)
     *
     * @return array The device's VLAN IDs / tags (indexed by vlanIfIndex)
     */
    public function ifVlanIds()
    {
        return $this->getSNMP()->walk1d( self::OID_VLAN_IF_VLAN_ID );
    }

    /**
     * Get the device's VLAN IDs / tags mapped to vlanIfIndex (indexed by tag)
     *
     * @return array The device's VLAN IDs / tags mapped to vlanIfIndex (indexed by tag)
     */
    public function ifVlanIdsToIfIndexes()
    {
        return array_flip( $this->getSNMP()->walk1d( self::OID_VLAN_IF_VLAN_ID ) );
    }


    /**
     * Get the device's VLAN IDs mapped to names
     *
     * Sample return:
     *
     *     [
     *         [1] => Default
     *         [2] => Mgmt
     *         ...
     *         [200] => INTERNET
     *         ...
     *     ]
     *
     * @return array The device's VLAN IDs mapped to names
     */
    public function idsToNames()
    {
        $names = $this->ifDescriptions();
        $ids   = $this->ifVlanIds();

        $ids   = array_intersect_key( $ids, $names );
        $names = array_intersect_key( $names, $ids );

        return( array_combine( $ids, $names ) );
    }


    /**
     * Get tagged ports by VLAN  (indexed by vlanIfIndex)
     *
     * The result is an array of HEX strings indicating VLAN tagging of an interface
     * indexed by the vlanIfIndex such as:
     *
     *     [
     *         [1000005] => 0000000000000000000000000000000000000000000000000000000000000000
     *         [1000007] => 0000000000008000000000000000000000000000000000000000000000000000
     *         [1000008] => 5000400000008000000000000000000000000000000000000000000000000000
     *         ...
     *     ]
     *
     * So, for VLAN ifIndex 1000008 above, if you take the first octet of '50', you can
     * see that of the first eight ports, only the 2nd and 4th (50 = 01010000) are tagged.
     *
     * @see \OSS_SNMP\SNMP::ppHexStringFlags() to translate a hex string to a true / false array
     * @see getTaggedPortsForVlan() for a useful use of this function.
     *
     * @return array The device's VLAN IDs / tags (indexed by vlanIfIndex)
     */
    public function opaqueTaggedPorts()
    {
        return $this->getSNMP()->subOidWalk( self::OID_VLAN_OPAQUE_TAGGED_PORTS, 14 );
    }

    /**
     * Get untagged ports by VLAN  (indexed by vlanIfIndex)
     *
     * The result is an array of HEX strings indicating VLAN (un)tagging of an interface
     * indexed by the vlanIfIndex such as:
     *
     *     [
     *         [1000005] => 0000000000000000000000000000000000000000000000000000000000000000
     *         [1000007] => 0000000000008000000000000000000000000000000000000000000000000000
     *         [1000008] => 5000400000008000000000000000000000000000000000000000000000000000
     *         ...
     *     ]
     *
     * So, for VLAN ifIndex 1000008 above, if you take the first octet of '50', you can
     * see that of the first eight ports, only the 2nd and 4th (50 = 01010000) are untagged.
     *
     * @see \OSS_SNMP\SNMP::ppHexStringFlags() to translate a hex string to a true / false array
     * @see getUntaggedPortsForVlan() for a useful use of this function.
     *
     * @return array The device's VLAN IDs / tags (indexed by vlanIfIndex)
     */
    public function opaqueUntaggedPorts()
    {
        return $this->getSNMP()->subOidWalk( self::OID_VLAN_OPAQUE_UNTAGGED_PORTS, 14 );
    }

    /**
     * For a given VLAN vlanIfIndex, this function returns an array
     * of ports indicating whether the port is a tagged member of the VLAN.
     *
     * @see getPortsForVlan()
     * @param int $vlanIfIndex The vlanIfIndex of the VLAN to get the results for
     * @return array Array indexed by ifIndex indicating whether the port is a tagged member of the given vlan
     */
    public function getTaggedPortsForVlan( $vlanIfIndex )
    {
        return $this->getPortsForVlan( $vlanIfIndex, 'opaqueTaggedPorts' );
    }

    /**
     * For a given VLAN vlanIfIndex, this function returns an array
     * of ports indicating whether the port is a untagged member of the VLAN.
     *
     * @see getPortsForVlan()
     * @param int $vlanIfIndex The vlanIfIndex of the VLAN to get the results for
     * @return array Array indexed by ifIndex indicating whether the port is an untagged member of the given vlan
     */
    public function getUntaggedPortsForVlan( $vlanIfIndex )
    {
        return $this->getPortsForVlan( $vlanIfIndex, 'opaqueUntaggedPorts' );
    }

    /**
     * For a given VLAN vlanIfIndex, this function returns an array
     * of ports indicating whether the port is a member (tagged or untagged)
     * of the VLAN.
     *
     * A sample result showing that ports with ifIndex 1002 to 1004 are a
     * member of a given VLAN while the others aren't is:
     *
     *     [
     *         [1001] => bool(false)
     *         [1002] => bool(true)
     *         [1003] => bool(true)
     *         [1004] => bool(true)
     *         [1005] => bool(true)
     *         [1006] => bool(false)
     *     ]
     *
     * @param int $vlanIfIndex The vlanIfIndex of the VLAN to get the results for
     * @param string|null If null, both tagged or untagged ports. Otherwise one of `opaqueTaggedPorts` or `opaqueUntaggedPorts`
     * @return array Array indexed by ifIndex indicating whether the port is a member of the given vlan
     */
    public function getPortsForVlan( $vlanIfIndex, $fn = null )
    {
        $rtn = [];

        if( $fn === null )
            $fn = [ 'opaqueTaggedPorts', 'opaqueUntaggedPorts' ];
        else
            $fn = [ $fn ];

        // to be useful, we need to return this array indexed by ifIndex
        $ifIndexes = $this->getSNMP()->useBridge()->basePortIfIndexes();

        foreach( $fn as $f )
        {
            $ports = $this->$f();

            if( !isset( $ports[ $vlanIfIndex ] ) )
                continue;

            $ports = $this->getSNMP()->ppHexStringFlags( $ports[ $vlanIfIndex ] );

            foreach( $ports as $int => $isMember )
            {
                if( isset( $ifIndexes[ $int ] ) )
                    $rtn[ $ifIndexes[ $int ] ] = isset( $rtn[ $ifIndexes[ $int ] ] ) && $rtn[ $ifIndexes[ $int ] ] ? true : $isMember;
            }
        }

        return $rtn;
    }

}
