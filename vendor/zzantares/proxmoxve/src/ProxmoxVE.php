<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE;

/**
 * ProxmoxVE class. In order to interact with the proxmox server, the desired
 * app's code needs to create and use an object of this class.
 *
 * @author César Muñoz <zzantares@gmail.com>
 */
class ProxmoxVE
{
    /**
     * @const TIMEOUT Time in seconds before droping Proxmox connection.
     */
    const TIMEOUT = 30;


    /**
     * @const USER_AGENT The User-Agent HTTP Header value.
     */
    const USER_AGENT = 'Proxmox VE API';


    /**
     * Sends an standard HTTP request to the specified URL with the HTTP method,
     * data, HTTP header and cookies specified.
     *
     * @param string $url     The requested URL.
     * @param string $method  The HTTP requesting method, GET, POST, PUT, DELETE
     *                        are supported.
     * @param string $params  The POST/PUT data to send in URL encoded format.
     *                        If going to send request via GET method, params
     *                        should be already encoded in the URL.
     * @param array $headers  The indexed array filled with the HTTP headers to
     *                        set in the request.
     * @param string $cookies The cookies to send in the HTTP request, multiple
     *                        cookies are separated with '; ' (note the space
     *                        after the semicolon).
     *
     * @return string The response that server send back.
     */
    public static function request(
        $url,
        $method = 'GET',
        $params = array(),
        $headers = array(),
        $cookies = null
    ) {
        $curlSession = curl_init();

        switch($method) {
            case 'POST':
                curl_setopt($curlSession, CURLOPT_POST, true);
                curl_setopt($curlSession, CURLOPT_POSTFIELDS, $params);
                curl_setopt($curlSession, CURLOPT_HTTPHEADER, $headers);
                break;

            case 'PUT':
                curl_setopt($curlSession, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curlSession, CURLOPT_POSTFIELDS, $params);
                curl_setopt($curlSession, CURLOPT_HTTPHEADER, $headers);
                break;

            case 'DELETE':
                curl_setopt($curlSession, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curlSession, CURLOPT_POSTFIELDS, $params);
                curl_setopt($curlSession, CURLOPT_HTTPHEADER, $headers);
                break;

            case 'GET':
            default:
        }

        if ($cookies) {
            curl_setopt($curlSession, CURLOPT_COOKIE, $cookies);
        }

        curl_setopt($curlSession, CURLOPT_URL, $url);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlSession, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);

        $response = curl_exec($curlSession);
        curl_close($curlSession);

        // Never parse response, that depends on the API response type.
        return $response;
    }


    /**
     * Token object holding Proxmox ticket session and CSRF prevention token.
     *
     * @var \ProxmoxVE\AuthToken
     */
    protected $authToken;


    /**
     * @todo What if the used program needs the raw json string? then why 
     *       we would parse response? need to add something to handle that.
     * @todo Add param to pass a HttpAdapter object to use in the application,
     *       if no adapter object is defined then we will use cURL.
     * @todo Add param to pass a logger psr-3 compliant? 
     */


    /**
     * Constructor.
     *
     * @param \ProxmoxVE\AuthToken $authToken Token object holding login data.
     *
     * @throws \RuntimeException If cURL is not enabled.
     */
    public function __construct(AuthToken $authToken)
    {
        // Check if CURL is enabled
        if (!function_exists('curl_version')) {
            throw new \RuntimeException('PHP5-CURL needs to be enabled!');
        }

        $this->authToken = $authToken;
    }


    /**
     * Sets the current AuthToken to the one is passed.
     *
     * @param \ProxmoxVE\AuthToken $authToken New AuthToken object to use.
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }


    /**
     * Gets the AuthToken that is used to make requests.
     *
     * @return \ProxmoxVE\AuthToken Object containing the ticket and csrf wich
     *                              are used in every request.
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }


    /**
     * Performs a GET request to the Proxmox server.
     *
     * @param string $url   The resource tree path you want to ask for, see more
     *                      at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params An associative array filled with GET params.
     * 
     * @return array        A PHP array json_decode($response, true).
     */
    protected function get($url, $params = array())
    {
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $cookies = 'PVEAuthCookie=' . $this->authToken->getTicket();

        return self::request($url, 'GET', null, null, $cookies);
    }


    /**
     * Performs a POST request to the Proxmox server, this function cant be used
     * to login into the server, for that need to call Credentials->login().
     *
     * @param string $url   The resource tree path you want to ask for, see more
     *                      at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params An associative array filled with POST params to send
     *                      in the request.
     *
     * @return array        A PHP array json_decode($response, true).
     */
    protected function post($url, $params = array())
    {
        $params = http_build_query($params);
        $cookies = 'PVEAuthCookie=' . $this->authToken->getTicket();
        $headers = array('CSRFPreventionToken: ' . $this->authToken->getCsrf());

        return self::request($url, 'POST', $params, $headers, $cookies);
    }


    /**
     * Performs a PUT request to the Proxmox server.
     *
     * @param string $url   The resource tree path you want to ask for, see more
     *                      at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params An associative array filled with params.
     *
     * @return array        A PHP array json_decode($response, true).
     */
    protected function put($url, $params = array())
    {
        $params = http_build_query($params);
        $cookies = 'PVEAuthCookie=' . $this->authToken->getTicket();
        $headers = array('CSRFPreventionToken: ' . $this->authToken->getCsrf());

        return self::request($url, 'PUT', $params, $headers, $cookies);
    }


    /**
     * Performs a DELETE request to the Proxmox server.
     *
     * @param string $url   The resource tree path you want to ask for, see more
     *                      at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params An associative array filled with params.
     *
     * @return array        A PHP array json_decode($response, true).
     */
    protected function delete($url, $params = array())
    {
        $params = http_build_query($params);
        $cookies = 'PVEAuthCookie=' . $this->authToken->getTicket();
        $headers = array('CSRFPreventionToken: ' . $this->authToken->getCsrf());

        return self::request($url, 'DELETE', $params, $headers, $cookies);
    }
}
