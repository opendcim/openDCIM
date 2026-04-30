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
    DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
    DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace OSS_SNMP\MIBS;

/**
 * A class for obtaining route information using ipCidrRouteTable information.
 *
 * @see http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?local=en&translate=Translate&objectInput=1.3.6.1.2.1.4.24.4.1#oidContent
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Dave Hope <dave@hope.mx>
 */
class Routes extends \OSS_SNMP\MIB
{
	const OID_ROUTE_ENTRY_DEST		= '1.3.6.1.2.1.4.24.4.1.1';	// ipCidrRouteDest
	const OID_ROUTE_ENTRY_MASK		= '1.3.6.1.2.1.4.24.4.1.2'; // ipCidrRouteMask
	const OID_ROUTE_ENTRY_TOS   	= '1.3.6.1.2.1.4.24.4.1.3'; // ipCidrRouteTos
	const OID_ROUTE_ENTRY_NEXTHOP	= '1.3.6.1.2.1.4.24.4.1.4'; // ipCidrRouteNextHop
	const OID_ROUTE_ENTRY_IFINDEX	= '1.3.6.1.2.1.4.24.4.1.5'; // ipCidrRouteIfIndex
	const OID_ROUTE_ENTRY_TYPE		= '1.3.6.1.2.1.4.24.4.1.6'; // ipCidrRouteType
	const OID_ROUTE_ENTRY_PROTO		= '1.3.6.1.2.1.4.24.4.1.7'; // ipCidrRouteProto
	const OID_ROUTE_ENTRY_AGE		= '1.3.6.1.2.1.4.24.4.1.8'; // ipCidrRouteAge
	const OID_ROUTE_ENTRY_INFO		= '1.3.6.1.2.1.4.24.4.1.9'; // ipCidrRouteInfo
	const OID_ROUTE_ENTRY_NEXTHOPAS = '1.3.6.1.2.1.4.24.4.1.10'; // ipCidrRouteNextHopAS
	const OID_ROUTE_ENTRY_METRIC1	= '1.3.6.1.2.1.4.24.4.1.11'; // ipCidrRouteMetric1 
	const OID_ROUTE_ENTRY_METRIC2	= '1.3.6.1.2.1.4.24.4.1.12'; // ipCidrRouteMetric2 
	const OID_ROUTE_ENTRY_METRIC3	= '1.3.6.1.2.1.4.24.4.1.13'; // ipCidrRouteMetric3 
	const OID_ROUTE_ENTRY_METRIC4	= '1.3.6.1.2.1.4.24.4.1.14'; // ipCidrRouteMetric4 
	const OID_ROUTE_ENTRY_METRIC5	= '1.3.6.1.2.1.4.24.4.1.15'; // ipCidrRouteMetric5
	const OID_ROUTE_ENTRY_STATUS	= '1.3.6.1.2.1.4.24.4.1.16'; // ipCidrRouteStatus


	/**
	 * Returns the destination network
	 * 
	 * > "The destination IP address of this route."
	 *
	 * @return array Returns the destination network for all routes indexed by SNMP route ID.
	 */
	public function routeDest()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_DEST , 12 , 24 );
	}
	

	/**
	 *  Returns the destination netmask
	 *
	 * > "Indicate the mask to be logical-ANDed with the destination address
	 * > before being compared to the value in the ipCidrRouteDest field. For
	 * > those systems that do not support arbitrary subnet masks, an agent
	 * > constructs the value of the ipCidrRouteMask by reference to the IP
	 * > Ad-dress Class."
	 *
	 * @return array Returns the netmask for all routes indexed by SNMP route ID.
	 */
	public function routeMask()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_MASK , 12 , 24 );
	}


	/**
	 * Returns the route type of service
	 *
	 * > "The policy specifier is the IP TOS Field. The encoding of IP TOS
	 * > is as specified by the following convention. Zero indicates the
	 * > default path if no more specific policy applies."
	 *
	 * @return array Returns the TOS for all routes indexed by SNMP route ID.
	 */
	public function routeTos()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_TOS , 12 , 24 );
	}

	/**
	 * Returns the next hop
	 * 
	 * > "On remote routes, the address of the next sys- tem en route;
	 * > Otherwise, 0.0.0.0."
	 * 
	 * @return array Returns the next hop for all routes indexed by SNMP route ID.
	 */
	public function routeNextHop()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_NEXTHOP , 12 , 24 );
	}


	/**
	 * Returns the interface index for the next hop.
	 *
	 * > "The ifIndex value which identifies the local interface through which
	 * > the next hop of this route should be reached."
	 *
	 * @return array Returns the ifindex for all routes indexed by SNMP route ID.
	 */
	public function routeIfIndex()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_IFINDEX , 12 , 24 );
	}


	/**
     * Possible value for route type
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_TYPE_OTHER	= 1;

	/**
     * Possible value for route type
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_TYPE_REJECT	= 2;

	/**
     * Possible value for route type
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_TYPE_LOCAL	= 3;

	/**
     * Possible value for route type
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_TYPE_REMOTE	= 4;

	/**
	 * Look up for text representation of route type
	 * @var array Look up for text representation of route types
	 */
	public static $ROUTE_ENTRY_TYPES = [
		self::ROUTE_ENTRY_TYPE_OTHER	=> 'other',
		self::ROUTE_ENTRY_TYPE_REJECT	=> 'reject',
		self::ROUTE_ENTRY_TYPE_LOCAL	=> 'local',
		self::ROUTE_ENTRY_TYPE_REMOTE	=> 'remote'
	];


	/**
	 * Returns the route type for all connections (see `self::$ROUTE_ENTRY_TYPES`)
	 *
	 * > "The type of route. Note that local(3) refers to a route for which the
	 * > next hop is the final destination; remote(4) refers to a route for
	 * > which the next hop is not the final destina-tion.
	 *
	 * > Routes which do not result in traffic forwarding or rejection should not 
	 * > be displayed even if the implementation keeps them stored internally.
	 * > reject (2) refers to a route which, if matched, discards the message as 
	 * > unreachable. This is used in someprotocols as a means of correctly
	 * > aggregating routes."
	 * 
	 * @param bool $translate If true, use `self::$ROUTE_ENTRY_TYPES` array to return textual representation
	 * @return array The Route types.
	 */
	public function routeType( $translate = false )
	{
		$s = $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_TYPE , 12 , 24 );

		if( !$translate )
			return $s;

		return $this->getSNMP()->translate( $s, self::$ROUTE_ENTRY_TYPES );
	}


	/**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_OTHER	= 1;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_LOCAL	= 2;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_NETMGMT	= 3;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_ICMP	= 4;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_EGP		= 5;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_GGP		= 6;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_HELLO	= 7;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_RIP		= 8;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_ISIS	= 9;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_ESLS	= 10;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_CISCOLGRP = 11;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_BBNSPFLGP = 12;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_OSPF	= 13;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_BGP		= 14;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_IDPR	= 15;

     /**
     * Possible value for route protocol
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_PROTO_CISCOEIGRP = 16;

    /**
     * Look up for text representation of route protcols
     * @var array Look up for text representation of route protcol
     */	
	public static $ROUTE_ENTRY_PROTOS = [
		self::ROUTE_ENTRY_PROTO_OTHER	=> 'other',
		self::ROUTE_ENTRY_PROTO_LOCAL	=> 'local',
		self::ROUTE_ENTRY_PROTO_NETMGMT	=> 'netmgmt',
		self::ROUTE_ENTRY_PROTO_ICMP	=> 'icmp',
		self::ROUTE_ENTRY_PROTO_EGP		=> 'egp',
		self::ROUTE_ENTRY_PROTO_GGP		=> 'ggp',
		self::ROUTE_ENTRY_PROTO_HELLO	=> 'hello',
		self::ROUTE_ENTRY_PROTO_RIP		=> 'RIP',
		self::ROUTE_ENTRY_PROTO_ISIS	=> 'isis',
		self::ROUTE_ENTRY_PROTO_ESLS	=> 'esls',
		self::ROUTE_ENTRY_PROTO_CISCOLGRP => 'Ciscplgrp',
		self::ROUTE_ENTRY_PROTO_BBNSPFLGP => 'bbnSpflgp',
		self::ROUTE_ENTRY_PROTO_OSPF	=> 'ospf',
		self::ROUTE_ENTRY_PROTO_BGP		=> 'bgp',
		self::ROUTE_ENTRY_PROTO_IDPR	=> 'idpr',
		self::ROUTE_ENTRY_PROTO_CISCOEIGRP => 'CiscoEigrp'
	];


	/**
	 * Returns the route protocol.
	 *
	 * > "The routing mechanism via which this route was learned. Inclusion
	 * > of values for gateway rout-ing protocols is not intended to imply
	 * > that hosts should support those protocols."
	 * 
	 * @param bool $translate If true, use the `$ROUTE_ENTRY_PROTOS` array to return textual representation
	 * @return array The route protocols (see `self::$ROUTE_ENTRY_PROTOS`)
	 */
	public function routeProto( $translate = false )
	{
		$s = $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_PROTO , 12 , 24 );
	
		if( !$translate )
			return $s;

		return $this->getSNMP()->translate( $s, self::$ROUTE_ENTRY_PROTOS );
	}


    /**
     * Returns the route age
     *
     * > "The number of seconds since this route was last updated or otherwise determined to be
     * > correct. Note that no semantics of `too old' can be implied except through knowledge
     * > of the routing protocol by which the route was learned."
     *
     * @return array The age of the routes in seconds
     */
	public function routeAge()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_AGE , 12 , 24 );
	}


    /**
     * Returns the route info
     *
     * > "A reference to MIB definitions specific to the particular routing 
     * > protocol which is responsible for this route, as determined by the
     * > value specified in the route's ipCidrRouteProto value.
     * > 
     *
     * @return array A reference to MIB definitions specific to the particular routing protocol.
     */
	public function routeInfo()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_INFO , 12 , 24 );
	}


    /**
     * Returns the AS of the next hop
     *
     * > "The Autonomous System Number of the Next Hop. The semantics of
     * > this object are determined by the routing-protocol specified in
     * > the route's ipCidrRouteProto value. When this object is unknown
     * >  or not relevant its value should be set to zero."
     *
     * @return array The AS of the next hop
     */
	public function routeNextHopAS()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_NEXTHOPAS , 12 , 24 );
	}


    /**
     * The first routing metric for this route
     *
     * @return array The first routing metric for the route
     */
	public function routeMetric1()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_METRIC1 , 12 , 24 );
	}


    /**
     * The second routing metric for this route
     *
     * @return array The first routing metric for the route
     */
	public function routeMetric2()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_METRIC2 , 12 , 24 );
	}


    /**
     * The third routing metric for this route
     *
     * @return array The first routing metric for the route
     */
	public function routeMetric3()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_METRIC3 , 12 , 24 );
	}


    /**
     * The fourth routing metric for this route
     *
     * @return array The first routing metric for the route
     */
	public function routeMetric4()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_METRIC4 , 12 , 24 );
	}


    /**
     * The fifth routing metric for this route
     *
     * @return array The first routing metric for the route
     */
	public function routeMetric5()
	{
		return $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_METRIC5 , 12 , 24 );
	}


    /**
     * Possible value for route status
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_STATUS_ACTIVE	= 1;

    /**
     * Possible value for route status
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_STATUS_NOTINSERVICE = 2;

    /**
     * Possible value for route status
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_STATUS_NOTREADY = 3;

    /**
     * Possible value for route status
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_STATUS_CREATEANDGO = 4;

    /**
     * Possible value for route status
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_STATUS_CREATEANDWAIT = 5;

    /**
     * Possible value for route status
     * @var int Possible value for peer connection state
     */
	const ROUTE_ENTRY_STATUS_DESTROY = 6;


    /**
     * Look up for text representation of route status
     * @var array Look up for text representation of route status
     */
	public static $ROUTE_STATUS_TYPES = [
		self::ROUTE_ENTRY_STATUS_ACTIVE	=> 'active',
		self::ROUTE_ENTRY_STATUS_NOTINSERVICE => 'not in service',
		self::ROUTE_ENTRY_STATUS_NOTREADY => 'not ready',
		self::ROUTE_ENTRY_STATUS_CREATEANDGO => 'create and go',
		self::ROUTE_ENTRY_STATUS_CREATEANDWAIT => 'create and wait',
		self::ROUTE_ENTRY_STATUS_DESTROY => 'destroy'
	];
	

    /**
     * Returns the route status
     *
     * > "The row status variable, used according to row installation and
     * > removal conventions."
     *
     * @return array The routes installation and removal status
     */
	public function routeStatus( $translate = false )
	{
		$s = $this->getSNMP()->subOidWalkLong( self::OID_ROUTE_ENTRY_STATUS , 12 , 24 );
		
		if( !$translate )
			return $s;
		
		return $this->getSNMP()->translate( $s, self::$ROUTE_STATUS_TYPES );
	}


	/**
	 * Utility function to gather all routes into a single array.
	 * @param bool $translate Where a called function supports translation, if true then translate
	 * @return array Array of routes
	 */
	public function routeDetails( $translate = false )
	{
		$fetchList = [
			'routeDest' => 'destination',
			'routeMask' => 'mask',
			'routeTos' => 'TOS',
			'routeNextHop' => 'nextHop',
			'routeIfIndex' => 'ifIndex',
			'routeType' => 'type',
			'routeProto' => 'protocol',
			'routeAge' => 'age',
			'routeInfo' => 'info',
			'routeNextHopAS' => 'nextHopAS',
			'routeMetric1' => 'metric1',
			'routeMetric2' => 'metric2',
			'routeMetric3' => 'metric3',
			'routeMetric4' => 'metric4',
			'routeMetric5' => 'metric5',
			'routeStatus' => 'status'
		];
		$canTranslate = [ 'routeType' , 'routeProto', 'routeStatus' ];
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
 
}
?>
