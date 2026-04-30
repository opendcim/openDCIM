<?php

namespace OSS_SNMP\MIBS;

/**
 * A class for performing SNMP V2 queries on devices that supports Vdsl2 line mib.
 *
 * @see https://tools.ietf.org/html/rfc5650
 * @see http://www.circitor.fr/Mibs/Files/VDSL2-LINE-MIB.mib
 */
class Vdsl2Line extends \OSS_SNMP\MIB
{
    const OID_XDSL2_LINE_CONFIG_TEMPLATE                = '.1.3.6.1.2.1.10.251.1.1.1.1.1';
    const OID_XDSL2_LINE_CONFIG_FALLBACK_TEMPLATE       = '.1.3.6.1.2.1.10.251.1.1.1.1.2';  // no fn() implemented
    const OID_XDSL2_LINE_ALARM_CONFIG_TEMPLATE          = '.1.3.6.1.2.1.10.251.1.1.1.1.3';  // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_PMSF              = '.1.3.6.1.2.1.10.251.1.1.1.1.4';  // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_LDSF              = '.1.3.6.1.2.1.10.251.1.1.1.1.5';  // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_LDSF_FAIL_REASON  = '.1.3.6.1.2.1.10.251.1.1.1.1.6';  // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_BPSC              = '.1.3.6.1.2.1.10.251.1.1.1.1.7';  // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_BPSC_FAIL_REASON  = '.1.3.6.1.2.1.10.251.1.1.1.1.8';  // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_BPSC_REQUESTS     = '.1.3.6.1.2.1.10.251.1.1.1.1.9';  // no fn() implemented
    const OID_XDSL2_LINE_AUTOMODE_COLD_START            = '.1.3.6.1.2.1.10.251.1.1.1.1.10'; // no fn() implemented
    const OID_XDSL2_LINE_COMMAND_CONF_RESET             = '.1.3.6.1.2.1.10.251.1.1.1.1.11'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_ACTUAL_TEMPLATE         = '.1.3.6.1.2.1.10.251.1.1.1.1.12'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_XTU_TRANSMISSION_SYSTEM = '.1.3.6.1.2.1.10.251.1.1.1.1.13';
    const OID_XDSL2_LINE_STATUS_POWER_MNG_STATE         = '.1.3.6.1.2.1.10.251.1.1.1.1.14';
    const OID_XDSL2_LINE_STATUS_INIT_RESULT             = '.1.3.6.1.2.1.10.251.1.1.1.1.15'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_LAST_STATE_DS           = '.1.3.6.1.2.1.10.251.1.1.1.1.16'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_LAST_STATE_US           = '.1.3.6.1.2.1.10.251.1.1.1.1.17'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_XTUR                    = '.1.3.6.1.2.1.10.251.1.1.1.1.18'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_XTUC                    = '.1.3.6.1.2.1.10.251.1.1.1.1.19'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_ATTAINABLE_RATE_DS      = '.1.3.6.1.2.1.10.251.1.1.1.1.20';
    const OID_XDSL2_LINE_STATUS_ATTAINABLE_RATE_US      = '.1.3.6.1.2.1.10.251.1.1.1.1.21';
    const OID_XDSL2_LINE_STATUS_ACT_PSD_DS              = '.1.3.6.1.2.1.10.251.1.1.1.1.22'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_ACT_PSD_US              = '.1.3.6.1.2.1.10.251.1.1.1.1.23'; // no fn() implemented
    const OID_XDSL2_LINE_STATUS_ACT_ATP_DS              = '.1.3.6.1.2.1.10.251.1.1.1.1.24';
    const OID_XDSL2_LINE_STATUS_ACT_ATP_US              = '.1.3.6.1.2.1.10.251.1.1.1.1.25';
    const OID_XDSL2_LINE_BAND_STATUS_LINE_ATENNUATION   = '.1.3.6.1.2.1.10.251.1.1.2.1.2';
    const OID_XDSL2_LINE_BAND_STATUS_SNR_MGN            = '.1.3.6.1.2.1.10.251.1.1.2.1.4';
    const OID_XDSL2_CHANNEL_STATUS_ACTUAL_DATA_RATE     = '.1.3.6.1.2.1.10.251.1.2.2.1.2';


    /**
     * Array of the xDSL2 Line Configuration Templates (profiles)
     *
     * e.g.  [2] => line-profile-10
     *
     * @return array Array of line profiles indexed by ifIndex
     */
    public function profiles()
    {
        return $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_CONFIG_TEMPLATE );
    }

    /**
     * The xDSL2 Line Configuration Template (profile)
     *
     * @param int $ifIndex The ifIndex to get the results for
     * @return string The line profile
     */
    public function profile($ifIndex)
    {
        return $this->getSNMP()->get( self::OID_XDSL2_LINE_CONFIG_TEMPLATE . '.' . $ifIndex );
    }


    /**
     * Array of actual net data rates
     *
     * "Actual net data rate at which the bearer channel is operating,
     * if in L0 power management state. In L1 or L2 states, it relates to the previous L0 state.
     * The data rate is coded in bit/s"
     *
     *   'ifIndex.1' => downstream_rate,
     *   'ifIndex.2' => upstream_rate
     *
     * would yield an array:
     *   '1.1' => 7200000,
     *   '1.2' => 1780000
     *
     * @return array Actual net data rates
     */
    public function rates()
    {
        return $this->getSNMP()->subOidWalk( self::OID_XDSL2_CHANNEL_STATUS_ACTUAL_DATA_RATE , 14, -1 );
    }

    /**
     * The actual downstream rate of specified ifIndex
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all dsl ports have all properties.
     *
     * @param  int $ifIndex The ifIndex to get the results for
     * @return int          The actual downstream rate in bit/s
     */
    public function dsRate($ifIndex)
    {
        try
        {
            return $this->getSNMP()->get( self::OID_XDSL2_CHANNEL_STATUS_ACTUAL_DATA_RATE . '.' . $ifIndex . '.1' );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    /**
     * The actual upstream rate of specified ifIndex
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all dsl ports have all properties.
     *
     * @param  int $ifIndex The ifIndex to get the results for
     * @return int          The actual upstream rate in bit/s
     */
    public function usRate($ifIndex)
    {
        try
        {
            return $this->getSNMP()->get( self::OID_XDSL2_CHANNEL_STATUS_ACTUAL_DATA_RATE . '.' . $ifIndex . '.2' );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }


    /**
     * Get an array of device xTU SNR margins
     *
     * "SNR Margin is the maximum increase in dB of the noise power
     * received at the XTU (xTU-R for a band in the downstream direction
     * and xTU-C for a band in the upstream direction), such that
     * the BER requirements are met for all bearer channels received
     * at the XTU. Values range from -640 to 630 in units of 0.1 dB
     * (Physical values are -64 to 63 dB).
     * A special value of 0x7FFFFFFF (2147483647) indicates the
     * SNR Margin is out of range to be represented.
     * A special value of 0x7FFFFFFE (2147483646) indicates the
     * SNR Margin measurement is currently unavailable."
     *
     *  [ "ifindex.band" => snr_margin ]
     *
     * e.g.
     *
     *  [
     *      'ifIndex.upstream'   => snr_margin
     *      'ifIndex.downstream' => snr_margin
     *      'ifIndex.us0'        => snr_margin
     *      'ifIndex.ds1'        => snr_margin
     *      'ifIndex.us1'        => snr_margin
     *      'ifIndex.ds2'        => snr_margin
     *      'ifIndex.us2'        => snr_margin
     *      'ifIndex.ds3'        => snr_margin
     *      'ifIndex.us3'        => snr_margin
     *  ]
     *
     * would yield an array:
     *  [
     *      '1.1' => 255
     *      '1.2' => 118
     *      '1.3' => 255
     *      '1.4' => 118
     *      '1.5' => 255
     *      '1.6' => 118
     *      '1.7' => 255
     *      '1.8' => 118
     *      '1.9' => 255
     *  ]
     *
     * @param int|null $ifIndex The ifIndex to get the results for
     * @return array Device xTU SNR margins
     */
    public function margins($ifIndex = null)
    {
        try
        {
            $oid = self::OID_XDSL2_LINE_BAND_STATUS_SNR_MGN;
            if ($ifIndex) {
                $oid .= '.' . $ifIndex;
            }

            return $this->getSNMP()->subOidWalk( $oid, 14, -1 );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    /**
     * Get line SNR margin for upstream band number 0 (US0)
     *
     * @param  int $ifIndex The ifIndex to get the results for
     * @return int The SNR margin for band US0
     */
    public function us0SnrMargin($ifIndex)
    {
        try
        {
            return $this->getSNMP()->get( self::OID_XDSL2_LINE_BAND_STATUS_SNR_MGN . '.' . $ifIndex . '.3' );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    /**
     * Get line SNR margin for downstream band number 1 (DS1)
     *
     * @param  int $ifIndex The ifIndex to get the results for
     * @return int The SNR margin for band DS1
     */
    public function ds1SnrMargin($ifIndex)
    {
        try
        {
            return $this->getSNMP()->get( self::OID_XDSL2_LINE_BAND_STATUS_SNR_MGN . '.' . $ifIndex . '.4' );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }


    /**
     * Get an array of device xTU line attenuations
     *
     *"Values range from 0 to 1270 in units of 0.1 dB (Physical values
     * are 0 to 127 dB).
     * A special value of 0x7FFFFFFF (2147483647) indicates the line
     * attenuation is out of range to be represented.
     * A special value of 0x7FFFFFFE (2147483646) indicates the line
     * attenuation measurement is unavailable."
     *
     *  [ "ifindex.band" => attenuation ]
     *
     * e.g.
     *
     *  [
     *      'ifIndex.upstream'   => attenuation
     *      'ifIndex.downstream' => attenuation
     *      'ifIndex.us0'        => attenuation
     *      'ifIndex.ds1'        => attenuation
     *      'ifIndex.us1'        => attenuation
     *      'ifIndex.ds2'        => attenuation
     *      'ifIndex.us2'        => attenuation
     *      'ifIndex.ds3'        => attenuation
     *      'ifIndex.us3'        => attenuation
     *  ]
     *
     * would yield an array:
     *  [
     *      '1.1' => 2147483646
     *      '1.2' => 2147483646
     *      '1.3' => 152
     *      '1.4' => 197
     *      '1.5' => 1271
     *      '1.6' => 1271
     *      '1.7' => 1271
     *      '1.8' => 1271
     *      '1.9' => 1271
     *  ]
     *
     * @param int|null $ifIndex The ifIndex to get the results for
     * @return array Device xTU line attenuations (indexed by ifIndex)
     */
    public function attenuations($ifIndex = null)
    {
        try
        {
            $oid = self::OID_XDSL2_LINE_BAND_STATUS_LINE_ATENNUATION;
            if ($ifIndex) {
                $oid .= '.' . $ifIndex;
            }

            return $this->getSNMP()->subOidWalk( $oid, 14, -1 );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    /**
     * Get line attenuation for upstream band number 0 (US0)
     *
     * @param  int $ifIndex The ifIndex to get the results for
     * @return int The line attenuation for band US0
     */
    public function us0Attenuation($ifIndex)
    {
        try
        {
            return $this->getSNMP()->get( self::OID_XDSL2_LINE_BAND_STATUS_LINE_ATENNUATION . '.' . $ifIndex . '.3' );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    /**
     * Get line attenuation for downstream band number 1 (DS1)
     *
     * @param  int $ifIndex The ifIndex to get the results for
     * @return int The line attenuation for band DS1
     */
    public function ds1Attenuation($ifIndex)
    {
        try
        {
            return $this->getSNMP()->get( self::OID_XDSL2_LINE_BAND_STATUS_LINE_ATENNUATION . '.' . $ifIndex . '.4' );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }


    /**
     * Maximum Attainable Data Rate Downstream.
     *
     * "The maximum downstream net data rate currently attainable by
     * the xTU-C transmitter and the xTU-R receiver, coded in bit/s."
     *
     * e.g. [1] => 4276000
     *
     * @return array Associate array of downstream attainable rates indexed by ifIndex
     */
    public function dsAttainableRates()
    {
        return $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_STATUS_ATTAINABLE_RATE_DS );
    }

    /**
     * Maximum Attainable Data Rate Downstream of specified ifIndex
     *
     * @param int $ifIndex The ifIndex to get the results for
     * @return array Associate array of downstream attainable rates indexed by ifIndex
     */
    public function dsAttainableRate($ifIndex)
    {
        return $this->getSNMP()->get( self::OID_XDSL2_LINE_STATUS_ATTAINABLE_RATE_DS . "." . $ifIndex );
    }


    /**
     * Maximum Attainable Data Rate Upstream.
     *
     * "The maximum upstream net data rate currently attainable by the
     * xTU-R transmitter and the xTU-C receiver, coded in bit/s."
     *
     * e.g. [1] => 1252000
     *
     * @return array Associate array of upstream attainable rates indexed by ifIndex
     */
    public function usAttainableRates()
    {
        return $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_STATUS_ATTAINABLE_RATE_US );
    }

    /**
     * Maximum Attainable Data Rate Upstream of specified ifIndex
     *
     * @param int $ifIndex The ifIndex to get the results for
     * @return array Associate array of downstream attainable rates indexed by ifIndex
     */
    public function usAttainableRate($ifIndex)
    {
        return $this->getSNMP()->get( self::OID_XDSL2_LINE_STATUS_ATTAINABLE_RATE_US . "." . $ifIndex );
    }


    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_T1413 = '0000000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ETSI_DTS = '4000000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_POTS_NON_OVERLAPPED = '2000000000000000';

     /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_POTS_OVERLAPPED = '1000000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_ISDN_NON_OVERLAPPED = '0800000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_ISDN_OVERLAPPED = '0400000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_TCM_ISDN_NON_OVERLAPPED = '0200000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_TCM_ISDN_OVERLAPPED = '0100000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITE_POTS_NON_OVERLAPPED = '0080000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITE_POTS_OVERLAPPED = '0040000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITE_TCM_ISDN_NON_OVERLAPPED = '0020000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITE_TCM_ISDN_OVERLAPPED = '0010000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GDMT_TCM_ISDN_SYMMETRIC = '0008000000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_NON_OVERLAPPED = '0000200000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_OVERLAPPED = '0000100000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_ISDN_NON_OVERLAPPED = '0000080000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_ISDN_OVERLAPPED = '0000040000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITEBIS_POTS_NON_OVERLAPPED = '0000008000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITEBIS_POTS_OVERLAPPED = '0000004000000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_I_ALL_DIGITAL_NON_OVERLAPPED = '0000000800000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_I_ALL_DIGITAL_OVERLAPPED = '0000000400000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_J_ALL_DIGITAL_NON_OVERLAPPED = '0000000200000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_J_ALL_DIGITAL_OVERLAPPED = '0000000100000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITEBIS_ANNEX_I_NON_OVERLAPPED = '0000000080000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_GLITEBIS_ANNEX_I_OVERLAPPED = '0000000040000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_NON_OVERLAPPED_MODE1 = '0000000020000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_NON_OVERLAPPED_MODE2 = '0000000010000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_OVERLAPPED_MODE3 = '0000000008000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_OVERLAPPED_MODE4 = '0000000004000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_M_NON_OVERLAPPED = '0000000002000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_M_OVERLAPPED = '0000000001000000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_NON_OVERLAPPED = '0000000000800000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_OVERLAPPED = '0000000000400000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ISDN_NON_OVERLAPPED = '0000000000200000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ISDN_OVERLAPPED = '0000000000100000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_I_ALL_DIGITAL_NON_OVERLAPPED = '0000000000020000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_I_ALL_DIGITAL_OVERLAPPED = '0000000000010000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_J_ALL_DIGITAL_NON_OVERLAPPED = '0000000000008000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_J_ALL_DIGITAL_OVERLAPPED = '0000000000004000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_ANNEX_M_NON_OVERLAPPED = '0000000000002000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_ANNEX_M_OVERLAPPED = '0000000000001000';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_VDSL2_ANNEX_A = '0000000000000080';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_VDSL2_ANNEX_B = '0000000000000040';

    /**
     * Constant for possible value of transmission mode.
     * @see transmissionModes()
     */
    const XDSL2_TRANSMISSION_MODE_VDSL2_ANNEX_C = '0000000000000020';


    /**
     * Text representation of transmission modes.
     *
     * @see transmissionModes()
     * @var array Text representations of transmission modes.
     */
    public static $XDSL2_TRANSMISSION_MODES = array(
        self::XDSL2_TRANSMISSION_MODE_T1413                                         => 'T1.413',
        self::XDSL2_TRANSMISSION_MODE_ETSI_DTS                                      => 'ETSI DTS/TM06006',
        self::XDSL2_TRANSMISSION_MODE_GDMT_POTS_NON_OVERLAPPED                      => 'G.992.1 POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GDMT_POTS_OVERLAPPED                          => 'G.992.1 POTS overlapped',
        self::XDSL2_TRANSMISSION_MODE_GDMT_ISDN_NON_OVERLAPPED                      => 'G.992.1 ISDN non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GDMT_ISDN_OVERLAPPED                          => 'G.992.1 ISDN overlapped',
        self::XDSL2_TRANSMISSION_MODE_GDMT_TCM_ISDN_NON_OVERLAPPED                  => 'G.992.1 TCM-ISDN non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GDMT_TCM_ISDN_OVERLAPPED                      => 'G.992.1 TCM-ISDN overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITE_POTS_NON_OVERLAPPED                     => 'G.992.2 POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITE_POTS_OVERLAPPED                         => 'G.992.2 POTS overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITE_TCM_ISDN_NON_OVERLAPPED                 => 'G.992.2 with TCM-ISDN non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITE_TCM_ISDN_OVERLAPPED                     => 'G.992.2 with TCM-ISDN overlapped',
        //Bit 13-17: Reserved
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_NON_OVERLAPPED                     => 'G.992.3 POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_OVERLAPPED                         => 'G.992.3 POTS overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_ISDN_NON_OVERLAPPED                     => 'G.992.3 ISDN non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_ISDN_OVERLAPPED                         => 'G.992.3 ISDN overlapped',
        //Bit 22-23: Reserved
        self::XDSL2_TRANSMISSION_MODE_GLITEBIS_POTS_NON_OVERLAPPED                  => 'G.992.4 POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITEBIS_POTS_OVERLAPPED                      => 'G.992.4 POTS overlapped',
        //Bit 26-27: Reserved
        self::XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_I_ALL_DIGITAL_NON_OVERLAPPED      => 'G.992.3 Annex I All-Digital non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_I_ALL_DIGITAL_OVERLAPPED          => 'G.992.3 Annex I All-Digital overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_J_ALL_DIGITAL_NON_OVERLAPPED      => 'G.992.3 Annex J All-Digital non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_ANNEX_J_ALL_DIGITAL_OVERLAPPED          => 'G.992.3 Annex J All-Digital overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITEBIS_ANNEX_I_NON_OVERLAPPED               => 'G.992.4 Annex I All-Digital non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_GLITEBIS_ANNEX_I_OVERLAPPED                   => 'G.992.4 Annex I All-Digital overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_NON_OVERLAPPED_MODE1       => 'G.992.3 Annex L POTS non-overlapped, mode 1, wide U/S',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_NON_OVERLAPPED_MODE2       => 'G.992.3 Annex L POTS non-overlapped, mode 2, narrow U/S',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_OVERLAPPED_MODE3           => 'G.992.3 Annex L POTS overlapped, mode 3, wide U/S',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_L_OVERLAPPED_MODE4           => 'G.992.3 Annex L POTS overlapped, mode 4, narrow U/S',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_M_NON_OVERLAPPED             => 'G.992.3 Annex M POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2_POTS_ANNEX_M_OVERLAPPED                 => 'G.992.3 Annex M POTS overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_NON_OVERLAPPED                 => 'G.992.5 POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_OVERLAPPED                     => 'G.992.5 POTS overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ISDN_NON_OVERLAPPED                 => 'G.992.5 ISDN non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ISDN_OVERLAPPED                     => 'G.992.5 ISDN overlapped',
        //Bit 44-45: Reserved
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_I_ALL_DIGITAL_NON_OVERLAPPED  => 'G.992.5 Annex I All-Digital non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_I_ALL_DIGITAL_OVERLAPPED      => 'G.992.5 Annex I All-Digital overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_J_ALL_DIGITAL_NON_OVERLAPPED  => 'G.992.5 Annex J All-Digital non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_ANNEX_J_ALL_DIGITAL_OVERLAPPED      => 'G.992.5 Annex J All-Digital overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_ANNEX_M_NON_OVERLAPPED         => 'G.992.5 Annex M POTS non-overlapped',
        self::XDSL2_TRANSMISSION_MODE_ADSL2PLUS_POTS_ANNEX_M_OVERLAPPED             => 'G.992.5 Annex M POTS overlapped',
        //Bit 52-55: Reserved
        self::XDSL2_TRANSMISSION_MODE_VDSL2_ANNEX_A                                  => 'G.993.2 Annex A',
        self::XDSL2_TRANSMISSION_MODE_VDSL2_ANNEX_B                                  => 'G.993.2 Annex B',
        self::XDSL2_TRANSMISSION_MODE_VDSL2_ANNEX_C                                  => 'G.993.2 Annex C'
        //Bit 59-63: Reserved
        );


    /**
     * Array of xDSL line transmission modes
     *
     * "The xTU Transmission System (xTS) in use. It is coded in a bitmap representation with
     * one bit set to '1' (the selected coding for the DSL line). This parameter may be derived
     * from the handshaking procedures defined in Recommendation G.994.1. A set of xDSL line
     * transmission modes, with one bit per mode."
     *
     * @see XDSL2_TRANSMISSION_MODES
     * @see https://tools.ietf.org/html/rfc5650 Xdsl2TransmissionModeType
     * @see http://www.circitor.fr/Mibs/Html/VDSL2-LINE-TC-MIB.php#Xdsl2TransmissionModeType
     * @param boolean $translate If true, return the string representation
     * @return array An array of xDSL line transmission modes
     */
    public function transmissionModes($translate = false)
    {
        try
        {
            $modes = $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_STATUS_XTU_TRANSMISSION_SYSTEM );

            if( !$translate ) {
                return $modes;
            }

            return $this->getSNMP()->translate( $modes, self::$XDSL2_TRANSMISSION_MODES );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }


    /**
     * Actual Aggregate Transmit Power Downstream.
     *
     * "The total amount of transmit power delivered by the xTU-C at
     *  the U-C reference point, at the instant of measurement.  It
     *  ranges from -310 to 310 units of 0.1 dBm (physical values are -31
     *  to 31 dBm).
     *  A value of 0x7FFFFFFF (2147483647) indicates the measurement is
     *  out of range to be represented."
     *
     *  e.g. [1] => 192
     *
     * @return array Associate array of downstream actual aggregate transmit powers
     */
    public function dsActualAggregateTransmitPowers()
    {
        return $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_STATUS_ACT_ATP_DS );
    }

    /**
     * Actual Aggregate Transmit Power Upstream.
     *
     * "The total amount of transmit power delivered by the xTU-R at the
     *  U-R reference point, at the instant of measurement.  It ranges
     *  from -310 to 310 units of 0.1 dBm (physical values are -31
     *  to 31 dBm).
     *  A value of 0x7FFFFFFF (2147483647) indicates the measurement is
     *  out of range to be represented."
     *
     * e.g.
     *     [1] => 120
     *     [2] => 104
     *
     * @return array Associate array of upstream actual aggregate transmit powers
     */
    public function usActualAggregateTransmitPowers()
    {
        return $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_STATUS_ACT_ATP_US );
    }


    /**
     * Constant for possible value of power management state.
     * @see powerManagementStates()
     */
    const XDSL2_POWER_MANAGEMENT_STATE_L0 = 1;
    /**
     * Constant for possible value of power management state.
     * @see powerManagementStates()
     */
    const XDSL2_POWER_MANAGEMENT_STATE_L1 = 2;
    /**
     * Constant for possible value of power management state.
     * @see powerManagementStates()
     */
    const XDSL2_POWER_MANAGEMENT_STATE_L2 = 3;
    /**
     * Constant for possible value of power management state.
     * @see powerManagementStates()
     */
    const XDSL2_POWER_MANAGEMENT_STATE_L3 = 4;

    /**
     * Text representation of power management states.
     *
     * @see powerManagementStates()
     * @var array Text representations of power management states.
     */
    public static $XDSL2_POWER_MANAGEMENT_STATES = array(
        self::XDSL2_POWER_MANAGEMENT_STATE_L0  => 'L0',
        self::XDSL2_POWER_MANAGEMENT_STATE_L1  => 'L1',
        self::XDSL2_POWER_MANAGEMENT_STATE_L2  => 'L2',
        self::XDSL2_POWER_MANAGEMENT_STATE_L3  => 'L3'
        );

    /**
     * Array of the current power management states.
     *
     * "One of four possible power management states:
     *     L0 - Synchronized and full transmission (i.e., Showtime),
     *     L1 - Low Power with reduced net data rate (G.992.2 only),
     *     L2 - Low Power with reduced net data rate (G.992.3 and G.992.4 only),
     *     L3 - No power
     * The various possible values are:l0(1), l1(2), l2(3), l3(4)."
     *
     * @see XDSL2_POWER_MANAGEMENT_STATES
     * @param boolean $translate If true, return the string representation
     * @return array An array of xDSL line current power management states
     */
    public function powerManagementStates($translate = false)
    {
        $states = $this->getSNMP()->walk1d( self::OID_XDSL2_LINE_STATUS_POWER_MNG_STATE );
        if( !$translate ) {
            return $states;
        }

        return $this->getSNMP()->translate( $states, self::$XDSL2_POWER_MANAGEMENT_STATES );
    }

}