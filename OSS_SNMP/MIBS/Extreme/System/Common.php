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

namespace OSS_SNMP\MIBS\Extreme\System;

/**
 * A class for performing SNMP V2 queries on Extreme devices
 *
 * These OIDs are from the private.extremenetworks.extremeAgent.extremeSystem.extremeSystemCommon tree
 *
 * @copyright Copyright (c) 2013 - 2014, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Common extends \OSS_SNMP\MIBS\Extreme\System
{

    const OID_OVER_TEMPERATURE_ALARM       = '.1.3.6.1.4.1.1916.1.1.1.7.0';
    const OID_CURRENT_TEMPERATURE          = '.1.3.6.1.4.1.1916.1.1.1.8.0';

    const OID_FAN_NUMBER                   = '.1.3.6.1.4.1.1916.1.1.1.9.1.1';
    const OID_FAN_OPERATIONAL              = '.1.3.6.1.4.1.1916.1.1.1.9.1.2';
    const OID_FAN_ENT_PHYSICAL_INDEX       = '.1.3.6.1.4.1.1916.1.1.1.9.1.3';
    const OID_FAN_SPEED                    = '.1.3.6.1.4.1.1916.1.1.1.9.1.4';


    const OID_POWER_SUPPLY_NUMBER                            = '.1.3.6.1.4.1.1916.1.1.1.27.1.1';
    const OID_POWER_SUPPLY_STATUS                            = '.1.3.6.1.4.1.1916.1.1.1.27.1.2';
    const OID_POWER_SUPPLY_INPUT_VOLTAGE                     = '.1.3.6.1.4.1.1916.1.1.1.27.1.3';
    const OID_POWER_SUPPLY_SERIAL_NUMBER                     = '.1.3.6.1.4.1.1916.1.1.1.27.1.4';
    const OID_POWER_SUPPLY_ENT_PHYSICAL_INDEX                = '.1.3.6.1.4.1.1916.1.1.1.27.1.5';
    const OID_POWER_SUPPLY_FAN1_SPEED                        = '.1.3.6.1.4.1.1916.1.1.1.27.1.6';
    const OID_POWER_SUPPLY_FAN2_SPEED                        = '.1.3.6.1.4.1.1916.1.1.1.27.1.7';
    const OID_POWER_SUPPLY_SOURCE                            = '.1.3.6.1.4.1.1916.1.1.1.27.1.8';
    const OID_POWER_SUPPLY_INPUT_POWER_USAGE                 = '.1.3.6.1.4.1.1916.1.1.1.27.1.9';
    const OID_POWER_MON_SUPPLY_NUM_OUTPUT                    = '.1.3.6.1.4.1.1916.1.1.1.27.1.10';
    const OID_POWER_SUPPLY_INPUT_POWER_USAGE_UNIT_MULTIPLIER = '.1.3.6.1.4.1.1916.1.1.1.27.1.11';


    const OID_SYSTEM_POWER_STATE      = '.1.3.6.1.4.1.1916.1.1.1.36.0';

    const OID_BOOT_TIME               = '.1.3.6.1.4.1.1916.1.1.1.37.0';

    /**
     * Alarm status of overtemperature sensor in device enclosure.
     *
     * @return bool
     */
    public function overTemperatureAlarm()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->get( self::OID_OVER_TEMPERATURE_ALARM ) );
    }

    /**
     * Current temperature in degrees celcius measured inside
     * device enclosure.
     *
     * @return int Current temperature in degrees celcius measured inside device enclosure.
     */
    public function currentTemperature()
    {
        return $this->getSNMP()->get( self::OID_CURRENT_TEMPERATURE );
    }

    /**
     * Get the identifiers of the cooling fans.
     *
     * Identifier of cooling fan, numbered from the front and/or
     * left side of device.
     *
     * E.g. from a X670V-48x:
     *
     *     [
     *         [101] => 101
     *         [102] => 102
     *         [103] => 103
     *         [104] => 104
     *         [105] => 105
     *         [106] => 106
     *     ]
     *
     * @return array Identifiers of the cooling fans. Indexed by the identifier.
     */
    public function fanNumbers()
    {
        return $this->getSNMP()->walk1d( self::OID_FAN_NUMBER );
    }

    /**
     * Operational status of a cooling fan.
     *
     * Boolean result, true if operationa
     *
     * @return array Operational status of the cooling fans (booleans). Indexed by the identifier (see `fanNumbers()`).
     */
    public function fanOperational()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_FAN_OPERATIONAL ) );
    }

    /**
     * The entity index for this fan entity in the entityPhysicalTable table of the
     * entity MIB.
     *
     * @return array The entity index for this fan entity in the entityPhysicalTable table of the entity MIB.  Indexed by the identifier (see `fanNumbers()`).
     */
    public function fanEntPhysicalIndex()
    {
        return $this->getSNMP()->walk1d( self::OID_FAN_ENT_PHYSICAL_INDEX );
    }

    /**
     * The speed (RPM) of a cooling fan in the fantray.
     *
     * E.g. from a X670V-48x:
     *
     *     [
     *         [101] => 4428
     *         [102] => 9273
     *         [103] => 4428
     *         [104] => 9273
     *         [105] => 4509
     *         [106] => 9452
     *     ]
     *
     * @return array The speed (RPM) of a cooling fan in the fantray. Indexed by the identifier (see `fanNumbers()`).
     */
    public function fanSpeed()
    {
        return $this->getSNMP()->walk1d( self::OID_FAN_SPEED );
    }



    /**
     * Get the identifiers of the power supplies
     *
     * E.g. from a X670V-48x:
     *
     *     [
     *         [1] => 1
     *         [2] => 2
     *     ]
     *
     * @return array Identifiers of the power supplies
     */
    public function powerSupplyNumbers()
    {
        return $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_NUMBER );
    }


    /**
     * Constant for possible value of chassis PSU state - notPresent (1)
     * @see powerSupplyStatus()
     */
    const POWER_SUPPLY_STATUS_NOT_PRESENT = 1;

    /**
     * Constant for possible value of chassis PSU state - presentOK (2)
     * @see powerSupplyStatus()
     */
    const POWER_SUPPLY_STATUS_PRESENT_OK = 2;

    /**
     * Constant for possible value of chassis PSU state - presentNotOK (3)
     * @see powerSupplyStatus()
     */
    const POWER_SUPPLY_STATUS_PRESENT_NOT_OK = 3;

    /**
     * Constant for possible value of chassis PSU state - presentPowerOff (4)
     * @see powerSupplyStatus()
     */
    const POWER_SUPPLY_STATUS_PRESENT_POWER_OFF = 4;


    /**
     * Text representation of PSU states
     *
     * @see powerSupplyStatus()
     * @var array Text representations of PSU states
     */
    public static $POWER_SUPPLY_STATES = [
        self::POWER_SUPPLY_STATUS_NOT_PRESENT          => 'notPresent',
        self::POWER_SUPPLY_STATUS_PRESENT_OK           => 'presentOK',
        self::POWER_SUPPLY_STATUS_PRESENT_NOT_OK       => 'presentNotOK',
        self::POWER_SUPPLY_STATUS_PRESENT_POWER_OFF    => 'presentPowerOff'
    ];

    /**
     * Get the identifiers of the power supplies
     *
     * E.g. from a X670V-48x without $translate:
     *
     *     [
     *         [1] => 2
     *         [2] => 2
     *     ]
     *
     * E.g. from a X670V-48x with $translate:
     *
     *     [
     *         [1] => "presentOK"
     *         [2] => "presentOK"
     *     ]
     *
     * @param boolean $translate If true, return the string representation via self::$POWER_SUPPLY_STATES
     * @return array Identifiers of the power supplies
     */
    public function powerSupplyStatus( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_STATUS );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$POWER_SUPPLY_STATES );
    }

    /**
    * Get the serial numbers of the power supplies
    *
    * @return array Serial numbers of the power supplies
    */
    public function powerSupplySerialNumbers()
    {
        return $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_SERIAL_NUMBER );
    }

    /**
     * The entity index for this psu entity in the entityPhysicalTable table of the
     * entity MIB.
     *
     * @return array The entity index for this psu entity in the entityPhysicalTable table of the entity MIB.  Indexed by the identifier (see `fanNumbers()`).
     */
    public function powerSupplyEntPhysicalIndex()
    {
        return $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_ENT_PHYSICAL_INDEX );
    }



    /**
     * Constant for possible value of negative PSU fan speeds
     * @see powerSupplyFan1Speed() and powerSupplyFan2Speed()
     */
    const POWER_SUPPLY_FAN_SPEED_NOT_PRESENT = -1;

    /**
     * Constant for possible value of negative PSU fan speeds
     * @see powerSupplyFan1Speed() and powerSupplyFan2Speed()
     */
    const POWER_SUPPLY_FAN_SPEED_NO_RPM_INFO = -2;


    /**
     * Text representation of PSU fan speed states
     *
     * @see powerSupplyFan1Speed() and powerSupplyFan2Speed()
     * @var array Text representations of PSU fan states
     */
    public static $POWER_SUPPLY_FAN_SPEED_STATES = [
        self::POWER_SUPPLY_FAN_SPEED_NOT_PRESENT          => 'notPresent',
        self::POWER_SUPPLY_FAN_SPEED_NO_RPM_INFO          => 'noRPMInfo'
    ];


    /**
     * The speed (RPM) of Fan-1 in the power supply unit.
     *
     * A negative result means:
     *
     * * -1 => not present
     * * -2 => no RPM info
     *
     * You can translate these via:
     *
     *     $host->translate(
     *         $host->useExtreme_System_Common()->powerSupplyFan2Speed(),
     *         OSS_SNMP\MIBS\Extreme\System\Common::$POWER_SUPPLY_FAN_SPEED_STATES
     *     );
     *
     * @return array The speed (RPM) of Fan-1 in the power supply unit. NB - check docs for negative result meanings.
     */
    public function powerSupplyFan1Speed()
    {
        return $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_FAN1_SPEED );
    }




    /**
     * The speed (RPM) of Fan-2 in the power supply unit.
     *
     * @see powerSupplyFan1Speed() for documentation
     *
     * @return array The speed (RPM) of Fan-2 in the power supply unit. NB - check docs for negative result meanings.
     */
    public function powerSupplyFan2Speed()
    {
        return $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_FAN2_SPEED );
    }



    /**
     * Constant for possible value of PSU source
     * @see powerSupplySource()
     */
    const POWER_SUPPLY_SOURCE_UNKNOWN = 1;

    /**
     * Constant for possible value of PSU source
     * @see powerSupplySource()
     */
    const POWER_SUPPLY_SOURCE_AC = 2;

    /**
     * Constant for possible value of PSU source
     * @see powerSupplySource()
     */
    const POWER_SUPPLY_SOURCE_DC = 3;

    /**
     * Constant for possible value of PSU source
     * @see powerSupplySource()
     */
    const POWER_SUPPLY_SOURCE_EXTERNAL_POWER_SUPPLY = 4;

    /**
     * Constant for possible value of PSU source
     * @see powerSupplySource()
     */
    const POWER_SUPPLY_SOURCE_INTERNAL_REDUNDANT = 5;

    /**
     * Text representation of PSU sources
     *
     * @see powerSupplySource()
     * @var array Text representations of PSU sources
     */
    public static $POWER_SUPPLY_SOURCES = [
        self::POWER_SUPPLY_SOURCE_UNKNOWN               => 'unknown',
        self::POWER_SUPPLY_SOURCE_AC                    => 'ac',
        self::POWER_SUPPLY_SOURCE_DC                    => 'dc',
        self::POWER_SUPPLY_SOURCE_EXTERNAL_POWER_SUPPLY => 'externalPowerSupply',
        self::POWER_SUPPLY_SOURCE_INTERNAL_REDUNDANT    => 'internalRedundant'
    ];


    /**
     * The power supply unit input source.
     *
     *
     * @param boolean $translate If true, return the string representation via self::$POWER_SUPPLY_SOURCES
     * @return array The power supply unit input source.
     */
    public function powerSupplySource( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_POWER_SUPPLY_SOURCE );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$POWER_SUPPLY_SOURCES );
    }



    /**
     * Constant for possible value of system power state
     * @see systemPowerState()
     */
    const SYSTEM_POWER_STATE_COMPUTING = 1;

    /**
     * Constant for possible value of system power state
     * @see systemPowerState()
     */
    const SYSTEM_POWER_STATE_SUFFICIENT_BUT_NOT_REDUNDANT_POWER = 2;

    /**
     * Constant for possible value of system power state
     * @see systemPowerState()
     */
    const SYSTEM_POWER_STATE_REDUNDANT_POWER_AVAILABLE = 3;

    /**
     * Constant for possible value of system power state
     * @see systemPowerState()
     */
    const SYSTEM_POWER_STATE_INSUFFICIENT_POWER = 4;

    /**
    * Text representation of power state
    *
    * @see systemPowerState()
    * @var array Text representations of system power state
    */
    public static $POWER_STATES = [
        self::SYSTEM_POWER_STATE_COMPUTING                          => 'computing',
        self::SYSTEM_POWER_STATE_SUFFICIENT_BUT_NOT_REDUNDANT_POWER => 'sufficientButNotRedundantPower',
        self::SYSTEM_POWER_STATE_REDUNDANT_POWER_AVAILABLE          => 'redundantPowerAvailable',
        self::SYSTEM_POWER_STATE_INSUFFICIENT_POWER                 => 'insufficientPower'
    ];

    /**
     * The system power state
     *
     * @param boolean $translate If true, return the string representation via self::$POWER_STATES
     * @return array The power state.
     */
    public function systemPowerState( $translate = false )
    {
        $states = $this->getSNMP()->get( self::OID_SYSTEM_POWER_STATE );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$POWER_STATES );
    }

    /**
     * The boot time expressed in standard time_t value.
     * When interpreted as an absolute time value, it
     * represents the number of seconds elapsed since 00:00:00
     * on January 1, 1970, Coordinated Universal Time (UTC)
     *
     * @return int Boot time as the number of seconds elapsed since 00:00:00 on January 1, 1970 (UTC)
     */
    public function bootTime()
    {
        return $this->getSNMP()->get( self::OID_BOOT_TIME );
    }

}
