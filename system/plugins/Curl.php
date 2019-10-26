<?php

defined('BASE') or exit('No direct script access allowed');

class Curl
{
    private $curl = null;
    private $error = '';

    public $options = [];
    public $headers = [];
    public $referer = null;
    public $user_agent = null;

    public $use_cookie = false;
    public $cookie_file = '';

    public $response_header = [];
    public $response_body = '';
    public $follow_redirections = true;

    public function __construct()
    {
        if ($this->use_cookie) {
            $this->cookie_file = storage_path('cache/curl-cookie.cache');
        }

        if (null == $this->user_agent) {
            $this->user_agent = isset($_SERVER['HTTP_USER_AGENT'])
                ? $_SERVER['HTTP_USER_AGENT']
                : 'PHP '.PHP_VERSION.' (https://php.net)';
        }
    }

    public function get($url, $params = [])
    {
        if (filled($params)) {
            $url .= (false !== stripos($url, '?')) ? '&' : '?';
            $url .= (is_string($params)) ? $params : http_build_query($params, '', '&');
        }
        $this->makeRequest('GET', $url);
    }

    public function post($url, $params = [])
    {
        $this->makeRequest('POST', $url, $params);
    }

    public function head($url, $params = [])
    {
        $this->makeRequest('HEAD', $url, $params);
    }

    public function put($url, $params = [])
    {
        $this->makeRequest('PUT', $url, $params);
    }

    public function delete($url, $params = [])
    {
        $this->makeRequest('DELETE', $url, $params);
    }

    private function makeRequest($method, $url, $params = [])
    {
        $this->error = '';
        $this->curl = curl_init();
        if (is_array($params)) {
            $params = http_build_query($params, '', '&');
        }

        $this->setRequestMethod($method);
        $this->setRequestOptions($url, $params);
        $this->setRequestHeaders();

        $response = curl_exec($this->curl);
        if ($response) {
            $response = $this->getResponse($response);
        } else {
            $this->error = curl_errno($this->curl).' - '.curl_error($this->curl);
        }

        curl_close($this->curl);
    }

    private function setRequestHeaders()
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }

    private function setRequestMethod($method)
    {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->curl, CURLOPT_NOBODY, true);
                break;

            case 'GET':
                curl_setopt($this->curl, CURLOPT_HTTPGET, true);
                break;

            case 'POST':
                curl_setopt($this->curl, CURLOPT_POST, true);
                break;

            default:
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    private function setRequestOptions($url, $params)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        if (filled($params)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->user_agent);

        if (false !== $this->use_cookie) {
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie_file);
        }

        if ($this->follow_redirections) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        }

        if (null !== $this->referer) {
            curl_setopt($this->curl, CURLOPT_REFERER, $this->referer);
        }

        foreach ($this->options as $option => $value) {
            $options = constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option)));
            curl_setopt($this->curl, $options, $value);
        }
    }

    private function getResponse($response)
    {
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';
        preg_match_all($pattern, $response, $matches);
        $headersData = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headersData));

        $this->response_body = str_replace($headersData, '', $response);
        $versionAndStatus = array_shift($headers);

        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $versionAndStatus, $matches);
        $this->response_header['Http-Version'] = $matches[1];
        $this->response_header['Status-Code'] = $matches[2];
        $this->response_header['Status'] = $matches[2].' '.$matches[3];

        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->response_header[$matches[1]] = $matches[2];
        }
    }

    public function getError()
    {
        return $this->error;
    }
}
