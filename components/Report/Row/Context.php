<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 06.07.15
 * Time: 12:39
 */

namespace Report\Row;


class Context
{

    /**
     * @var array
     */
    private $_params;

    private $_time;


    /**
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
        $this->_time = microtime();
    }

    /**
     * @param array $params
     */
    public function addParams(array $params)
    {
        foreach ($params as $param) {
            $this->addParam($param);
        }
    }

    /**
     * @param $param
     */
    public function addParam($param)
    {
        if (!is_array($param)) {
            $this->_params[] = $param;
        } else {
            $this->_params = array_merge($this->_params, $param);
        }
    }

    /**
     * @param $param
     */
    public function unshiftParam($param)
    {
        if (!is_array($param)) {
            array_unshift($this->_params, $param);
        } else {
            $this->_params = array_merge($param, $this->_params);
        }
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    public function getTime()
    {
        return $this->_time;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getParam($name)
    {
        if (!isset($this->_params[$name])) {
            return null;
        }
        return $this->_params[$name];
    }


} 