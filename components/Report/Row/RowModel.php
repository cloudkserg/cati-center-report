<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 26.06.15
 * Time: 15:39
 */

namespace Report\Row;



use Export\Cell\Cell;
use Export\Style\CellStyle;
use Stat\Cache\ArrayCache;
use Stat\Cache\CacheInterface;
use Stat\Helper\CacheHelper;

class RowModel implements ModelInterface
{

    /**
     * @var Context
     */
    protected  $_context;

    /**
     * @var array
     */
    protected $_params = [];

    /**
     * @var RowInterface
     */
    protected $_row;

    /**
     * @var CacheInterface
     */
    protected $_cache;

    /**
     * @var CellStyle
     */
    protected $_style;


    /**
     * @var null|callable[]
     */
    protected $_formatters;


    /**
     * @param RowInterface $row
     * @param array $params
     * @param Context $context
     */
    public function __construct(RowInterface $row, array $params = array(), Context $context = null)
    {
        $this->_params = $params;
        $this->_context = $context;
        $this->_row = $row;
    }

    /**
     * @param CellStyle $style
     */
    public function setDefaultStyle(CellStyle $style)
    {
        $this->_style = $style;
    }

    /**
     * @return CellStyle
     */
    public function getDefaultStyle()
    {
        if (!isset($this->_style)) {
            $this->_style = new CellStyle();
        }
        return $this->_style;
    }

    /**
     * @param array $formatters
     */
    public function setFormatters(array $formatters)
    {
        $this->_formatters = $formatters;
    }

    /**
     * @return \callable[]|null
     */
    public function getFormatters()
    {
        return $this->_formatters;
    }


    /**
     * @return RowInterface
     */
    public function getRow()
    {
        return $this->_row;
    }

    /**
     * @return ArrayCache|CacheInterface
     */
    public function getCache()
    {
        if (!isset($this->_cache)) {
            $this->_cache = new ArrayCache();
        }
        return $this->_cache;
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->_cache = $cache;
    }


    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->_context;
    }


    /**
     * @param ColumnInterface $column
     * @return mixed
     */
    public function getValue(ColumnInterface $column)
    {
        return $this->getCell($column)->getValue();
    }

    /**
     * @param ColumnInterface $column
     * @return Cell
     */
    public function getCell(ColumnInterface $column)
    {
        $value = $this->getCacheValue($column);
        if (!$value instanceof Cell) {
            return Cell::create($value)->setStyle($this->getDefaultStyle());
        }
        return $value;
    }

    /**
     * @param ColumnInterface $column
     * @return Cell|mixed
     */
    private function getCacheValue(ColumnInterface $column)
    {
        $cacheKey = CacheHelper::getCacheKeyFromParams(
            array_merge([$column->getExportColumn()->getId()], $this->_params)
        );
        return $this->getCache()->cacheValue($cacheKey, function () use ($column) {
           return $this->getRawValue($column);
        });
    }



    /**
     * @param ColumnInterface $column
     * @return mixed
     */
    protected function getRawValue(ColumnInterface $column)
    {
        return $column->computeValue($this);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @param $exportColumnId
     * @return ColumnInterface
     */
    public function getColumn($exportColumnId)
    {
        if ($this->_row === null) {
            \AdminDebugHelper::printParamsRow($this->_params);

            throw new \Exception('Не задана строка');
        }
        return $this->_row->getColumn($exportColumnId);
    }


} 