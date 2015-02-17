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

namespace OSS_SNMP\MIBS\Cisco;

/**
 * A class for performing SNMP V2 queries on Cisco devices
 *
 * @copyright Copyright (c) 2012 - 2013, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class SMST extends \OSS_SNMP\MIBS\Cisco
{

    const OID_STP_X_SMST_MAX_INSTANCES         = '.1.3.6.1.4.1.9.9.82.1.14.1.0'; 
    const OID_STP_X_SMST_MAX_INSTANCE_ID       = '.1.3.6.1.4.1.9.9.82.1.14.2.0'; 
    const OID_STP_X_SMST_REGION_REVISION       = '.1.3.6.1.4.1.9.9.82.1.14.3.0'; 

    // OIDs for identifying which VLANs are part of which MST instance
    const OID_STP_X_SMST_INSTANCE_TABLE_VLANS_MAPPED_1K2K  = ".1.3.6.1.4.1.9.9.82.1.14.5.1.2";
    const OID_STP_X_SMST_INSTANCE_TABLE_VLANS_MAPPED_3K4K  = ".1.3.6.1.4.1.9.9.82.1.14.5.1.3";

    const OID_STP_X_SMST_REMAINING_HOP_COUNT         = '.1.3.6.1.4.1.9.9.82.1.14.5.1.4'; 
    const OID_STP_X_SMST_INSTANCE_CIST_REGIONAL_ROOT = '.1.3.6.1.4.1.9.9.82.1.14.5.1.5.0';
    const OID_STP_X_SMST_INSTANCE_CIST_INT_ROOT_COST = '.1.3.6.1.4.1.9.9.82.1.14.5.1.6.0';

    
    /**
     * Returns the maximum number of MST instances
     *
     * > "The maximum number of MST instances
     * > that can be supported by the device for IEEE MST"
     *
     * @return string The maximum number of MST instances
     */
    public function maxInstances()
    {
        return $this->getSNMP()->get( self::OID_STP_X_SMST_MAX_INSTANCES );
    }

    /**
     * Returns the maximum MST instance ID
     *
     * > "The maximum MST (Multiple Spanning Tree) instance id, 
     * > that can be supported by the device for IEEE MST"
     *
     * @return string The maximum MST instance ID
     */
    public function maxInstanceId()
    {
        return $this->getSNMP()->get( self::OID_STP_X_SMST_MAX_INSTANCE_ID );
    }

    /**
     * Returns the operational SMST region revision.
     *
     * @return string The operational SMST region revision
     */
    public function regionRevision()
    {
        return $this->getSNMP()->get( self::OID_STP_X_SMST_REGION_REVISION );
    }
    

    /**
     * Return array of MST instances containing an array of mapped VLANs
     *
     * The form of the returned array is:
     *
     *     [
     *         $mstInstanceId => [
     *             $vlanTag => true / false,
     *             $vlanTag => true / false,
     *             ...
     *         ],
     *         ...
     *     ]
     *
     * If a VLAN tag is not present in the array of VLANs, then it is not a member of that MST instance. 
     *
     * @see vlansMappedAsRanges()
     * @param int $instanceId Limit results to a single instance ID (returned array is just vlans)
     * @return Array as described above.
     *
     */
    public function vlansMapped( $instanceID = false )
    {
        $vlansMapped = [];

        $instances1k2k = $this->getSNMP()->walk1d( self::OID_STP_X_SMST_INSTANCE_TABLE_VLANS_MAPPED_1K2K );
        $instances3k4k = $this->getSNMP()->walk1d( self::OID_STP_X_SMST_INSTANCE_TABLE_VLANS_MAPPED_3K4K );

        if( $instanceID )
        {
            foreach( $instances1k2k as $id => $instances )
                if( $id != $instanceID )
                    unset( $instances1k2k[ $id ] );

            foreach( $instances3k4k as $id => $instances )
                if( $id != $instanceID )
                    unset( $instances3k4k[ $id ] );
        }

        foreach( [ -1 => $instances1k2k, 2047 => $instances3k4k ] as $offset => $instances )
        {
            foreach( $instances as $instanceId => $mapped )
            {
                $mapped = $this->getSNMP()->ppHexStringFlags( $mapped );
                foreach( $mapped as $vlanid => $flag )
                {
                    // Cisco seems to be returning some crud. Strip it out:
                    if( $vlanid + $offset <= 0 || $vlanid + $offset > 4094 )
                        continue;

                    $vlansMapped[ $instanceId ][ $vlanid + $offset ] = $flag;
                }
            }
        }

        if( $instanceID )
            $vlansMapped = $vlansMapped[ $instanceID ];

        return $vlansMapped;
    }

    /**
     * Return array of MST instances containing an array of mapped VLAN ranges
     *
     * The form of the returned array is:
     *
     *     [
     *         $mstInstanceId => [
     *             500-599,
     *             3000-4094,
     *             ...
     *         ],
     *         ...
     *     ]
     *
     * Example usage:
     *
     *     foreach( $ports as $id => $portConf )
     *     {
     *         echo sprintf( "%-16s - %-8s:\t", $portConf['host'], $portConf['port'] );
     *         echo $hosts[ $portConf['host'] ]->useIface()->operationStates( true )[ $portNameToIndex[ $portConf['host'] ][ $portConf['port'] ] ] . "\n"; 
     *     }
     *
     * Which results in (for example):
     *
     *     MST0       vlans mapped: 1-299,400-499,600-799,900-999,1800-4094
     *     MST1       vlans mapped: 300-399
     *     MST2       vlans mapped: 500-599,800-899,1000-1099,1300-1499
     *     MST3       vlans mapped: 1500-1599
     *     MST4       vlans mapped: 1100-1199
     *     MST5       vlans mapped: 1200-1299
     *     MST6       vlans mapped: 1600-1799
     * 
     *
     * @see vlansMapped()
     * @param int $instanceId Limit results to a single instance ID (returned array is one dimensional)
     * @return Array as described above.
     *
     */
    public function vlansMappedAsRanges( $instanceID = false )
    {
        $vlansMapped = $this->vlansMapped( $instanceID );

        if ( $instanceID )
            $vlansMapped[ $instanceID ] = $vlansMapped;

        $ranges = [];

        // big loop to turn sequential VLANs into ranges
        // FIXME extract as utility function?
        foreach( $vlansMapped as $id => $mapped )
        {
            $start = false;
            $inc = false;

            foreach( $mapped as $vid => $flag )
            {
                if( $flag )
                {
                    if( !$start )
                    {
                        $start = $vid;
                        $inc   = $vid;
                        continue;
                    }

                    if( $vid - $inc == 1 )
                    {
                        $inc++;
                        continue;
                    }

                    if( $vid - $inc != 1 )
                    {
                        if( $start == $inc )
                            $ranges[ $id ][] = $start;
                        else
                            $ranges[ $id ][] = "{$start}-{$inc}";

                        $start = false;
                        continue;
                    }
                }
                else
                {
                    if( !$start )
                        continue;
                    else
                    {
                        if( $start == $inc )
                            $ranges[ $id ][] = $start;
                        else
                            $ranges[ $id ][] = "{$start}-{$inc}";

                        $start = false;
                        continue;
                    }

                }

            }

            if( $start )
            {
                if( $start == $inc )
                    $ranges[ $id ][] = $start;
                else
                    $ranges[ $id ][] = "{$start}-{$inc}";
            }
        }

        if( $instanceID )
            return $ranges[ $instanceID ];

        return $ranges;
    }

    /**
     * Returns the remaining hop count for all MST instances
     *
     * > "The remaining hop count for this MST instance. If this object
     * > value is not applicable on an MST instance, then the value
     * > retrieved for this object for that MST instance will be -1. 
     * > 
     * > This object is only instantiated when the object value of
     * > stpxSpanningTreeType is mst(4)."
     *
     * @return array The remaining hop count for all MST instances
     */
    public function remainingHopCount()
    {
        return $this->getSNMP()->walk1d( self::OID_STP_X_SMST_REMAINING_HOP_COUNT );
    }
    
    /**
     * Returns an array of running MST instances.
     *
     * This is a hack on the remainingHopCount() as the MIB of this
     * is empty on my test box (.1.3.6.1.4.1.9.9.82.1.14.5.1.1)
     *
     * We name the instances as well based on the region name / use specified string.
     *
     * @param string $name If null, then instances are named using the MST region name. Else this is the root of the name.
     * @return array The running MST instances
     */
    public function instances( $name = null )
    {
        if( $name === null )
            $name = $this->getSNMP()->useCisco_MST()->regionName() . '.';
            
        $hops = $this->remainingHopCount();
        
        $instances = [];
        foreach( $hops as $i => $h )
            if( $h != -1 )
                $instances[ $i ] = "{$name}{$i}";
        
        return $instances;
    }
    
    /**
     * Returns the maximum number of MST instances
     *
     * > "Indicates the Bridge Identifier (refer to BridgeId 
     * > defined in BRIDGE-MIB) of CIST (Common and Internal 
     * > Spanning Tree) Regional Root for the MST region.
     * > 
     * > This object is only instantiated when the object value of
     * > stpxSpanningTreeType is mst(4) and stpxSMSTInstanceIndex
     * > is 0."
     *
     * @return string The bridge identifier of the CIST regional root for the MST region
     */
    public function cistRegionalRoot()
    {
        return $this->getSNMP()->get( self::OID_STP_X_SMST_INSTANCE_CIST_REGIONAL_ROOT );
    }

    /**
     * Returns the CIST Internal Root Path Cost
     *
     * > "Indicates the CIST Internal Root Path Cost, i.e., the
     * > path cost to the CIST Regional Root as specified by the
     * > corresponding stpxSMSTInstanceCISTRegionalRoot for the 
     * > MST region.
     * > 
     * > This object is only instantiated when the object value of
     * > stpxSpanningTreeType is mst(4) and stpxSMSTInstanceIndex
     * > is 0."
     *
     * @return string The bridge identifier of the CIST regional root for the MST region
     */
    public function cistIntRootCost()
    {
        return $this->getSNMP()->get( self::OID_STP_X_SMST_INSTANCE_CIST_INT_ROOT_COST );
    }


}
