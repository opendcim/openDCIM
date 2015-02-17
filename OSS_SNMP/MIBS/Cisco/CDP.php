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

namespace OSS_SNMP\MIBS\Cisco;

/**
 * A class for performing SNMP V2 queries on Cisco devices
 *
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class CDP extends \OSS_SNMP\MIBS\Cisco
{

    const OID_CDP_INTERFACE_ENABLED               = '.1.3.6.1.4.1.9.9.23.1.1.1.1.2';
    const OID_CDP_INTERFACE_NAME                  = '.1.3.6.1.4.1.9.9.23.1.1.1.1.6';

    const OID_CDP_CACHE_NEIGHBOUR_ADDRESS_TYPE    = '.1.3.6.1.4.1.9.9.23.1.2.1.1.3';
    const OID_CDP_CACHE_NEIGHBOUR_ADDRESS         = '.1.3.6.1.4.1.9.9.23.1.2.1.1.4';
    const OID_CDP_CACHE_NEIGHBOUR_VERSION         = '.1.3.6.1.4.1.9.9.23.1.2.1.1.5';
    const OID_CDP_CACHE_NEIGHBOUR_ID              = '.1.3.6.1.4.1.9.9.23.1.2.1.1.6';
    const OID_CDP_CACHE_NEIGHBOUR_PORT            = '.1.3.6.1.4.1.9.9.23.1.2.1.1.7';
    const OID_CDP_CACHE_NEIGHBOUR_PLATFORM        = '.1.3.6.1.4.1.9.9.23.1.2.1.1.8';
    const OID_CDP_CACHE_NEIGHBOUR_CAPABILITY      = '.1.3.6.1.4.1.9.9.23.1.2.1.1.9';
    const OID_CDP_CACHE_NEIGHBOUR_VTP_MGMT_DOMAIN = '.1.3.6.1.4.1.9.9.23.1.2.1.1.10';
    const OID_CDP_CACHE_NEIGHBOUR_NATIVE_VLAN     = '.1.3.6.1.4.1.9.9.23.1.2.1.1.11';
    const OID_CDP_CACHE_NEIGHBOUR_DUPLEX          = '.1.3.6.1.4.1.9.9.23.1.2.1.1.12';
    const OID_CDP_CACHE_NEIGHBOUR_LAST_CHANGE     = '.1.3.6.1.4.1.9.9.23.1.2.1.1.24';

    const OID_CDP_GLOBAL_RUN                      = '.1.3.6.1.4.1.9.9.23.1.3.1.0';
    const OID_CDP_GLOBAL_MESSAGE_INTERVAL         = '.1.3.6.1.4.1.9.9.23.1.3.2.0';
    const OID_CDP_GLOBAL_HOLDTIME                 = '.1.3.6.1.4.1.9.9.23.1.3.3.0';
    const OID_CDP_GLOBAL_DEVICE_ID                = '.1.3.6.1.4.1.9.9.23.1.3.4.0';
    const OID_CDP_GLOBAL_LAST_CHANGE              = '.1.3.6.1.4.1.9.9.23.1.3.5.0';


    /**
     * Get the device's global CDP (Cisco Discovery Protocol) run status
     *
     *
     * > An indication of whether the Cisco Discovery Protocol is currently
     * > running.  Entries in cdpCacheTable are deleted when CDP is disabled.
     *
     * @return boolean True if enabled globally, else false
     */
    public function globalRun()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->get( self::OID_CDP_GLOBAL_RUN ) );
    }

    /**
     * Get the interval at which CDP messages are to be generated
     *
     * > The interval at which CDP messages are to be generated.
     * > The default value is 60 seconds.
     *
     * @return int The interval at which CDP messages are to be generated
     */
    public function globalMessageInterval()
    {
        return $this->getSNMP()->get( self::OID_CDP_GLOBAL_MESSAGE_INTERVAL );
    }

    /**
     * Get the time for the receiving device holds CDP message
     *
     * > The time for the receiving device holds CDP message.
     * > The default value is 180 seconds."
     *
     * @return int The time for the receiving device holds CDP message
     */
    public function globalHoldTime()
    {
        return $this->getSNMP()->get( self::OID_CDP_GLOBAL_HOLDTIME );
    }

    /**
     * The time when the cache table was last changed
     *
     * > Indicates the time when the cache table was last changed. It
     * > is the most recent time at which any row was last created,
     * > modified or deleted.
     *
     * @return int The time (timeticks) when the cache table was last changed
     */
    public function globalLastChange()
    {
        return $this->getSNMP()->get( self::OID_CDP_GLOBAL_LAST_CHANGE );
    }

    /**
     * Get the device's CDP (Cisco Discovery Protocol) ID
     *
     * @return string The device's CDP (Cisco Discovery Protocol) ID
     */
    public function id()
    {
        return $this->getSNMP()->get( self::OID_CDP_GLOBAL_DEVICE_ID );
    }


    /**
     * Get the device's interfaces CDP enabled status
     *
     * Applies the TruthValue post processor to turn
     * SNMP values into true / false.
     *
     * @see OSS_SNMP\SNMP::ppTruthValue()
     *
     * @return array The device's interfaces CDP enabled status' (as boolean true / false)
     */
    public function interfaceEnabled()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_CDP_INTERFACE_ENABLED ) );
    }

    /**
     * Get the device's interface names as seen in CDP
     *
     * > The name of the local interface as advertised by CDP in the Port-ID TLV
     *
     * @return array The device's interface names as seen in CDP
     */
    public function interfaceNames()
    {
        return $this->getSNMP()->walk1d( self::OID_CDP_INTERFACE_NAME );
    }



    /**
     * Constant for possible value of CDP neighbour address type
     * @see OSS_SNMP\MIBS\Cisco\CDP::neighbourAddressTypes()
     */
    const CDP_CACHE_NEIGHBOUR_ADDRESS_TYPE_IP = 1;

    /**
     * Text representation of CDP neighbour address type
     *
     * @see OSS_SNMP\SNMP\MIBS\Cisco\CDP::neighbourAddressTypes()
     * @var array Text representation of CDP neighbour address type
     */
    public static $CDP_CACHE_NEIGHBOUR_ADDRESS_TYPES = array(
        self::CDP_CACHE_NEIGHBOUR_ADDRESS_TYPE_IP => 'ip'
    );

    /**
     * Get the CDP neighbours' address type indexed by the current device's port ID
     *
     * > An indication of the type of address contained in the corresponding instance of cdpCacheAddress
     *
     * @param boolean $translate If true, return the string representation via self::$VTP_VLAN_TYPES
     * @return array The CDP neighbours' address type indexed by the current device's port ID
     */
    public function neighbourAddressTypes( $translate = false )
    {
        $types = $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_ADDRESS_TYPE, 15 );

        if( !$translate )
            return $types;

        return $this->getSNMP()->translate( $types, self::$CDP_CACHE_NEIGHBOUR_ADDRESS_TYPES );
    }


    /**
     * Get the device's CDP neighbour addresses indexed by the current device's port ID
     *
     * > The (first) network-layer address of the device
     * > as reported in the Address TLV of the most recently received
     * > CDP message.  For example, if the corresponding instance of
     * > cacheAddressType had the value 'ip(1)', then this object
     * > would be an IPv4-address.  If the neighbor device is
     * > SNMP-manageable, it is supposed to generate its CDP messages
     * > such that this address is one at which it will receive SNMP
     * > messages. Use cdpCtAddressTable to extract the remaining
     * > addresses from the Address TLV received most recently."
     *
     * @return array The device's CDP neighbour addresses indexed by the current device's port ID
     */
    public function neighbourAddresses()
    {
        $addresses = $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_ADDRESS, 15 );

        foreach( $addresses as $portId => $address )
        {
            if( strlen( $address ) == 8 && $this->neighbourAddressTypes()[ $portId ] == self::CDP_CACHE_NEIGHBOUR_ADDRESS_TYPE_IP )
                $addresses[ $portId ] = long2ip( hexdec( $address ) );
        }

        return $addresses;
    }

    /**
     * Get the device's CDP neighbour version indexed by the current device's port ID
     *
     * > The Version string as reported in the most recent CDP
     * > message.  The zero-length string indicates no Version
     * > field (TLV) was reported in the most recent CDP message."
     *
     * @return array The device's CDP neighbour version indexed by the current device's port ID
     */
    public function neighbourVersions()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_VERSION, 15 );
    }


    /**
     * Get the device's CDP neighbours (by their CDP ID) indexed by the current device's port ID
     *
     * > The Device-ID string as reported in the most recent CDP
     * > message.  The zero-length string indicates no Device-ID
     * > field (TLV) was reported in the most recent CDP message."
     *
     * @return array The device's CDP neighbours (by their CDP ID) indexed by the current device's port ID
     */
    public function neighbourId()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_ID, 15 );
    }

    /**
     * Get the device's CDP neighbours connected port description indexed by the current device's port ID
     *
     * E.g. a sample call may return:
     *
     *     Array
     *     (
     *         [10101] => GigabitEthernet0/1
     *         [10102] => FastEthernet0/2
     *         [10103] => GigabitEthernet1/0/24
     *         [10105] => GigabitEthernet1/0/2
     *     )
     *
     * meaning, for example, that our local port with ID `10101` is connected to port `GigabitEthernet0/1` on the neighbour
     * connected to that local port. You can discover the neighbour ID via `neighbourId()`.
     *
     * > The Port-ID string as reported in the most recent CDP
     * > message.  This will typically be the value of the ifName
     * > object (e.g., 'Ethernet0').  The zero-length string
     * > indicates no Port-ID field (TLV) was reported in the
     * > most recent CDP message.
     *
     * @see \OSS_SNMP\SNMP\MIBS\Cisco\CDP::neighbourId()
     * @return array The device's CDP neighbours connected port *description* indexed by the current device's port ID
     */
    public function neighbourPort()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_PORT, 15 );
    }

    /**
     * Get the device's CDP neighbour platforms indexed by the current device's port ID
     *
     * > The Device's Hardware Platform as reported in the most recent CDP
     * > message. The zero-length string indicates that no Platform field
     * > (TLV) was reported in the most recent CDP message.
     *
     * @return array The device's CDP neighbour platforms indexed by the current device's port ID
     */
    public function neighbourPlatforms()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_PLATFORM, 15 );
    }



    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_ROUTER = 0b1;

    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_TRANSPARENT_BRIDGE = 0b10;

    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_SOURCE_ROUTE_BRIDGE = 0b100;

    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_SWITCH = 0b1000;

    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_HOST = 0b10000;

    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_IGMP_CAPABLE = 0b100000;

    /**
     * Constant for possible value of CDP neighbour capability
     * @see neighbourCapability()
     */
    const CDP_CACHE_NEIGHBOUR_CAPABILITY_REPEATER = 0b1000000;

    /**
     * Text representation of CDP capabilities
     *
     * @see neighbourCapability()
     * @var array Text representation of CDP neighbour capabilities
     */
    public static $CDP_CACHE_NEIGHBOUR_CAPABILITIES = array(
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_ROUTER              => 'Router',
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_TRANSPARENT_BRIDGE  => 'Transparent Bridge',
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_SOURCE_ROUTE_BRIDGE => 'Source Route Bridge',
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_SWITCH              => 'Switch',
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_HOST                => 'Host',
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_IGMP_CAPABLE        => 'IGMP Capable',
        self::CDP_CACHE_NEIGHBOUR_CAPABILITY_REPEATER            => 'Repeater'
    );

    /**
     * Get the device's CDP neighbour capabilities (as a decimal integer) indexed by the current device's port ID
     *
     * > The Device's Functional Capabilities as reported in the most
     * > recent CDP message.  For latest set of specific values, see
     * > the latest version of the CDP specification. The zero-length
     * > string indicates no Capabilities field (TLV) was reported in
     * > the most recent CDP message."
     *
     * @see REFERENCE "Cisco Discovery Protocol Specification, 10/19/94."
     * @see http://www.cisco.com/univercd/cc/td/doc/product/lan/trsrb/frames.htm#xtocid12
     * @see http://wiki.wireshark.org/CDP
     *
     * @return array The device's CDP neighbour capabilities (as a decimal integer) indexed by the current device's port ID
     */
    public function neighbourCapability()
    {
        $rtn = $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_CAPABILITY, 15 );

        foreach( $rtn as $k => $v )
            $rtn[$k] = (int)hexdec( $v );

        return $rtn;
    }

    /**
     * Query if a given neighbour (by connected port ID) has the given capability
     *
     * Example:
     *
     *     if( $host->useCisco_CDP()->neighbourHasCapability( $portId, \OSS_SNMP\SNMP\MIBS\Cisco\CDP::CDP_CACHE_NEIGHBOUR_CAPABILITY_SWITCH )
     *          echo "Host is a switch!!";
     *
     * @param int $portId The CDP neighbour by connected local port ID
     * @param int $capability The capability to query for (defined by self::CDP_CACHE_NEIGHBOUR_CAPABILITY_XXX constants)
     * @return boolean True if the neighbour has the given capability
     */
    public function neighbourHasCapability( $portId, $capability )
    {
        if( $this->neighbourCapability()[ $portId ] & $capability )
            return true;

        return false;
    }

    /**
     * Get an array of individual capabilities of a given neighbour (by connected port ID)
     *
     * Example:
     *
     *     print_r( $host->useCisco_CDP()->neighbourCapabilities( 10111 ) )
     *
     *         [0] => 8         // self::CDP_CACHE_NEIGHBOUR_CAPABILITY_SWITCH
     *         [1] => 32        // self::CDP_CACHE_NEIGHBOUR_CAPABILITY_IGMP_CAPABLE
     *
     *     print_r( $host->useCisco_CDP()->neighbourCapabilities( 10111, true ) )
     *
     *         [0] => "Switch"              // self::CDP_CACHE_NEIGHBOUR_CAPABILITY_SWITCH
     *         [1] => "IGMP Capable"        // self::CDP_CACHE_NEIGHBOUR_CAPABILITY_IGMP_CAPABLE
     *
     *
     *
     * @param int $portId The CDP neighbour by connected local port ID
     * @param int $translate Set to true to return descriptions rather than integers
     * @return array Individual capabilities of a given neighbour
     */
    public function neighbourCapabilities( $portId, $translate = false )
    {
        $capabilities = array();

        foreach( self::$CDP_CACHE_NEIGHBOUR_CAPABILITIES as $mask => $description )
        {
            if( $this->neighbourCapability()[ $portId ] & $mask )
                $capabilities[] = $mask;
        }

        if( $translate )
            return $this->getSNMP()->translate( $capabilities, self::$CDP_CACHE_NEIGHBOUR_CAPABILITIES );

        return $capabilities;
    }




    /**
     * Get the device's CDP neighbours' VTP management domain indexed by the current device's port ID
     *
     * > The VTP Management Domain for the remote device's interface,
     * > as reported in the most recently received CDP message. This
     * > object is not instantiated if no VTP Management Domain field
     * > (TLV) was reported in the most recently received CDP message.
     *
     * @see REFERENCE "managementDomainName in CISCO-VTP-MIB"
     *
     * @return array The device's CDP neighbours' VTP management domain indexed by the current device's port ID
     */
    public function neighbourVTPMgmtDomain()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_VTP_MGMT_DOMAIN, 15 );
    }


    /**
     * Get the remote device's interface's native VLAN (indexed by local portId)
     *
     * > The remote device's interface's native VLAN, as reported in the
     * > most recent CDP message.  The value 0 indicates
     * > no native VLAN field (TLV) was reported in the most
     * > recent CDP message.
     *
     * @return array The remote device's interface's native VLAN (indexed by local portId)
     */
    public function neighbourNativeVLAN()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_NATIVE_VLAN, 15 );
    }




    /**
     * Constant for possible value of CDP neighbour duplex
     * @see neighbourDuplexMode()
     */
    const CDP_CACHE_NEIGHBOUR_DUPLEX_UNKNOWN = 1;

    /**
     * Constant for possible value of CDP neighbour duplex
     * @see neighbourDuplexMode()
     */
    const CDP_CACHE_NEIGHBOUR_DUPLEX_HALF = 2;

    /**
     * Constant for possible value of CDP neighbour duplex
     * @see neighbourDuplexMode()
     */
    const CDP_CACHE_NEIGHBOUR_DUPLEX_FULL = 3;

    /**
     * Text representation of CDP capabilities
     *
     * @see neighbourDuplexMode()
     * @var array Text representation of CDP neighbour duplex modes
     */
    public static $CDP_CACHE_NEIGHBOUR_DUPLEXES = array(
        self::CDP_CACHE_NEIGHBOUR_DUPLEX_UNKNOWN => 'unknown',
        self::CDP_CACHE_NEIGHBOUR_DUPLEX_HALF    => 'half-duplex',
        self::CDP_CACHE_NEIGHBOUR_DUPLEX_FULL    => 'full-duplex'
    );


    /**
     * Get the remote device's interface's duplex mode (indexed by local portId)
     *
     * > he remote device's interface's duplex mode, as reported in the
     * > most recent CDP message.  The value unknown(1) indicates
     * > no duplex mode field (TLV) was reported in the most
     * > recent CDP message."
     *
     * @param boolean $translate If true, return the string representation via self::$VTP_VLAN_TYPES
     * @return array The remote device's interface's duplex mode (indexed by local portId)
     */
    public function neighbourDuplexMode( $translate = false )
    {
        $modes = $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_DUPLEX, 15 );

        if( !$translate )
            return $modes;

        return $this->getSNMP()->translate( $modes, self::$CDP_CACHE_NEIGHBOUR_DUPLEXES );
    }


    /**
     * Get the remote device's last change time (indexed by local portId)
     *
     * > Indicates the time when this cache entry was last changed.
     * > This object is initialised to the current time when the entry
     * > gets created and updated to the current time whenever the value
     * > of any (other) object instance in the corresponding row is
     * > modified."
     *
     * @return array The remote device's last change time(indexed by local portId)
     */
    public function neighbourLastChange()
    {
        return $this->getSNMP()->subOidWalk( self::OID_CDP_CACHE_NEIGHBOUR_LAST_CHANGE, 15 );
    }



    /**
     * CDP utility function to get all CDP neighbours and their connected ports.
     *
     * Returns an array of neighbours indexed by the neighbour CDP ID with a lot of details.
     *
     * For example, here's a sample return for a switch with two neighbours where one neighbour
     * is connected with a LAG / PortChannel and `$inverse` was set to true.
     *
     *
     *     array(2) {
     *         ["cr-sw07.example.ie"] => array(1) {
     *             [0] => array(7) {
     *                 ["localPortId"] => int(10103)
     *                 ["localPortName"] => string(7) "Gi1/0/3"
     *                 ["localPort"] => string(20) "GigabitEthernet1/0/3"
     *                 ["isLAG"] => bool(false)
     *                 ["remotePort"] => string(21) "GigabitEthernet1/0/24"
     *                 ["remotePortId"] => int(10124)
     *                 ["remotePortName"] => string(8) "Gi1/0/24"
     *             }
     *         }
     *         ["cr-sw01.example.ie"] => array(2) {
     *             [0] => array(11) {
     *                 ["localPortId"] => int(10111)
     *                 ["localPortName"] => string(8) "Gi1/0/11"
     *                     ["localPort"] => string(21) "GigabitEthernet1/0/11"
     *                 ["isLAG"] => bool(true)
     *                 ["lagPortId"] => int(5048)
     *                 ["lagPortName"] => string(4) "Po48"
     *                 ["remotePort"] => string(21) "GigabitEthernet1/0/11"
     *                 ["remotePortId"] => int(10111)
     *                 ["remotePortName"] => string(8) "Gi1/0/11"
     *                 ["remoteLagPortId"] => int(5048)
     *                 ["remoteLagPortName"] => string(4) "Po48"
     *             }
     *             [1] => array(11) {
     *                 ["localPortId"] => int(10112)
     *                 ["localPortName"] => string(8) "Gi1/0/12"
     *                 ["localPort"] => string(21) "GigabitEthernet1/0/12"
     *                 ["isLAG"] => bool(true)
     *                 ["lagPortId"] => int(5048)
     *                 ["lagPortName"] => string(4) "Po48"
     *                 ["remotePort"] => string(21) "GigabitEthernet1/0/12"
     *                 ["remotePortId"] => int(10112)
     *                 ["remotePortName"] => string(8) "Gi1/0/12"
     *                 ["remoteLagPortId"] => int(5048)
     *                 ["remoteLagPortName"] => string(4) "Po48"
     *             }
     *         }
     *
     * @see neighbourId()
     * @see \OSS_SNMP\SNMP\MIBS\Interface::descriptions()
     * @see neighbourPort()
     * @param boolean $inverse If true, all remoteXXX params will be discovered (only remotePort is returned otherwise)
     * @param array $skipHostIds If using $inverse, pass an array of CDP IDs of neighbours that should not be 'inverse' discovered.
     * @return array CDP neighbours and their connected ports
     */
    public function neighbours( $inverse = false, $skipHostIds = null )
    {
        $neighbours = array();
        $remotes = array();

        foreach( $this->neighbourId() as $localPortId => $neighbourCdpId )
        {
            if( !isset( $neighbours[ $neighbourCdpId ] ) )
            {
                $neighbours[ $neighbourCdpId ] = array();
                $count = 0;
            }
            else
                $count = count( $neighbours[ $neighbourCdpId ] );

            $neighbours[ $neighbourCdpId ][$count]['localPortId']   = $localPortId;
            $neighbours[ $neighbourCdpId ][$count]['localPortName'] = $this->getSNMP()->useIface()->names()[$localPortId];
            $neighbours[ $neighbourCdpId ][$count]['localPort']     = $this->getSNMP()->useIface()->descriptions()[$localPortId];

            try
            {
                if( $this->getSNMP()->useLAG()->isAggregatePorts()[$localPortId] )
                {
                    $neighbours[ $neighbourCdpId ][$count]['isLAG']       = true;
                    $neighbours[ $neighbourCdpId ][$count]['lagPortId']   = $this->getSNMP()->useLAG()->portAttachedIds()[$localPortId];
                    $neighbours[ $neighbourCdpId ][$count]['lagPortName'] = $this->getSNMP()->useIface()->names()[ $neighbours[ $neighbourCdpId ][$count]['lagPortId'] ];
                }
                else
                {
                    $neighbours[ $neighbourCdpId ][$count]['isLAG']     = false;
                }
            }
            catch( \OSS_SNMP\Exception $e )
            {
                $neighbours[ $neighbourCdpId ][$count]['isLAG']     = false;
            }

            $neighbours[ $neighbourCdpId ][$count]['remotePort']    = $this->neighbourPort()[$localPortId];

            if( $inverse )
            {
                if( !is_array( $skipHostIds ) || !in_array( $neighbourCdpId, $skipHostIds ) )
                {
                    try
                    {
                        if( !isset( $remotes[ $neighbourCdpId ] ) )
                            $remotes[ $neighbourCdpId ] = new \OSS_SNMP\SNMP( $neighbourCdpId, $this->getSNMP()->getCommunity() );

                        $remote = $remotes[ $neighbourCdpId ];
                        $rneighbours = $remote->useCisco_CDP()->neighbours( false );

                        foreach( $rneighbours as $rNeighbourCdpId => $rLinks )
                        {
                            foreach( $rLinks as $rIndex => $rPortDetails )
                            {
                                $rLocalPortId = $rPortDetails[ 'localPortId' ];
                                if( $rNeighbourCdpId == $this->id()
                                        && $remote->useIface()->descriptions()[ $rLocalPortId ] == $neighbours[ $neighbourCdpId ][$count]['remotePort'] )
                                {
                                    $neighbours[ $neighbourCdpId ][$count]['remotePortId']   = $rLocalPortId;
                                    $neighbours[ $neighbourCdpId ][$count]['remotePortName'] = $remote->useIface()->names()[ $rLocalPortId ];

                                    if( $neighbours[ $neighbourCdpId ][$count]['isLAG'] )
                                    {
                                        $neighbours[ $neighbourCdpId ][$count]['remoteLagPortId']   = $remote->useLAG()->portAttachedIds()[$rLocalPortId];
                                        $neighbours[ $neighbourCdpId ][$count]['remoteLagPortName'] = $remote->useIface()->names()[ $neighbours[ $neighbourCdpId ][$count]['remoteLagPortId'] ];
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    catch( \OSS_SNMP\Exception $e )
                    {
                    }
                }
            }

        }

        return $neighbours;
    }

    /**
     * Recursivily crawls all CDP neighbours to build up a flat array of all devices
     * indexed by the CDP device id.
     *
     * Array form is same as that returned by neighbours()
     *
     * @see neighbours()
     * @param array $devices Unless you're doing something funky, just pass an empty array. This is where the result will be found.
     * @param string $device CDP device ID of next host to crawl. On first pass, set to null - used internally when recursing
     * @param array $ignore An array of CDP device IDs to *ignore*. I.e. to not include in recursive crawling
     * @return array The resultant array of all crawled devices (same as that passed in the @param $devices parameter)
     */
    public function crawl( &$devices = array(), $device = null, $ignore = array() )
    {
        if( !count( $devices ) )
        {
            $device = $this->id();
            $devices[ $device ] = $this->neighbours( true, $ignore );
        }

        foreach( $devices[ $device ] as $feNeighbour => $feConnections )
        {
            if( in_array( $feNeighbour, $ignore ) )
            {
                if( isset( $devices[ $device ][$feNeighbour] ) )
                    unset( $devices[ $device ][$feNeighbour] );

                continue;
            }

            if( !isset( $devices[ $feNeighbour ] ) )
            {
                $snmp = new \OSS_SNMP\SNMP( $feNeighbour, $this->getSNMP()->getCommunity() );

                try
                {
                    $devices[ $feNeighbour ] = $snmp->useCisco_CDP()->neighbours( true, $ignore );
                    unset( $snmp );
                    $this->crawl( $devices, $feNeighbour, $ignore );
                }
                catch( \OSS_SNMP\Exception $e )
                {
                    // this device did not respond / have CDP enabled / CDP available - skip
                    unset( $devices[$feNeighbour] );
                }
            }
        }

        return $devices;
    }


    /**
     * Find the layer 2 topology as an array with no link mentioned more than once.
     *
     * Huh? This function:
     *
     * * takes the result of crawl() (or calls crawl()) to get the CDP topology;
     * * foreach device, builds an array of device to device links;
     * * SO LONG as that link has already not been accounted for in the other direction.
     *
     * I.e. if a link is found A -> B, then the same B -> A link will not be included.
     *
     * The primary differences to the return value of this and crawl() are:
     *
     * * links only appear once (unidirectional) rather than twice (bidirectional)
     * * the links are indexed by the localPortName rather than an integer index:
     *
     *     [cr-sw04.degkcp.example.ie] => Array
     *     (
     *          [cd-sw02.degkcp.example.ie] => Array
     *          (
     *              [GigabitEthernet1/0/3] => Array
     *              (
     *                  [remotePort] => FastEthernet0/1
     *                  [isLAG]      => false
     *                  ........
     *          )
     *     )
     *
     *
     * All port information is copied over from the supplied / called `crawl()` array
     *
     * @see crawl()
     * @param array $devices The result of crawl() (if null, this function performs a crawl())
     * @return array L2 topology as described above.
     */
    public function linkTopology( $devices = null )
    {
        if( $devices == null )
            $devices = $this->crawl();

        $links = array();
        foreach( $devices as $feDevice => $feNeighbours )
        {
            foreach( $feNeighbours as $fe2Device => $fe2Links )
            {
                foreach( $fe2Links as $fe2Link )
                {
                    // have we already accounted for this link on the other side?
                    if( isset( $links[ $fe2Device ][ $feDevice ][ $fe2Link['remotePort'] ] ) )
                        continue;

                    if( !isset( $links[ $feDevice ] ) )
                        $links[ $feDevice ] = array();

                    if( !isset( $links[ $feDevice ][ $fe2Device ] ) )
                        $links[ $feDevice ][ $fe2Device ] = array();

                    $links[ $feDevice ][ $fe2Device ][ $fe2Link['localPort'] ] = array();

                    foreach( $fe2Link as $k => $v )
                        $links[ $feDevice ][ $fe2Device ][ $fe2Link['localPort'] ][ $k ]   = $v;
                }
            }
        }

        return $links;
    }


    /**
     * Utility function to process the output from neighbours() and remove individual trunk ports leaving only
     * single LAG links.
     *
     * For example, here's a sample return for a switch with a neighbour
     * connected with a LAG / PortChannel:
     *
     *     array(2) {
     *     ["cr-sw01.example.ie"] => array(2) {
     *         [0] => array(11) {
     *             ["localPortId"] => int(10111)
     *             ["localPortName"] => string(8) "Gi1/0/11"
     *             ["localPort"] => string(21) "GigabitEthernet1/0/11"
     *             ["isLAG"] => bool(true)
     *             ["lagPortId"] => int(5048)
     *             ["lagPortName"] => string(4) "Po48"
     *             ["remotePort"] => string(21) "GigabitEthernet1/0/11"
     *             ["remotePortId"] => int(10111)
     *             ["remotePortName"] => string(8) "Gi1/0/11"
     *             ["remoteLagPortId"] => int(5048)
     *             ["remoteLagPortName"] => string(4) "Po48"
     *         }
     *         [1] => array(11) {
     *             ["localPortId"] => int(10112)
     *             ["localPortName"] => string(8) "Gi1/0/12"
     *             ["localPort"] => string(21) "GigabitEthernet1/0/12"
     *             ["isLAG"] => bool(true)
     *             ["lagPortId"] => int(5048)
     *             ["lagPortName"] => string(4) "Po48"
     *             ["remotePort"] => string(21) "GigabitEthernet1/0/12"
     *             ["remotePortId"] => int(10112)
     *             ["remotePortName"] => string(8) "Gi1/0/12"
     *             ["remoteLagPortId"] => int(5048)
     *             ["remoteLagPortName"] => string(4) "Po48"
     *         }
     *     }
     *     ...
     *     }
     *
     * The result of this function would be:
     *
     *     array(2) {
     *     ["cr-sw01.example.ie"] => array(1) {
     *         [0] => array(11) {
     *             ["localPortId"] => int(5048)
     *             ["localPortName"] => string(8) "Po48"
     *             ["localPort"] => string(21) "Po48"
     *             ["isLAG"] => bool(true)
     *             ["lagPortId"] => int(5048)
     *             ["lagPortName"] => string(4) "Po48"
     *             ["remotePort"] => string(21) "Po48"
     *             ["remotePortId"] => int(5048)
     *             ["remotePortName"] => string(8) "Po48"
     *             ["remoteLagPortId"] => int(5048)
     *             ["remoteLagPortName"] => string(4) "Po48"
     *         }
     *     }
     *     ...
     *     }
     *
     * @see neighbours()
     * @param array $neighbours The result of a call to neighbours()
     * @return array Processed CDP neighbours with LAG ports collapsed
     */
    public function collapseLAGs( $neighbours )
    {
        foreach( $neighbours as $neighbour => $links )
        {
            $removed = array();
            foreach( $links as $idx => $linkDetails )
            {
                if( $linkDetails['isLAG'] )
                {
                    if( isset( $removed[ $linkDetails['localLagPortId'] ] ) )
                        unset( $neighbours[ $neighbour ][ $idx ] );
                    else
                    {
                        $removed[ $linkDetails['localLagPortId'] ] = true;
                        $neighbours[ $neighbour ][ $idx ]['localPortId']    = $neighbours[ $neighbour ][ $idx ]['lagPortId'];
                        $neighbours[ $neighbour ][ $idx ]['localPortName']  = $neighbours[ $neighbour ][ $idx ]['lagPortName'];
                        $neighbours[ $neighbour ][ $idx ]['localPort']      = $neighbours[ $neighbour ][ $idx ]['lagPortName'];
                        $neighbours[ $neighbour ][ $idx ]['remotePortId']   = $neighbours[ $neighbour ][ $idx ]['remoteLagPortId'];
                        $neighbours[ $neighbour ][ $idx ]['remotePortName'] = $neighbours[ $neighbour ][ $idx ]['remoteLagPortName'];
                        $neighbours[ $neighbour ][ $idx ]['remotePort']     = $neighbours[ $neighbour ][ $idx ]['remoteLagPortName'];
                    }
                }
            }
        }

        return $neighbours;
    }

    /**
     * An extension of `collapseLAGs()` to work with `crawl()` and `linkTopology()`.
     *
     * Rather than taking the input from `neighbours()`, it takes input from `crawl()` or `linkTopology()`
     * and processes all neighbours.
     *
     * @see collapseLAGs()
     * @param array $devices The result of a call to crawl() or linkTopology()
     * @return array Processed CDP neighbours with LAG ports collapsed
     */
    public function collapseDevicesLAGs( $devices )
    {
        foreach( $devices as $parent => $neighbours )
            $devices[ $parent ] = $this->collapseLAGs( $neighbours );

        return $devices;
    }


}




