<?php
namespace Report\Template;

use Carbon\Carbon;
use Export\Schema\Column as ExportColumn;
use Export\Style\CellStyle;
use Project;
use Report\Row\BlockInterface;
use Report\Row\TotalColumn;
use Report\Row\Column;
use Report\Row\Context;
use Report\Row\RowInterface;
use Stat\Cache\ArrayCache;
use Stat\Helper\CacheHelper;


/**
 * RowTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
abstract class RowTemplate
{
    /**
     * _filter
     *
     * @var \CModel
     */
    protected $_filter;

    /**
     * _project
     *
     * @var \Project
     */
    private $_project;

    /**
     * @var RowInterface
     */
    private $_row;


    /**
     * @var ArrayCache
     */
    protected $_cache;




    /**
     * @param null $filter
     */
    public function __construct($filter = null)
    {
        $this->_cache = new ArrayCache();
        $this->_filter = $filter;
    }


    protected function getDefaultLabelStyle()
    {
        return new CellStyle();
    }


    /**
     * getRow
     *
     * @return RowInterface
     */
    public function getRow() {
        if (!isset($this->_row)) {
            $this->_row = $this->buildRow();
        }

        return $this->_row;
    }

    /**
     * @return RowInterface
     */
    abstract protected function buildRow();

    /**
     * @return \CModel
     */
    public function getFilter()
    {
        return $this->_filter;
    }


    /**
     * getProject
     *
     * @return \Project
     */
    protected function getProject()
    {
        if (!isset($this->_project)) {
            $this->_project = Project::model()->findByPk($this->_filter->project_id);
        }
        return $this->_project;
    }

    /**
     * createColumn
     *
     * @param mixed $key
     * @param mixed $label
     * @param mixed $value
     * @param CellStyle $cellStyle
     * @param string $type
     * @return Column
     * @throws \Exception
     */
    public function createColumn($key, $label, $value, CellStyle $cellStyle = null, $type = 'text')
    {
        if (!is_callable(array($this, $value))) {
            throw new \Exception('not known callable function ' . $value);
        }

        return $this->createClosureColumn($key, $label, array($this, $value), $cellStyle, $type);
    }
    
    public function createClosureColumn($key, $label, callable $value, CellStyle $cellStyle = null, $type = 'text')
    {
        $exportColumn = ExportColumn::create($type, $key, $label);
        if (isset($cellStyle)) {
            $exportColumn->setLabelStyle($cellStyle);
        } else {
            $exportColumn->setLabelStyle($this->getDefaultLabelStyle());
        }

        return new Column(
            $exportColumn,
            new Context(),
            $value
        );
    }


    /**
     * getCacheValue
     *
     * @param mixed $label
     * @param array $args
     * @param Closure $func
     * @return Closure
     */
    protected function getCacheValue($label, array $args, $func)
    {
        $cacheKey = $label . '_' .CacheHelper::getCacheKeyFromParams($args);
        return $this->_cache->cacheValue($cacheKey, $func);
    }


    /**
     * @param BlockInterface $block
     * @param $columnId
     * @param $label
     * @param CellStyle $cellStyle
     * @param string $type
     * @return \Report\Row\ColumnInterface
     * @throws \Exception
     */
    public function createTotalColumn(BlockInterface $block, $columnId, $label,
                                      CellStyle $cellStyle = null,
                                      $type = ExportColumn::NUMERIC
    ) {
        $exportColumn = ExportColumn::create($type, $columnId, $label);
        if (isset($cellStyle)) {
            $exportColumn->setLabelStyle($cellStyle);
        }
        return new TotalColumn($exportColumn, $block, $columnId);
    }


    /**
     * @return string
     */
    public function getEmpty()
    {
        return '';
    }








}
