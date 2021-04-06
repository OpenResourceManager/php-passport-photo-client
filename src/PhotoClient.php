<?php

namespace OpenResourceManager\PassportPhoto;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class PhotoClient
 *
 * Base class used to build the HTTP session.
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/OpenResourceManager/php-passport-photo-client/master/LICENSE MIT License
 * @author Alex Markessinis
 */
class PhotoClient
{
    /**
     * HTTP Client.
     *
     * The HTTP client used to communicate with Passport.
     *
     * @var Client
     */
    private $_privateHttpClient = null;

    /**
     * HTTP Client.
     *
     * The HTTP client used to communicate with Passport.
     *
     * @var Client
     */
    private $_publicHttpClient;

    /**
     * The library version.
     *
     * This string defines the client version
     *
     * @var string
     */
    private $_version = '0.0.1';

    /**
     * Base API URL.
     *
     * The base API url used to communicate with Passport.
     *
     * @var string
     */
    private $_baseURL;

    /**
     * Passport Authorization Token.
     *
     * The OAuth2 token passed to Passport for authorization
     *
     * @var string
     */
    private $_token;

    /**
     * PhotoClient constructor
     *
     * Builds the class
     *
     * @param string $baseURL
     * @param string|null $token
     */
    public function __construct(string $baseURL, string $token = null)
    {
        // Set the secret property
        $this->_token = $token;
        // Sets the base url
        $this->_baseURL = $baseURL;
        // Build and set the HTTP clients
        if (!empty($token)) $this->_privateHttpClient = $this->_buildPrivateClient();
        $this->_publicHttpClient = $this->_buildPublicClient();
    }

    /**
     * Build Private HTTP Client
     *
     * Builds and returns an HTTP client for authenticated requests.
     *
     * @return Client
     */
    private function _buildPrivateClient(): Client
    {
        // Return the new HTTP client
        return new Client([
            'base_uri' => implode('/', array(rtrim($this->_baseURL, '/'), 'img/private/avatar/')),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->_token,
                'User-Agent' => 'OpenResourceManager/PassportPhotoClient/' . $this->_version,
                'Accept' => '*/*'
            ],
        ]);
    }

    /**
     * Build Public HTTP Client
     *
     * Builds and returns an HTTP client for public requests.
     *
     * @return Client
     */
    private function _buildPublicClient(): Client
    {
        // Return the new HTTP client
        return new Client([
            'base_uri' => implode('/', array(rtrim($this->_baseURL, '/'), 'img/avatar/')),
            'headers' => [
                'User-Agent' => 'OpenResourceManager/PassportPhotoClient/' . $this->_version,
                'Accept' => '*/*'
            ],
        ]);
    }

    /**
     * Get Photo
     *
     * Sends an HTTP request to obtain a photo
     *
     * @param Client $httpClient
     * @param string $identString
     * @param string $downloadDir
     * @param array $params
     * @return false|string
     */
    private function _getPhoto(Client $httpClient, string $identString, string $downloadDir = '', array $params = [])
    {
        // Set the default download directory
        if (empty($downloadDir)) $downloadDir = sys_get_temp_dir();
        // Build the file path
        $filePath = realpath(implode('/', [$downloadDir, $identString . '.jpg']));
        // Try the HTTP request
        try {
            $response = $httpClient->get($identString, [
                'query' => $params,
                'sink' => $filePath
            ]);
        } catch (GuzzleException $e) {
            // If something went wrong return false
            return false;
        }
        // If the request went as planned return the absolute path to the picture
        if ($response->getStatusCode() === 200) return $filePath;
        // If we get here the status code was not 200, so return false
        return false;
    }

    /**
     * Get Private Photo
     *
     * Sends an HTTP request to obtain a private authenticated photo by Identifier or Username
     *
     * @param string $identString A username or identifier for the user's photo.
     * @param string $downloadDir The path to save the file, defaults to the PHP Temp Directory.
     * @param array $params An array of HTTP GET query parameters for the request, for options see: https://glide.thephpleague.com/1.0/api/quick-reference/
     * @return false|string
     */
    public function getPrivatePhoto(string $identString, string $downloadDir = '', array $params = [])
    {
        if (!empty($this->_privateHttpClient)) {
            return $this->_getPhoto($this->_privateHttpClient, $identString, $downloadDir, $params);
        }
        return false;
    }

    /**
     * Get Public Photo
     *
     * Sends an HTTP request to obtain a public photo by Photo ID
     *
     * @param string $identString A username or identifier for the user's photo.
     * @param string $downloadDir The path to save the file, defaults to the PHP Temp Directory.
     * @param array $params An array of HTTP GET query parameters for the request, for options see: https://glide.thephpleague.com/1.0/api/quick-reference/
     * @return false|string
     */
    public function getPublicPhoto(string $identString, string $downloadDir = '', array $params = [])
    {
        return $this->_getPhoto($this->_publicHttpClient, $identString, $downloadDir, $params);
    }
}