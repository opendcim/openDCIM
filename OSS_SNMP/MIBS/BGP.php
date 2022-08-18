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
 * A class for performing SNMP V2 BGP queries
 *
 * @see http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?local=en&translate=Translate&objectInput=1.3.6.1.2.1.15
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class BGP extends \OSS_SNMP\MIB
{

    const OID_BGP_VERSION              = '.1.3.6.1.2.1.15.1.0';

    const OID_BGP_LOCAL_ASN            = '.1.3.6.1.2.1.15.2.0';

    const OID_BGP_PEER_IDENTIFIER                       = '.1.3.6.1.2.1.15.3.1.1';
    const OID_BGP_PEER_CONNECTION_STATE                 = '.1.3.6.1.2.1.15.3.1.2';
    const OID_BGP_PEER_ADMIN_STATE                      = '.1.3.6.1.2.1.15.3.1.3';
    const OID_BGP_PEER_NEGOTIATED_VERSION               = '.1.3.6.1.2.1.15.3.1.4';
    const OID_BGP_PEER_LOCAL_ADDRESS                    = '.1.3.6.1.2.1.15.3.1.5';
    const OID_BGP_PEER_LOCAL_PORT                       = '.1.3.6.1.2.1.15.3.1.6';
    const OID_BGP_PEER_REMOTE_ADDR                      = '.1.3.6.1.2.1.15.3.1.7';
    const OID_BGP_PEER_REMOTE_PORT                      = '.1.3.6.1.2.1.15.3.1.8';
    const OID_BGP_PEER_REMOTE_ASN                       = '.1.3.6.1.2.1.15.3.1.9';
    const OID_BGP_PEER_IN_UPDATES                       = '.1.3.6.1.2.1.15.3.1.10';
    const OID_BGP_PEER_OUT_UPDATES                      = '.1.3.6.1.2.1.15.3.1.11';
    const OID_BGP_PEER_IN_TOTAL_MESSAGES                = '.1.3.6.1.2.1.15.3.1.12';
    const OID_BGP_PEER_OUT_TOTAL_MESSAGES               = '.1.3.6.1.2.1.15.3.1.13';
    const OID_BGP_PEER_LAST_ERROR                       = '.1.3.6.1.2.1.15.3.1.14';
    const OID_BGP_PEER_FSM_ESTABLISHED_TRANSITIONS      = '.1.3.6.1.2.1.15.3.1.15';
    const OID_BGP_PEER_FSM_ESTABLISHED_TIME             = '.1.3.6.1.2.1.15.3.1.16';
    const OID_BGP_PEER_CONNECT_RETRY_INTERVAL           = '.1.3.6.1.2.1.15.3.1.17';
    const OID_BGP_PEER_HOLD_TIME                        = '.1.3.6.1.2.1.15.3.1.18';
    const OID_BGP_PEER_KEEP_ALIVE                       = '.1.3.6.1.2.1.15.3.1.19';
    const OID_BGP_PEER_HOLD_TIME_CONFIGURED             = '.1.3.6.1.2.1.15.3.1.20';
    const OID_BGP_PEER_KEEP_ALIVE_CONFIGURED            = '.1.3.6.1.2.1.15.3.1.21';
    const OID_BGP_PEER_MIN_AS_ORIGINATION_INTERVAL      = '.1.3.6.1.2.1.15.3.1.22';
    const OID_BGP_PEER_MIN_ROUTE_ADVERTISEMENT_INTERVAL = '.1.3.6.1.2.1.15.3.1.23';
    const OID_BGP_PEER_IN_UPDATE_ELAPSED_TIME           = '.1.3.6.1.2.1.15.3.1.24';

    const OID_BGP_IDENTIFIER           = '.1.3.6.1.2.1.15.4.0';

    const OID_BGP_PATH_ATTR_PEER                         = '.1.3.6.1.2.1.15.6.1.1';
    const OID_BGP_PATH_ATTR_ADDR_PREFIX_LENGTH           = '.1.3.6.1.2.1.15.6.1.2';
    const OID_BGP_PATH_ATTR_ADDR_PREFIX                  = '.1.3.6.1.2.1.15.6.1.3';
    const OID_BGP_PATH_ATTR_ORIGIN                       = '.1.3.6.1.2.1.15.6.1.4';
    const OID_BGP_PATH_ATTR_AS_PATH_SEGMENT              = '.1.3.6.1.2.1.15.6.1.5';
    const OID_BGP_PATH_ATTR_NEXT_HOP                     = '.1.3.6.1.2.1.15.6.1.6';
    const OID_BGP_PATH_ATTR_MED                          = '.1.3.6.1.2.1.15.6.1.7';
    const OID_BGP_PATH_ATTR_LOCAL_PREF                   = '.1.3.6.1.2.1.15.6.1.8';
    const OID_BGP_PATH_ATTR_ATOMIC_AGGREGATE             = '.1.3.6.1.2.1.15.6.1.9';
    const OID_BGP_PATH_ATTR_AGGREGATOR_AS                = '.1.3.6.1.2.1.15.6.1.10';
    const OID_BGP_PATH_ATTR_AGGREGATOR_ADDR              = '.1.3.6.1.2.1.15.6.1.11';
    const OID_BGP_PATH_ATTR_CALC_LOCAL_PREF              = '.1.3.6.1.2.1.15.6.1.12';
    const OID_BGP_PATH_ATTR_BEST                         = '.1.3.6.1.2.1.15.6.1.13';
    const OID_BGP_PATH_ATTR_UNKNOWN                      = '.1.3.6.1.2.1.15.6.1.14';


    /**
     * Returns the BGP version
     *
     * > "Vector of supported BGP protocol version numbers. Each peer negotiates the version
     * > from this vector. Versions are identified via the string of bits contained within this
     * > object. The first octet contains bits 0 to 7, the second octet contains bits 8 to 15,
     * > and so on, with the most significant bit referring to the lowest bit number in the
     * > octet (e.g., the MSB of the first octet refers to bit 0). If a bit, i, is present
     * > and set, then the version (i+1) of the BGP is supported."
     *
     * @return string Returns the BGP version
     */
    public function version()
    {
        return $this->getSNMP()->get( self::OID_BGP_VERSION );
    }


    /**
     * Returns the local BGP AS number
     *
     * > "The local autonomous system number."
     *
     * @return int The local autonomous system number.
     */
    public function localASN()
    {
        return $this->getSNMP()->get( self::OID_BGP_LOCAL_ASN );
    }


    /**
     * Returns the BGP identifier of all peers indexed by neighbour IPv4 address
     *
     * > "The BGP Identifier of this entry's BGP peer."
     *
     * @return array Returns the BGP identifier of all peers indexed by neighbour IPv4 address
     */
    public function peerIdentifiers()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_IDENTIFIER );
    }


    /**
     * Possible value for peer connection state
     * @var int Possible value for peer connection state
     */
    const BGP_PEER_CONNECTION_STATE_IDLE = 1;

    /**
     * Possible value for peer connection state
     * @var int Possible value for peer connection state
     */
    const BGP_PEER_CONNECTION_STATE_CONNECT = 2;

    /**
     * Possible value for peer connection state
     * @var int Possible value for peer connection state
     */
    const BGP_PEER_CONNECTION_STATE_ACTIVE = 3;

    /**
     * Possible value for peer connection state
     * @var int Possible value for peer connection state
     */
    const BGP_PEER_CONNECTION_STATE_OPENSENT = 4;

    /**
     * Possible value for peer connection state
     * @var int Possible value for peer connection state
     */
    const BGP_PEER_CONNECTION_STATE_OPENCONFIRM = 5;

    /**
     * Possible value for peer connection state
     * @var int Possible value for peer connection state
     */
    const BGP_PEER_CONNECTION_STATE_ESTABLISHED = 6;

    /**
     * Look up for text representation of BGP peer connection states
     * @var array Look up for text representation of BGP peer connection states
     */
    public static $BGP_PEER_CONNECTION_STATES = [
        self::BGP_PEER_CONNECTION_STATE_IDLE        => 'idle',
        self::BGP_PEER_CONNECTION_STATE_CONNECT     => 'connect',
        self::BGP_PEER_CONNECTION_STATE_ACTIVE      => 'active',
        self::BGP_PEER_CONNECTION_STATE_OPENSENT    => 'opensent',
        self::BGP_PEER_CONNECTION_STATE_OPENCONFIRM => 'openconfirm',
        self::BGP_PEER_CONNECTION_STATE_ESTABLISHED => 'established'
    ];

    /**
     * Returns the BGP peer connection state (see `self::$BGP_PEER_CONNECTION_STATES`)
     *
     * > "The BGP peer connection state."
     *
     * @param bool $translate If true, use the `$BGP_PEER_CONNECTION_STATES` array to return textual representation
     * @return array The BGP peer connection state (see `self::$BGP_PEER_CONNECTION_STATES`)
     */
    public function peerConnectionStates( $translate = false )
    {
        $s = $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_CONNECTION_STATE );

        if( !$translate )
            return $s;

        return $this->getSNMP()->translate( $s, self::$BGP_PEER_CONNECTION_STATES );
    }





    /**
     * Possible value for peer admin state
     * @var int Possible value for peer admin state
     */
    const BGP_PEER_ADMIN_STATE_STOP = 1;

    /**
     * Possible value for peer admin state
     * @var int Possible value for peer admin state
     */
    const BGP_PEER_ADMIN_STATE_START = 2;

    /**
     * Look up for text representation of BGP peer admin states
     * @var array Look up for text representation of BGP peer admin states
     */
    public static $BGP_PEER_ADMIN_STATES = [
        self::BGP_PEER_ADMIN_STATE_STOP  => 'stop',
        self::BGP_PEER_ADMIN_STATE_START => 'start'
    ];

    /**
     * Returns the BGP peer admin states (see `self::$BGP_PEER_ADMIN_STATES`)
     *
     * > "The desired state of the BGP connection. A transition from 'stop' to 'start' will
     * > cause the BGP Start Event to be generated. A transition from 'start' to 'stop' will
     * > cause the BGP Stop Event to be generated. This parameter can be used to restart BGP
     * > peer connections. Care should be used in providing write access to this object
     * > without adequate authentication."
     *
     * @param bool $translate If true, use the `$BGP_PEER_ADMIN_STATES` array to return textual representation
     * @return array The BGP peer admin states (see `self::$BGP_PEER_ADMIN_STATES`)
     */
    public function peerAdminStates( $translate = false )
    {
        $s = $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_ADMIN_STATE );

        if( !$translate )
            return $s;

        return $this->getSNMP()->translate( $s, self::$BGP_PEER_ADMIN_STATES );
    }


    /**
     * Returns the negotiated version of BGP running between the two peers
     *
     * > "The negotiated version of BGP running between the two peers"
     *
     * @return array The negotiated version of BGP running between the two peers
     */
    public function peerNegotiatedVersions()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_NEGOTIATED_VERSION );
    }

    /**
     * Returns the local IP address of this entry's BGP connection.
     *
     * > "The local IP address of this entry's BGP connection."
     *
     * @return array The local IP address of this entry's BGP connection.
     */
    public function peerLocalAddresses()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_LOCAL_ADDRESS );
    }

    /**
     * Returns the local ports for the TCP connections between the BGP peers.
     *
     * > "The local port for the TCP connection between the BGP peers."
     *
     * @return array The local ports for the TCP connections between the BGP peers.
     */
    public function peerLocalPorts()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_LOCAL_PORT );
    }

    /**
     * Returns the local IP address of this entry's BGP peer.
     *
     * > "The remote IP address of this entry's BGP peer."
     *
     * @return array The remote IP address of this entry's BGP peer.
     */
    public function peerRemoteAddresses()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_REMOTE_ADDR );
    }

    /**
     * Returns the remote ports for the TCP connections between the BGP peers.
     *
     * > "The remote port for the TCP connection between the BGP peers."
     *
     * @return array The remote ports for the TCP connections between the BGP peers.
     */
    public function peerRemotePorts()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_REMOTE_PORT );
    }

    /**
     * Returns The remote autonomous system number.
     *
     * > "The remote autonomous system number."
     *
     * @return array The remote autonomous system number.
     */
    public function peerRemoteASNs()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_REMOTE_ASN );
    }

    /**
     * Returns The number of BGP UPDATE messages received on this connection.
     *
     * > "The number of BGP UPDATE messages received on this connection. This object
     * > should be initialized to zero (0) when the connection is established."
     *
     * @return array The number of BGP UPDATE messages received on this connection.
     */
    public function peerInUpdates()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_IN_UPDATES );
    }

    /**
     * Returns The number of BGP UPDATE messages transmitted on this connection.
     *
     * > "The number of BGP UPDATE messages transmitted on this connection. This
     * > object should be initialized to zero (0) when the connection is established."
     *
     * @return array The number of BGP UPDATE messages transmitted on this connection.
     */
    public function peerOutUpdates()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_OUT_UPDATES );
    }

    /**
     * Returns The total number of messages received from the remote peer on this connection.
     *
     * > "The total number of messages received from the remote peer on this connection.
     * > This object should be initialized to zero when the connection is established."
     *
     * @return array The total number of messages received from the remote peer on this connection.
     */
    public function peerInTotalMessages()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_IN_TOTAL_MESSAGES );
    }

    /**
     * Returns The total number of messages transmitted to the remote peer on this connection.
     *
     * > "The total number of messages transmitted to the remote peer on this connection. This
     * > object should be initialized to zero when the connection is established."
     *
     * @return array The total number of messages transmitted to the remote peer on this connection.
     */
    public function peerOutTotalMessages()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_OUT_TOTAL_MESSAGES );
    }



    /**
     * Returns The last error code and subcode seen by this peer on this connection.
     *
     * > "The last error code and subcode seen by this peer on this connection. If no error has
     * > occurred, this field is zero. Otherwise, the first byte of this two byte OCTET STRING
     * > contains the error code, and the second byte contains the subcode."
     *
     * @return array The last error code and subcode seen by this peer on this connection.
     */
    public function peerLastErrors()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_LAST_ERROR );
    }

    /**
     * Returns The total number of times the BGP FSM transitioned into the established state.
     *
     * > "The total number of times the BGP FSM transitioned into the established state."
     *
     * @return array The total number of times the BGP FSM transitioned into the established state.
     */
    public function peerEstabledTransitions()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_FSM_ESTABLISHED_TRANSITIONS );
    }

    /**
     * Returns how long (in seconds) this peer has been in the Established state or
     * how long since this peer was last in the Established state
     *
     * > "This timer indicates how long (in seconds) this peer has been in the
     * > Established state or how long since this peer was last in the
     * > Established state. It is set to zero when a new peer is configured or the router is
     * > booted"
     *
     * @return array How long (secs) this peer has been in (or since it was last in) the Established state
     */
    public function peerEstablishedTimes()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_FSM_ESTABLISHED_TIME );
    }

    /**
     * Returns Time interval in seconds for the ConnectRetry timer.
     *
     * > "Time interval in seconds for the ConnectRetry timer. The suggested value
     * > for this timer is 120 seconds."
     *
     * @return array Time interval in seconds for the ConnectRetry timer.
     */
    public function peerConnectRetryIntervals()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_CONNECT_RETRY_INTERVAL );
    }

    /**
     * Returns Time interval in seconds for the Hold Timer established with the peer.
     *
     * > "Time interval in seconds for the Hold Timer established with the peer. The
     * > value of this object is calculated by this BGP speaker by using the smaller of the
     * > value in bgpPeerHoldTimeConfigured and the Hold Time received in the OPEN message.
     * > This value must be at lease three seconds if it is not zero (0) in which case the
     * > Hold Timer has not been established with the peer, or, the value of
     * > bgpPeerHoldTimeConfigured is zero (0)."
     *
     * @return array Time interval in seconds for the Hold Timer established with the peer.
     */
    public function peerHoldTimes()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_HOLD_TIME );
    }

    /**
     * Returns Time interval in seconds for the KeepAlive timer established with the peer.
     *
     * > "Time interval in seconds for the KeepAlive timer established with the peer. The value
     * > of this object is calculated by this BGP speaker such that, when compared with
     * > bgpPeerHoldTime, it has the same proportion as what bgpPeerKeepAliveConfigured has when
     * > compared with bgpPeerHoldTimeConfigured. If the value of this object is zero (0),
     * > it indicates that the KeepAlive timer has not been established with the peer, or,
     * > the value of bgpPeerKeepAliveConfigured is zero (0)."
     *
     * @return array Time interval in seconds for the KeepAlive timer established with the peer.
     */
    public function peerKeepAlives()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_KEEP_ALIVE );
    }

    /**
     * Returns Time interval in seconds for the Hold Time configured for this BGP speaker with this peer.
     *
     * > "Time interval in seconds for the Hold Time configured for this BGP speaker with this
     * > peer. This value is placed in an OPEN message sent to this peer by this BGP
     * > speaker, and is compared with the Hold Time field in an OPEN message received
     * > from the peer when determining the Hold Time (bgpPeerHoldTime) with the peer.
     * > This value must not be less than three seconds if it is not zero (0) in which
     * > case the Hold Time is NOT to be established with the peer. The suggested
     * > value for this timer is 90 seconds."
     *
     * @return array Time interval in seconds for the Hold Time configured for this BGP speaker with this peer.
     */
    public function peerHoleTimesConfigured()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_HOLD_TIME_CONFIGURED );
    }

    /**
     * Returns Time interval in seconds for the KeepAlive timer configured for this BGP speaker with this peer.
     *
     * > "Time interval in seconds for the KeepAlive timer configured for this BGP
     * > speaker with this peer. The value of this object will only determine the
     * > KEEPALIVE messages' frequency relative to the value specified in
     * > bgpPeerHoldTimeConfigured; the actual time interval for the KEEPALIVE messages
     * > is indicated by bgpPeerKeepAlive. A reasonable maximum value for this timer
     * > would be configured to be one third of that of bgpPeerHoldTimeConfigured.
     * > If the value of this object is zero (0), no periodical KEEPALIVE messages are sent
     * > to the peer after the BGP connection has been established. The suggested value for
     * > this timer is 30 seconds"
     *
     * @return array Time interval in seconds for the KeepAlive timer configured for this BGP speaker with this peer.
     */
    public function peerKeepAlivesConfigured()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_KEEP_ALIVE_CONFIGURED );
    }

    /**
     * Returns Time interval in seconds for the MinASOriginationInterval timer.
     *
     * > "Time interval in seconds for the MinASOriginationInterval timer.
     * > The suggested value for this timer is 15 seconds."
     *
     * @return array Time interval in seconds for the MinASOriginationInterval timer.
     */
    public function peerMinASOriginationIntervals()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_MIN_AS_ORIGINATION_INTERVAL );
    }

    /**
     * Returns Time interval in seconds for the MinRouteAdvertisementInterval timer.
     *
     * > "Time interval in seconds for the MinRouteAdvertisementInterval timer.
     * > The suggested value for this timer is 30 seconds"
     *
     * @return array Time interval in seconds for the MinRouteAdvertisementInterval timer.
     */
    public function peerMinRouteAdvertisementIntervals()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_MIN_ROUTE_ADVERTISEMENT_INTERVAL );
    }

    /**
     * Returns Elapsed time in seconds since the last BGP UPDATE message was received from the peer.
     *
     * > "Elapsed time in seconds since the last BGP UPDATE message was received from the peer.
     * > Each time bgpPeerInUpdates is incremented, the value of this object is set to zero (0)."
     *
     * @return array Elapsed time in seconds since the last BGP UPDATE message was received from the peer.
     */
    public function peerInUpdateElapsedTimes()
    {
        return $this->getSNMP()->walkIPv4( self::OID_BGP_PEER_IN_UPDATE_ELAPSED_TIME );
    }


    /**
     * Utility function to gather all peer information into a single array.
     *
     * For example, this would return something like:
     *
     *     Array
     *     (
     *         ....
     *         [192.0.2.126] => Array
     *         (
     *             [identity] => 192.0.2.45
     *             [connectionState] => established
     *             [adminState] => start
     *             [negotiatedVersion] => 4
     *             [localAddress] => 193.242.111.74
     *             [localPort] => 26789
     *             [remoteAddress] => 192.0.2.126
     *             [remotePort] => 179
     *             [remoteASN] => 65505
     *             [inUpdates] => 4
     *             [outUpdates] => 2
     *             [inTotalMessages] => 180988
     *             [outTotalMessages] => 181012
     *             [lastError] => 0000
     *             [establishedTransitions] => 1
     *             [establishedTime] => 9867469
     *             [connectRetryInterval] => 60
     *             [holdTime] => 180
     *             [keepAlive] => 60
     *             [holdTimeConfigured] => 180
     *             [keepAliveConfigured] => 60
     *             [minASOriginationInterval] => 30
     *             [minRouteAdvertisementInterval] => 30
     *             [inUpdateElapsedTime] => 0
     *         )
     *         ....
     *     )
     *
     * @param bool $translate Where a called function supports translation, if true then translate
     * @return array Array of peer details - see example above.
     */
    public function peerDetails( $translate = false )
    {
        $fetchList = [
            'peerIdentifiers' => 'identity',
            'peerConnectionStates' => 'connectionState',
            'peerAdminStates' => 'adminState',
            'peerNegotiatedVersions' => 'negotiatedVersion',
            'peerLocalAddresses' => 'localAddress',
            'peerLocalPorts' => 'localPort',
            'peerRemoteAddresses' => 'remoteAddress',
            'peerRemotePorts' => 'remotePort',
            'peerRemoteASNs' => 'remoteASN',
            'peerInUpdates' => 'inUpdates',
            'peerOutUpdates' => 'outUpdates',
            'peerInTotalMessages' => 'inTotalMessages',
            'peerOutTotalMessages' => 'outTotalMessages',
            'peerLastErrors' => 'lastError',
            'peerEstabledTransitions' => 'establishedTransitions',
            'peerEstablishedTimes' => 'establishedTime',
            'peerConnectRetryIntervals' => 'connectRetryInterval',
            'peerHoldTimes' => 'holdTime',
            'peerKeepAlives' => 'keepAlive',
            'peerHoleTimesConfigured' => 'holdTimeConfigured',
            'peerKeepAlivesConfigured' => 'keepAliveConfigured',
            'peerMinASOriginationIntervals' => 'minASOriginationInterval',
            'peerMinRouteAdvertisementIntervals' => 'minRouteAdvertisementInterval',
            'peerInUpdateElapsedTimes' => 'inUpdateElapsedTime'
        ];

        $canTranslate = [ 'peerConnectionStates', 'peerAdminStates' ];

        $details = [];

        foreach( $fetchList as $fn => $idx )
        {
            if( in_array( $fn, $canTranslate ) )
                $values = $this->$fn( $translate );
            else
                $values = $this->$fn();

            foreach( $values as $ip => $value )
                $details[ $ip ][ $idx ] = $value;
        }

        return $details;
    }




    /**
     * Returns the local BGP identifier
     *
     * > "The BGP Identifier of local system."
     *
     * @return string The BGP Identifier of local system.
     */
    public function identifier()
    {
        return $this->getSNMP()->get( self::OID_BGP_IDENTIFIER );
    }
}
