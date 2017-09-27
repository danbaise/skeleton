<?php

namespace App\Core;


class Request
{

    public static $data = [];
    public static $root = "";


    public function setRoot($root)
    {
        self::$root = $root;
    }

    /*
    Get the base url
    Ex: http://www.example.com/path/to/here/?p=1 => http://www.example.com

    @return [string] The base url of the request
    */
    public function base()
    {
        $protocol = isset($_SERVER['HTTPS']) ? "https" : "http";
        return $protocol . "://" . $_SERVER['HTTP_HOST'];
    }

    /*
    Get the full url of the request
    Ex: http://www.example.com/path/to/here/?p=1 => http://www.example.com/path/to/here/?p=1

    @return [string] The full url of the request, including query strings
    */
    public function url()
    {
        return $this->base() . $_SERVER['REQUEST_URI'];
    }

    /*
    Get the url path (portion that follows the base, minus query strings)
    Ex: http://www.example.com/path/to/here/?p=1 => /path/to/here/

    @return [string] The url path, excluding query strings
    */
    public function path()
    {
        return str_replace("?" . $this->queryString(), "", $_SERVER['REQUEST_URI']);
    }

    /*
    Get the current path of the request
    Ex: http://www.example.com/path/to/here/?p=1 => /path/to/here/ (the root here is "")

    @return [string] Trims the web root off of the path for the relative path and returns it
    */
    public function here()
    {
        if (Request::$root != "/") {
            $here = str_replace(Request::$root, "", $this->path());
        } else {
            $here = $this->path();
        }
        if (substr($here, 0, 1) != "/") {
            $here = "/" . $here;
        }
        return $here;
    }

    /*
    Get the query string
    Ex: http://www.example.com/path/to/here/?p=1 => ?p=1

    @return [string] The query string
    */
    public function queryString()
    {
        return (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : "");
    }

    /*
    Get a query string argument, if it exists, from $_SERVER['QUERY_STRING']
    Ex: http://www.example.com/path/to/here/?p=1&q=2
        $request->query("p") => 1
        $request->query("q") => 2
        $request->query("r") => null

    @return [string] The query argument value or null
    */
    public function query($arg)
    {
        $queryString = $this->queryString();
        if (empty($queryString)) {
            return null;
        }
        $queries = array();
        $queryFields = explode('&', $queryString);
        foreach ($queryFields as $component) {
            $tmp = explode("=", $component);
            $queries[$tmp[0]] = (isset($tmp[1]) ? $tmp[1] : "");
        }
        if (isset($queries[$arg])) {
            return $queries[$arg];
        }
        return null;
    }

    /*
    Get the IP address of the request

    @return [string] The IP address
    */
    public function ipAddress()
    {
        return $_SERVER["REMOTE_ADDR"];
    }

    /*
    Builds a link from the base and the given subpath (or relative path)
    Ex: link("/about/team/") => http://www.example.com/about/team/

    @param [string] $subpath The relative path
    @param [boolean] $print If true, then echo the link, otherwise return the string
    @param [boolean] $ssl Force the link to SSL, if not already
    @return [string] The link if $print = false
    */
    public function link($subpath, $print = false, $ssl = false)
    {
        $link = "";
        if ($subpath) {
            if (substr($subpath, 0, 1) == "/") {
                $subpath = substr($subpath, 1);
            }
            $link = $this->base() . Request::$root . $subpath;
        } else {
            $link = $this->base() . Request::$root;
        }

        if ($ssl) {
            if (stripos($link, "http://") !== false) {
                $link = str_replace("http://", "https://", $link);
            }
        }

        if ($print) {
            echo $link;
        } else {
            return $link;
        }
    }

    /*
    Check for a particular property of the request.

    @param [string] $prop The property to check for.
        These include:
            "get"
            "post"
            "put"
            "ajax"
            "ssl"
    @return [boolean] True if the request is $prop
    */
    public function is($prop)
    {
        if ($prop == "get") {
            return (strtolower($_SERVER['REQUEST_METHOD']) == "get");
        } else {
            if ($prop == "post") {
                return (strtolower($_SERVER['REQUEST_METHOD']) == "post");
            } else {
                if ($prop == "put") {
                    return (strtolower($_SERVER['REQUEST_METHOD']) == "put");
                } else {
                    if ($prop == "ajax") {
                        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
                    } else {
                        if ($prop == "ssl") {
                            return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != false);
                        }
                    }
                }
            }
        }
    }

    /*
    Get a specific GET parameter, or get the entire array of GET parameters

    @param [string] $key The key to retrieve
    @return [string, null, array] The value of the key if $key is provided, null if the key does not exist,
        or the array of $_GET params if no $key is supplied
    */
    public static function get($key = null)
    {
        if ($key) {
            if (isset(self::$data['get'][$key])) {
                return self::$data['get'][$key];
            } else {
                return null;
            }
        }
        return (!empty(self::$data['post']) ? self::$data['post'] : null);
    }

    /*
    Get a specific POST parameter, or get the entire array of POST parameters

    @param [string] $key The key to retrieve
    @return [string, null, array] The value of the key if $key is provided, null if the key does not exist,
        or the array of $_POST params if no $key is supplied
    */
    public static function post($key = null)
    {
        if ($key) {
            if (isset(self::$data['post'][$key])) {
                return self::$data['post'][$key];
            } else {
                return null;
            }
        }
        return (!empty(self::$data['post']) ? self::$data['post'] : null);
    }

    /*
    Get a specific FILES parameter, or get the entire array of FILES parameters

    @param [string] $key The key to retrieve
    @return [string, null, array] The value of the key if $key is provided, null if the key does not exist,
        or the array of $_FILES params if no $key is supplied
    */
    public function files($key = null)
    {
        if ($key) {
            if (isset($_FILES[$key])) {
                return $_FILES[$key];
            } else {
                return null;
            }
        }
        return (!empty($_FILES) ? $_FILES : null);
    }

    public function input()
    {
        $path_info = str_replace(self::$root, "", $this->path());

        return self::$data = [
            'get' => (isset($_GET) && !empty($_GET)) ? $_GET : null,
            'post' => (isset($_POST) && !empty($_POST)) ? $_POST : null,
            'rawData' => file_get_contents('php://input', 'r'),
            'url' => $this->url(),
            'parseUrl' => parse_url($this->url()),
            'path_info' => $path_info && ($path_info != '/') ? ltrim($path_info, '/') : '/',
        ];
    }

}