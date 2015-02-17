<?php

/*
    Copyright (c) 2013, Open Source Solutions Limited, Dublin, Ireland
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

namespace OSS_SNMP\MIBS\Foundry;

/**
 * A class for performing SNMP V2 queries on Foundry devices
 *
 * @copyright Copyright (c) 2013, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Chassis extends \OSS_SNMP\MIBS\Foundry
{

    const OID_ACTUAL_TEMPERATURE   = '.1.3.6.1.4.1.1991.1.1.1.1.18.0';
    const OID_WARNING_TEMPERATURE  = '.1.3.6.1.4.1.1991.1.1.1.1.19.0';
    const OID_SHUTDOWN_TEMPERATURE = '.1.3.6.1.4.1.1991.1.1.1.1.20.0';

    const OID_PSU_DESCRIPTION      = '.1.3.6.1.4.1.1991.1.1.1.2.1.1.2';
    const OID_PSU_STATE            = '.1.3.6.1.4.1.1991.1.1.1.2.1.1.3';

    const OID_FAN_DESCRIPTION      = '.1.3.6.1.4.1.1991.1.1.1.3.1.1.2';
    const OID_FAN_STATE            = '.1.3.6.1.4.1.1991.1.1.1.3.1.1.3';

    const OID_CPU_1SEC_UTILISATION = '.1.3.6.1.4.1.1991.1.1.2.1.50.0';
    const OID_CPU_5SEC_UTILISATION = '.1.3.6.1.4.1.1991.1.1.2.1.51.0';
    const OID_CPU_1MIN_UTILISATION = '.1.3.6.1.4.1.1991.1.1.2.1.52.0';

    const OID_MEMORY_UTILISATION   = '.1.3.6.1.4.1.1991.1.1.2.1.53.0';
    const OID_MEMORY_TOTAL         = '.1.3.6.1.4.1.1991.1.1.2.1.54.0';
    const OID_MEMORY_FREE          = '.1.3.6.1.4.1.1991.1.1.2.1.55.0';


    const OID_GLOBAL_QUEUE_OVERFLOW  = '.1.3.6.1.4.1.1991.1.1.2.1.30.0';
    const OID_GLOBAL_BUFFER_SHORTAGE = '.1.3.6.1.4.1.1991.1.1.2.1.31.0';
    const OID_GLOBAL_DMA_FAILURE     = '.1.3.6.1.4.1.1991.1.1.2.1.32.0';
    const OID_GLOBAL_RESOURCE_LOW    = '.1.3.6.1.4.1.1991.1.1.2.1.33.0';
    const OID_GLOBAL_EXCESSIVE_ERROR = '.1.3.6.1.4.1.1991.1.1.2.1.34.0';

    const OID_SERIAL_NUMBER          = '.1.3.6.1.4.1.1991.1.1.1.1.2.0';

    /**
     * Get the device's chassis temperature
     *
     *
     * > "Temperature of the chassis. Each unit is 0.5 degrees Celcius.
     * > Only management module built with temperature sensor hardware
     * > is applicable. For those non-applicable management module, it
     * > returns no-such-name."
     *
     * @return int The device's chassis temperature
     */
    public function actualTemperature()
    {
        return $this->getSNMP()->get( self::OID_ACTUAL_TEMPERATURE );
    }

    /**
     * Get the device's chassis temperature warning threshold
     *
     *
     * > "Actual temperature higher than this threshold value will trigger
     * > the switch to send a temperature warning trap. Each unit is 0.5
     * > degrees Celcius. Only management module built with temperature
     * > sensor hardware is applicable. For those non-applicable management
     * > module, it returns no-such-name."
     *
     * @return int The device's chassis temperature warning threshold
     */
    public function warningTemperature()
    {
        return $this->getSNMP()->get( self::OID_WARNING_TEMPERATURE );
    }

    /**
     * Get the device's chassis shutdown temperature
     *
     *
     * > "Actual temperature higher than this threshold value will shutdown
     * > a partial of the switch hardware to cool down the system. Each unit
     * > is 0.5 degrees Celcius. Only management module built with temperature
     * > sensor hardware is applicable. For those non-applicable management
     * > module, it returns no-such-name"
     *
     * @return int The device's chassis shutdown temperature
     */
    public function shutdownTemperature()
    {
        return $this->getSNMP()->get( self::OID_SHUTDOWN_TEMPERATURE );
    }


    /**
     * Get the descriptions of the chassis' PSUs
     *
     * @return array Descriptions of the chassis' PSUs
     */
    public function psuDescriptions()
    {
        return $this->getSNMP()->walk1d( self::OID_PSU_DESCRIPTION );
    }


    /**
     * Constant for possible value of chassis PSU state - other (1)
     * @see psuStates()
     */
    const PSU_STATE_OTHER = 1;

    /**
         * Constant for possible value of chassis PSU state - normal (2)
     * @see psuStates()
     */
    const PSU_STATE_NORMAL = 2;

    /**
     * Constant for possible value of chassis PSU state - failure (3)
     * @see psuStates()
     */
    const PSU_STATE_FAILURE = 3;

    /**
     * Text representation of PSU states
     *
     * @see psuStates()
     * @var array Text representations of PSU states
     */
    public static $PSU_STATES = [
        self::PSU_STATE_OTHER     => 'other',
        self::PSU_STATE_NORMAL    => 'normal',
        self::PSU_STATE_FAILURE   => 'failure'
    ];


    /**
     * Get the device's PSU states
     *
     * @see $PSU_STATES
     *
     * @param boolean $translate If true, return the string representation via self::$PSU_STATES
     * @return array The device's PSU states
     */
    public function psuStates( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_PSU_STATE );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$PSU_STATES );
    }

    /**
     * Get the descriptions of the chassis' fans
     *
     * @return array Descriptions of the chassis' fans
     */
    public function fanDescriptions()
    {
        return $this->getSNMP()->walk1d( self::OID_FAN_DESCRIPTION );
    }


    /**
     * Constant for possible value of chassis fan state - other (1)
     * @see fanStates()
     */
    const FAN_STATE_OTHER = 1;

    /**
         * Constant for possible value of chassis fan state - normal (2)
     * @see fanStates()
     */
    const FAN_STATE_NORMAL = 2;

    /**
     * Constant for possible value of chassis fan state - failure (3)
     * @see fanStates()
     */
    const FAN_STATE_FAILURE = 3;

    /**
     * Text representation of fan states
     *
     * @see fanStates()
     * @var array Text representations of fan states
     */
    public static $FAN_STATES = [
        self::FAN_STATE_OTHER     => 'other',
        self::FAN_STATE_NORMAL    => 'normal',
        self::FAN_STATE_FAILURE   => 'failure'
    ];


    /**
     * Get the device's fan states
     *
     * @see $FAN_STATES
     *
     * @param boolean $translate If true, return the string representation via self::$FAN_STATES
     * @return array The device's fan states
     */
    public function fanStates( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_FAN_STATE );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$FAN_STATES );
    }



    /**
     * Get the device's CPU utilisation - 1 sec average
     *
     * @return int The device's CPU utilisation - 1 sec average
     */
    public function cpu1secUtilisation()
    {
        return $this->getSNMP()->get( self::OID_CPU_1SEC_UTILISATION );
    }

    /**
     * Get the device's CPU utilisation - 5 sec average
     *
     * @return int The device's CPU utilisation - 5 sec average
     */
    public function cpu5secUtilisation()
    {
        return $this->getSNMP()->get( self::OID_CPU_5SEC_UTILISATION );
    }

    /**
     * Get the device's CPU utilisation - 1 min average
     *
     * @return int The device's CPU utilisation - 1 min average
     */
    public function cpu1minUtilisation()
    {
        return $this->getSNMP()->get( self::OID_CPU_1MIN_UTILISATION );
    }



    /**
     * Get the device's serial number
     *
     * > The serial number of the chassis. If the
     * > serial number is unknown or unavailable then
     * > the value should be a zero length string.
     *
     * @see http://www.mibdepot.com/cgi-bin/getmib3.cgi?win=mib_a&i=1&n=FOUNDRY-SN-AGENT-MIB&r=foundry&f=sn_agent.mib&v=v1&t=sca&o=snChasSerNum
     *
     * @return string The chassis serial number
     */
    public function serialNumber()
    {
        return $this->getSNMP()->get( self::OID_SERIAL_NUMBER );
    }



    /**
     * Get the device's dynamic memory utilisation (percentage)
     *
     * > "The system dynamic memory utilization, in unit of percentage"
     *
     * @return int The device's dynamic memory usage utilisation
     */
    public function memoryUtilisation()
    {
        return $this->getSNMP()->get( self::OID_MEMORY_UTILISATION );
    }

    /**
     * Get the device's total memory capacity (bytes)
     *
     * @return int The device's total memory capacity (bytes)
     */
    public function memoryTotal()
    {
        return $this->getSNMP()->get( self::OID_MEMORY_TOTAL );
    }

    /**
     * Get the device's dynamic memory available / free (bytes)
     *
     * @return int The device's dynamic memory available / free (bytes)
     */
    public function memoryFree()
    {
        return $this->getSNMP()->get( self::OID_MEMORY_FREE );
    }


    /**
     * Are the device queues in overflow?
     *
     * @return bool Queues in overflow
     */
    public function isQueueOverflow()
    {
        return $this->getSNMP()->get( self::OID_GLOBAL_QUEUE_OVERFLOW );
    }

    /**
     * Is the device buffers adequate
     *
     * @return bool Buffers adequate
     */
    public function isBufferShortage()
    {
        return $this->getSNMP()->get( self::OID_GLOBAL_BUFFER_SHORTAGE );
    }

    /**
     * Are the device's DMAs in good condition?
     *
     * @return bool DMAs in failure condition?
     */
    public function isDMAFailure()
    {
        return $this->getSNMP()->get( self::OID_GLOBAL_DMA_FAILURE );
    }

    /**
     * Does the device have a resource low warning?
     *
     * @return bool Does the device have a resource low warning?
     */
    public function isResourceLow()
    {
        return $this->getSNMP()->get( self::OID_GLOBAL_RESOURCE_LOW );
    }

    /**
     * Does the device have any excessive collision, FCS errors, alignment warning etc.
     *
     * @return bool Does the device have any excessive collision, FCS errors, alignment warning etc.
     */
    public function isExcessiveError()
    {
        return $this->getSNMP()->get( self::OID_GLOBAL_EXCESSIVE_ERROR );
    }

}
