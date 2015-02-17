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
 * A class for performing SNMP V2 queries on Asterisk
 *
 * @see https://wiki.asterisk.org/wiki/display/AST/Asterisk+MIB+Definitions
 * @copyright Copyright (c) 2012, Open Source Solutions Limited, Dublin, Ireland
 * @author Barry O'Donovan <barry@opensolutions.ie>
 */
class Asterisk extends \OSS_SNMP\MIB
{

    const OID_ASTERISK_VERSION_STRING = '.1.3.6.1.4.1.22736.1.1.1.0';
    const OID_ASTERISK_VERSION_TAG    = '.1.3.6.1.4.1.22736.1.1.2.0';

    const OID_ASTERISK_UP_TIME         = '.1.3.6.1.4.1.22736.1.2.1.0';
    const OID_ASTERISK_RELOAD_TIME     = '.1.3.6.1.4.1.22736.1.2.2.0';
    const OID_ASTERISK_PID             = '.1.3.6.1.4.1.22736.1.2.3.0';
    const OID_ASTERISK_CONTROL_SOCKET  = '.1.3.6.1.4.1.22736.1.2.4.0';
    const OID_ASTERISK_CALLS_ACTIVE    = '.1.3.6.1.4.1.22736.1.2.5.0';
    const OID_ASTERISK_CALLS_PROCESSED = '.1.3.6.1.4.1.22736.1.2.6.0';

    const OID_ASTERISK_MODULES         = '.1.3.6.1.4.1.22736.1.3.1.0';

    /**
     * Returns the version of Asterisk
     *
     * > Text version string of the version of Asterisk that
	 * > the SNMP Agent was compiled to run against.
     *
     * @return string The version of Asterisk
     */
    public function version()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_VERSION_STRING );
    }

    /**
     * Returns the Subversion (SVN) revision of Asterisk
     *
     * > SubVersion revision of the version of Asterisk that
     * > the SNMP Agent was compiled to run against -- this is
     * > typically 0 for release-versions of Asterisk.
     *
     * @return int The SVN revision of Asterisk
     */
    public function tag()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_VERSION_TAG );
    }

    /**
     * Returns the time ticks (100th sec) since Asterisk was started
     *
     * > Time ticks since Asterisk was started.
     *
     * @return int Time ticks since Asterisk was started
     */
    public function uptime()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_UP_TIME );
    }

    /**
     * Returns the time ticks (100th sec) since the Asterisk config was reload
     *
     * > Time ticks since Asterisk was last reloaded.
     *
     * @return int Time ticks since the Asterisk config was reload
     */
    public function reloadTime()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_RELOAD_TIME );
    }

    /**
     * Returns the process ID of the Asterisk instance
     *
     * > The process id of the running Asterisk process.
     *
     * @return int The process ID of the Asterisk instance
     */
    public function pid()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_PID );
    }

    /**
     * Returns the path for the control socket for giving Asterisk commands
     *
     * > The control socket for giving Asterisk commands.
     *
     * @return string The control socket for giving Asterisk commands
     */
    public function controlSocket()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_CONTROL_SOCKET );
    }

    /**
     * Returns the number of calls currently active on the Asterisk PBX.
     *
     * > The number of calls currently active on the Asterisk PBX.
     *
     * @return int The number of calls currently active on the Asterisk PBX.
     */
    public function callsActive()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_CALLS_ACTIVE );
    }

    /**
     * Returns the total number of calls processed through the Asterisk PBX since last restart.
     *
     * > The total number of calls processed through the Asterisk PBX since last restart.
     *
     * @return int The total number of calls processed through the Asterisk PBX since last restart.
     */
    public function callsProcessed()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_CALLS_PROCESSED );
    }

    /**
     * Returns the number of modules currently loaded into Asterisk.
     *
     * > Number of modules currently loaded into Asterisk.
     *
     * @return int The number of modules currently loaded into Asterisk
     */
    public function modules()
    {
        return $this->getSNMP()->get( self::OID_ASTERISK_MODULES );
    }



}
