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
 * A class for performing SNMP V2 queries on generic devices.
 *
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Sergio Gómez Bachiller <sergio@uco.es>
 */
class VlanMembership extends \OSS_SNMP\MIBS\Cisco
{
    const OID_VLAN_MEMBERSHIP_VLAN_TYPE = '.1.3.6.1.4.1.9.9.68.1.2.2.1.1';
    const OID_VLAN_MEMBERSHIP_VLAN = '.1.3.6.1.4.1.9.9.68.1.2.2.1.2';
    const OID_VLAN_MEMBERSHIP_PORT_STATUS = '.1.3.6.1.4.1.9.9.68.1.2.2.1.3';

    /**
     * Constant for possible value of interface vlan type.
     *
     * @see vlanTypes()
     */
    const VLAN_MEMBERSHIP_VLAN_TYPE_STATIC = 1;

    /**
     * Constant for possible value of interface vlan type.
     *
     * @see vlanTypes()
     */
    const VLAN_MEMBERSHIP_VLAN_TYPE_DYNAMIC = 2;

    /**
     * Constant for possible value of interface vlan type.
     *
     * @see vlanTypes()
     */
    const VLAN_MEMBERSHIP_VLAN_TYPE_MULTI_VLAN = 3;

    /**
     * Text representation of interface vlan type.
     *
     * @see vlanTypes()
     *
     * @var array Text representations of interface vlan type.
     */
    public static $VLAN_MEMBERSHIP_VLAN_TYPES = [
        self::VLAN_MEMBERSHIP_VLAN_TYPE_STATIC => 'static',
        self::VLAN_MEMBERSHIP_VLAN_TYPE_DYNAMIC => 'dynamic',
        self::VLAN_MEMBERSHIP_VLAN_TYPE_MULTI_VLAN => 'multiVlan',
    ];

    /**
     * Get an array of the type of VLAN membership of each device port.
     *
     * E.g. the follow SNMP output yields the shown array:
     *
     * .1.3.6.1.4.1.9.9.68.1.2.2.1.1.10128 = INTEGER: static(1)
     * .1.3.6.1.4.1.9.9.68.1.2.2.1.1.10129 = INTEGER: dynamic(2)
     * ...
     * 
     *      [10128] => 1
     *      [10129] => 2
     *
     * @return array
     */
    public function vlanTypes($translate = false)
    {
        $states = $this->getSNMP()->walk1d(self::OID_VLAN_MEMBERSHIP_VLAN_TYPE);

        if (!$translate) {
            return $states;
        }

        return $this->getSNMP()->translate($states, self::$VLAN_MEMBERSHIP_VLAN_TYPES);
    }

    /**
     * Get an array of the VLAN id of each device port in access mode.
     *
     * E.g. the follow SNMP output yields the shown array:
     *
     * .1.3.6.1.4.1.9.9.68.1.2.2.1.2.10128 = INTEGER: 1
     * .1.3.6.1.4.1.9.9.68.1.2.2.1.2.10129 = INTEGER: 10
     * ...
     *
     *      [10128] => 1
     *      [10129] => 10
     */
    public function vlans()
    {
        return $this->getSNMP()->walk1d(self::OID_VLAN_MEMBERSHIP_VLAN);
    }

    /**
     * Constant for possible value of the current VLAN status of the port.
     *
     * @see portStatus()
     */
    const VLAN_MEMBERSHIP_PORT_STATUS_INACTIVE = 1;

    /**
     * Constant for possible value of the current VLAN status of the port.
     *
     * @see portStatus()
     */
    const VLAN_MEMBERSHIP_PORT_STATUS_ACTIVE = 2;

    /**
     * Constant for possible value of the current VLAN status of the port.
     *
     * @see portStatus()
     */
    const VLAN_MEMBERSHIP_PORT_STATUS_SHUTDOWN = 3;

    /**
     * Text representation of VLAN status of the port.
     *
     * @see vlanTypes()
     *
     * @var array Text representations of interface vlan type.
     */
    public static $VLAN_MEMBERSHIP_PORT_STATUS = [
        self::VLAN_MEMBERSHIP_PORT_STATUS_INACTIVE => 'inactive',
        self::VLAN_MEMBERSHIP_PORT_STATUS_ACTIVE => 'active',
        self::VLAN_MEMBERSHIP_PORT_STATUS_SHUTDOWN => 'shutdown',
    ];

    /**
     * Get an array of the current status of VLAN in each device port.
     *
     * .1.3.6.1.4.1.9.9.68.1.2.2.1.1.10128 = INTEGER: inactive(1)
     * .1.3.6.1.4.1.9.9.68.1.2.2.1.1.10129 = INTEGER: active(2)
     * ...
     *
     *      [10128] => 1
     *      [10129] => 2
     *
     * @param bool $translate If true, return the string representation.
     *
     * @return array An array with the current VLAN status of ports.
     */
    public function portStatus($translate = false)
    {
        $states = $this->getSNMP()->walk1d(self::OID_VLAN_MEMBERSHIP_PORT_STATUS);

        if (!$translate) {
            return $states;
        }

        return $this->getSNMP()->translate($states, self::$VLAN_MEMBERSHIP_PORT_STATUS);
    }
}
