<?php
/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 007 07.11
 * Time: 17:19:28
 */

class Curl
{
    private static $cookie;
    private $url;
    private $method = 'GET';
    private $headers = [
        'Content-Type' => 'application/json',
    ];
    private $result;
    private $userAgent = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0";
    private $code;

    public function __construct()
    {
    }

    public static function getCookie()
    {
        return self::$cookie;
    }

    public static function getCookieArray()
    {
        $cookies = [];
        foreach (explode(';', self::$cookie) as $item) {
            list($key, $value) = explode('=', $item);
            $cookies[$key] = $value;
        }
        return $cookies;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     * @return $this
     */
    public function setUrl($url)
    {
        if (!empty($url)) {
            $this->url = $url;
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return $this
     */
    public function setMethod($method)
    {
        if (!empty($method)) {
            $this->method = strtoupper($method);
        }
        return $this;
    }

    public function run(array $data = [])
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        if ($this->method === 'POST') {
            var_dump(http_build_query($data));
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        //todo::реализовать другие методы
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->mixHeaders());
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_COOKIE, empty(self::$cookie) ? "" : "Set-Cookie: " . self::$cookie);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $out = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = trim(substr($out, 0, $header_size));
        $this->result = trim(substr($out, $header_size));
        preg_match_all('|Set-Cookie: (.*);|U', $header, $results);
        self::$cookie = implode(';', $results[1]);
        $this->code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $this;
    }

    /**
     * @return array
     */
    private function mixHeaders()
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }
        return $headers;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getJson()
    {
        return json_decode($this->result, true);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed|string
     */
    public function getHeader($key)
    {
        return $this->headers[$key] ? $this->headers[$key] : '';
    }
}
