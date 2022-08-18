<?php

namespace OSS_SNMP\MIBS\Huawei;

/**
 * A class for performing SNMP V2 queries on Huawei devices (DSLAMs)
 */
class System extends \OSS_SNMP\MIB
{
    const OID_SYSTEM_IP_ADDRESS                 = '.1.3.6.1.4.1.2011.6.3.1.1.0';  // no fn() implemented
    const OID_SYSTEM_IP_MASK                    = '.1.3.6.1.4.1.2011.6.3.1.2.0';  // no fn() implemented
    const OID_SYSTEM_SOFTWARE_VERSION           = '.1.3.6.1.4.1.2011.6.3.1.3.0';
    const OID_SYSTEM_TIME                       = '.1.3.6.1.4.1.2011.6.3.1.4.0';  // no fn() implemented
    const OID_SYSTEM_AVERAGE_BUFFER_USED        = '.1.3.6.1.4.1.2011.6.3.1.6.0';  // no fn() implemented
    const OID_SYSTEM_RSVED_VLAN                 = '.1.3.6.1.4.1.2011.6.3.1.7.0';  // no fn() implemented
    const OID_SYSTEM_RSVED_VLAN_DB              = '.1.3.6.1.4.1.2011.6.3.1.8.0';  // no fn() implemented
    const OID_IO_PACKET_VERSION                 = '.1.3.6.1.4.1.2011.6.3.1.9.0';  // no fn() implemented
    const OID_SYSTEM_WORK_SCENARIO              = '.1.3.6.1.4.1.2011.6.3.1.10.0'; // no fn() implemented
    const OID_SYSTEM_TEMPERATURE_HIGH_THRESHOLD = '.1.3.6.1.4.1.2011.6.3.1.11.0'; // no fn() implemented
    const OID_SYSTEM_TEMPERATURE_LOW_THRESHOLD  = '.1.3.6.1.4.1.2011.6.3.1.12.0'; // no fn() implemented
    const OID_SYSTEM_EXCHANGE_MODE              = '.1.3.6.1.4.1.2011.6.3.1.13.0'; // no fn() implemented
    const OID_SYSTEM_ACTIVE_PATCH_VERSION       = '.1.3.6.1.4.1.2011.6.3.1.14.0';
    const OID_SYSTEM_DEACTIVE_PATCH_VERSION     = '.1.3.6.1.4.1.2011.6.3.1.15.0'; // no fn() implemented
    const OID_SYSTEM_ENERGY_SAVING_SWITCH       = '.1.3.6.1.4.1.2011.6.3.1.17.0'; // no fn() implemented
    const OID_SYSTEM_ENCODING                   = '.1.3.6.1.4.1.2011.6.3.1.18.0'; // no fn() implemented
    const OID_SYSTEM_ADMIN_STATE_MODE           = '.1.3.6.1.4.1.2011.6.3.1.21.0'; // no fn() implemented
    const OID_SYSTEM_ADMIN_STATUS               = '.1.3.6.1.4.1.2011.6.3.1.22.0'; // no fn() implemented
    const OID_SYSTEM_VERSION_VRCB               = '.1.3.6.1.4.1.2011.6.3.1.999.0'; // no fn() implemented

    /**
     * Returns the operating system software version
     *
     * @return string The operating system software version
     */
    public function softwareVersion()
    {
        return $this->getSNMP()->get( self::OID_SYSTEM_SOFTWARE_VERSION );
    }

    /**
     * Returns the system activated patch
     *
     * @return string The system activated patch
     */
    public function activatedPatch()
    {
      try {
        return $this->getSNMP()->get( self::OID_SYSTEM_ACTIVE_PATCH_VERSION );
      } catch( \OSS_SNMP\Exception $e ) {
        return null;
      }
    }

}
