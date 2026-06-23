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
 * A class to performing SNMP LLDP queries.
 *
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Sergio Gómez Bachiller <sergio@uco.es>
 * @see http://oidref.com/1.0.8802.1.1.2
 */
class LLDP extends \OSS_SNMP\MIB
{
    /*
     * Local system
     */

    /**
     * The type of encoding used to identify the chassis associated with the
     * local system.
     *
     * @see localChassisIdSubtype()
     */
    const OID_LLDP_LOC_CHASSIS_ID_SUBTYPE = '1.0.8802.1.1.2.1.3.1.0';
    /**
     * The string value used to identify the chassis component associated with
     * the local system.
     *
     * @see localChassisId()
     */
    const OID_LLDP_LOC_CHASSIS_ID = '1.0.8802.1.1.2.1.3.2.0';

    /**
     * The string value used to identify the system name of the
     * local system.
     *
     * @see localSystemName()
     */
    const OID_LLDP_LOC_SYS_NAME = '1.0.8802.1.1.2.1.3.3.0';

    /**
     * The string value used to identify the system description
     * of the local system.
     *
     * @see localSystemDescription()
     */
    const OID_LLDP_LOC_SYS_DESC = '1.0.8802.1.1.2.1.3.4.0';

    /**
     * The bitmap value used to identify which system capabilities
     * are supported on the local system.
     *
     * @see localSystemCapabilitySupported()
     */
    const OID_LLDP_LOC_SYS_CAP_SUPPORTED = '1.0.8802.1.1.2.1.3.5.0';

    /**
     * The bitmap value used to identify which system capabilities
     * are enabled on the local system.
     *
     * @see localSystemCapabilityEnabled()
     */
    const OID_LLDP_LOC_SYS_CAP_ENABLED = '1.0.8802.1.1.2.1.3.6.0';

    /**
     * The type of port identifier encoding used in the associated
     * 'lldpLocPortId' object.
     *
     * @see localPortIdSubtype()
     */
    const OID_LLDP_LOC_PORT_ID_SUBTYPE = '1.0.8802.1.1.2.1.3.7.1.2';

    /**
     * The string value used to identify the port component
     * associated with the local system.
     *
     * @see localPortId()
     */
    const OID_LLDP_LOC_PORT_ID = '1.0.8802.1.1.2.1.3.7.1.3';

    /**
     * The string value used to identify the description of
     * the given port associated with the local system.
     *
     * @see localPortDescription()
     */
    const OID_LLDP_LOC_PORT_DESC = '1.0.8802.1.1.2.1.3.7.1.4';

    /*
     * Remote system
     */

    /**
     * The type of encoding used to identify the chassis associated with the
     * remote system.
     *
     * @see remoteChassisIdSubtype()
     */
    const OID_LLDP_REM_CHASSIS_ID_SUBTYPE = '.1.0.8802.1.1.2.1.4.1.1.4';
    /**
     * The string value used to identify the chassis component associated with
     * the remote system.
     *
     * @see remoteChassisId()
     */
    const OID_LLDP_REM_CHASSIS_ID = '.1.0.8802.1.1.2.1.4.1.1.5';

    /**
     * The type of port identifier encoding used in the associated
     * 'lldpRemPortId' object.
     *
     * @see remotePortIdSubtype()
     */
    const OID_LLDP_REM_PORT_ID_SUBTYPE = '.1.0.8802.1.1.2.1.4.1.1.6';

    /**
     * The string value used to identify the port component
     * associated with the remote system.
     *
     * @see remotePortId()
     */
    const OID_LLDP_REM_PORT_ID = '.1.0.8802.1.1.2.1.4.1.1.7';

    /**
     * The string value used to identify the description of
     * the given port associated with the remote system.
     *
     * @see remotePortDescription()
     */
    const OID_LLDP_REM_PORT_DESC = '.1.0.8802.1.1.2.1.4.1.1.8';

    /**
     * The string value used to identify the system name of the
     * remote system.
     *
     * @see remoteSystemName()
     */
    const OID_LLDP_REM_SYS_NAME = '.1.0.8802.1.1.2.1.4.1.1.9';

    /**
     * The string value used to identify the system description
     * of the remote system.
     *
     * @see remoteSystemDescription()
     */
    const OID_LLDP_REM_SYS_DESC = '.1.0.8802.1.1.2.1.4.1.1.10';

    /**
     * The bitmap value used to identify which system capabilities
     * are supported on the remote system.
     *
     * @see remoteSystemCapabilitySupported()
     */
    const OID_LLDP_REM_SYS_CAP_SUPPORTED = '.1.0.8802.1.1.2.1.4.1.1.11';

    /**
     * The bitmap value used to identify which system capabilities
     * are enabled on the remote system.
     *
     * @see remoteSystemCapabilityEnabled()
     */
    const OID_LLDP_REM_SYS_CAP_ENABLED = '.1.0.8802.1.1.2.1.4.1.1.12';

    // ...

    /**
     * EntPhysicalAlias when entPhysClass has a value of ‘chassis(3)’ (IETF RFC 2737).
     */
    const CHASSIS_ID_SUBTYPE_CHASSIS_COMPONENT = 1;
    /**
     * IfAlias (IETF RFC 2863).
     */
    const CHASSIS_ID_SUBTYPE_INTERFACE_ALIAS = 2;
    /**
     * EntPhysicalAlias when entPhysicalClass has a value ‘port(10)’ or ‘backplane(4)’ (IETF RFC 2737).
     */
    const CHASSIS_ID_SUBTYPE_PORT_COMPONENT = 3;
    /**
     * MAC address (IEEE Std 802-2001).
     */
    const CHASSIS_ID_SUBTYPE_MAC_ADDRESS = 4;
    /**
     * Octet string that identifies a particular network address family and an
     * associated network address that are encoded in network octet order.
     */
    const CHASSIS_ID_SUBTYPE_NETWORK_ADDRESS = 5;
    /**
     * ifName (IETF RFC 2863).
     */
    const CHASSIS_ID_SUBTYPE_INTERFACE_NAME = 6;
    /**
     * Alpha-numeric string locally assigned.
     */
    const CHASSIS_ID_SUBTYPE_LOCALLY_ASSIGNED = 7;

    /**
     * Text representations of chassis id subtypes.
     *
     * @see remoteChassisIdSubtype()
     * @see IEEE 802.1AB-2004 9.5.2.2
     *
     * @var array Text representations of chassis id subtypes
     */
    public static $CHASSIS_ID_SUBTYPES = array(
        self::CHASSIS_ID_SUBTYPE_CHASSIS_COMPONENT => 'Chassis component',
        self::CHASSIS_ID_SUBTYPE_INTERFACE_ALIAS => 'Interface alias',
        self::CHASSIS_ID_SUBTYPE_PORT_COMPONENT => 'Port component',
        self::CHASSIS_ID_SUBTYPE_MAC_ADDRESS => 'MAC address',
        self::CHASSIS_ID_SUBTYPE_NETWORK_ADDRESS => 'Network address',
        self::CHASSIS_ID_SUBTYPE_INTERFACE_NAME => 'Interface name',
        self::CHASSIS_ID_SUBTYPE_LOCALLY_ASSIGNED => 'Locally assigned',
    );

    /**
     * IfAlias (IETF RFC 2863).
     */
    const PORT_ID_SUBTYPE_INTERFACE_ALIAS = 1;
    /**
     * EntPhysicalAlias when entPhysicalClass has a value ‘port(10)’ or ‘backplane(4)’ (IETF RFC 2737).
     */
    const PORT_ID_SUBTYPE_PORT_COMPONENT = 2;
    /**
     * MAC address (IEEE Std 802-2001).
     */
    const PORT_ID_SUBTYPE_MAC_ADDRESS = 3;
    /**
     * Octet string that identifies a particular network address family and an
     * associated network address that are encoded in network octet order.
     */
    const PORT_ID_SUBTYPE_NETWORK_ADDRESS = 4;
    /**
     * ifName (IETF RFC 2863).
     */
    const PORT_ID_SUBTYPE_INTERFACE_NAME = 5;
    /**
     * Agent circuit ID (IETF RFC 3046).
     */
    const PORT_ID_SUBTYPE_AGENT_CIRCUIT_ID = 6;
    /**
     * Alpha-numeric string locally assigned.
     */
    const PORT_ID_SUBTYPE_LOCALLY_ASSIGNED = 7;

    /**
     * Text representations of port id subtypes.
     *
     * @see remotePortIdSubtype()
     * @see IEEE 802.1AB-2004 9.5.3.2
     *
     * @var array Text representations of port id subtypes
     */
    public static $PORT_ID_SUBTYPES = array(
        self::PORT_ID_SUBTYPE_INTERFACE_ALIAS => 'Interface alias',
        self::PORT_ID_SUBTYPE_PORT_COMPONENT => 'Port component',
        self::PORT_ID_SUBTYPE_MAC_ADDRESS => 'MAC address',
        self::PORT_ID_SUBTYPE_NETWORK_ADDRESS => 'Network address',
        self::PORT_ID_SUBTYPE_INTERFACE_NAME => 'Interface name',
        self::PORT_ID_SUBTYPE_AGENT_CIRCUIT_ID => 'Agent circuid ID',
        self::PORT_ID_SUBTYPE_LOCALLY_ASSIGNED => 'Locally assigned',
    );

    /**
     * Repeater.
     *
     * @see IETF RFC 2108
     */
    const SYSTEM_CAPABILITIES_REPEATER = 0b1;
    /**
     * Bridge.
     *
     * @see IETF RFC 2674
     */
    const SYSTEM_CAPABILITIES_BRIDGE = 0b10;
    /**
     * WLAN Access Point.
     *
     * @see IEEE 802.11 MIB
     */
    const SYSTEM_CAPABILITIES_WLAN_AP = 0b100;
    /**
     * Router.
     *
     * @see IETF RFC 1812
     */
    const SYSTEM_CAPABILITIES_ROUTER = 0b1000;
    /**
     * Telephone.
     *
     * @see IETF RFC 2011
     */
    const SYSTEM_CAPABILITIES_TELEPHONE = 0b10000;
    /**
     * DOCSIS cable device.
     *
     * @see IETF RFC 2669 and IETF RFC 2670
     */
    const SYSTEM_CAPABILITIES_DOCSIS = 0b100000;
    /**
     * Station only capability is intended for devices that implement
     * only an end station capabilit.
     */
    const SYSTEM_CAPABILITIES_STATION_ONLY = 0b1000000;

    /**
     * Text representation of system capabilities.
     *
     * @see IEEE 802.1AB-2004 9.5.8.1
     *
     * @var array Text representation of system capabilities
     */
    public static $SYSTEM_CAPABILITIES = array(
        self::SYSTEM_CAPABILITIES_REPEATER => 'Repeater',
        self::SYSTEM_CAPABILITIES_BRIDGE => 'Bridge',
        self::SYSTEM_CAPABILITIES_WLAN_AP => 'WLAN Access Point',
        self::SYSTEM_CAPABILITIES_ROUTER => 'Router',
        self::SYSTEM_CAPABILITIES_TELEPHONE => 'Telephone',
        self::SYSTEM_CAPABILITIES_DOCSIS => 'DOCSIS cable device',
        self::SYSTEM_CAPABILITIES_STATION_ONLY => 'Station Only',
    );

    /*
     * Local system
     */

    /**
     * Get The type of encoding used to identify the chassis
     * associated with the local system.
     *
     * @see CHASSIS_ID_SUBTYPES
     *
     * @param bool $translate If true, return the string representation
     *
     * @return int|string The chassis id subtype or its string representation
     */
    public function localChassisIdSubtype($translate = false)
    {
        $subtypes = $this->getSNMP()->get(self::OID_LLDP_LOC_CHASSIS_ID_SUBTYPE);

        if (!$translate) {
            return $subtypes;
        }

        return $this->getSNMP()->translate($subtypes, self::$CHASSIS_ID_SUBTYPES);
    }

    /**
     * Get the string value used to identify the chassis component
     * associated with the remote system.
     *
     * @return string the chassis component identity
     */
    public function localChassisId()
    {
        return $this->getSNMP()->get(self::OID_LLDP_LOC_CHASSIS_ID);
    }

    /**
     * Get the string value used to identify the system name of the
     * local system.
     *
     * @return string The system name
     */
    public function localSystemName()
    {
        return $this->getSNMP()->get(self::OID_LLDP_LOC_SYS_NAME);
    }

    /**
     * Get the string value used to identify the system description
     * of the local system.
     *
     * @return string The system description
     */
    public function localSystemDescription()
    {
        return $this->getSNMP()->get(self::OID_LLDP_LOC_SYS_DESC);
    }

    /**
     * Get the bitmap value used to identify which system capabilities
     * are supported on the local system.
     *
     * @return int the system capabilities are supported on the local system
     */
    public function localSystemCapabilitySupported()
    {
        $capability = $this->getSNMP()->get(self::OID_LLDP_LOC_SYS_CAP_SUPPORTED);

        return ord($capability);
    }

    /**
     * Query if the local system supports the given capability.
     *
     * Example:
     *
     *     if( $host->useLLVM()->localSystemHasCapabilitySupported(\OSS_SNMP\SNMP\MIBS\LLDP::SYSTEM_CAPABILITIES_ROUTER )
     *          echo "Host is a router!!";
     *
     *
     * @param int $capability The capability to query for (defined by self::SYSTEM_CAPABILITIES_* constants)
     *
     * @return bool True if the local system supports the given capability
     */
    public function localSystemHasCapabilitySupported($capability)
    {
        if ($this->localSystemCapabilitySupported() & $capability) {
            return true;
        }

        return false;
    }

    /**
     * Get an array of individual supported capabilities of the local system.
     *
     * Example:
     *
     *     print_r( $host->useLLVM()->localSystemCapabilitiesSupported( ) )
     *
     *         [0] => 8         // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => 32        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     *     print_r( $host->useLLVM()->localSystemCapabilitiesSupported( true ) )
     *
     *         [0] => "Router"                     // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => "DOCSIS cable device"        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     * @param bool $translate Set to true to return descriptions rather than integers
     *
     * @return array Individual capabilities of the local system
     */
    public function localSystemCapabilitiesSupported($translate = false)
    {
        $capabilities = array();
        $localCapabilities = $this->localSystemCapabilitySupported();

        foreach (self::$SYSTEM_CAPABILITIES as $mask => $description) {
            if ($localCapabilities & $mask) {
                $capabilities[] = $mask;
            }
        }

        if ($translate) {
            return $this->getSNMP()->translate($capabilities, self::$SYSTEM_CAPABILITIES);
        }

        return $capabilities;
    }

    /**
     * Get the bitmap value used to identify which system capabilities
     * are enabled on the local system.
     *
     * @return int the system capabilities are enabled on the local system
     */
    public function localSystemCapabilityEnabled()
    {
        $capability = $this->getSNMP()->get(self::OID_LLDP_LOC_SYS_CAP_ENABLED);

        return ord($capability);
    }

    /**
     * Query if the local system has the given capability enabled.
     *
     * Example:
     *
     *     if( $host->useLLVM()->localSystemHasCapabilitySupported( \OSS_SNMP\SNMP\MIBS\LLDP::SYSTEM_CAPABILITIES_ROUTER )
     *          echo "Host is a router!!";
     *
     *
     * @param int $capability The capability to query for (defined by self::SYSTEM_CAPABILITIES_* constants)
     *
     * @return bool True if the local system has the given capability enabled
     */
    public function localSystemHasCapabilityEnabled($capability)
    {
        if ($this->localSystemCapabilityEnabled() & $capability) {
            return true;
        }

        return false;
    }

    /**
     * Get an array of individual enabled capabilities of the local system.
     *
     * Example:
     *
     *     print_r( $host->useLLVM()->localSystemCapabilitiesEnabled( ) )
     *
     *         [0] => 8         // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => 32        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     *     print_r( $host->useLLVM()->localSystemCapabilitiesSupported( true ) )
     *
     *         [0] => "Router"                     // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => "DOCSIS cable device"        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     * @param int  $portId    The local system connect by the local port ID
     * @param bool $translate Set to true to return descriptions rather than integers
     *
     * @return array Individual capabilities of a given local system
     */
    public function localSystemCapabilitiesEnabled($translate = false)
    {
        $capabilities = array();
        $localCapabilities = $this->localSystemCapabilityEnabled();

        foreach (self::$SYSTEM_CAPABILITIES as $mask => $description) {
            if ($localCapabilities & $mask) {
                $capabilities[] = $mask;
            }
        }

        if ($translate) {
            return $this->getSNMP()->translate($capabilities, self::$SYSTEM_CAPABILITIES);
        }

        return $capabilities;
    }

    /**
     * Get an array with the type of port identifier encoding used in the associated
     * 'lldpLocPortId' object.
     *
     * E.g.:
     *
     *
     * .1.0.8802.1.1.2.1.3.7.1.2.503 => INTEGER: 7
     * .1.0.8802.1.1.2.1.3.7.1.2.505 => INTEGER: 7
     * ...
     *
     *     [503] => 7
     *     [505] => 7
     *
     * @see PORT_ID_SUBTYPES
     *
     * @param bool $translate If true, return the string representation
     *
     * @return array An array of port id subtypes
     */
    public function localPortIdSubtype($translate = false)
    {
        $subtypes = $this->getSNMP()->walk1d(self::OID_LLDP_LOC_PORT_ID_SUBTYPE);

        if (!$translate) {
            return $subtypes;
        }

        return $this->getSNMP()->translate($subtypes, self::$PORT_ID_SUBTYPES);
    }

    /**
     * Get an array with the string value used to identify the port component
     * associated with a given port in the local system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.3.7.1.2.503 => STRING: "503"
     * .1.0.8802.1.1.2.1.3.7.1.2.505 => STRING: "505"
     * ...
     *
     *     [503] => 503
     *     [505] => 505
     *
     * @return array the port component identities
     */
    public function localPortId()
    {
        return $this->getSNMP()->walk1d(self::OID_LLDP_LOC_PORT_ID);
    }

    /**
     * Get an array with the string value used to identify the 802 LAN station's port
     * description associated with the local system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.3.7.1.4.503 => STRING: "switch01"
     * .1.0.8802.1.1.2.1.3.7.1.4.505 => STRING: "switch02"
     * ...
     *
     *     [503] => switch01
     *     [505] => switch02
     *
     * @return array The port descriptions
     */
    public function localPortDescription()
    {
        return $this->getSNMP()->walk1d(self::OID_LLDP_LOC_PORT_DESC);
    }

    /*
     * Remote system
     */

    /**
     * Get an array with type of encoding used to identify the chassis
     * associated with the remote system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.4.1.1.4.5108638.200.102 = INTEGER: 4
     * .1.0.8802.1.1.2.1.4.1.1.4.5761237.201.104 = INTEGER: 4
     * ...
     *
     *          [200] => 4
     *          [201] => 4
     *
     * @see CHASSIS_ID_SUBTYPES
     *
     * @param bool $translate If true, return the string representation
     *
     * @return array An array of chassis id subtypes
     */
    public function remoteChassisIdSubtype($translate = false)
    {
        $subtypes = $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_CHASSIS_ID_SUBTYPE, 13);

        if (!$translate) {
            return $subtypes;
        }

        return $this->getSNMP()->translate($subtypes, self::$CHASSIS_ID_SUBTYPES);
    }

    /**
     * Get an array with the string value used to identify the chassis component
     * associated with the remote system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.4.1.1.5.7369071.718.125 => Hex-STRING: 08 1F F3 E9 D8 00
     * .1.0.8802.1.1.2.1.4.1.1.5.7706202.653.126 => Hex-STRING: 08 B2 58 A1 EA 80
     * ...
     *          [718] => 081FF3E9D800
     *          [653] => 08B258A1EA80
     *
     * @return array the chassis component identities
     */
    public function remoteChassisId()
    {
        return $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_CHASSIS_ID, 13);
    }

    /**
     * Get an array with the type of port identifier encoding used in the associated
     * 'lldpRemPortId' object.
     *
     * E.g.:
     *
     *
     * .1.0.8802.1.1.2.1.4.1.1.6.15590464.515.3 => INTEGER: 5
     * .1.0.8802.1.1.2.1.4.1.1.6.15591663.556.4 => INTEGER: 5
     * ...
     *
     *     [515] => 5
     *     [556] => 5
     *
     * @see PORT_ID_SUBTYPES
     *
     * @param bool $translate If true, return the string representation
     *
     * @return array An array of port id subtypes
     */
    public function remotePortIdSubtype($translate = false)
    {
        $subtypes = $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_PORT_ID_SUBTYPE, 13);

        if (!$translate) {
            return $subtypes;
        }

        return $this->getSNMP()->translate($subtypes, self::$PORT_ID_SUBTYPES);
    }

    /**
     * Get an array with the string value used to identify the port component
     * associated with a given port in the remote system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.4.1.1.7.15590464.515.3 => STRING: "Gi1/0/24"
     * .1.0.8802.1.1.2.1.4.1.1.7.15591663.556.4 => STRING: "Gi0/1"
     * ...
     *
     *     [515] => Gi1/0/24
     *     [556] => Gi0/1
     *
     * @return array the port component identities
     */
    public function remotePortId()
    {
        return $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_PORT_ID, 13);
    }

    /**
     * Get an array with the string value used to identify the 802 LAN station's
     * port description associated with the remote system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.4.1.1.8.15590464.515.3 => STRING: "GigabitEthernet1/0/24"
     * .1.0.8802.1.1.2.1.4.1.1.8.15591663.556.4 => STRING: "GigabitEthernet0/1"
     * ...
     *
     *     [515] => GigabitEthernet1/0/24
     *     [556] => GigabitEthernet0/1
     *
     * @return array The port descriptions
     */
    public function remotePortDescription()
    {
        return $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_PORT_DESC, 13);
    }

    /**
     * Get an array with the string value used to identify the system name of the
     * remote system.
     *
     * E.g.:
     *
     * .1.0.8802.1.1.2.1.4.1.1.9.15590464.515.3 => STRING: "switch01"
     * .1.0.8802.1.1.2.1.4.1.1.9.15591663.556.4 => STRING: "switch02"
     * ...
     *
     *     [515] => switch01
     *     [556] => switch02
     *
     * @return array The system names
     */
    public function remoteSystemName()
    {
        return $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_SYS_NAME, 13);
    }

    /**
     * Get an array with the string value used to identify the system description
     * of the remote system.
     *
     * E.g.
     *
     * .1.0.8802.1.1.2.1.4.1.1.10.15590464.515.3 => STRING: "Cisco IOS Software, ..."
     * .1.0.8802.1.1.2.1.4.1.1.10.15591663.556.4 => STRING: "Cisco IOS Software, ..."
     * ...
     *
     *     [515] => "Cisco IOS Software, ..."
     *     [556] => "Cisco IOS Software, ..."
     *
     * @return array The system descriptions
     */
    public function remoteSystemDescription()
    {
        return $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_SYS_DESC, 13);
    }

    /**
     * Get an array with the bitmap value used to identify which system capabilities
     * are supported on the remote system.
     *
     * @return array the system capabilities are supported on the remote system
     */
    public function remoteSystemCapabilitySupported()
    {
        $capabilities = $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_SYS_CAP_SUPPORTED, 13);

        foreach ($capabilities as $index => $capability) {
            $capabilities[$index] = ord($capability);
        }

        return $capabilities;
    }

    /**
     * Query if a given remote system (by connected port ID) supports the given capability.
     *
     * Example:
     *
     *     if( $host->useLLVM()->remoteSystemHasCapabilitySupported( $portId, \OSS_SNMP\SNMP\MIBS\LLDP::SYSTEM_CAPABILITIES_ROUTER )
     *          echo "Host is a router!!";
     *
     *
     * @param int $portId     The remote system connect by the local port ID
     * @param int $capability The capability to query for (defined by self::SYSTEM_CAPABILITIES_* constants)
     *
     * @return bool True if the remote system supports the given capability
     */
    public function remoteSystemHasCapabilitySupported($portId, $capability)
    {
        if ($this->remoteSystemCapabilitySupported()[$portId] & $capability) {
            return true;
        }

        return false;
    }

    /**
     * Get an array of individual supported capabilities of a given remote system (by connected port ID).
     *
     * Example:
     *
     *     print_r( $host->useLLVM()->remoteSystemCapabilitiesSupported( 10111 ) )
     *
     *         [0] => 8         // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => 32        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     *     print_r( $host->useLLVM()->remoteSystemCapabilitiesSupported( 10111, true ) )
     *
     *         [0] => "Router"                     // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => "DOCSIS cable device"        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     * @param int  $portId    The remote system connect by the local port ID
     * @param bool $translate Set to true to return descriptions rather than integers
     *
     * @return array Individual capabilities of a given remote system
     */
    public function remoteSystemCapabilitiesSupported($portId, $translate = false)
    {
        $capabilities = array();
        $remoteCapabilities = $this->remoteSystemCapabilitySupported()[$portId];

        foreach (self::$SYSTEM_CAPABILITIES as $mask => $description) {
            if ($remoteCapabilities & $mask) {
                $capabilities[] = $mask;
            }
        }

        if ($translate) {
            return $this->getSNMP()->translate($capabilities, self::$SYSTEM_CAPABILITIES);
        }

        return $capabilities;
    }

    /**
     * Get an array with the bitmap value used to identify which system capabilities
     * are enabled on the remote system.
     *
     * @return array the system capabilities are enabled on the remote system
     */
    public function remoteSystemCapabilityEnabled()
    {
        $capabilities = $this->getSNMP()->subOidWalk(self::OID_LLDP_REM_SYS_CAP_ENABLED, 13);

        foreach ($capabilities as $index => $capability) {
            $capabilities[$index] = ord($capability);
        }

        return $capabilities;
    }

    /**
     * Query if a given remote system (by connected port ID) has the given capability enabled.
     *
     * Example:
     *
     *     if( $host->useLLVM()->remoteSystemHasCapabilitySupported( $portId, \OSS_SNMP\SNMP\MIBS\LLDP::SYSTEM_CAPABILITIES_ROUTER )
     *          echo "Host is a router!!";
     *
     *
     * @param int $portId     The remote system connect by the local port ID
     * @param int $capability The capability to query for (defined by self::SYSTEM_CAPABILITIES_* constants)
     *
     * @return bool True if the remote system has the given capability enabled
     */
    public function remoteSystemHasCapabilityEnabled($portId, $capability)
    {
        if ($this->remoteSystemCapabilityEnabled()[$portId] & $capability) {
            return true;
        }

        return false;
    }

    /**
     * Get an array of individual enabled capabilities of a given remote system (by connected port ID).
     *
     * Example:
     *
     *     print_r( $host->useLLVM()->remoteSystemCapabilitiesEnabled( 10111 ) )
     *
     *         [0] => 8         // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => 32        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     *     print_r( $host->useLLVM()->remoteSystemCapabilitiesSupported( 10111, true ) )
     *
     *         [0] => "Router"                     // self::SYSTEM_CAPABILITIES_ROUTER
     *         [1] => "DOCSIS cable device"        // self::SYSTEM_CAPABILITIES_DOCSIS
     *
     * @param int  $portId    The remote system connect by the local port ID
     * @param bool $translate Set to true to return descriptions rather than integers
     *
     * @return array Individual capabilities of a given remote system
     */
    public function remoteSystemCapabilitiesEnabled($portId, $translate = false)
    {
        $capabilities = array();
        $remoteCapabilities = $this->remoteSystemCapabilityEnabled()[$portId];

        foreach (self::$SYSTEM_CAPABILITIES as $mask => $description) {
            if ($remoteCapabilities & $mask) {
                $capabilities[] = $mask;
            }
        }

        if ($translate) {
            return $this->getSNMP()->translate($capabilities, self::$SYSTEM_CAPABILITIES);
        }

        return $capabilities;
    }
}
