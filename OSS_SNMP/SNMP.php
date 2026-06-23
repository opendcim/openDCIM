<?php
/*
    Copyright (c) 2012 - 2015, Open Source Solutions Limited, Dublin, Ireland
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

namespace OSS_SNMP;

// Add a trivial auto-loader
spl_autoload_register( function( $class ) {
    if( substr( $class, 0, 9 ) == 'OSS_SNMP\\' )
    {
        $class = str_replace( '\\', '/', $class );
        require( dirname( __FILE__ ) . '/../' . $class . '.php' );
    }
});


/**
 * A class for performing SNMP V2 queries and processing the results.
 *
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class SNMP
{
    /**
     * The SNMP community to use when polling SNMP services. Defaults to 'public' by the constructor.
     *
     * @var string The SNMP community to use when polling SNMP services. Defaults to 'public' by the constructor.
     */
    protected $_community;

    /**
     * The SNMP host to query. Defaults to '127.0.0.1'
     * @var string The SNMP host to query. Defaults to '127.0.0.1' by the constructor.
     */
    protected $_host;

    /**
     * Wraps the host in [] which forces IPv6
     * @var bool Wraps the host in [] which forces IPv6
     */
    protected $_forceIPv6 = false;


    /**
     * The SNMP host to query. Defaults to v2
     * @var string The SNMP host to query. Defaults to v2 by the constructor.
     */
    protected $_version;

    /**
     * Essentially the same thing as the community for v1 and v2
     */
    protected $_secName;

    /**
     * The security level on the device. Defaults to noAuthNoPriv by the constructor.
     * valid strings: (noAuthNoPriv|authNoPriv|authPriv)
     */
    protected $_secLevel;

    /**
     * The authentication encryption picked on the device.
     * Defaults to MD5 by the constructor.
     * valid strings: (MD5|SHA)
     */
    protected $_authProtocol;

    /**
     * The password for the secName. Defaults to None by the constructor.
     */
    protected $_authPassphrase;

    /**
     * The communication encryption picked on the device.
     * Defaults to DES by the constructor.
     * valid strings: (DES|AES)
     */
    protected $_privProtocol;

    /**
     * The password for the secName. Defaults to None by the constructor.
     */
    protected $_privPassphrase;

    /**
     * The SNMP query timeout value (microseconds). Default: 1000000
     * @var int The SNMP query timeout value (microseconds). Default: 1000000
     */
    protected $_timeout = 1000000;

    /**
     * The SNMP query retry count. Default: 5
     * @var int The SNMP query retry count. Default: 5
     */
    protected $_retry = 5;


    /**
     * A variable to hold the last unaltered result of an SNMP query
     * @var mixed The last unaltered result of an SNMP query
     */
    protected $_lastResult = null;

    /**
     * A variable to hold the platform object
     * @var mixed The platform object
     */
    protected $_platform = null;

    /**
     * The cache object to use as the cache
     * @var \OSS_SNMP\Cache The cache object to use
     */
    protected $_cache = null;


    /**
     * Test / dummy mode to help phpunit testing (yeah, hacky)
     *
     * Initiate this by using host and community: phpunit.test.example.com  /  mXPOSpC52cSFg1qN
     *
     * @var bool
     */
    protected $_dummy = false;

    /**
     * Set to true to disable local cache lookup and force SNMP queries
     *
     * Results are still stored. If you need to force a SNMP query, you can:
     *
     * $snmp = new OSS_SNMP( ... )'
     * ...
     * $snmp->disableCache();
     * $snmp->get( ... );
     * $snmp->enableCache();
     */
    protected $_disableCache = false;

    /**
     * SNMP output constants to mirror those of PHP
     * @var SNMP output constants to mirror those of PHP
     */
    const OID_OUTPUT_FULL    = 3; //SNMP_OID_OUTPUT_FULL;

    /**
     * SNMP output constants to mirror those of PHP
     * @var SNMP output constants to mirror those of PHP
     */
    const OID_OUTPUT_NUMERIC = 4; //SNMP_OID_OUTPUT_NUMERIC;


    /**
     * Definition of an SNMP return type 'TruthValue'
     * @var Definition of an SNMP return type 'TruthValue'
     */
    const SNMP_TRUTHVALUE_TRUE  = 1;

    /**
     * Definition of an SNMP return type 'TruthValue'
     * @var Definition of an SNMP return type 'TruthValue'
     */
    const SNMP_TRUTHVALUE_FALSE = 2;

    /**
     * PHP equivalents of SNMP return type TruthValue
     * @var array PHP equivalents of SNMP return type TruthValue
     */
    public static $SNMP_TRUTHVALUES = array(
        self::SNMP_TRUTHVALUE_TRUE  => true,
        self::SNMP_TRUTHVALUE_FALSE => false
    );

    /**
     * The constructor.
     *
     * @param string $host The target host for SNMP queries.
     * @param string $community The community to use for SNMP queries.
     * @return OSS_SNMP An instance of $this (for fluent interfaces)
     */
    public function __construct( $host = '127.0.0.1', $community = 'public' , $version = '2c' , $seclevel = 'noAuthNoPriv' , $authprotocol = 'MD5' , $authpassphrase = 'None' , $privprotocol = 'DES' , $privpassphrase = 'None' )
    {
        if( $host === 'phpunit.test.example.com' && $community === 'mXPOSpC52cSFg1qN' ) {
            $this->_dummy = true;
        } else {

            // make sure SNMP is installed!
            if( !function_exists( 'snmp2_get' ) ) {
                die( "It looks like the PHP SNMP package is not installed. This is required!\n" );
            }

            $this->setOidOutputFormat( self::OID_OUTPUT_NUMERIC );

        }

        return $this->setHost( $host )
                    ->setCommunity( $community )
                    ->setVersion( $version)
                    ->setSecName( $community )
                    ->setSecLevel( $seclevel )
                    ->setAuthProtocol( $authprotocol )
                    ->setAuthPassphrase( $authpassphrase )
                    ->setPrivProtocol( $privprotocol )
                    ->setPrivPassphrase( $privpassphrase );
    }


    /**
     * Proxy to the snmp2_real_walk command
     *
     * @param string $oid The OID to walk
     * @return array The results of the walk
     */
    public function realWalk( $oid )
    {
        switch( $this->getVersion() ) {
            case 1:
                return $this->_lastResult = @snmprealwalk( $this->getHost(), $this->getCommunity(), $oid, $this->getTimeout(), $this->getRetry() );
                break;
            case '2c':
                return $this->_lastResult = @snmp2_real_walk( $this->getHost(), $this->getCommunity(), $oid, $this->getTimeout(), $this->getRetry() );
                break;
            case '3':
                return $this->_lastResult = @snmp3_real_walk( $this->getHost(), $this->getSecName(), $this->getSecLevel(),
                        $this->getAuthProtocol(), $this->getAuthPassphrase(), $this->getPrivProtocol(), $this->getPrivPassphrase(),
                        $oid, $this->getTimeout(), $this->getRetry()
                    );
                break;
            default:
                throw new Exception( 'Invalid SNMP version: ' . $this->getVersion() );
        }
    }


    /**
     * Get a single SNMP value
     *
     * @throws \OSS_SNMP\Exception On *any* SNMP error, warnings are supressed and a generic exception is thrown
     * @param string $oid The OID to get
     * @return mixed The resultant value
     */
    public function get( $oid )
    {
        if( $this->cache() && ( $rtn = $this->getCache()->load( $oid ) ) !== null )
            return $rtn;

        switch( $this->getVersion() ) {
            case 1:
                $this->_lastResult = @snmpget( $this->getHost(), $this->getCommunity(), $oid, $this->getTimeout(), $this->getRetry() );
                break;
            case '2c':
                $this->_lastResult = @snmp2_get( $this->getHost(), $this->getCommunity(), $oid, $this->getTimeout(), $this->getRetry() );
                break;
            case '3':
                $this->_lastResult = @snmp3_get( $this->getHost(), $this->getSecName(), $this->getSecLevel(),
                        $this->getAuthProtocol(), $this->getAuthPassphrase(), $this->getPrivProtocol(), $this->getPrivPassphrase(),
                        $oid, $this->getTimeout(), $this->getRetry()
                    );
                break;
            default:
                throw new Exception( 'Invalid SNMP version: ' . $this->getVersion() );
        }

        if( $this->_lastResult === false )
            throw new Exception( 'Could not perform walk for OID ' . $oid );

        return $this->getCache()->save( $oid, $this->parseSnmpValue( $this->_lastResult ) );
    }

    /**
     * Get indexed SNMP values (first degree)
     *
     * Walks the SNMP tree returning an array of key => value pairs.
     *
     * This is a first degree walk and it will throw an exception if there is more that one degree of values.
     *
     * I.e. the following query with sample results:
     *
     * walk1d( '.1.0.8802.1.1.2.1.3.7.1.4' )
     *
     *       .1.0.8802.1.1.2.1.3.7.1.4.1 = STRING: "GigabitEthernet1/0/1"
     *       .1.0.8802.1.1.2.1.3.7.1.4.2 = STRING: "GigabitEthernet1/0/2"
     *       .1.0.8802.1.1.2.1.3.7.1.4.3 = STRING: "GigabitEthernet1/0/3"
     *       .....
     *
     * would yield an array:
     *
     *      1 => GigabitEthernet1/0/1
     *      2 => GigabitEthernet1/0/2
     *      3 => GigabitEthernet1/0/3
     *
     * @param string $oid The OID to walk
     * @return array The resultant values
     * @throws \OSS_SNMPException On *any* SNMP error, warnings are supressed and a generic exception is thrown
     */
    public function walk1d( $oid )
    {
        if( $this->cache() && ( $rtn = $this->getCache()->load( $oid ) ) !== null )
            return $rtn;

        $this->_lastResult = $this->realWalk( $oid );

        if( $this->_lastResult === false )
            throw new Exception( 'Could not perform walk for OID ' . $oid );

        $result = array();

        $oidPrefix = null;
        foreach( $this->_lastResult as $_oid => $value )
        {
            if( $oidPrefix !== null && $oidPrefix != substr( $_oid, 0, strrpos( $_oid, '.' ) ) )
                throw new Exception( 'Requested OID tree is not a first degree indexed SNMP value' );
            else
                $oidPrefix = substr( $_oid, 0, strrpos( $_oid, '.' ) );

            $result[ substr( $_oid, strrpos( $_oid, '.' ) + 1 ) ] = $this->parseSnmpValue( $value );
        }

        return $this->getCache()->save( $oid, $result );
    }

    /**
     * Get indexed SNMP values where the array key is the given position of the OID
     *
     * I.e. the following query with sample results:
     *
     * subOidWalk( '.1.3.6.1.4.1.9.9.23.1.2.1.1.9', 15 )
     *
     *
     *       .1.3.6.1.4.1.9.9.23.1.2.1.1.9.10101.5 = Hex-STRING: 00 00 00 01
     *       .1.3.6.1.4.1.9.9.23.1.2.1.1.9.10105.2 = Hex-STRING: 00 00 00 01
     *       .1.3.6.1.4.1.9.9.23.1.2.1.1.9.10108.4 = Hex-STRING: 00 00 00 01
     *
     * would yield an array:
     *
     *      10101 => Hex-STRING: 00 00 00 01
     *      10105 => Hex-STRING: 00 00 00 01
     *      10108 => Hex-STRING: 00 00 00 01
     *
     * subOidWalk( '.1.3.6.1.2.1.17.4.3.1.1', 15, -1 )
     *
     * 		.1.3.6.1.2.1.17.4.3.1.1.0.0.136.54.152.12 = Hex-STRING: 00 00 75 33 4E 92
     * 		.1.3.6.1.2.1.17.4.3.1.1.8.3.134.58.182.16 = Hex-STRING: 00 00 75 33 4E 93
     * 		.1.3.6.1.2.1.17.4.3.1.1.0.4.121.22.55.8 = Hex-STRING: 00 00 75 33 4E 94
     *
     * would yield an array:
     *		[54.152.12] => Hex-STRING: 00 00 75 33 4E 92
     * 		[58.182.16] => Hex-STRING: 00 00 75 33 4E 93
     * 		[22.55.8]   => Hex-STRING: 00 00 75 33 4E 94
     *
     * @throws \OSS_SNMP\Exception On *any* SNMP error, warnings are supressed and a generic exception is thrown
     * @param string $oid The OID to walk
     * @param int $position The position of the OID to use as the key
     * @param int $elements Number of additional elements to include in the returned array keys after $position.
     *                      This defaults to 1 meaning just the requested OID element (see examples above).
     *                      With -1, retrieves ALL to the end.
     *                      If there is less elements than $elements, return all availables (no error).
     *
     * @return array The resultant values
     */
    public function subOidWalk( $oid, $position, $elements = 1)
    {
        if( $this->cache() && ( $rtn = $this->getCache()->load( $oid ) ) !== null )
            return $rtn;

        $this->_lastResult = $this->realWalk( $oid );

        if( $this->_lastResult === false )
            throw new Exception( 'Could not perform walk for OID ' . $oid );

        $result = array();

        foreach( $this->_lastResult as $_oid => $value )
        {
            $oids = explode( '.', $_oid );

            $index = $oids[ $position];
            for( $pos = $position + 1; $pos < sizeof($oids) && ( $elements == -1 || $pos < $position+$elements ); $pos++ ) {
                $index .= '.' . $oids[ $pos ];
            }

            $result[ $index ] = $this->parseSnmpValue( $value );
        }

        return $this->getCache()->save( $oid, $result );
    }



    /**
     * Get indexed SNMP values where they are indexed by IPv4 addresses
     *
     * I.e. the following query with sample results:
     *
     * subOidWalk( '.1.3.6.1.2.1.15.3.1.1. )
     *
     *
     *       .1.3.6.1.2.1.15.3.1.1.10.20.30.4 = IpAddress: 192.168.10.10
     *       ...
     *
     * would yield an array:
     *
     *      [10.20.30.4] => "192.168.10.10"
     *      ....
     *
     * @throws \OSS_SNMP\Exception On *any* SNMP error, warnings are supressed and a generic exception is thrown
     * @param string $oid The OID to walk
     * @return array The resultant values
     */
    public function walkIPv4( $oid )
    {
        if( $this->cache() && ( $rtn = $this->getCache()->load( $oid ) ) !== null )
            return $rtn;

        $this->_lastResult = $this->realWalk( $oid );

        if( $this->_lastResult === false )
            throw new Exception( 'Could not perform walk for OID ' . $oid );

        $result = array();

        foreach( $this->_lastResult as $_oid => $value )
        {
            $oids = explode( '.', $_oid );

            $len = count( $oids );

            $result[ $oids[ $len - 4 ] . '.' . $oids[ $len - 3 ] . '.' . $oids[ $len - 2 ] . '.' . $oids[ $len - 1 ]  ] = $this->parseSnmpValue( $value );
        }

        return $this->getCache()->save( $oid, $result );
    }



    /**
     * Parse the result of an SNMP query into a PHP type
     *
     * For example, [STRING: "blah"] is parsed to a PHP string containing: blah
     *
     * @param string $v The value to parse
     * @return mixed The parsed value
     * @throws Exception
     */
    public function parseSnmpValue( $v )
    {
        // first, rule out an empty string
        if( $v == '""' || $v == '' )
            return "";

        $type = substr( $v, 0, strpos( $v, ':' ) );
        $value = trim( substr( $v, strpos( $v, ':' ) + 1 ) );

        switch( $type )
        {
            case 'STRING':
                if( substr( $value, 0, 1 ) == '"' )
                    $rtn = (string)substr( substr( $value, 1 ), 0, -1 );
                else
                    $rtn = (string)$value;
                break;

            case 'INTEGER':
                if( !is_numeric( $value ) ){
                    // find the first digit and offset the string to that point
                    // just in case there is some mib strangeness going on
                    preg_match('/\d/', $value, $m, PREG_OFFSET_CAPTURE);
                    $rtn = (int)substr( $value, $m[0][1] );
                }else{
                    $rtn = (int)$value;
                }
                break;

            case 'Counter32':
                $rtn = (int)$value;
                break;

            case 'Counter64':
                $rtn = (int)$value;
                break;

                case 'Gauge32':
                $rtn = (int)$value;
                break;

            case 'Hex-STRING':
                $rtn = (string)implode( '', explode( ' ', preg_replace( '/[^A-Fa-f0-9]/', '', $value ) ) );
                break;

            case 'IpAddress':
                $rtn = (string)$value;
                break;

            case 'OID':
                $rtn = (string)$value;
                break;

            case 'Timeticks':
                $rtn = (int)substr( $value, 1, strrpos( $value, ')' ) - 1 );
                break;

            default:
                throw new Exception( "ERR: Unhandled SNMP return type: $type\n" );
        }

        return $rtn;
    }

    /**
     * Utility function to convert TruthValue SNMP responses to true / false
     *
     * @param integer $value The TruthValue ( 1 => true, 2 => false) to convert
     * @return boolean
     */
    public static function ppTruthValue( $value )
    {
        if( is_array( $value ) ) {
            foreach( $value as $k => $v ) {
                $value[$k] = isset(self::$SNMP_TRUTHVALUES[$v]) ? self::$SNMP_TRUTHVALUES[$v] : false;
            }
        } else {
            $value = isset(self::$SNMP_TRUTHVALUES[$value]) ? self::$SNMP_TRUTHVALUES[$value] : false;
        }

        return $value;
    }


    /**
      * An array of arrays where each array element
      * represents true / false values for a given
      * hex digit.
      *
      * @see ppHexStringFlags()
      */
    public static $HEX_STRING_WORDS_AS_ARRAY = [
        '0' => [ false, false, false, false ],
        '1' => [ false, false, false, true  ],
        '2' => [ false, false, true,  false ],
        '3' => [ false, false, true,  true  ],
        '4' => [ false, true,  false, false ],
        '5' => [ false, true,  false, true  ],
        '6' => [ false, true,  true,  false ],
        '7' => [ false, true,  true,  true  ],
        '8' => [ true,  false, false, false ],
        '9' => [ true,  false, false, true  ],
        'a' => [ true,  false, true,  false ],
        'b' => [ true,  false, true,  true  ],
        'c' => [ true,  true,  false, false ],
        'd' => [ true,  true,  false, true  ],
        'e' => [ true,  true,  true,  false ],
        'f' => [ true,  true,  true,  true  ],
    ];

    /**
     * Takes a HEX-String of true / false - on / off - set / unset flags
     * and converts it to an indexed (from 1) array of true / false values.
     *
     * For example, passing it ``500040`` will result in an array:
     *
     *     [
     *         [1]  => false, [2]  => true,  [3] => false, [4]  => true,
     *         [5]  => false, [6]  => false, [7] => false, [8]  => false,
     *         ...
     *         [17] => false, [18] => true, [19] => false, [20] => false,
     *         [21] => false, [22] => true, [23] => false, [24] => false
     *     ]
     *
     * @param string $str The hex string to parse
     * @return array The array of true / false flags indexed from 1
     */
    public static function ppHexStringFlags( $str )
    {
        $str = strtolower( $str );  // ensure all hex digits are lower case

        $values = [ 0 => 'dummy' ];

        for( $i = 0; $i < strlen( $str ); $i++ )
            $values = array_merge( $values, self::$HEX_STRING_WORDS_AS_ARRAY[ $str[$i] ] );

        unset( $values[ 0 ] );

        return $values;
    }

    /**
     * Utility function to translate one value(s) to another via an associated array
     *
     * I.e. all elements '$value' will be replaced with $translator( $value ) where
     * $translator is an associated array.
     *
     * Huh? Just read the code below!
     *
     * @param mixed $values A scalar or array or values to translate
     * @param array $translator An associated array to use to translate the values
     * @return mixed The translated scalar or array
     */
    public static function translate( $values, $translator )
    {
        if( !is_array( $values ) )
        {
            if( isset( $translator[ $values ] ) )
                return $translator[ $values ];
            else
                return "*** UNKNOWN ***";
        }

        foreach( $values as $k => $v )
        {
            if( isset( $translator[ $v ] ) )
                $values[$k] = $translator[ $v ];
            else
                $values[$k] = "*** UNKNOWN ***";
        }

        return $values;
    }

    /**
     * Sets the output format for SNMP queries.
     *
     * Should be one of the class OID_OUTPUT_* constants
     *
     * @param int $f The fomat to use
     * @return OSS_SNMP\SNMP An instance of $this (for fluent interfaces)
     */
    public function setOidOutputFormat( $f )
    {
        snmp_set_oid_output_format( $f );
        return $this;
    }

    public function setSecName( $n )
    {
        $this->_secName = $n;
        return $this;
    }

    public function getSecName()
    {
        return $this->_secName;
    }

    public function setSecLevel( $l )
    {
        $this->_secLevel = $l;
        return $this;
    }

    public function getSecLevel()
    {
        return $this->_secLevel;
    }

    public function setAuthProtocol( $p )
    {
        $this->_authProtocol = $p;
        return $this;
    }

    public function getAuthProtocol()
    {
        return $this->_authProtocol;
    }

    public function setAuthPassphrase( $p )
    {
        $this->_authPassphrase = $p;
        return $this;
    }

    public function getAuthPassphrase()
    {
        return $this->_authPassphrase;
    }

    public function setPrivProtocol( $p )
    {
        $this->_privProtocol = $p;
        return $this;
    }

    public function getPrivProtocol()
    {
        return $this->_privProtocol;
    }

    public function setPrivPassphrase( $p )
    {
        $this->_privPassphrase = $p;
        return $this;
    }

    public function getPrivPassphrase()
    {
        return $this->_privPassphrase;
    }

    /**
     * Sets the version for SNMP queries.
     *
     * @param string $v The version for SNMP queries.
     * @return \OSS_SNMP\SNMP An instance of $this (for fluent interfaces)
     */
    public function setVersion( $v )
    {
        $this->_version = $v;
        return $this;
    }
    /**
     * Gets the version for SNMP queries.
     *
     * @return \OSS_SNMP\SNMP An instance of $this (for fluent interfaces)
     */
    public function getVersion()
    {
        return $this->_version;
    }


    /**
     * Sets the target host for SNMP queries.
     *
     * @param string $h The target host for SNMP queries.
     * @return \OSS_SNMP\SNMP An instance of $this (for fluent interfaces)
     */
    public function setHost( $h )
    {
        // need to be careful with IPv6 addresses
        if( strpos( $h, ':' ) !== false || $this->getForceIPv6() ) {
            $this->_host = '[' . $h . ']';
        } else {
            $this->_host = $h;
        }

        // clear the temporary result cache and last result
        $this->_lastResult = null;
        unset( $this->_resultCache );
        $this->_resultCache = array();

        return $this;
    }

    /**
     * Returns the target host as currently configured for SNMP queries
     *
     * @return string The target host as currently configured for SNMP queries
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Forces use of IPv6
     *
     * @param bool $b Set to true to force IPv4, false for default behavior
     * @return \OSS_SNMP\SNMP An instance of $this (for fluent interfaces)
     */
    public function setForceIPv6( $b )
    {
        $this->_forceIPv6 = $b;
        return $this;
    }

    /**
     * Is IPv6 forced?
     *
     * @return bool True to force IPv4, false for default behavior
     */
    public function getForceIPv6()
    {
        return $this->_forceIPv6;
    }

    /**
     * Sets the community string to use for SNMP queries.
     *
     * @param string $c The community to use for SNMP queries.
     * @return OSS_SNMP An instance of $this (for fluent interfaces)
     */
    public function setCommunity( $c )
    {
        $this->_community = $c;
        return $this;
    }

    /**
     * Returns the community string currently in use.
     *
     * @return string The community string currently in use.
     */
    public function getCommunity()
    {
        return $this->_community;
    }

    /**
     * Sets the timeout to use for SNMP queries (microseconds).
     *
     * @param int $t The timeout to use for SNMP queries (microseconds).
     * @return OSS_SNMP An instance of $this (for fluent interfaces)
     */
    public function setTimeout( $t )
    {
        $this->_timeout = $t;
        return $this;
    }

    /**
     * Returns the SNMP query timeout (microseconds).
     *
     * @return int The the SNMP query timeout (microseconds)
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Sets the SNMP query retry count.
     *
     * @param int $r The SNMP query retry count
     * @return OSS_SNMP An instance of $this (for fluent interfaces)
     */
    public function setRetry( $r )
    {
        $this->_retry = $r;
        return $this;
    }

    /**
     * Returns the SNMP query retry count
     *
     * @return string The SNMP query retry count
     */
    public function getRetry()
    {
        return $this->_retry;
    }

    /**
     * Returns the unaltered original last SNMP result
     *
     * @return mixed The unaltered original last SNMP result
     */
    public function getLastResult()
    {
        return $this->_lastResult;
    }

    /**
     * Returns the internal result cache
     *
     * @return array The internal result cache
     */
    public function getResultCache()
    {
        return $this->_resultCache;
    }


    /**
     * Disable lookups of the local cache
     *
     * @return SNMP An instance of this for fluent interfaces
     */
    public function disableCache()
    {
        $this->_disableCache = true;
        return $this;
    }


    /**
     * Enable lookups of the local cache
     *
     * @return SNMP An instance of this for fluent interfaces
     */
    public function enableCache()
    {
        $this->_disableCache = false;
        return $this;
    }

    /**
     * Query whether we are using the cache or not
     *
     * @return boolean True of the local lookup cache is enabled. Otherwise false.
     */
    public function cache()
    {
        return !$this->_disableCache;
    }

    /**
     * Set the cache to use
     *
     * @param \OSS_SNMP\Cache $c The cache to use
     * @return \OSS_SNMP\SNMP For fluent interfaces
     */
    public function setCache( $c )
    {
        $this->_cache = $c;
        return $this;
    }

    /**
     * Get the cache in use (or create a Cache\Basic instance
     *
     * We kind of mandate the use of a cache as the code is written with a cache in mind.
     * You are free to disable it via disableCache() but your machines may be hammered!
     *
     * We would suggest disableCache() / enableCache() used in pairs only when really needed.
     *
     * @return \OSS_SNMP\Cache The cache object
     */
    public function getCache()
    {
        if( $this->_cache === null )
            $this->_cache = new \OSS_SNMP\Cache\Basic();

        return $this->_cache;
    }


    /**
     * Magic method for generic function calls
     *
     * @param string $method
     * @param array $args
     * @throws Exception
     */
    public function __call( $method, $args )
    {
        if( substr( $method, 0, 3 ) == 'use' )
            return $this->useExtension( substr( $method, 3 ), $args );

        throw new Exception( "ERR: Unknown method requested in magic __call(): $method\n" );
    }


    /**
     * This is the MIB Extension magic
     *
     * Calling $this->useXXX_YYY_ZZZ()->fn() will instantiate
     * an extension MIB class is the given name and this $this SNMP
     * instance and then call fn().
     *
     * See the examples for more information.
     *
     * @param string $mib The extension class to use
     * @param array $args
     * @return \OSS_SNMP\MIBS
     */
    public function useExtension( $mib, $args )
    {
        $mib = '\\OSS_SNMP\\MIBS\\' . str_replace( '_', '\\', $mib );
        $m = new $mib();
        $m->setSNMP( $this );
        return $m;
    }


    public function getPlatform()
    {
        if( $this->_platform === null )
            $this->_platform = new Platform( $this );

        return $this->_platform;
    }


    /**
     * Get indexed SNMP values where the array key is spread over a number of OID positions
     *
     * @throws \OSS_SNMP\Exception On *any* SNMP error, warnings are supressed and a generic exception is thrown
     * @param string $oid The OID to walk
     * @param int $positionS The start position of the OID to use as the key
     * @param int $positionE The end position of the OID to use as the key
     * @return array The resultant values
     */
    public function subOidWalkLong( $oid, $positionS, $positionE )
    {
        if( $this->cache() && ( $rtn = $this->getCache()->load( $oid ) ) !== null )
            return $rtn;

        $this->_lastResult = $this->realWalk( $oid );

        if( $this->_lastResult === false )
            throw new Exception( 'Could not perform walk for OID ' . $oid );

        $result = array();

        foreach( $this->_lastResult as $_oid => $value )
        {
            $oids = explode( '.', $_oid );

            $oidKey = '';
            for($i = $positionS; $i <= $positionE; $i++)
            {
                $oidKey .= $oids[$i] .'.';
            }

            $result[ $oidKey ] = $this->parseSnmpValue( $value );
        }

        return $this->getCache()->save( $oid, $result );
    }

    /**
     * Set the value of an SNMP object
     *
     * @param string $oid The OID to set
     * @param string $type The MIB defines the type of each object id
     * @param mixed $value The new value
     * @return boolean
     */
    public function set($oid, $type, $value)
    {
        switch( $this->getVersion() ) {
            case 1:
                $this->_lastResult = @snmpset( $this->getHost(), $this->getCommunity(), $oid, $type, $value, $this->getTimeout(), $this->getRetry() );
                break;
            case '2c':
                $this->_lastResult = @snmp2_set( $this->getHost(), $this->getCommunity(), $oid, $type, $value, $this->getTimeout(), $this->getRetry() );
                break;
            case '3':
                $this->_lastResult = @snmp3_set( $this->getHost(), $this->getSecName(), $this->getSecLevel(),
                        $this->getAuthProtocol(), $this->getAuthPassphrase(), $this->getPrivProtocol(), $this->getPrivPassphrase(),
                        $oid, $type, $value, $this->getTimeout(), $this->getRetry()
                    );
                break;
            default:
                throw new Exception( 'Invalid SNMP version: ' . $this->getVersion() );
        }

        if( $this->_lastResult === false )
            throw new Exception( 'Could not add variable ' . $value . ' for OID ' . $oid );

       $this->getCache()->clear( $oid );

       return $this->_lastResult;
    }


    /**
     * Indicate if we are in dummy mode or not
     * @return bool
     */
    public function iAmADummy()
    {
        return $this->_dummy === true;
    }

}
