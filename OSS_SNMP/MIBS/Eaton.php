<?php

/*
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
 * A class for performing SNMP V2 queries on Eaton devices
 *
 * @author Wilbur Longwisch <wilpig@wilpig.com>
 */
class Eaton extends \OSS_SNMP\MIB
{
    const OID_EATON_PRODUCT_STRING     = '.1.3.6.1.4.1.534.6.6.7.1.2.1.2.0';
    const OID_EATON_PARTNUM_STRING     = '.1.3.6.1.4.1.534.6.6.7.1.2.1.3.0';
    const OID_EATON_SERIAL_STRING      = '.1.3.6.1.4.1.534.6.6.7.1.2.1.4.0';
    const OID_EATON_VERSION_STRING     = '.1.3.6.1.4.1.534.6.6.7.1.2.1.5.0';
    const OID_EATON_NAME_STRING        = '.1.3.6.1.4.1.534.6.6.7.1.2.1.6.0';

    const OID_EATON_INPUT_VA           = '.1.3.6.1.4.1.534.6.6.7.3.5.1.3.0.1';
    const OID_EATON_INPUT_WATTS        = '.1.3.6.1.4.1.534.6.6.7.3.5.1.4.0.1';
    const OID_EATON_INPUT_ID           = '.1.3.6.1.4.1.534.6.6.7.3.1.1.1.0';
    const OID_EATON_INPUT_TYPE         = '.1.3.6.1.4.1.534.6.6.7.3.1.1.2.0';
    const OID_EATON_INPUT_COUNT        = '.1.3.6.1.4.1.534.6.6.7.3.1.1.7.0';
    const OID_EATON_INPUT_PLUG_TYPE    = '.1.3.6.1.4.1.534.6.6.7.3.1.1.8.0';

    const OID_EATON_GROUP_COUNT        = '.1.3.6.1.4.1.534.6.6.7.1.2.1.22.0';
    const OID_EATON_GROUP_ID           = '.1.3.6.1.4.1.534.6.6.7.5.1.1.2.0';
    const OID_EATON_GROUP_NAME         = '.1.3.6.1.4.1.534.6.6.7.5.1.1.3.0';
    const OID_EATON_GROUP_TYPE         = '.1.3.6.1.4.1.534.6.6.7.5.1.1.4.0';
    const OID_EATON_GROUP_STATUS       = '.1.3.6.1.4.1.534.6.6.7.5.1.1.5.0';

    const OID_EATON_OUTLET_COUNT       = '.1.3.6.1.4.1.534.6.6.7.1.2.1.22.0';
    const OID_EATON_OUTLET_ID          = '.1.3.6.1.4.1.534.6.6.7.6.1.1.2.0';
    const OID_EATON_OUTLET_NAME        = '.1.3.6.1.4.1.534.6.6.7.6.1.1.3.0';
    const OID_EATON_OUTLET_TYPE        = '.1.3.6.1.4.1.534.6.6.7.6.1.1.5.0';
    const OID_EATON_OUTLET_STATUS      = '.1.3.6.1.4.1.534.6.6.7.6.6.1.2.0';
    const OID_EATON_OUTLET_SWITCHABLE  = '.1.3.6.1.4.1.534.6.6.7.6.6.1.9.0';

    const OID_EATON_TEMP_COUNT         = '.1.3.6.1.4.1.534.6.6.7.1.2.1.23.0';
    const OID_EATON_TEMP_ID            = '.1.3.6.1.4.1.534.6.6.7.7.1.1.1.0';
    const OID_EATON_TEMP_NAME          = '.1.3.6.1.4.1.534.6.6.7.7.1.1.2.0';
    const OID_EATON_TEMP_STATUS        = '.1.3.6.1.4.1.534.6.6.7.7.1.1.3.0';
    const OID_EATON_TEMP_VALUE         = '.1.3.6.1.4.1.534.6.6.7.7.1.1.4.0';

    const OID_EATON_HUMIDITY_COUNT     = '.1.3.6.1.4.1.534.6.6.7.1.2.1.24.0';
    const OID_EATON_HUMIDITY_ID        = '.1.3.6.1.4.1.534.6.6.7.7.2.1.1.0';
    const OID_EATON_HUMIDITY_NAME      = '.1.3.6.1.4.1.534.6.6.7.7.2.1.2.0';
    const OID_EATON_HUMIDITY_STATUS    = '.1.3.6.1.4.1.534.6.6.7.7.2.1.3.0';
    const OID_EATON_HUMIDITY_VALUE     = '.1.3.6.1.4.1.534.6.6.7.7.2.1.4.0';

	public static $OUTLET_TYPES = array(
	   0   => 'unknown',
	   1   => 'iecC13',
	   2   => 'iecC19',
	   10  => 'uk',
	   11  => 'french',
	   12  => 'schuko',
	   20  => 'nema515',
	   21  => 'nema51520',
	   22  => 'nema520',
	   23  => 'nemaL520',
	   24  => 'nemaL530',
	   25  => 'nema615',
	   26  => 'nema620',
	   27  => 'nemaL620',
	   28  => 'nemaL630',
	   29  => 'nemaL715',
	   30  => 'rf203p277',
	   100 => 'other1Phase',
	   200 => 'other2Phase',
	   300 => 'other3Phase',
	   101 => 'iecC14Inlet',
	   102 => 'iecC20Inlet',
	   103 => 'iec316P6',
	   104 => 'iec332P6',
	   105 => 'iec360P6',
	   106 => 'iecC14Plug',
	   107 => 'iecC20Plug',
	   120 => 'nema515',
	   121 => 'nemaL515',
	   122 => 'nema520',
	   123 => 'nemaL520',
	   124 => 'nema615',
	   125 => 'nemaL615',
	   126 => 'nemaL530',
	   127 => 'nema620',
	   128 => 'nemaL620',
	   129 => 'nemaL630',
	   130 => 'cs8265',
	   150 => 'french',
	   151 => 'schuko',
	   152 => 'uk',
	   201 => 'nemaL1420',
	   202 => 'nemaL1430',
	   301 => 'iec516P6',
	   302 => 'iec460P9',
	   303 => 'iec560P9',
	   304 => 'iec532P6',
	   306 => 'iec563P6',
	   320 => 'nemaL1520',
	   321 => 'nemaL2120',
	   322 => 'nemaL1530',
	   323 => 'nemaL2130',
	   324 => 'cs8365',
	   325 => 'nemaL2220',
	   326 => 'nemaL2230',
	   350 => 'bladeUps208V',
	   351 => 'bladeUps400V'
	);

	public static $OUTLET_STATUS = array(
		0  => 'off',
		1  => 'on',
		2  => 'pendingOff',
		3  => 'pendingOn'
	);

	public static $INPUT_TYPES = array(
		1  => 'singlePhase',
		2  => 'splitPhase',
		3  => 'threePhaseDelta',
		4  => 'threePhaseWye'
	);

	public static $SWITCHABLE_STATUS = array(
		1  => 'switchable',
		2  => 'notSwitchable'
	);

	public static $PROBE_STATUS = array(
		-1 => 'bad',
		0  => 'disconnected',
		1  => 'connected'
	);

	public static $GROUP_STATUS = array(
		0  => 'notApplicable',
		1  => 'breakerOn',
		2  => 'breakerOff'
	);

	public function name()
	{
		return $this->getSNMP()->get( self::OID_EATON_NAME_STRING );
	}

	public function description()
	{
		return $this->getSNMP()->walk1d( self::OID_EATON_OUTLET_NAME );
	}

	public function version()
	{
		return $this->getSNMP()->get( self::OID_EATON_VERSION_STRING );
	}

	public function numberOfOutlets()
	{
		return $this->getSNMP()->get( self::OID_EATON_OUTLET_COUNT );
	}

	public function types( $translate = false )
	{
		$types = $this->getSNMP()->walk1d( self::OID_EATON_OUTLET_TYPE );

		if( !$translate )
			return $types;

		return $this->getSNMP()->translate( $types, self::$OUTLET_TYPES );
	}

	public function status( $translate = false )
	{
		$types = $this->getSNMP()->walk1d( self::OID_EATON_OUTLET_STATUS );

		if( !$translate )
			return $types;

		return $this->getSNMP()->translate( $types, self::$OUTLET_STATUS );
	}

	public function totalWatts()
	{
		return $this->getSNMP()->get( self::OID_EATON_INPUT_WATTS );
	}

	public function totalVA()
	{
		return $this->getSNMP()->get( self::OID_EATON_INPUT_VA );
	}

}
