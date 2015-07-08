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
    const OID_EATON_VERSION_STRING = '.1.3.6.1.4.1.534.6.6.7.1.2.1.5.0';
	const OID_EATON_INPUT_WATTS    = '.1.3.6.1.4.1.534.6.6.7.3.5.1.4.0.1';
	const OID_EATON_INPUT_VA       = '.1.3.6.1.4.1.534.6.6.7.3.5.1.3.0.1';

	const OID_EATON_OUTLET_COUNT   = '.1.3.6.1.4.1.534.6.6.7.1.2.1.22.0';
    const OID_EATON_OUTLET_ID      = '.1.3.6.1.4.1.534.6.6.7.6.1.1.2.0';
    const OID_EATON_OUTLET_NAME    = '.1.3.6.1.4.1.534.6.6.7.6.1.1.3.0';
    const OID_EATON_OUTLET_TYPE    = '.1.3.6.1.4.1.534.6.6.7.6.1.1.5.0';
}

public static $OUTLET_TYPES = array(
    0  => 'unknown',
    1  => 'iecC13',
    2  => 'iecC19',
    10 => 'uk',
    11 => 'french',
    12 => 'schuko',
    20 => 'nema515',
    21 => 'nema51520',
    22 => 'nema520',
    23 => 'nemaL520',
    24 => 'nemaL530',
    25 => 'nema615',
    26 => 'nema620',
    27 => 'nemaL620',
    28 => 'nemaL630',
    29 => 'nemaL715',
    30 => 'rf203p277',
);

public function types( $translate = false )
{
    $types = $this->getSNMP()->walk1d( self::OID_EATON_OUTLET_TYPE );

    if( !$translate )
        return $types;

    return $this->getSNMP->translate( $types, self::$OUTLET_TYPES );
}
