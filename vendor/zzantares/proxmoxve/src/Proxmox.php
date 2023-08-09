<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Request;

use ProxmoxVE\Exception\AuthenticationException;
use ProxmoxVE\Exception\BadResponseException;

/**
 * ProxmoxVE class. In order to interact with the proxmox server, the desired
 * app's code needs to create and use an object of this class.
 *
 * @author César Muñoz <zzantares@gmail.com>
 */
class Proxmox
{
    /**
     * @var \GuzzleHttp\Client()
     */
    private $httpClient;

    /**
     * Contains the proxmox server authentication data.
     *
     * @var \ProxmoxVE\Credentials
     */
    private $credentials;


    /**
     * Holds the response type used to requests the API, possible values are
     * json, extjs, html, text, png.
     *
     * @var string
     */
    private $responseType;


    /**
     * Holds the fake response type, it is useful when you want to get the JSON
     * raw string instead of a PHP array.
     *
     * @var string
     */
    private $fakeType;


    /**
     * Stores the ProxmoxVE user session such as ticket, username and csrf
     * prevention token, are all in there.
     *
     * @var \ProxmoxVE\AuthToken
     */
    private $authToken;


    /**
     * Constructor.
     *
     * @param mixed $credentials Credentials object or associative array holding
     *                           the login data.
     * @param string $responseType The response type that is going to be returned when doing requests.
     * @param \GuzzleHttp\Client $httpClient The HTTP client to be used to send requests over the network.
     *
     * @throws \ProxmoxVE\Exception\MalformedCredentialsException If bad args
     *                                                            supplied.
     * @throws \ProxmoxVE\Exception\AuthenticationException If given credentials
     *                                                      are not valid.
     */
    public function __construct(
        $credentials,
        $responseType = 'array',
        $httpClient = null
    ) {
        $this->setHttpClient($httpClient);

        // Set credentials and login to the Proxmox server.
        $this->setCredentials($credentials);

        $this->setResponseType($responseType);
    }


    /**
     * Send a request to a given Proxmox API resource.
     *
     * @param string $actionPath The resource tree path you want to request, see
     *                           more at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params      An associative array filled with params.
     * @param string $method     HTTP method used in the request, by default
     *                           'GET' method will be used.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \InvalidArgumentException If the given HTTP method is not one of
     *                                   'GET', 'POST', 'PUT', 'DELETE',
     */
    private function requestResource($actionPath, $params = [], $method = 'GET')
    {
        $url = $this->getApiUrl() . $actionPath;

        $cookies = CookieJar::fromArray([
            $this->getCookieName() => $this->authToken->getTicket(),
        ], $this->credentials->getHostname());

        switch ($method) {
            case 'GET':
                return $this->httpClient->get($url, [
                    'verify' => false,
                    'exceptions' => false,
                    'cookies' => $cookies,
                    'query' => $params,
                ]);
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $headers = [
                    'CSRFPreventionToken' => $this->authToken->getCsrf(),
                ];
                return $this->httpClient->request($method, $url, [
                    'verify' => false,
                    'exceptions' => false,
                    'cookies' => $cookies,
                    'headers' => $headers,
                    'form_params' => $params,
                ]);
            default:
                $errorMessage = "HTTP Request method {$method} not allowed.";
                throw new \InvalidArgumentException($errorMessage);
        }
    }


    /**
     * Parses the response to the desired return type.
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response sent by the Proxmox server.
     *
     * @return mixed The parsed response, depending on the response type can be
     *               an array or a string.
     */
    private function processHttpResponse($response)
    {
        if ($response === null) {
            return null;
        }

        if ($response->getStatusCode() >= 400) {
            throw new BadResponseException($response->getReasonPhrase());
        }

        switch ($this->fakeType) {
            case 'pngb64':
                $base64 = base64_encode($response->getBody());
                return 'data:image/png;base64,' . $base64;
            case 'object': // 'object' not supported yet, we return array instead.
            case 'array':
                return json_decode($response->getBody(), true);
            default:
                return $response->getBody()->__toString();
        }
    }


    /**
     * Returns the name of the cookie that should be used to authenticate requests.
     *
     * @return string The required cookie for the system being used
     */
    private function getCookieName()
    {
        switch ($this->credentials->getSystem()) {
            default:
            case 'pve': $cookiename = 'PVEAuthCookie'; break;
            case 'pmg': $cookiename = 'PMGAuthCookie'; break;
        }

        return $cookiename;
    }


    /**
     * Sets the HTTP client to be used to send requests over the network, for
     * now Guzzle needs to be used.²
     *
     * @param \GuzzleHttp\Client $httpClient the client to be used
     */
    public function setHttpClient($httpClient = null)
    {
        $this->httpClient = $httpClient ?: new Client();
    }


    /**
     * Attempts to login using set credentials, if succeeded will return the
     * AuthToken used in all requests.
     *
     * @return \ProxmoxVE\AuthToken When successful login will return an
     *                              instance of the AuthToken class.
     *
     * @throws \ProxmoxVE\Exception\AuthenticationException If login fails.
     */
    public function login()
    {
        $loginUrl = $this->credentials->getApiUrl() . '/json/access/ticket';
        $response = $this->httpClient->post($loginUrl, [
            'verify' => false,
            'exceptions' => false,
            'form_params' => [
                'username' => $this->credentials->getUsername(),
                'password' => $this->credentials->getPassword(),
                'realm' => $this->credentials->getRealm(),
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        if (!$json['data']) {
            $error = 'Can not login using the provided credentials';
            throw new AuthenticationException($error);
        }

        return new AuthToken(
            $json['data']['CSRFPreventionToken'],
            $json['data']['ticket'],
            $json['data']['username']
        );
    }


    /**
     * Gets the Credentials object associated with this proxmox API instance.
     *
     * @return \ProxmoxVE\Credentials Object containing all proxmox data used to
     *                                connect to the server.
     */
    public function getCredentials()
    {
        return $this->credentials;
    }


    /**
     * Assign the passed Credentials object to the ProxmoxVE.
     *
     * @param object $credentials A custom object holding credentials or a
     *                            Credentials object to assign.
     *
     * @throws \ProxmoxVE\Exception\AuthenticationException If can not login.
     */
    public function setCredentials($credentials)
    {
        if (!$credentials instanceof Credentials) {
            $credentials = new Credentials($credentials);
        }

        $this->credentials = $credentials;
        $this->authToken = $this->login();
    }


    /**
     * Sets the response type that is going to be returned when doing requests.
     *
     * @param string $responseType One of json, html, extjs, text, png.
     */
    public function setResponseType($responseType = 'array')
    {
        $supportedFormats = array('json', 'html', 'extjs', 'text', 'png');

        if (in_array($responseType, $supportedFormats)) {
            $this->fakeType = false;
            $this->responseType = $responseType;
        } else {
            switch ($responseType) {
                case 'pngb64':
                    $this->fakeType = 'pngb64';
                    $this->responseType = 'png';
                    break;
                case 'object':
                case 'array':
                    $this->responseType = 'json';
                    $this->fakeType = $responseType;
                    break;
                default:
                    $this->responseType = 'json';
                    $this->fakeType = 'array'; // Default format
            }
        }
    }


    /**
     * Returns the response type that is being used by the Proxmox API client.
     *
     * @return string Response type being used.
     */
    public function getResponseType()
    {
        return $this->fakeType ?: $this->responseType;
    }


    /**
     * GET a resource defined in the pvesh tool.
     *
     * @param string $actionPath The resource tree path you want to ask for, see
     *                           more at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params      An associative array filled with params.
     *
     * @return array             A PHP array json_decode($response, true).
     *
     * @throws \InvalidArgumentException If given params are not an array.
     */
    public function get($actionPath, $params = [])
    {
        if (!is_array($params)) {
            $errorMessage = 'GET params should be an associative array.';
            throw new \InvalidArgumentException($errorMessage);
        }

        // Check if we have a prefixed '/' on the path, if not add one.
        if (substr($actionPath, 0, 1) != '/') {
            $actionPath = '/' . $actionPath;
        }

        $response = $this->requestResource($actionPath, $params);
        return $this->processHttpResponse($response);
    }


    /**
     * SET a resource defined in the pvesh tool.
     *
     * @param string $actionPath The resource tree path you want to ask for, see
     *                           more at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params      An associative array filled with params.
     *
     * @return array             A PHP array json_decode($response, true).
     *
     * @throws \InvalidArgumentException If given params are not an array.
     */
    public function set($actionPath, $params = [])
    {
        if (!is_array($params)) {
            $errorMessage = 'PUT params should be an associative array.';
            throw new \InvalidArgumentException($errorMessage);
        }

        // Check if we have a prefixed '/' on the path, if not add one.
        if (substr($actionPath, 0, 1) != '/') {
            $actionPath = '/' . $actionPath;
        }

        $response = $this->requestResource($actionPath, $params, 'PUT');
        return $this->processHttpResponse($response);
    }


    /**
     * CREATE a resource as defined by the pvesh tool.
     *
     * @param string $actionPath The resource tree path you want to ask for, see
     *                           more at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params      An associative array filled with POST params
     *
     * @return array             A PHP array json_decode($response, true).
     *
     * @throws \InvalidArgumentException If given params are not an array.
     */
    public function create($actionPath, $params = [])
    {
        if (!is_array($params)) {
            $errorMessage = 'POST params should be an asociative array.';
            throw new \InvalidArgumentException($errorMessage);
        }

        // Check if we have a prefixed '/' on the path, if not add one.
        if (substr($actionPath, 0, 1) != '/') {
            $actionPath = '/' . $actionPath;
        }

        $response = $this->requestResource($actionPath, $params, 'POST');
        return $this->processHttpResponse($response);
    }


    /**
     * DELETE a resource defined in the pvesh tool.
     *
     * @param string $actionPath The resource tree path you want to ask for, see
     *                           more at http://pve.proxmox.com/pve2-api-doc/
     * @param array $params      An associative array filled with params.
     *
     * @return array             A PHP array json_decode($response, true).
     *
     * @throws \InvalidArgumentException If given params are not an array.
     */
    public function delete($actionPath, $params = [])
    {
        if (!is_array($params)) {
            $errorMessage = 'DELETE params should be an associative array.';
            throw new \InvalidArgumentException($errorMessage);
        }

        // Check if we have a prefixed '/' on the path, if not add one.
        if (substr($actionPath, 0, 1) != '/') {
            $actionPath = '/' . $actionPath;
        }

        $response = $this->requestResource($actionPath, $params, 'DELETE');
        return $this->processHttpResponse($response);
    }


    // Later on below this line we'll move this logic to another place?


    /**
     * Returns the proxmox API URL where requests are sended.
     * Sample value: https://my-proxmox:8006/api2/json
     *
     * @return string Proxmox API URL.
     */
    public function getApiUrl()
    {
        return $this->credentials->getApiUrl() . '/' . $this->responseType;
    }


    /**
     * Retrieves the '/access' resource of the Proxmox API resources tree.
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function getAccess()
    {
        return $this->get('/access');
    }


    /**
     * Retrieves the '/cluster' resource of the Proxmox API resources tree.
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function getCluster()
    {
        return $this->get('/cluster');
    }


    /**
     * Retrieves the '/nodes' resource of the Proxmox API resources tree.
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function getNodes()
    {
        return $this->get('/nodes');
    }


    /**
     * Retrieves the '/pools' resource of the Proxmox API resources tree.
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function getPools()
    {
        return $this->get('/pools');
    }


    /**
     * Creates a pool resource inside the '/pools' resources tree.
     *
     * @param array $poolData An associative array filled with POST params
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function createPool($poolData)
    {
        if (!is_array($poolData)) {
            throw new \InvalidArgumentException('Pool data needs to be array');
        }

        return $this->create('/pools', $poolData);
    }


    /**
     * Retrieves all the storage found in the Proxmox server, or only the ones
     * matching the storage type provided if any.
     *
     * @param string $type the storage type.
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function getStorages($type = null)
    {
        if ($type === null) {
            return $this->get('/storage');
        }

        $supportedTypes = array(
            'lvm',
            'nfs',
            'dir',
            'zfs',
            'rbd',
            'iscsi',
            'sheepdog',
            'glusterfs',
            'iscsidirect',
        );

        if (in_array($type, $supportedTypes)) {
            return $this->get('/storage', array(
                'type' => $type,
            ));
        }

        /* If type not found returns null */
        return null;
    }


    /**
     * Creates a storage resource using the passed data.
     *
     * @param array $storageData An associative array filled with POST params
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function createStorage($storageData)
    {
        if (!is_array($storageData)) {
            $errorMessage = 'Storage data needs to be array';
            throw new \InvalidArgumentException($errorMessage);
        }

        /* Should we check the required keys (storage, type) in the array? */

        return $this->create('/storage', $storageData);
    }


    /**
     * Retrieves the '/version' resource of the Proxmox API resources tree.
     *
     * @return mixed The processed response, can be an array, string or object.
     */
    public function getVersion()
    {
        return $this->get('/version');
    }
}
