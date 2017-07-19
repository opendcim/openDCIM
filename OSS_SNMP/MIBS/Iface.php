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
class Iface extends \OSS_SNMP\MIB
{
    const OID_IF_NUMBER                  = '.1.3.6.1.2.1.2.1.0';
    
    const OID_IF_INDEX                   = '.1.3.6.1.2.1.2.2.1.1';
    const OID_IF_DESCRIPTION             = '.1.3.6.1.2.1.2.2.1.2';
    const OID_IF_TYPE                    = '.1.3.6.1.2.1.2.2.1.3';
    const OID_IF_MTU                     = '.1.3.6.1.2.1.2.2.1.4';
    const OID_IF_SPEED                   = '.1.3.6.1.2.1.2.2.1.5';
    const OID_IF_PHYS_ADDRESS            = '.1.3.6.1.2.1.2.2.1.6';
    const OID_IF_ADMIN_STATUS            = '.1.3.6.1.2.1.2.2.1.7';
    const OID_IF_OPER_STATUS             = '.1.3.6.1.2.1.2.2.1.8';
    const OID_IF_LAST_CHANGE             = '.1.3.6.1.2.1.2.2.1.9';

    const OID_IF_IN_OCTETS               = '.1.3.6.1.2.1.2.2.1.10';
    const OID_IF_IN_UNICAST_PACKETS      = '.1.3.6.1.2.1.2.2.1.11';
    const OID_IF_IN_NON_UNICAST_PACKETS  = '.1.3.6.1.2.1.2.2.1.12';
    const OID_IF_IN_DISCARDS             = '.1.3.6.1.2.1.2.2.1.13';
    const OID_IF_IN_ERRORS               = '.1.3.6.1.2.1.2.2.1.14';
    const OID_IF_IN_UNKNOWN_PROTOCOLS    = '.1.3.6.1.2.1.2.2.1.15';

    const OID_IF_OUT_OCTETS              = '.1.3.6.1.2.1.2.2.1.16';
    const OID_IF_OUT_UNICAST_PACKETS     = '.1.3.6.1.2.1.2.2.1.17';
    const OID_IF_OUT_NON_UNICAST_PACKETS = '.1.3.6.1.2.1.2.2.1.18';
    const OID_IF_OUT_DISCARDS            = '.1.3.6.1.2.1.2.2.1.19';
    const OID_IF_OUT_ERRORS              = '.1.3.6.1.2.1.2.2.1.20';
    const OID_IF_OUT_QUEUE_LENGTH        = '.1.3.6.1.2.1.2.2.1.21';

    const OID_IF_NAME                    = '.1.3.6.1.2.1.31.1.1.1.1';
    const OID_IF_HIGH_SPEED              = '.1.3.6.1.2.1.31.1.1.1.15';
    const OID_IF_ALIAS                   = '.1.3.6.1.2.1.31.1.1.1.18';
    
    /**
     * Get the number of network interfaces (regardless of
     * their current state) present on this system.
     *
     * @return int The number of network interfaces on the system
     */
    public function numberOfInterfaces()
    {
        return $this->getSNMP()->get( self::OID_IF_NUMBER );
    }


    /**
     * Get an array of device MTUs
     *
     * @return array An array of device MTUs
     */
    public function mtus()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_MTU );
    }

    /**
     * Get an array of the interfaces' physical addresses
     *
     * "The interface's address at the protocol layer
     * immediately `below' the network layer in the
     * protocol stack.  For interfaces which do not have
     * such an address (e.g., a serial line), this object
     * should contain an octet string of zero length."
     *
     * @return array An array of device physical addresses
     */
    public function physAddresses()
    {
        $pa = $this->getSNMP()->walk1d( self::OID_IF_PHYS_ADDRESS );
        
        // some switches return leading '00:' as '0:' - we correct this here:
        foreach( $pa as $i => $a )
            if( strpos( $a, ':' ) == 1 )
                $pa[ $i ] = '0' . $a;
        
        return $pa;
    }



    /**
     * Constant for possible value of interface admin status.
     * @see adminStates()
     */
    const IF_ADMIN_STATUS_UP = 1;

    /**
     * Constant for possible value of interface admin status.
     * @see adminStates()
     */
    const IF_ADMIN_STATUS_DOWN = 2;

    /**
     * Constant for possible value of interface admin status.
     * @see adminStates()
     */
    const IF_ADMIN_STATUS_TESTING = 3;

    /**
     * Text representation of interface admin status.
     *
     * @see adminStates()
     * @var array Text representations of interface admin status.
     */
    public static $IF_ADMIN_STATES = array(
        self::IF_ADMIN_STATUS_UP      => 'up',
        self::IF_ADMIN_STATUS_DOWN    => 'down',
        self::IF_ADMIN_STATUS_TESTING => 'testing'
    );

    /**
     * Get an array of device interface admin status (up / down)
     *
     * E.g. the follow SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.2.2.1.7.10128 = INTEGER: up(1)
     * .1.3.6.1.2.1.2.2.1.7.10129 = INTEGER: down(2)
     * ...
     *
     *      [10128] => 1
     *      [10129] => 2
     *
     * @see IF_ADMIN_STATES
     * @param boolean $translate If true, return the string representation
     * @return array An array of interface admin states
     */
    public function adminStates( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_IF_ADMIN_STATUS );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$IF_ADMIN_STATES );
    }

    /**
     * Get an array of device interface last change times
     *
     * Value returned is timeticks (one hundreds of a second)
     *
     * "The value of sysUpTime at the time the interface
     * entered its current operational state.  If the
     * current state was entered prior to the last re-
     * initialization of the local network management
     * subsystem, then this object contains a zero
     * value."
     *
     * @see \OSS_SNMP\MIBS\System::uptime()
     * @param bool $asUnixTimestamp Poll sysUpTime and use this to return a timestamp of the last change
     * @return array Timeticks (or zero) since last change of the interfaces
     */
    public function lastChanges( $asUnixTimestamp = false )
    {
        $lc = $this->getSNMP()->walk1d( self::OID_IF_LAST_CHANGE );
        
        if( $asUnixTimestamp )
        {
            $sysUptime = $this->getSNMP()->useSystem()->uptime() / 100;
            
            foreach( $lc as $i => $t )
                if( $t )
                    $lc[$i] = intval( floor( time() - $sysUptime + ( $t / 100 ) ) );
        }
        
        return $lc;
    }

    /**
     * Get an array of device interface in octets
     *
     * "The total number of octets received on the
     * interface, including framing characters."
     *
     * @return array The total number of octets received on interfaces
     */
    public function inOctets()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_IN_OCTETS );
    }

    /**
     * Get an array of device interface unicast packets in
     *
     * "The number of subnetwork-unicast packets
     * delivered to a higher-layer protocol."
     *
     * @return array The total number of unicast packets received on interfaces
     */
    public function inUnicastPackets()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_IN_UNICAST_PACKETS );
    }

    /**
     * Get an array of device interface non-unicast packets in
     *
     * "The number of non-unicast (i.e., subnetwork-
     * broadcast or subnetwork-multicast) packets
     * delivered to a higher-layer protocol."
     *
     * @return array The total number of non-unicast packets received on interfaces
     */
    public function inNonUnicastPackets()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_IN_NON_UNICAST_PACKETS );
    }

    /**
     * Get an array of device interface inbound discarded packets
     *
     * "The number of inbound packets which were chosen
     * to be discarded even though no errors had been
     * detected to prevent their being deliverable to a
     * higher-layer protocol.  One possible reason for
     * discarding such a packet could be to free up
     * buffer space."
     *
     * @return arrary The total number of discarded inbound packets received on interfaces
     */
    public function inDiscards()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_IN_DISCARDS );
    }

    /**
     * Get an array of device interface inbound error packets
     *
     * "The number of inbound packets that contained
     * errors preventing them from being deliverable to a
     * higher-layer protocol."
     *
     * @return array The total number of error inbound packets received on interfaces
     */
    public function inErrors()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_IN_ERRORS );
    }







    /**
     * Get an array of device interface out octets
     *
     * "The total number of octets transmitted out of the
     * interface, including framing characters."
     *
     * @return array The total number of octets transmitted on interfaces
     */
    public function outOctets()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_OUT_OCTETS );
    }

    /**
     * Get an array of device interface unicast packets out
     *
     * "The total number of packets that higher-level
     * protocols requested be transmitted to a
     * subnetwork-unicast address, including those that
     * were discarded or not sent."
     *
     * @return array The total number of unicast packets transmitted on interfaces
     */
    public function outUnicastPackets()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_OUT_UNICAST_PACKETS );
    }

    /**
     * Get an array of device interface non-unicast packets out
     *
     * "The total number of packets that higher-level
     * protocols requested be transmitted to a non-
     * unicast (i.e., a subnetwork-broadcast or
     * subnetwork-multicast) address, including those
     * that were discarded or not sent."
     *
     * @return array The total number of non-unicast packets requested sent interfaces
     */
    public function outNonUnicastPackets()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_OUT_NON_UNICAST_PACKETS );
    }

    /**
     * Get an array of device interface outbound discarded packets
     *
     * "The number of outbound packets which were chosen
     * to be discarded even though no errors had been
     * detected to prevent their being transmitted.  One
     * possible reason for discarding such a packet could
     * be to free up buffer space."
     *
     * @return arrary The total number of discarded outbound packets
     */
    public function outDiscards()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_OUT_DISCARDS );
    }

    /**
     * Get an array of device interface outbound error packets
     *
     * "The number of outbound packets that could not be
     * transmitted because of errors."
     *
     * @return array The total number of error outbound packets received on interfaces
     */
    public function outErrors()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_OUT_ERRORS );
    }

    /**
     * Get an array of interface outbound queue lengths
     *
     * "The length of the output packet queue (in packets)"
     *
     * @return array The total number of packets in the outbound queues
     */
    public function outQueueLength()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_OUT_QUEUE_LENGTH );
    }





    /**
     * Get an array of packets received on an interface of unknown protocol
     *
     * "The number of packets received via the interface
     * which were discarded because of an unknown or
     * unsupported protocol."
     *
     * @return array The number of packets received on an interface of unknown protocol
     */
    public function inUnknownProtocols()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_IN_UNKNOWN_PROTOCOLS );
    }

    /**
     * Get an array of device interface indexes
     *
     * E.g. the following SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.2.2.1.1 = INTEGER: 1
     * .1.3.6.1.2.1.2.2.1.2 = INTEGER: 2
     * ...
     *
     *      [1] => 1
     *      [2] => 2
     *
     * @return array An array of interface indexes
     */
    public function indexes()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_INDEX );
    }

    /**
     * Get an array of device interface names
     *
     * E.g. the following SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.31.1.1.1.1.10128 = STRING: Gi1/0/28
     * .1.3.6.1.2.1.31.1.1.1.1.10129 = STRING: Gi1/0/29
     * ...
     *
     *      [10128] => "Gi1/0/28"
     *      [10129] => "Gi1/0/29"
     *
     * @return array An array of interface names
     */
    public function names()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_NAME );
    }

    /**
     * Get an array of device interface aliases (e.g. as set by the interface description / port-name parameter)
     *
     * E.g. the followig SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.2.2.1.2.18.10128 = STRING: Connection to switch2
     * .1.3.6.1.2.1.2.2.1.2.18.10129 = STRING: Connection to switch3
     * ...
     *
     *      [10128] => "Connection to switch2"
     *      [10129] => "Connection to switch3"
     *
     * @return array An array of interface aliases
     */
    public function aliases()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_ALIAS );
    }

    /**
     * Get an array of device interface descriptions
     *
     * E.g. the following SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.31.1.1.1.1.10128 = STRING: GigabitEthernet1/0/28
     * .1.3.6.1.2.1.31.1.1.1.1.10129 = STRING: GigabitEthernet1/0/29
     * ...
     *
     *      [10128] => "GigabitEthernet1/0/28"
     *      [10129] => "GigabitEthernet1/0/29"
     *
     * @return array An array of interface descriptions
     */
    public function descriptions()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_DESCRIPTION );
    }

    /**
     * Get an array of device interface (operating) speeds
     *
     * E.g. the following SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.2.2.1.5.10128 = Gauge32: 1000000000
     * .1.3.6.1.2.1.2.2.1.5.10129 = Gauge32: 100000000
     * ...
     *
     *      [10128] => 1000000000
     *      [10129] => 100000000
     *
     * NB: operating speed as opposed to maximum speed
     *
     * **WARNING:** This is a 32 bit int so it cannot represent 10Gb
     * links. These would show up as:
     *
     *      [10127] => 4294967295
     *
     * Instead, use highSpeeds() which will represent the speed as Mbps
     *
     * @see highSpeeds()
     * @return array An array of interface operating speeds
     */
    public function speeds()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_SPEED );
    }

    /**
     * Get an array of device interface (operating) speeds
     *
     * From Cisco:
     *
     * > "An estimate of the interface's current bandwidth in units
     * > of 1,000,000 bits per second. If this object reports a
     * > value of `n' then the speed of the interface is somewhere in
     * > the range of `n-500,000' to `n+499,999'. For interfaces
     * > which do not vary in bandwidth or for those where no
     * > accurate estimation can be made, this object should contain
     * > the nominal bandwidth. For a sub-layer which has no concept
     * > of bandwidth, this object should be zero."
     *
     * E.g. the following SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.2.2.1.5.10127 = Gauge32: 10000
     * .1.3.6.1.2.1.2.2.1.5.10128 = Gauge32: 1000
     * .1.3.6.1.2.1.2.2.1.5.10129 = Gauge32: 100
     * ...
     *
     *      [10127] => 10000000000
     *      [10128] => 1000000000
     *      [10129] => 100000000
     *
     * @return array An array of interface operating speeds
     */
    public function highSpeeds()
    {
        return $this->getSNMP()->walk1d( self::OID_IF_HIGH_SPEED );
    }
    
    
    
    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_UP               = 1;
    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_DOWN             = 2;
    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_TESTING          = 3;
    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_UNKNOWN          = 4;
    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_DORMANT          = 5;
    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_NOT_PRESENT      = 6;

    /**
     * Constant for possible value of interface operation status.
     * @see operationStates()
     */
    const IF_OPER_STATUS_LOWER_LAYER_DOWN = 7;

    /**
     * Text representation of interface operating status.
     *
     * @see operationStates()
     * @var array Text representations of interface operating status.
     */
    public static $IF_OPER_STATES = array(
        self::IF_OPER_STATUS_UP                => 'up',
        self::IF_OPER_STATUS_DOWN              => 'down',
        self::IF_OPER_STATUS_TESTING           => 'testing',
        self::IF_OPER_STATUS_UNKNOWN           => 'unknown',
        self::IF_OPER_STATUS_DORMANT           => 'dormant',
        self::IF_OPER_STATUS_NOT_PRESENT       => 'notPresent',
        self::IF_OPER_STATUS_LOWER_LAYER_DOWN  => 'lowerLayerDown'
    );

    /**
     * Get an array of device interface operating status (up / down)
     *
     * E.g. the follow SNMP output yields the shown array:
     *
     * .1.3.6.1.2.1.2.2.1.8.10128 = INTEGER: up(1)
     * .1.3.6.1.2.1.2.2.1.8.10129 = INTEGER: down(2)
     * ...
     *
     *      [10128] => 1
     *      [10129] => 2
     *
     * @see IF_OPER_STATES
     * @param boolean $translate If true, return the string representation
     * @return array An array of interface states
     */
    public function operationStates( $translate = false )
    {
        $states = $this->getSNMP()->walk1d( self::OID_IF_OPER_STATUS );

        if( !$translate )
            return $states;

        return $this->getSNMP()->translate( $states, self::$IF_OPER_STATES );
    }


    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_OTHER = 1;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_REGULAR1822 = 2;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HDH1822 = 3;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DDNX25 = 4;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RFC877X25 = 5;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ETHERNETCSMACD = 6;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88023CSMACD = 7;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88024TOKENBUS = 8;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88025TOKENRING = 9;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88026MAN = 10;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_STARLAN = 11;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROTEON10MBIT = 12;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROTEON80MBIT = 13;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HYPERCHANNEL = 14;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FDDI = 15;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_LAPB = 16;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SDLC = 17;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DS1 = 18;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_E1 = 19;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_BASICISDN = 20;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PRIMARYISDN = 21;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPPOINTTOPOINTSERIAL = 22;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PPP = 23;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SOFTWARELOOPBACK = 24;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_EON = 25;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ETHERNET3MBIT = 26;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_NSIP = 27;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SLIP = 28;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ULTRA = 29;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DS3 = 30;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SIP = 31;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRAMERELAY = 32;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RS232 = 33;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PARA = 34;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ARCNET = 35;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ARCNETPLUS = 36;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATM = 37;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MIOX25 = 38;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SONET = 39;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_X25PLE = 40;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88022LLC = 41;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_LOCALTALK = 42;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SMDSDXI = 43;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRAMERELAYSERVICE = 44;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_V35 = 45;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HSSI = 46;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HIPPI = 47;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MODEM = 48;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_AAL5 = 49;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SONETPATH = 50;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SONETVT = 51;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SMDSICIP = 52;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPVIRTUAL = 53;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPMULTIPLEXOR = 54;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IEEE80212 = 55;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FIBRECHANNEL = 56;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HIPPIINTERFACE = 57;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRAMERELAYINTERCONNECT = 58;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_AFLANE8023 = 59;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_AFLANE8025 = 60;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_CCTEMUL = 61;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FASTETHER = 62;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISDN = 63;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_V11 = 64;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_V36 = 65;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_G703AT64K = 66;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_G703AT2MB = 67;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_QLLC = 68;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FASTETHERFX = 69;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_CHANNEL = 70;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IEEE80211 = 71;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IBM370PARCHAN = 72;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ESCON = 73;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DLSW = 74;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISDNS = 75;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISDNU = 76;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_LAPD = 77;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IPSWITCH = 78;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RSRB = 79;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMLOGICAL = 80;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DS0 = 81;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DS0BUNDLE = 82;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_BSC = 83;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ASYNC = 84;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_CNR = 85;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88025DTR = 86;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_EPLRS = 87;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ARAP = 88;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPCNLS = 89;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HOSTPAD = 90;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_TERMPAD = 91;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRAMERELAYMPI = 92;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_X213 = 93;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ADSL = 94;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RADSL = 95;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SDSL = 96;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VDSL = 97;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88025CRFPINT = 98;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MYRINET = 99;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEEM = 100;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEFXO = 101;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEFXS = 102;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEENCAP = 103;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEOVERIP = 104;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMDXI = 105;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMFUNI = 106;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMIMA = 107;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PPPMULTILINKBUNDLE = 108;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IPOVERCDLC = 109;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IPOVERCLAW = 110;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_STACKTOSTACK = 111;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VIRTUALIPADDRESS = 112;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MPC = 113;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IPOVERATM = 114;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISO88025FIBER = 115;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_TDLC = 116;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_GIGABITETHERNET = 117;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HDLC = 118;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_LAPF = 119;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_V37 = 120;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_X25MLP = 121;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_X25HUNTGROUP = 122;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_TRASNPHDLC = 123;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_INTERLEAVE = 124;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FAST = 125;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IP = 126;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DOCSCABLEMACLAYER = 127;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DOCSCABLEDOWNSTREAM = 128;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DOCSCABLEUPSTREAM = 129;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_A12MPPSWITCH = 130;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_TUNNEL = 131;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_COFFEE = 132;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_CES = 133;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMSUBINTERFACE = 134;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_L2VLAN = 135;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_L3IPVLAN = 136;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_L3IPXVLAN = 137;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DIGITALPOWERLINE = 138;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MEDIAMAILOVERIP = 139;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DTM = 140;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DCN = 141;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IPFORWARD = 142;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MSDSL = 143;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IEEE1394 = 144;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IF_GSN = 145;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DVBRCCMACLAYER = 146;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DVBRCCDOWNSTREAM = 147;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DVBRCCUPSTREAM = 148;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMVIRTUAL = 149;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MPLSTUNNEL = 150;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SRP = 151;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEOVERATM = 152;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEOVERFRAMERELAY = 153;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IDSL = 154;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_COMPOSITELINK = 155;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SS7SIGLINK = 156;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPWIRELESSP2P = 157;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRFORWARD = 158;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RFC1483 = 159;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_USB = 160;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IEEE8023ADLAG = 161;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_BGPPOLICYACCOUNTING = 162;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRF16MFRBUNDLE = 163;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_H323GATEKEEPER = 164;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_H323PROXY = 165;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MPLS = 166;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MFSIGLINK = 167;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HDSL2 = 168;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SHDSL = 169;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DS1FDL = 170;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_POS = 171;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DVBASIIN = 172;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DVBASIOUT = 173;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PLC = 174;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_NFAS = 175;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_TR008 = 176;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_GR303RDT = 177;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_GR303IDT = 178;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ISUP = 179;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPDOCSWIRELESSMACLAYER = 180;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPDOCSWIRELESSDOWNSTREAM = 181;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPDOCSWIRELESSUPSTREAM = 182;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HIPERLAN2 = 183;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPBWAP2MP = 184;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SONETOVERHEADCHANNEL = 185;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DIGITALWRAPPEROVERHEADCHANNEL = 186;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_AAL2 = 187;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RADIOMAC = 188;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMRADIO = 189;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_IMT = 190;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MVL = 191;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_REACHDSL = 192;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FRDLCIENDPT = 193;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMVCIENDPT = 194;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_OPTICALCHANNEL = 195;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_OPTICALTRANSPORT = 196;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PROPATM = 197;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEOVERCABLE = 198;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_INFINIBAND = 199;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_TELINK = 200;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_Q2931 = 201;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VIRTUALTG = 202;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SIPTG = 203;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SIPSIG = 204;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DOCSCABLEUPSTREAMCHANNEL = 205;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ECONET = 206;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PON155 = 207;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PON622 = 208;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_BRIDGE = 209;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_LINEGROUP = 210;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEEMFGD = 211;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEFGDEANA = 212;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_VOICEDID = 213;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MPEGTRANSPORT = 214;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_SIXTOFOUR = 215;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_GTP = 216;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PDNETHERLOOP1 = 217;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_PDNETHERLOOP2 = 218;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_OPTICALCHANNELGROUP = 219;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_HOMEPNA = 220;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_GFP = 221;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_CISCOISLVLAN = 222;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ACTELISMETALOOP = 223;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_FCIPLINK = 224;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_RPR = 225;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_QAM = 226;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_LMP = 227;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_CBLVECTASTAR = 228;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_DOCSCABLEMCMTSDOWNSTREAM = 229;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ADSL2 = 230;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MACSECCONTROLLEDIF = 231;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_MACSECUNCONTROLLEDIF = 232;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_AVICIOPTICALETHER = 233;

    /**
     * Constant for possible type of an interface
     * @see types()
     */
    const IF_TYPE_ATMBOND = 234;

    /**
     * Text representation of interface types.
     *
     * @see types()
     * @var array Text representations of interface types.
     */
    public static $IF_TYPES = array(
        self::IF_TYPE_OTHER                      => 'other',
        self::IF_TYPE_REGULAR1822                      => 'regular1822',
        self::IF_TYPE_HDH1822                      => 'hdh1822',
        self::IF_TYPE_DDNX25                      => 'ddnX25',
        self::IF_TYPE_RFC877X25                      => 'rfc877x25',
        self::IF_TYPE_ETHERNETCSMACD                      => 'ethernetCsmacd',
        self::IF_TYPE_ISO88023CSMACD                      => 'iso88023Csmacd',
        self::IF_TYPE_ISO88024TOKENBUS                      => 'iso88024TokenBus',
        self::IF_TYPE_ISO88025TOKENRING                      => 'iso88025TokenRing',
        self::IF_TYPE_ISO88026MAN                      => 'iso88026Man',
        self::IF_TYPE_STARLAN                      => 'starLan',
        self::IF_TYPE_PROTEON10MBIT                      => 'proteon10Mbit',
        self::IF_TYPE_PROTEON80MBIT                      => 'proteon80Mbit',
        self::IF_TYPE_HYPERCHANNEL                      => 'hyperchannel',
        self::IF_TYPE_FDDI                      => 'fddi',
        self::IF_TYPE_LAPB                      => 'lapb',
        self::IF_TYPE_SDLC                      => 'sdlc',
        self::IF_TYPE_DS1                      => 'ds1',
        self::IF_TYPE_E1                      => 'e1',
        self::IF_TYPE_BASICISDN                      => 'basicISDN',
        self::IF_TYPE_PRIMARYISDN                      => 'primaryISDN',
        self::IF_TYPE_PROPPOINTTOPOINTSERIAL                      => 'propPointToPointSerial',
        self::IF_TYPE_PPP                      => 'ppp',
        self::IF_TYPE_SOFTWARELOOPBACK                      => 'softwareLoopback',
        self::IF_TYPE_EON                      => 'eon',
        self::IF_TYPE_ETHERNET3MBIT                      => 'ethernet3Mbit',
        self::IF_TYPE_NSIP                      => 'nsip',
        self::IF_TYPE_SLIP                      => 'slip',
        self::IF_TYPE_ULTRA                      => 'ultra',
        self::IF_TYPE_DS3                      => 'ds3',
        self::IF_TYPE_SIP                      => 'sip',
        self::IF_TYPE_FRAMERELAY                      => 'frameRelay',
        self::IF_TYPE_RS232                      => 'rs232',
        self::IF_TYPE_PARA                      => 'para',
        self::IF_TYPE_ARCNET                      => 'arcnet',
        self::IF_TYPE_ARCNETPLUS                      => 'arcnetPlus',
        self::IF_TYPE_ATM                      => 'atm',
        self::IF_TYPE_MIOX25                      => 'miox25',
        self::IF_TYPE_SONET                      => 'sonet',
        self::IF_TYPE_X25PLE                      => 'x25ple',
        self::IF_TYPE_ISO88022LLC                      => 'iso88022llc',
        self::IF_TYPE_LOCALTALK                      => 'localTalk',
        self::IF_TYPE_SMDSDXI                      => 'smdsDxi',
        self::IF_TYPE_FRAMERELAYSERVICE                      => 'frameRelayService',
        self::IF_TYPE_V35                      => 'v35',
        self::IF_TYPE_HSSI                      => 'hssi',
        self::IF_TYPE_HIPPI                      => 'hippi',
        self::IF_TYPE_MODEM                      => 'modem',
        self::IF_TYPE_AAL5                      => 'aal5',
        self::IF_TYPE_SONETPATH                      => 'sonetPath',
        self::IF_TYPE_SONETVT                      => 'sonetVT',
        self::IF_TYPE_SMDSICIP                      => 'smdsIcip',
        self::IF_TYPE_PROPVIRTUAL                      => 'propVirtual',
        self::IF_TYPE_PROPMULTIPLEXOR                      => 'propMultiplexor',
        self::IF_TYPE_IEEE80212                      => 'ieee80212',
        self::IF_TYPE_FIBRECHANNEL                      => 'fibreChannel',
        self::IF_TYPE_HIPPIINTERFACE                      => 'hippiInterface',
        self::IF_TYPE_FRAMERELAYINTERCONNECT                      => 'frameRelayInterconnect',
        self::IF_TYPE_AFLANE8023                      => 'aflane8023',
        self::IF_TYPE_AFLANE8025                      => 'aflane8025',
        self::IF_TYPE_CCTEMUL                      => 'cctEmul',
        self::IF_TYPE_FASTETHER                      => 'fastEther',
        self::IF_TYPE_ISDN                      => 'isdn',
        self::IF_TYPE_V11                      => 'v11',
        self::IF_TYPE_V36                      => 'v36',
        self::IF_TYPE_G703AT64K                      => 'g703at64k',
        self::IF_TYPE_G703AT2MB                      => 'g703at2mb',
        self::IF_TYPE_QLLC                      => 'qllc',
        self::IF_TYPE_FASTETHERFX                      => 'fastEtherFX',
        self::IF_TYPE_CHANNEL                      => 'channel',
        self::IF_TYPE_IEEE80211                      => 'ieee80211',
        self::IF_TYPE_IBM370PARCHAN                      => 'ibm370parChan',
        self::IF_TYPE_ESCON                      => 'escon',
        self::IF_TYPE_DLSW                      => 'dlsw',
        self::IF_TYPE_ISDNS                      => 'isdns',
        self::IF_TYPE_ISDNU                      => 'isdnu',
        self::IF_TYPE_LAPD                      => 'lapd',
        self::IF_TYPE_IPSWITCH                      => 'ipSwitch',
        self::IF_TYPE_RSRB                      => 'rsrb',
        self::IF_TYPE_ATMLOGICAL                      => 'atmLogical',
        self::IF_TYPE_DS0                      => 'ds0',
        self::IF_TYPE_DS0BUNDLE                      => 'ds0Bundle',
        self::IF_TYPE_BSC                      => 'bsc',
        self::IF_TYPE_ASYNC                      => 'async',
        self::IF_TYPE_CNR                      => 'cnr',
        self::IF_TYPE_ISO88025DTR                      => 'iso88025Dtr',
        self::IF_TYPE_EPLRS                      => 'eplrs',
        self::IF_TYPE_ARAP                      => 'arap',
        self::IF_TYPE_PROPCNLS                      => 'propCnls',
        self::IF_TYPE_HOSTPAD                      => 'hostPad',
        self::IF_TYPE_TERMPAD                      => 'termPad',
        self::IF_TYPE_FRAMERELAYMPI                      => 'frameRelayMPI',
        self::IF_TYPE_X213                      => 'x213',
        self::IF_TYPE_ADSL                      => 'adsl',
        self::IF_TYPE_RADSL                      => 'radsl',
        self::IF_TYPE_SDSL                      => 'sdsl',
        self::IF_TYPE_VDSL                      => 'vdsl',
        self::IF_TYPE_ISO88025CRFPINT                      => 'iso88025CRFPInt',
        self::IF_TYPE_MYRINET                      => 'myrinet',
        self::IF_TYPE_VOICEEM                      => 'voiceEM',
        self::IF_TYPE_VOICEFXO                      => 'voiceFXO',
        self::IF_TYPE_VOICEFXS                      => 'voiceFXS',
        self::IF_TYPE_VOICEENCAP                      => 'voiceEncap',
        self::IF_TYPE_VOICEOVERIP                      => 'voiceOverIp',
        self::IF_TYPE_ATMDXI                      => 'atmDxi',
        self::IF_TYPE_ATMFUNI                      => 'atmFuni',
        self::IF_TYPE_ATMIMA                      => 'atmIma',
        self::IF_TYPE_PPPMULTILINKBUNDLE                      => 'pppMultilinkBundle',
        self::IF_TYPE_IPOVERCDLC                      => 'ipOverCdlc',
        self::IF_TYPE_IPOVERCLAW                      => 'ipOverClaw',
        self::IF_TYPE_STACKTOSTACK                      => 'stackToStack',
        self::IF_TYPE_VIRTUALIPADDRESS                      => 'virtualIpAddress',
        self::IF_TYPE_MPC                      => 'mpc',
        self::IF_TYPE_IPOVERATM                      => 'ipOverAtm',
        self::IF_TYPE_ISO88025FIBER                      => 'iso88025Fiber',
        self::IF_TYPE_TDLC                      => 'tdlc',
        self::IF_TYPE_GIGABITETHERNET                      => 'gigabitEthernet',
        self::IF_TYPE_HDLC                      => 'hdlc',
        self::IF_TYPE_LAPF                      => 'lapf',
        self::IF_TYPE_V37                      => 'v37',
        self::IF_TYPE_X25MLP                      => 'x25mlp',
        self::IF_TYPE_X25HUNTGROUP                      => 'x25huntGroup',
        self::IF_TYPE_TRASNPHDLC                      => 'trasnpHdlc',
        self::IF_TYPE_INTERLEAVE                      => 'interleave',
        self::IF_TYPE_FAST                      => 'fast',
        self::IF_TYPE_IP                      => 'ip',
        self::IF_TYPE_DOCSCABLEMACLAYER                      => 'docsCableMaclayer',
        self::IF_TYPE_DOCSCABLEDOWNSTREAM                      => 'docsCableDownstream',
        self::IF_TYPE_DOCSCABLEUPSTREAM                      => 'docsCableUpstream',
        self::IF_TYPE_A12MPPSWITCH                      => 'a12MppSwitch',
        self::IF_TYPE_TUNNEL                      => 'tunnel',
        self::IF_TYPE_COFFEE                      => 'coffee',
        self::IF_TYPE_CES                      => 'ces',
        self::IF_TYPE_ATMSUBINTERFACE                      => 'atmSubInterface',
        self::IF_TYPE_L2VLAN                      => 'l2vlan',
        self::IF_TYPE_L3IPVLAN                      => 'l3ipvlan',
        self::IF_TYPE_L3IPXVLAN                      => 'l3ipxvlan',
        self::IF_TYPE_DIGITALPOWERLINE                      => 'digitalPowerline',
        self::IF_TYPE_MEDIAMAILOVERIP                      => 'mediaMailOverIp',
        self::IF_TYPE_DTM                      => 'dtm',
        self::IF_TYPE_DCN                      => 'dcn',
        self::IF_TYPE_IPFORWARD                      => 'ipForward',
        self::IF_TYPE_MSDSL                      => 'msdsl',
        self::IF_TYPE_IEEE1394                      => 'ieee1394',
        self::IF_TYPE_IF_GSN                      => 'if-gsn',
        self::IF_TYPE_DVBRCCMACLAYER                      => 'dvbRccMacLayer',
        self::IF_TYPE_DVBRCCDOWNSTREAM                      => 'dvbRccDownstream',
        self::IF_TYPE_DVBRCCUPSTREAM                      => 'dvbRccUpstream',
        self::IF_TYPE_ATMVIRTUAL                      => 'atmVirtual',
        self::IF_TYPE_MPLSTUNNEL                      => 'mplsTunnel',
        self::IF_TYPE_SRP                      => 'srp',
        self::IF_TYPE_VOICEOVERATM                      => 'voiceOverAtm',
        self::IF_TYPE_VOICEOVERFRAMERELAY                      => 'voiceOverFrameRelay',
        self::IF_TYPE_IDSL                      => 'idsl',
        self::IF_TYPE_COMPOSITELINK                      => 'compositeLink',
        self::IF_TYPE_SS7SIGLINK                      => 'ss7SigLink',
        self::IF_TYPE_PROPWIRELESSP2P                      => 'propWirelessP2P',
        self::IF_TYPE_FRFORWARD                      => 'frForward',
        self::IF_TYPE_RFC1483                      => 'rfc1483',
        self::IF_TYPE_USB                      => 'usb',
        self::IF_TYPE_IEEE8023ADLAG                      => 'ieee8023adLag',
        self::IF_TYPE_BGPPOLICYACCOUNTING                      => 'bgppolicyaccounting',
        self::IF_TYPE_FRF16MFRBUNDLE                      => 'frf16MfrBundle',
        self::IF_TYPE_H323GATEKEEPER                      => 'h323Gatekeeper',
        self::IF_TYPE_H323PROXY                      => 'h323Proxy',
        self::IF_TYPE_MPLS                      => 'mpls',
        self::IF_TYPE_MFSIGLINK                      => 'mfSigLink',
        self::IF_TYPE_HDSL2                      => 'hdsl2',
        self::IF_TYPE_SHDSL                      => 'shdsl',
        self::IF_TYPE_DS1FDL                      => 'ds1FDL',
        self::IF_TYPE_POS                      => 'pos',
        self::IF_TYPE_DVBASIIN                      => 'dvbAsiIn',
        self::IF_TYPE_DVBASIOUT                      => 'dvbAsiOut',
        self::IF_TYPE_PLC                      => 'plc',
        self::IF_TYPE_NFAS                      => 'nfas',
        self::IF_TYPE_TR008                      => 'tr008',
        self::IF_TYPE_GR303RDT                      => 'gr303RDT',
        self::IF_TYPE_GR303IDT                      => 'gr303IDT',
        self::IF_TYPE_ISUP                      => 'isup',
        self::IF_TYPE_PROPDOCSWIRELESSMACLAYER                      => 'propDocsWirelessMaclayer',
        self::IF_TYPE_PROPDOCSWIRELESSDOWNSTREAM                      => 'propDocsWirelessDownstream',
        self::IF_TYPE_PROPDOCSWIRELESSUPSTREAM                      => 'propDocsWirelessUpstream',
        self::IF_TYPE_HIPERLAN2                      => 'hiperlan2',
        self::IF_TYPE_PROPBWAP2MP                      => 'propBWAp2Mp',
        self::IF_TYPE_SONETOVERHEADCHANNEL                      => 'sonetOverheadChannel',
        self::IF_TYPE_DIGITALWRAPPEROVERHEADCHANNEL                      => 'digitalWrapperOverheadChannel',
        self::IF_TYPE_AAL2                      => 'aal2',
        self::IF_TYPE_RADIOMAC                      => 'radioMAC',
        self::IF_TYPE_ATMRADIO                      => 'atmRadio',
        self::IF_TYPE_IMT                      => 'imt',
        self::IF_TYPE_MVL                      => 'mvl',
        self::IF_TYPE_REACHDSL                      => 'reachDSL',
        self::IF_TYPE_FRDLCIENDPT                      => 'frDlciEndPt',
        self::IF_TYPE_ATMVCIENDPT                      => 'atmVciEndPt',
        self::IF_TYPE_OPTICALCHANNEL                      => 'opticalChannel',
        self::IF_TYPE_OPTICALTRANSPORT                      => 'opticalTransport',
        self::IF_TYPE_PROPATM                      => 'propAtm',
        self::IF_TYPE_VOICEOVERCABLE                      => 'voiceOverCable',
        self::IF_TYPE_INFINIBAND                      => 'infiniband',
        self::IF_TYPE_TELINK                      => 'teLink',
        self::IF_TYPE_Q2931                      => 'q2931',
        self::IF_TYPE_VIRTUALTG                      => 'virtualTg',
        self::IF_TYPE_SIPTG                      => 'sipTg',
        self::IF_TYPE_SIPSIG                      => 'sipSig',
        self::IF_TYPE_DOCSCABLEUPSTREAMCHANNEL                      => 'docsCableUpstreamChannel',
        self::IF_TYPE_ECONET                      => 'econet',
        self::IF_TYPE_PON155                      => 'pon155',
        self::IF_TYPE_PON622                      => 'pon622',
        self::IF_TYPE_BRIDGE                      => 'bridge',
        self::IF_TYPE_LINEGROUP                      => 'linegroup',
        self::IF_TYPE_VOICEEMFGD                      => 'voiceEMFGD',
        self::IF_TYPE_VOICEFGDEANA                      => 'voiceFGDEANA',
        self::IF_TYPE_VOICEDID                      => 'voiceDID',
        self::IF_TYPE_MPEGTRANSPORT                      => 'mpegTransport',
        self::IF_TYPE_SIXTOFOUR                      => 'sixToFour',
        self::IF_TYPE_GTP                      => 'gtp',
        self::IF_TYPE_PDNETHERLOOP1                      => 'pdnEtherLoop1',
        self::IF_TYPE_PDNETHERLOOP2                      => 'pdnEtherLoop2',
        self::IF_TYPE_OPTICALCHANNELGROUP                      => 'opticalChannelGroup',
        self::IF_TYPE_HOMEPNA                      => 'homepna',
        self::IF_TYPE_GFP                      => 'gfp',
        self::IF_TYPE_CISCOISLVLAN                      => 'ciscoISLvlan',
        self::IF_TYPE_ACTELISMETALOOP                      => 'actelisMetaLOOP',
        self::IF_TYPE_FCIPLINK                      => 'fcipLink',
        self::IF_TYPE_RPR                      => 'rpr',
        self::IF_TYPE_QAM                      => 'qam',
        self::IF_TYPE_LMP                      => 'lmp',
        self::IF_TYPE_CBLVECTASTAR                      => 'cblVectaStar',
        self::IF_TYPE_DOCSCABLEMCMTSDOWNSTREAM                      => 'docsCableMCmtsDownstream',
        self::IF_TYPE_ADSL2                      => 'adsl2',
        self::IF_TYPE_MACSECCONTROLLEDIF                      => 'macSecControlledIF',
        self::IF_TYPE_MACSECUNCONTROLLEDIF                      => 'macSecUncontrolledIF',
        self::IF_TYPE_AVICIOPTICALETHER                      => 'aviciOpticalEther',
        self::IF_TYPE_ATMBOND                      => 'atmbond'
    );

    /**
     * Get an array of device interface types
     *
     * @see $IF_TYPES
     * @param boolean $translate If true, return the string representation
     * @return array An array of interface types
    */
    public function types( $translate = false )
    {
        $types = $this->getSNMP()->walk1d( self::OID_IF_TYPE );

        if( !$translate )
            return $types;

        return $this->getSNMP()->translate( $types, self::$IF_TYPES );
    }

    /**
     * Returns an associate array of STP port IDs (key) to interface IDs (value)
     *
     * e.g.  [22] => 10122
     *
     *
     * @return array Associate array of STP port IDs (key) to interface IDs (value)
     */
    public function bridgeBasePortIfIndexes()
    {
        return $this->getSNMP()->walk1d( self::OID_BRIDGE_BASE_PORT_IF_INDEX );
    }

}


