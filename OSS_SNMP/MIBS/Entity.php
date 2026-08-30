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

namespace OSS_SNMP\MIBS;

/**
 * A class for performing SNMP V2 queries on generic devices
 *
 * @copyright Copyright (c) 2012 - 2013, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @author Sergio Gómez <sergio@uco.es>
 */
class Entity extends \OSS_SNMP\MIB
{
    const OID_ENTITY_PHYSICAL_DESCRIPTION    = '.1.3.6.1.2.1.47.1.1.1.1.2';
    const OID_ENTITY_PHYSICAL_VENDOR_TYPE    = '.1.3.6.1.2.1.47.1.1.1.1.3';
    const OID_ENTITY_PHYSICAL_CONTAINED_IN   = '.1.3.6.1.2.1.47.1.1.1.1.4';
    const OID_ENTITY_PHYSICAL_CLASS          = '.1.3.6.1.2.1.47.1.1.1.1.5';
    const OID_ENTITY_PHYSICAL_PARENT_REL_POS = '.1.3.6.1.2.1.47.1.1.1.1.6';
    const OID_ENTITY_PHYSICAL_NAME           = '.1.3.6.1.2.1.47.1.1.1.1.7';
    const OID_ENTITY_PHYSICAL_HARDWARE_REV   = '.1.3.6.1.2.1.47.1.1.1.1.8';
    const OID_ENTITY_PHYSICAL_FIRMWARE_REV   = '.1.3.6.1.2.1.47.1.1.1.1.9';
    const OID_ENTITY_PHYSICAL_SOFTWARE_REV   = '.1.3.6.1.2.1.47.1.1.1.1.10';
    const OID_ENTITY_PHYSICAL_SERIALNUM      = '.1.3.6.1.2.1.47.1.1.1.1.11';
    const OID_ENTITY_PHYSICAL_MFG_NAME       = '.1.3.6.1.2.1.47.1.1.1.1.12';
    const OID_ENTITY_PHYSICAL_MODEL_NAME     = '.1.3.6.1.2.1.47.1.1.1.1.13';
    const OID_ENTITY_PHYSICAL_ALIAS          = '.1.3.6.1.2.1.47.1.1.1.1.14';
    const OID_ENTITY_PHYSICAL_ASSET_ID       = '.1.3.6.1.2.1.47.1.1.1.1.15';
    const OID_ENTITY_PHYSICAL_IS_FRU         = '.1.3.6.1.2.1.47.1.1.1.1.16';

    /**
     * Returns an associate array of entPhysicalDescr
     *
     * e.g.
     *
     *     [1] = STRING: "Cisco Systems Catalyst 6500 9-slot Chassis System"
     *     [2] = STRING: "Cisco Systems Catalyst 6500 9-slot Physical Slot"
     *     [3] = STRING: "Cisco Systems Catalyst 6500 9-slot Physical Slot"
     *     [4] = STRING: "Cisco Systems Catalyst 6500 9-slot Physical Slot"
     *
     *
     *
     * @return array Associate array of entPhysicalDescr
     */
    public function physicalDescription()
    {
        return $this->getSNMP()->walk1d( self::OID_ENTITY_PHYSICAL_DESCRIPTION );
    }

    /**
     * Returns an associate array of entPhysicalName
     *
     * e.g.
     *
     *     [1] = STRING: "WS-C6509-E"
     *     [2] = STRING: "Physical Slot 1"
     *     [3] = STRING: "Physical Slot 2"
     *     [4] = STRING: "Physical Slot 3"
     *
     *
     *
     * @return array Associate array of entPhysicalName
     */
    public function physicalName()
    {
        return $this->getSNMP()->walk1d( self::OID_ENTITY_PHYSICAL_NAME );
    }

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_CHASSIS = 3;

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_CONTAINER = 5;

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_POWER_SUPPLY = 6;

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_FAN = 7;

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_SENSOR = 8;

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_MODULE = 9;

    /**
     * Physical entitly class type
     * @var int Physical entitly class type
     */
    const PHYSICAL_CLASS_PORT = 10;


    /**
     * Translator array for physical class types
     * @var array Translator array for physical class types
     */
    public static $ENTITY_PHSYICAL_CLASS = array(
        self::PHYSICAL_CLASS_CHASSIS      => 'chassis',
        self::PHYSICAL_CLASS_CONTAINER    => 'container',
        self::PHYSICAL_CLASS_POWER_SUPPLY => 'powerSupply',
        self::PHYSICAL_CLASS_FAN          => 'fan',
        self::PHYSICAL_CLASS_SENSOR       => 'sensor',
        self::PHYSICAL_CLASS_MODULE       => 'module',
        self::PHYSICAL_CLASS_PORT         => 'port'
    );

    /**
     * Returns an associate array of entPhysicalClass
     *
     * e.g.  [1005] => 10 / port
     *
     *
     * @param boolean $translate If true, return the string representation via self::$ENTITY_PHSYICAL_CLASS
     * @return array Associate array of entPhysicalClass
     */
    public function physicalClass( $translate = false )
    {
        $classes = $this->getSNMP()->walk1d( self::OID_ENTITY_PHYSICAL_CLASS );

        if( !$translate )
            return $classes;

        return $this->getSNMP()->translate( $classes, self::$ENTITY_PHSYICAL_CLASS );
    }


    /**
     * Returns an associate array of entPhysicalParentRelPos
     *
     * e.g.  [1005] => 1
     *
     *
     * @return array Associate array of entPhysicalParentRelPos
     */
    public function physicalParentRelPos()
    {
        return $this->getSNMP()->walk1d( self::OID_ENTITY_PHYSICAL_PARENT_REL_POS );
    }

    /**
     * Returns an associate array of physical aliases
     *
     * e.g.  [1005] => 10001
     *
     *
     * @return array Associate array of physical aliases
     */
    public function physicalAlias()
    {
        return $this->getSNMP()->walk1d( self::OID_ENTITY_PHYSICAL_ALIAS );
    }


    /**
     * Utility function for MIBS\Cisco\RSTP::rstpPortRole() to try and translate a port index
     * into a port ID
     *
     * Makes a number of assumptions including that it has to be of type port, that the ID must be >10000,
     * etc.
     *
     * @return Array of relative positions to port IDs
     */
    public function relPosToAlias()
    {
        $rtn = [];
        $aliases = $this->physicalAlias();
        foreach( $this->physicalParentRelPos() as $index => $pos )
        {
            if( isset( $aliases[ $index ] ) && strlen( $aliases[ $index ] )
                    && is_numeric( $aliases[ $index ] ) && $aliases[ $index ] > 10000
                    && !isset( $rtn[ $pos ] ) && $this->physicalClass()[ $index ] == self::PHYSICAL_CLASS_PORT )
                $rtn[ $pos ] = $aliases[ $index ];
        }

        return $rtn;
    }
    
    /**
     * Returns an associate array of entPhysicalSerialNum
     *
     * e.g.
     *
     *     [1001] = STRING: "FOC16829FD54"
     *     [1002] = STRING: ""
     *     [1003] = STRING: ""
     *     [1004] = STRING: ""
     *
     * @return array Associate array of entPhysicalSerialNum
     */
    public function physicalSerialNum()
    {
        return $this->getSNMP()->walk1d( self::OID_ENTITY_PHYSICAL_SERIALNUM );
    }


    /**
     * Returns an associate array of entPhysicalVendorType
     *
     * e.g.
     *
     *     [1] => .1.3.6.1.4.1.9.12.3.1.3.144
     *     [2] => .1.3.6.1.4.1.9.12.3.1.5.1
     *     [3] => .1.3.6.1.4.1.9.12.3.1.5.1
     *     [4] => .1.3.6.1.4.1.9.12.3.1.5.1
     *
     * @return array Associate array of entPhysicalVendorType
     */
    public function physicalVendorType()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_VENDOR_TYPE);
    }

    /**
     * Returns an associate array of entPhysicalContainedIn
     *
     * e.g.
     *
     *     [1] => 0
     *     [2] => 1
     *     [3] => 1
     *     [4] => 1
     *
     * @return array Associate array of entPhysicalContainedIn
     */
    public function physicalContainedIndex()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_CONTAINED_IN);
    }

    /**
     * Returns an associate array of entPhysicalHardwareRev
     *
     * e.g.
     *
     *     [1] => V2
     *     [2] =>
     *     [3] =>
     *     [4] =>
     *
     * @return array Associate array of entPhysicalHardwareRev
     */
    public function physicalHardwareRevision()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_HARDWARE_REV);
    }

    /**
     * Returns an associate array of entPhysicalFirmwareRev
     *
     * e.g.
     *
     *     [1] => 12.1(22)EA14
     *     [2] =>
     *     [3] =>
     *     [4] =>
     *
     * @return array Associate array of entPhysicalFirmwareRev
     */
    public function physicalFirmwareRevision()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_FIRMWARE_REV);
    }

    /**
     * Returns an associate array of entPhysicalSoftwareRev
     *
     * e.g.
     *
     *     [1] => 12.1(22)EA14
     *     [2] =>
     *     [3] =>
     *     [4] =>
     *
     * @return array Associate array of entPhysicalSoftwareRev
     */
    public function physicalSoftwareRevision()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_SOFTWARE_REV);
    }

    /**
     * Returns an associate array of entPhysicalMfgName
     *
     * e.g.
     *
     *     [1] => cisco
     *     [2] => cisco
     *     [3] => cisco
     *     [4] => cisco
     *
     * @return array Associate array of entPhysicalMfgName
     */
    public function physicalManufacturerName()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_MFG_NAME);
    }

    /**
     * Returns an associate array of entPhysicalModelName
     *
     * e.g.
     *
     *     [1] => WS-C6509-E
     *     [2] =>
     *     [3] =>
     *     [4] =>
     *
     * @return array Associate array of entPhysicalModelName
     */
    public function physicalModelName()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_MODEL_NAME);
    }

    /**
     * Returns an associate array of entPhysicalAssetID
     *
     * @return array Associate array of entPhysicalAssetID
     */
    public function physicalAssetId()
    {
        return $this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_ASSET_ID);
    }

    /**
     * Returns an associate array of entPhysicalIsFRU
     *
     * e.g.
     *
     *     [1] => true
     *     [2] => false
     *     [3] => false
     *     [4] => false
     *
     * @return array Associate array of entPhysicalIsFRU
     */
    public function physicalIsFRU()
    {
        return $this->getSNMP()->ppTruthValue($this->getSNMP()->walk1d(self::OID_ENTITY_PHYSICAL_IS_FRU));
    }
}
