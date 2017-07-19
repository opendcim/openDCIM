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

namespace OSS_SNMP\MIBS\Asterisk;

/**
 * A class for performing SNMP V2 queries on Asterisk
 *
 * @see https://wiki.asterisk.org/wiki/display/AST/Asterisk+MIB+Definitions
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Channels extends \OSS_SNMP\MIB
{

    const OID_ASTERISK_CHANNELS_ACTIVE      = '.1.3.6.1.4.1.22736.1.5.1.0';

    const OID_ASTERISK_CHANNELS_SUPPORTED   = '.1.3.6.1.4.1.22736.1.5.3.0';

    const OID_ASTERISK_CHANNEL_TYPE_NAME        = '.1.3.6.1.4.1.22736.1.5.4.1.2';
    const OID_ASTERISK_CHANNEL_TYPE_DESCRIPTION = '.1.3.6.1.4.1.22736.1.5.4.1.3';
    const OID_ASTERISK_CHANNEL_TYPE_STATE       = '.1.3.6.1.4.1.22736.1.5.4.1.4';
    const OID_ASTERISK_CHANNEL_TYPE_INDICATION  = '.1.3.6.1.4.1.22736.1.5.4.1.5';
    const OID_ASTERISK_CHANNEL_TYPE_TRANSFER    = '.1.3.6.1.4.1.22736.1.5.4.1.6';
    const OID_ASTERISK_CHANNEL_TYPE_CHANNELS    = '.1.3.6.1.4.1.22736.1.5.4.1.7';
    
    const OID_ASTERISK_CHANNEL_NAME             = '.1.3.6.1.4.1.22736.1.5.2.1.2';
    const OID_ASTERISK_CHANNEL_LANGUAGE         = '.1.3.6.1.4.1.22736.1.5.2.1.3';
    const OID_ASTERISK_CHANNEL_TYPE             = '.1.3.6.1.4.1.22736.1.5.2.1.4';
    const OID_ASTERISK_CHANNEL_MUSIC_CLASS      = '.1.3.6.1.4.1.22736.1.5.2.1.5';
    const OID_ASTERISK_CHANNEL_BRIDGE           = '.1.3.6.1.4.1.22736.1.5.2.1.6';
    const OID_ASTERISK_CHANNEL_MASQ             = '.1.3.6.1.4.1.22736.1.5.2.1.7';
    const OID_ASTERISK_CHANNEL_MASQR            = '.1.3.6.1.4.1.22736.1.5.2.1.8';
    const OID_ASTERISK_CHANNEL_WHEN_HANGUP      = '.1.3.6.1.4.1.22736.1.5.2.1.9';
    const OID_ASTERISK_CHANNEL_APP              = '.1.3.6.1.4.1.22736.1.5.2.1.10';
    const OID_ASTERISK_CHANNEL_DATA             = '.1.3.6.1.4.1.22736.1.5.2.1.11';
    const OID_ASTERISK_CHANNEL_CONTEXT          = '.1.3.6.1.4.1.22736.1.5.2.1.12';
    const OID_ASTERISK_CHANNEL_MACRO_CONTEXT    = '.1.3.6.1.4.1.22736.1.5.2.1.13';
    const OID_ASTERISK_CHANNEL_MACRO_EXTEN      = '.1.3.6.1.4.1.22736.1.5.2.1.14';
    const OID_ASTERISK_CHANNEL_MACRO_PRI        = '.1.3.6.1.4.1.22736.1.5.2.1.15';
    const OID_ASTERISK_CHANNEL_EXTEN            = '.1.3.6.1.4.1.22736.1.5.2.1.16';
    const OID_ASTERISK_CHANNEL_PRI              = '.1.3.6.1.4.1.22736.1.5.2.1.17';
    const OID_ASTERISK_CHANNEL_ACCOUNT_CODE     = '.1.3.6.1.4.1.22736.1.5.2.1.18';
    const OID_ASTERISK_CHANNEL_FORWARD_TO       = '.1.3.6.1.4.1.22736.1.5.2.1.19';
    const OID_ASTERISK_CHANNEL_UNQIUEID         = '.1.3.6.1.4.1.22736.1.5.2.1.20';
    const OID_ASTERISK_CHANNEL_CALL_GROUP       = '.1.3.6.1.4.1.22736.1.5.2.1.21';
    const OID_ASTERISK_CHANNEL_PICKUP_GROUP     = '.1.3.6.1.4.1.22736.1.5.2.1.22';
    const OID_ASTERISK_CHANNEL_STATE            = '.1.3.6.1.4.1.22736.1.5.2.1.23';
    const OID_ASTERISK_CHANNEL_MUTED            = '.1.3.6.1.4.1.22736.1.5.2.1.24';
    const OID_ASTERISK_CHANNEL_RINGS            = '.1.3.6.1.4.1.22736.1.5.2.1.25';
    const OID_ASTERISK_CHANNEL_CID_DNID         = '.1.3.6.1.4.1.22736.1.5.2.1.26';
    const OID_ASTERISK_CHANNEL_CID_NUM          = '.1.3.6.1.4.1.22736.1.5.2.1.27';
    const OID_ASTERISK_CHANNEL_CID_NAME         = '.1.3.6.1.4.1.22736.1.5.2.1.28';
    const OID_ASTERISK_CHANNEL_CID_ANI          = '.1.3.6.1.4.1.22736.1.5.2.1.29';
    const OID_ASTERISK_CHANNEL_CID_RDNIS        = '.1.3.6.1.4.1.22736.1.5.2.1.30';
    const OID_ASTERISK_CHANNEL_CID_PRESENTATION = '.1.3.6.1.4.1.22736.1.5.2.1.31';
    const OID_ASTERISK_CHANNEL_CID_ANI2         = '.1.3.6.1.4.1.22736.1.5.2.1.32';
    const OID_ASTERISK_CHANNEL_CID_TON          = '.1.3.6.1.4.1.22736.1.5.2.1.33';
    const OID_ASTERISK_CHANNEL_CID_TNS          = '.1.3.6.1.4.1.22736.1.5.2.1.34';
    const OID_ASTERISK_CHANNEL_AMA_FLAGS        = '.1.3.6.1.4.1.22736.1.5.2.1.35';
    const OID_ASTERISK_CHANNEL_ADSI             = '.1.3.6.1.4.1.22736.1.5.2.1.36';
    const OID_ASTERISK_CHANNEL_TONE_ZONE        = '.1.3.6.1.4.1.22736.1.5.2.1.37';
    const OID_ASTERISK_CHANNEL_HANGUP_CAUSE     = '.1.3.6.1.4.1.22736.1.5.2.1.38';
    const OID_ASTERISK_CHANNEL_VARIABLES        = '.1.3.6.1.4.1.22736.1.5.2.1.39';
    const OID_ASTERISK_CHANNEL_FLAGS            = '.1.3.6.1.4.1.22736.1.5.2.1.40';
    const OID_ASTERISK_CHANNEL_TRANSFER_CAP     = '.1.3.6.1.4.1.22736.1.5.2.1.41';
    
    const OID_ASTERISK_CHANNELS_BRIDGED     = '.1.3.6.1.4.1.22736.1.5.5.1.0';

    /**
     * Returns the current number of active channels.
     *
     * > Current number of active channels.
     *
     * @return int The current number of active channels.
     */
    public function active()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_CHANNELS_ACTIVE );
    }


    /**
     * Returns the number of channel types (technologies) supported.
     *
     * > Number of channel types (technologies) supported.
     *
     * @return int The number of channel types (technologies) supported.
     */
    public function supported()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_CHANNELS_SUPPORTED );
    }


    /**
     * Array of supported channel type names
     *
     * > Unique name of the technology we are describing.
     *
     * @return array Supported channel type names
     */
    public function names()
    {
        return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE_NAME );
    }

    /**
     * Array of supported channel type descriptions
     *
     * > Description of the channel type (technology).
     *
     * @return array Supported channel type descriptions
     */
    public function descriptions()
    {
        return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE_DESCRIPTION );
    }

    /**
     * Array of supported channel type device state capability
     *
     * > Whether the current technology can hold device states.
     *
     * @return array Whether the current technology can hold device states.
     */
    public function deviceStates()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE_STATE ) );
    }

    /**
     * Array of supported channel type progress indication capability
     *
     * > Whether the current technology supports progress indication.
     *
     * @return array Whether the current technology supports progress indication.
     */
    public function progressIndications()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE_INDICATION ) );
    }

    /**
     * Array of supported channel type transfer capability
     *
     * > Whether the current technology supports transfers, where
     * > Asterisk can get out from inbetween two bridged channels.
     *
     * @return array Whether the current technology transfers
     */
    public function transfers()
    {
        return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE_TRANSFER ) );
    }

    /**
     * Array of active calls on supported channels
     *
     * > Number of active channels using the current technology.
     *
     * @return array Active calls on supported channels
     */
    public function activeCalls()
    {
        return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE_CHANNELS );
    }

    /**
     * Number of channels currently in a bridged state.
     *
     * > Number of channels currently in a bridged state.
     *
     * @return int Array of active calls on supported channels
     */
    public function bridged()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_CHANNELS_BRIDGED );
    }

    /**
     * Utility function to gather channel details together in an associative array.
     *
     * Returns an array of support channel types. For example:
     *
     *     Array
     *     (
     *         ....
     *         [SIP] => Array
     *             (
     *                 [name] => SIP
     *                 [index] => 5
     *                 [description] => Session Initiation Protocol (SIP)
     *                 [hasDeviceState] => 1
     *                 [hasProgressIndications] => 1
     *                 [canTransfer] => 1
     *                 [activeCalls] => 0
     *             )
     *         ....
     *     )
     *
     * If you chose to index by SNMP table entries, the above element would be indexed with `5` rather than `SIP`.
     *
     * @param bool $useIndex If true, the array is indexed using the SNMP table index rather than the unique channel type name
     * @return array Channel details as an associative array
     */
    public function details( $useIndex = false )
    {
        $details = [];

        foreach( $this->names() as $index => $name )
        {
            if( $useIndex )
                $idx = $index;
            else
                $idx = $name;

            $details[ $idx ]['name']                   = $name;
            $details[ $idx ]['index']                  = $index;
            $details[ $idx ]['description']            = $this->descriptions()[$index];
            $details[ $idx ]['hasDeviceState']         = $this->deviceStates()[$index];
            $details[ $idx ]['hasProgressIndications'] = $this->progressIndications()[$index];
            $details[ $idx ]['canTransfer']            = $this->transfers()[$index];
            $details[ $idx ]['activeCalls']            = $this->activeCalls()[$index];
        }

        return $details;
    }

    
    
    

    
    
    
    
    
    
    
    
    /**
     * Active Channel Information: Name of the current channel.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Name of the current channel.
     *
     * @return array Name of the current channel.
     */
    public function chanName()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_NAME );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Which language the current channel is configured to use -- used mainly for prompts.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Which language the current channel is configured to use -- used mainly for prompts.
     *
     * @return array Which language the current channel is configured to use -- used mainly for prompts.
     */
    public function chanLanguage()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_LANGUAGE );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Underlying technology for the current channel.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Underlying technology for the current channel.
     *
     * @return array Underlying technology for the current channel.
     */
    public function chanType()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TYPE );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Music class to be used for Music on Hold for this channel.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Music class to be used for Music on Hold for this channel.
     *
     * @return array Music class to be used for Music on Hold for this channel.
     */
    public function chanMusicClass()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MUSIC_CLASS );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Which channel this channel is currently bridged (in a conversation) with.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Which channel this channel is currently bridged (in a conversation) with.
     *
     * @return array Which channel this channel is currently bridged (in a conversation) with.
     */
    public function chanBridge()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_BRIDGE );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Channel masquerading for us.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Channel masquerading for us.
     *
     * @return array Channel masquerading for us.
     */
    public function chanMasq()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MASQ );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Channel we are masquerading for.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Channel we are masquerading for.
     *
     * @return array Channel we are masquerading for.
     */
    public function chanMasqr()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MASQR );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: How long until this channel will be hung up.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > How long until this channel will be hung up.
     *
     * @return array How long until this channel will be hung up.
     */
    public function chanWhenHangup()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_WHEN_HANGUP );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current application for the channel.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current application for the channel.
     *
     * @return array Current application for the channel.
     */
    public function chanApp()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_APP );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Arguments passed to the current application.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Arguments passed to the current application.
     *
     * @return array Arguments passed to the current application.
     */
    public function chanData()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_DATA );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current extension context.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current extension context.
     *
     * @return array Current extension context.
     */
    public function chanContext()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CONTEXT );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current macro context.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current macro context.
     *
     * @return array Current macro context.
     */
    public function chanMacroContext()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MACRO_CONTEXT );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current macro extension.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current macro extension.
     *
     * @return array Current macro extension.
     */
    public function chanMacroExten()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MACRO_EXTEN );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current macro priority.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current macro priority.
     *
     * @return array Current macro priority.
     */
    public function chanMacroPri()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MACRO_PRI );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current extension.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current extension.
     *
     * @return array Current extension.
     */
    public function chanExten()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_EXTEN );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Current priority.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Current priority.
     *
     * @return array Current priority.
     */
    public function chanPri()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_PRI );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Account Code for billing.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Account Code for billing.
     *
     * @return array Account Code for billing.
     */
    public function chanAccountCode()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_ACCOUNT_CODE );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Where to forward to if asked to dial on this interface.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Where to forward to if asked to dial on this interface.
     *
     * @return array Where to forward to if asked to dial on this interface.
     */
    public function chanForwardTo()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_FORWARD_TO );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Unique Channel Identifier.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Unique Channel Identifier.
     *
     * @return array Unique Channel Identifier.
     */
    public function chanUniqueId()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_UNQIUEID );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Call Group.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Call Group.
     *
     * @return array Call Group.
     */
    public function chanCallGroup()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CALL_GROUP );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Pickup Group.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Pickup Group.
     *
     * @return array Pickup Group.
     */
    public function chanPickupGroup()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_PICKUP_GROUP );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_DOWN = 0;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_RESERVED = 1;
    
        /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_OFF_HOOK = 2;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_DIALING = 3;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_RING = 4;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_RINGING = 5;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_UP = 6;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_BUSY = 7;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_DIALING_OFF_HOOK = 8;
    
    /**
     * Possible state of an Asterisk channel as returned by `chanState()`
     * @var int Possible state of an Asterisk channel as returned by `chanState()`
     */
    const CHANNEL_STATE_PRE_RING = 9;
    
    
    public static $CHANNEL_STATES = [
        self::CHANNEL_STATE_DOWN             => 'down',
        self::CHANNEL_STATE_RESERVED         => 'reserved',
        self::CHANNEL_STATE_OFF_HOOK         => 'offHook',
        self::CHANNEL_STATE_DIALING          => 'dialing',
        self::CHANNEL_STATE_RING             => 'ring',
        self::CHANNEL_STATE_RINGING          => 'ringing',
        self::CHANNEL_STATE_UP               => 'up',
        self::CHANNEL_STATE_BUSY             => 'busy',
        self::CHANNEL_STATE_DIALING_OFF_HOOK => 'dialingOffHook',
        self::CHANNEL_STATE_PRE_RING         => 'preRing'
    ];
    
    /**
     * Active Channel Information: Channel state (see channel state constants).
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Channel state (see channel state constants).
     *
     * @param bool $translate If true, use the `$CHANNEL_STATES` array to return textual representation
     * @return array Channel state (see channel state constants).
     */
    public function chanState( $translate = false )
    {
        try
        {
            $s = $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_STATE );
            
            if( !$translate )
                return $s;
            
            return $this->getSNMP()->translate( $s, self::$CHANNEL_STATES );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Transmission of voice data has been muted.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Transmission of voice data has been muted.
     *
     * @return array Transmission of voice data has been muted.
     */
    public function chanMuted()
    {
        try
        {
            return $this->getSNMP()->ppTruthValue( $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_MUTED ) );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Number of rings so far.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Number of rings so far.
     *
     * @return array Number of rings so far.
     */
    public function chanRings()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_RINGS );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Dialled Number ID.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Dialled Number ID.
     *
     * @return array Dialled Number ID.
     */
    public function chanCidDNID()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_DNID );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Caller Number.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Caller Number.
     *
     * @return array Caller Number.
     */
    public function chanCidNum()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_NUM );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Caller Name.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Caller Name.
     *
     * @return array Caller Name.
     */
    public function chanCidName()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_NAME );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: ANI
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > ANI
     *
     * @return array ANI
     */
    public function chanCidANI()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_ANI );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Redirected Dialled Number Service.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Redirected Dialled Number Service.
     *
     * @return array Redirected Dialled Number Service.
     */
    public function chanCidRDNIS()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_RDNIS );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Number Presentation/Screening.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Number Presentation/Screening.
     *
     * @return array Number Presentation/Screening.
     */
    public function chanCidPresentation()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_PRESENTATION );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: ANI 2 (info digit).
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > ANI 2 (info digit).
     *
     * @return array ANI 2 (info digit).
     */
    public function chanCidANI2()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_ANI2 );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Type of Number.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Type of Number.
     *
     * @return array Type of Number.
     */
    public function chanCidTON()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_TON );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Transit Network Select.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Transit Network Select.
     *
     * @return array Transit Network Select.
     */
    public function chanCidTNS()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_CID_TNS );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    
    
    /**
     * Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     * @var int Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     */
    const CHANNEL_AMA_FLAG_DEFAULT = 0;
    
    /**
     * Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     * @var int Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     */
    const CHANNEL_AMA_FLAG_OMIT = 1;
    
    /**
     * Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     * @var int Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     */
    const CHANNEL_AMA_FLAG_BILLING = 2;
    
    /**
     * Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     * @var int Possible AMA flag of an Asterisk channel as returned by `chanAMAFlags()`
     */
    const CHANNEL_AMA_FLAG_DOCUMENTATION = 3;
    
    
    public static $CHANNEL_AMA_FLAGS = [
        self::CHANNEL_AMA_FLAG_DEFAULT       => 'default',
        self::CHANNEL_AMA_FLAG_OMIT          => 'omit',
        self::CHANNEL_AMA_FLAG_BILLING       => 'billing',
        self::CHANNEL_AMA_FLAG_DOCUMENTATION => 'documentation'
    ];
    
    /**
     * Active Channel Information: AMA Flags. (See constants)
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > AMA Flags. (See constants)
     *
     * @param bool $translate If true, use the `$CHANNEL_AMA_FLAGS` array to return textual representation
     * @return array AMA Flags. (See constants)
     */
    public function chanAMAFlags( $translate = false )
    {
        try
        {
            $s = $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_AMA_FLAGS );
            
            if( !$translate )
                return $s;
    
            return $this->getSNMP()->translate( $s, self::$CHANNEL_AMA_FLAGS );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    

    
    /**
     * Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     * @var int Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     */
    const CHANNEL_ADSI_UNKNOWN = 0;
    
    /**
     * Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     * @var int Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     */
    const CHANNEL_ADSI_AVAILABLE = 1;
    
    /**
     * Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     * @var int Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     */
    const CHANNEL_ADSI_UNAVAILABLE = 2;
    
    /**
     * Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     * @var int Possible ADSI of an Asterisk channel as returned by `chanADSI()`
     */
    const CHANNEL_ADSI_OFF_HOOK_ONLY = 3;
    
    
    public static $CHANNEL_ADSIs = [
        self::CHANNEL_ADSI_UNKNOWN       => 'unknown',
        self::CHANNEL_ADSI_AVAILABLE     => 'available',
        self::CHANNEL_ADSI_UNAVAILABLE   => 'unavailable',
        self::CHANNEL_ADSI_OFF_HOOK_ONLY => 'offHookOnly'
    ];
    
    
    
    /**
     * Active Channel Information: Whether or not ADSI is detected on CPE. (see constants)
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Whether or not ADSI is detected on CPE. (see constants)
     *
     * @param bool $translate If true, use the `$CHANNEL_ADSIs` array to return textual representation
     * @return array Whether or not ADSI is detected on CPE. (see constants)
     */
    public function chanADSI( $translate = false )
    {
        try
        {
            $s = $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_ADSI );
            
            if( !$translate )
                return $s;
    
            return $this->getSNMP()->translate( $s, self::$CHANNEL_ADSIs );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Indication zone to use for channel.
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Indication zone to use for channel.
     *
     * @return array Indication zone to use for channel.
     */
    public function chanToneZone()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TONE_ZONE );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_NOT_DEFINED = 0;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_UNREGISTERED = 3;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_NORMAL = 16;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_BUSY = 17;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_NO_ANSWER = 19;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_CONGESTION = 34;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_FAILURE = 38;
    
    /**
     * Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     * @var int Possible hangup cause of an Asterisk channel as returned by `chanHangupCause()`
     */
    const CHANNEL_HANGUP_CAUSE_NO_SUCH_DRIVER = 66;
    
    
    public static $CHANNEL_HANGUP_CAUSES = [
        self::CHANNEL_HANGUP_CAUSE_NOT_DEFINED    => 'notDefined',
        self::CHANNEL_HANGUP_CAUSE_UNREGISTERED   => 'unregistered',
        self::CHANNEL_HANGUP_CAUSE_NORMAL         => 'normal',
        self::CHANNEL_HANGUP_CAUSE_BUSY           => 'busy',
        self::CHANNEL_HANGUP_CAUSE_NO_ANSWER      => 'noAnswer',
        self::CHANNEL_HANGUP_CAUSE_CONGESTION     => 'congestion',
        self::CHANNEL_HANGUP_CAUSE_FAILURE        => 'failure',
        self::CHANNEL_HANGUP_CAUSE_NO_SUCH_DRIVER => 'noSuchDriver'
    ];
    
    
    /**
     * Active Channel Information: Why is the channel hung up. (see constants)
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Why is the channel hung up. (see constants)
     *
     * @param bool $translate If true, use the `$CHANNEL_HANGUP_CAUSES` array to return textual representation
     * @return array Why is the channel hung up. (see constants)
     */
    public function chanHangupCause( $translate = false )
    {
        try
        {
            $s = $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_HANGUP_CAUSE );
            
            if( !$translate )
                return $s;
    
            return $this->getSNMP()->translate( $s, self::$CHANNEL_HANGUP_CAUSES );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Active Channel Information: Channel Variables defined for this channel.
     *
     * Returns an array of arrays where the inner array is key/value pairs
     * of channel variables for that channel: `[varName] => [varValue]`
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Channel Variables defined for this channel.
     *
     * @return array Channel Variables defined for this channel.
     */
    public function chanVariables()
    {
        try
        {
            $vars = $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_VARIABLES );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
        
        foreach( $vars as $idx => $var )
            $vars[ $idx ] = $this->_chanVarsToArray( $var );
        
        return $vars;
    }
    
    /**
     * Utility function for `chanVariables()` to break the string returned by
     * OID_ASTERISK_CHANNEL_VARIABLES into an array of key / value pairs
     *
     * @param string $str String containing channel variables (from `chanVariables()`
     * @return Array An array of `[varName] => [varValue]` pairs for the channel
     */
    protected function _chanVarsToArray( $str )
    {
        $arr = [];
        foreach( explode( "\n", $str ) as $s )
        {
            $arr[ substr( $s, 0, strpos( $s, '=' ) ) ] = substr( $s, strpos( $s, '=' ) + 1 );
        }
        return $arr;
    }
    
    /**
     * Active Channel Information: Flags set on this channel.
     *
     * Returns a HEX number - but I could not map it to the following from Asterisk docs:
     *
     * > BITS {
     * >    wantsJitter(0),
     * >    deferDTMF(1),
     * >    writeInterrupt(2),
     * >    blocking(3),
     * >    zombie(4),
     * >    exception(5),
     * >    musicOnHold(6),
     * >    spying(7),
     * >    nativeBridge(8),
     * >    autoIncrementingLoop(9)
     * > }
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Flags set on this channel. (see constants)
     *
     * @return array Flags set on this channel. (see constants)
     */
    public function chanFlags()
    {
        try
        {
            return $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_FLAGS );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }

    
    
    
    /**
     * Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     * @var int Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     */
    const CHANNEL_TRANSFER_CAPABILITY_SPEECH = 0;
    
    /**
     * Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     * @var int Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     */
    const CHANNEL_TRANSFER_CAPABILITY_DIGITAL = 8;
    
    /**
     * Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     * @var int Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     */
    const CHANNEL_TRANSFER_CAPABILITY_RESTRICTED_DIGITAL = 9;
    
    /**
     * Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     * @var int Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     */
    const CHANNEL_TRANSFER_CAPABILITY_AUDIO_3K = 16;
    
    /**
     * Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     * @var int Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     */
    const CHANNEL_TRANSFER_CAPABILITY_DIGITAL_WITH_TONES = 17;
    
    /**
     * Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     * @var int Possible channel transfer capabilities of an Asterisk channel as returned by `chanTransferCap()`
     */
    const CHANNEL_TRANSFER_CAPABILITY_VIDEO = 24;
    
    
    public static $CHANNEL_TRANSFER_CAPABILITIES = [
        self::CHANNEL_TRANSFER_CAPABILITY_SPEECH                => 'speech',
        self::CHANNEL_TRANSFER_CAPABILITY_DIGITAL               => 'digital',
        self::CHANNEL_TRANSFER_CAPABILITY_RESTRICTED_DIGITAL    => 'restrictedDigital',
        self::CHANNEL_TRANSFER_CAPABILITY_AUDIO_3K              => 'audio3k',
        self::CHANNEL_TRANSFER_CAPABILITY_DIGITAL_WITH_TONES    => 'digitalWithTones',
        self::CHANNEL_TRANSFER_CAPABILITY_VIDEO                 => 'video'
    ];
    
    /**
     * Active Channel Information: Transfer Capabilities for this channel. (see constants)
     *
     * NB: SNMP exceptions are caught and in such cases null is returned
     * as not all channels have all properties.
     *
     * > Transfer Capabilities for this channel. (see constants)
     *
     * @param bool $translate If true, use the `$CHANNEL_TRANSFER_CAPABILITIES` array to return textual representation
     * @return array Transfer Capabilities for this channel. (see constants)
     */
    public function chanTransferCap( $translate = false )
    {
        try
        {
            $s = $this->getSNMP()->walk1d( self::OID_ASTERISK_CHANNEL_TRANSFER_CAP );
            
            if( !$translate )
                return $s;
    
            return $this->getSNMP()->translate( $s, self::$CHANNEL_TRANSFER_CAPABILITIES );
        }
        catch( \OSS_SNMP\Exception $e )
        {
            return null;
        }
    }
    
    /**
     * Utility function to gather together all the details of individual channels into an array.
     *
     * Essentially, this function calls all `chanXXX()` functions to return the details for
     * individual channels gathered together. E.g.
     *
     *     Array
     *     (
     *         ....
     *         [SIP/foobar-654-00000372] => Array
     *         (
     *             [chanName] => SIP/foobar-654-00000372
     *             [chanLanguage] => en
     *             [chanType] => SIP
     *             [chanMusicClass] => (null)
     *             ...
     *             [chanVariables] => Array
     *             (
     *                 [DIALEDPEERNUMBER] => foobar-654
     *                 [SIPCALLID] => 1be189fa6281ffc1108db32935f05016@192.168.7.7:5060
     *             )
     *             [chanFlags] => 1020
     *             [chanTransferCap] => speech
     *         )
     *         ....
     *     )
     *
     * The function returns an array of all channels. Unknown parameters within the channel are
     * set to null.
     *
     * An empty array is returned if there are no active channels.
     *
     * @param bool $translate Translate parameters when possible
     * @param bool $useIndexes Rather than indexing the outer array with the unique channel name, index with the SNMP table position
     * @return array  All the details of individual channels into an array.
     */
    public function channelDetails( $translate = false, $useIndexes = false )
    {
        try { $chanName = $this->chanName(); } catch( \OSS_SNMP\Exception $e ) { $chanName = null; }
        
        // if there's no channels, skip the rest
        if( $chanName === null || !count( $chanName ) )
            return [];
        
        try { $chanLanguage = $this->chanLanguage(); } catch( \OSS_SNMP\Exception $e ) { $chanLanguage = null; }
        try { $chanType = $this->chanType(); } catch( \OSS_SNMP\Exception $e ) { $chanType = null; }
        try { $chanMusicClass = $this->chanMusicClass(); } catch( \OSS_SNMP\Exception $e ) { $chanMusicClass = null; }
        try { $chanBridge = $this->chanBridge(); } catch( \OSS_SNMP\Exception $e ) { $chanBridge = null; }
        try { $chanMasq = $this->chanMasq(); } catch( \OSS_SNMP\Exception $e ) { $chanMasq = null; }
        try { $chanMasqr = $this->chanMasqr(); } catch( \OSS_SNMP\Exception $e ) { $chanMasqr = null; }
        try { $chanWhenHangup = $this->chanWhenHangup(); } catch( \OSS_SNMP\Exception $e ) { $chanWhenHangup = null; }
        try { $chanApp = $this->chanApp(); } catch( \OSS_SNMP\Exception $e ) { $chanApp = null; }
        try { $chanData = $this->chanData(); } catch( \OSS_SNMP\Exception $e ) { $chanData = null; }
        try { $chanContext = $this->chanContext(); } catch( \OSS_SNMP\Exception $e ) { $chanContext = null; }
        try { $chanMacroContext = $this->chanMacroContext(); } catch( \OSS_SNMP\Exception $e ) { $chanMacroContext = null; }
        try { $chanMacroExten = $this->chanMacroExten(); } catch( \OSS_SNMP\Exception $e ) { $chanMacroExten = null; }
        try { $chanMacroPri = $this->chanMacroPri(); } catch( \OSS_SNMP\Exception $e ) { $chanMacroPri = null; }
        try { $chanExten = $this->chanExten(); } catch( \OSS_SNMP\Exception $e ) { $chanExten = null; }
        try { $chanPri = $this->chanPri(); } catch( \OSS_SNMP\Exception $e ) { $chanPri = null; }
        try { $chanAccountCode = $this->chanAccountCode(); } catch( \OSS_SNMP\Exception $e ) { $chanAccountCode = null; }
        try { $chanForwardTo = $this->chanForwardTo(); } catch( \OSS_SNMP\Exception $e ) { $chanForwardTo = null; }
        try { $chanUniqueId = $this->chanUniqueId(); } catch( \OSS_SNMP\Exception $e ) { $chanUniqueId = null; }
        try { $chanCallGroup = $this->chanCallGroup(); } catch( \OSS_SNMP\Exception $e ) { $chanCallGroup = null; }
        try { $chanPickupGroup = $this->chanPickupGroup(); } catch( \OSS_SNMP\Exception $e ) { $chanPickupGroup = null; }
        try { $chanState = $this->chanState( $translate ); } catch( \OSS_SNMP\Exception $e ) { $chanState = null; }
        try { $chanMuted = $this->chanMuted(); } catch( \OSS_SNMP\Exception $e ) { $chanMuted = null; }
        try { $chanRings = $this->chanRings(); } catch( \OSS_SNMP\Exception $e ) { $chanRings = null; }
        try { $chanCidDNID = $this->chanCidDNID(); } catch( \OSS_SNMP\Exception $e ) { $chanCidDNID = null; }
        try { $chanCidNum = $this->chanCidNum(); } catch( \OSS_SNMP\Exception $e ) { $chanCidNum = null; }
        try { $chanCidName = $this->chanCidName(); } catch( \OSS_SNMP\Exception $e ) { $chanCidName = null; }
        try { $chanCidANI = $this->chanCidANI(); } catch( \OSS_SNMP\Exception $e ) { $chanCidANI = null; }
        try { $chanCidRDNIS = $this->chanCidRDNIS(); } catch( \OSS_SNMP\Exception $e ) { $chanCidRDNIS = null; }
        try { $chanCidPresentation = $this->chanCidPresentation(); } catch( \OSS_SNMP\Exception $e ) { $chanCidPresentation = null; }
        try { $chanCidANI2 = $this->chanCidANI2(); } catch( \OSS_SNMP\Exception $e ) { $chanCidANI2 = null; }
        try { $chanCidTON = $this->chanCidTON(); } catch( \OSS_SNMP\Exception $e ) { $chanCidTON = null; }
        try { $chanCidTNS = $this->chanCidTNS(); } catch( \OSS_SNMP\Exception $e ) { $chanCidTNS = null; }
        try { $chanAMAFlags = $this->chanAMAFlags( $translate ); } catch( \OSS_SNMP\Exception $e ) { $chanAMAFlags = null; }
        try { $chanADSI = $this->chanADSI( $translate ); } catch( \OSS_SNMP\Exception $e ) { $chanADSI = null; }
        try { $chanToneZone = $this->chanToneZone(); } catch( \OSS_SNMP\Exception $e ) { $chanToneZone = null; }
        try { $chanHangupCause = $this->chanHangupCause( $translate ); } catch( \OSS_SNMP\Exception $e ) { $chanHangupCause = null; }
        try { $chanVariables = $this->chanVariables(); } catch( \OSS_SNMP\Exception $e ) { $chanVariables = null; }
        try { $chanFlags = $this->chanFlags(); } catch( \OSS_SNMP\Exception $e ) { $chanFlags = null; }
        try { $chanTransferCap = $this->chanTransferCap( $translate ); } catch( \OSS_SNMP\Exception $e ) { $chanTransferCap = null; }
    
    
        $details = [];
        
        foreach( $chanName as $idx => $name )
        {
            $index = $useIndexes ? $idx : ( ( $chanName !== null && isset( $chanName[ $idx ] ) ) ? $chanName[ $idx ] : $idx );
            
            $details[ $index ]['chanName'] = ( $chanName !== null && isset( $chanName[ $idx ] ) ) ? $chanName[ $idx ] : null;
            $details[ $index ]['chanLanguage'] = ( $chanLanguage !== null && isset( $chanLanguage[ $idx ] ) ) ? $chanLanguage[ $idx ] : null;
            $details[ $index ]['chanType'] = ( $chanType !== null && isset( $chanType[ $idx ] ) ) ? $chanType[ $idx ] : null;
            $details[ $index ]['chanMusicClass'] = ( $chanMusicClass !== null && isset( $chanMusicClass[ $idx ] ) ) ? $chanMusicClass[ $idx ] : null;
            $details[ $index ]['chanBridge'] = ( $chanBridge !== null && isset( $chanBridge[ $idx ] ) ) ? $chanBridge[ $idx ] : null;
            $details[ $index ]['chanMasq'] = ( $chanMasq !== null && isset( $chanMasq[ $idx ] ) ) ? $chanMasq[ $idx ] : null;
            $details[ $index ]['chanMasqr'] = ( $chanMasqr !== null && isset( $chanMasqr[ $idx ] ) ) ? $chanMasqr[ $idx ] : null;
            $details[ $index ]['chanWhenHangup'] = ( $chanWhenHangup !== null && isset( $chanWhenHangup[ $idx ] ) ) ? $chanWhenHangup[ $idx ] : null;
            $details[ $index ]['chanApp'] = ( $chanApp !== null && isset( $chanApp[ $idx ] ) ) ? $chanApp[ $idx ] : null;
            $details[ $index ]['chanData'] = ( $chanData !== null && isset( $chanData[ $idx ] ) ) ? $chanData[ $idx ] : null;
            $details[ $index ]['chanContext'] = ( $chanContext !== null && isset( $chanContext[ $idx ] ) ) ? $chanContext[ $idx ] : null;
            $details[ $index ]['chanMacroContext'] = ( $chanMacroContext !== null && isset( $chanMacroContext[ $idx ] ) ) ? $chanMacroContext[ $idx ] : null;
            $details[ $index ]['chanMacroExten'] = ( $chanMacroExten !== null && isset( $chanMacroExten[ $idx ] ) ) ? $chanMacroExten[ $idx ] : null;
            $details[ $index ]['chanMacroPri'] = ( $chanMacroPri !== null && isset( $chanMacroPri[ $idx ] ) ) ? $chanMacroPri[ $idx ] : null;
            $details[ $index ]['chanExten'] = ( $chanExten !== null && isset( $chanExten[ $idx ] ) ) ? $chanExten[ $idx ] : null;
            $details[ $index ]['chanPri'] = ( $chanPri !== null && isset( $chanPri[ $idx ] ) ) ? $chanPri[ $idx ] : null;
            $details[ $index ]['chanAccountCode'] = ( $chanAccountCode !== null && isset( $chanAccountCode[ $idx ] ) ) ? $chanAccountCode[ $idx ] : null;
            $details[ $index ]['chanForwardTo'] = ( $chanForwardTo !== null && isset( $chanForwardTo[ $idx ] ) ) ? $chanForwardTo[ $idx ] : null;
            $details[ $index ]['chanUniqueId'] = ( $chanUniqueId !== null && isset( $chanUniqueId[ $idx ] ) ) ? $chanUniqueId[ $idx ] : null;
            $details[ $index ]['chanCallGroup'] = ( $chanCallGroup !== null && isset( $chanCallGroup[ $idx ] ) ) ? $chanCallGroup[ $idx ] : null;
            $details[ $index ]['chanPickupGroup'] = ( $chanPickupGroup !== null && isset( $chanPickupGroup[ $idx ] ) ) ? $chanPickupGroup[ $idx ] : null;
            $details[ $index ]['chanState'] = ( $chanState !== null && isset( $chanState[ $idx ] ) ) ? $chanState[ $idx ] : null;
            $details[ $index ]['chanMuted'] = ( $chanMuted !== null && isset( $chanMuted[ $idx ] ) ) ? $chanMuted[ $idx ] : null;
            $details[ $index ]['chanRings'] = ( $chanRings !== null && isset( $chanRings[ $idx ] ) ) ? $chanRings[ $idx ] : null;
            $details[ $index ]['chanCidDNID'] = ( $chanCidDNID !== null && isset( $chanCidDNID[ $idx ] ) ) ? $chanCidDNID[ $idx ] : null;
            $details[ $index ]['chanCidNum'] = ( $chanCidNum !== null && isset( $chanCidNum[ $idx ] ) ) ? $chanCidNum[ $idx ] : null;
            $details[ $index ]['chanCidName'] = ( $chanCidName !== null && isset( $chanCidName[ $idx ] ) ) ? $chanCidName[ $idx ] : null;
            $details[ $index ]['chanCidANI'] = ( $chanCidANI !== null && isset( $chanCidANI[ $idx ] ) ) ? $chanCidANI[ $idx ] : null;
            $details[ $index ]['chanCidRDNIS'] = ( $chanCidRDNIS !== null && isset( $chanCidRDNIS[ $idx ] ) ) ? $chanCidRDNIS[ $idx ] : null;
            $details[ $index ]['chanCidPresentation'] = ( $chanCidPresentation !== null && isset( $chanCidPresentation[ $idx ] ) ) ? $chanCidPresentation[ $idx ] : null;
            $details[ $index ]['chanCidANI2'] = ( $chanCidANI2 !== null && isset( $chanCidANI2[ $idx ] ) ) ? $chanCidANI2[ $idx ] : null;
            $details[ $index ]['chanCidTON'] = ( $chanCidTON !== null && isset( $chanCidTON[ $idx ] ) ) ? $chanCidTON[ $idx ] : null;
            $details[ $index ]['chanCidTNS'] = ( $chanCidTNS !== null && isset( $chanCidTNS[ $idx ] ) ) ? $chanCidTNS[ $idx ] : null;
            $details[ $index ]['chanAMAFlags'] = ( $chanAMAFlags !== null && isset( $chanAMAFlags[ $idx ] ) ) ? $chanAMAFlags[ $idx ] : null;
            $details[ $index ]['chanADSI'] = ( $chanADSI !== null && isset( $chanADSI[ $idx ] ) ) ? $chanADSI[ $idx ] : null;
            $details[ $index ]['chanToneZone'] = ( $chanToneZone !== null && isset( $chanToneZone[ $idx ] ) ) ? $chanToneZone[ $idx ] : null;
            $details[ $index ]['chanHangupCause'] = ( $chanHangupCause !== null && isset( $chanHangupCause[ $idx ] ) ) ? $chanHangupCause[ $idx ] : null;
            $details[ $index ]['chanVariables'] = ( $chanVariables !== null && isset( $chanVariables[ $idx ] ) ) ? $chanVariables[ $idx ] : null;
            $details[ $index ]['chanFlags'] = ( $chanFlags !== null && isset( $chanFlags[ $idx ] ) ) ? $chanFlags[ $idx ] : null;
            $details[ $index ]['chanTransferCap'] = ( $chanTransferCap !== null && isset( $chanTransferCap[ $idx ] ) ) ? $chanTransferCap[ $idx ] : null;
        }
        
        return $details;
    }
}
