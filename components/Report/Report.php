<?php
namespace Report;

use ExportComponent, Iterator, CModel;
use Export\Schema\Schema;
use Report\Row\ColumnInterface;
use Report\Row\ModelInterface;
use Report\Row\RowModel;
use Report\Row\TotalRow;
use Report\Row\TotalRowModel;

/**
 * Report
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
abstract class Report extends \CComponent implements ReportInterface
{

    /**
     * _tick
     *
     * @var callable
     */
    private $_tick;

    /**
     * _filter
     *
     * @var CModel
     */
    protected $_filter;

    /**
     * _export
     *
     * @var ExportComponent
     */
    protected $_export;


    /**
     * _row
     *
     * @var \Report\Row\Row
     */
    protected $_row;


    /**
     * @var  \Export\Schema\Column[]
     */
    protected $_exportColumns;

    /**
     * _formatters
     *
     * @var array
     */
    private $_formatters;

    /**
     * @var int
     */
    private $_countModels;



    /**
     * @param array $attributes
     * @param ExportComponent $export
     * @param callable $tick
     * @return static
     */
    public static function create(array $attributes, ExportComponent $export, callable $tick)
    {
        return new static($attributes, $export, $tick);
    }

    /**
     * @param array $attributes
     * @param ExportComponent $export
     * @param callable $tick
     * @throws \Exception
     */
    public function __construct(array $attributes, ExportComponent $export, callable $tick)
    {
        $this->_filter = $this->buildFilter($attributes);
        $this->_tick = $tick;
        $this->_export = $export;

        $this->init();

        $template = $this->getTemplate();
        $this->_row = $template->getRow();
        $this->_exportColumns = $this->getExportColumns();
        $this->_formatters = $this->getFormatters();
    }


    /**
     * @return array
     */
    private function getExportColumns()
    {
        $columns = array();
        foreach ($this->_row->getColumns() as $column) {
            if ($this->isShowColumn($column)) {
                $columns[] = $column->getExportColumn();
            }
        }

        return $columns;

    }


    /**
     * generateHeaders
     *
     * @return void
     */
    public function generateHeaders()
    {
        $this->_export->setSchema(new Schema($this->_exportColumns));

        $titles = $this->getTitles();
        foreach ($titles as $item) {
            $this->_export->addString($item);
        }

        $this->generatePreRows();
        $this->_export->addHeaders();
    }

    /**
     * generatePreRows
     *
     * @return void
     */
    public function generatePreRows()
    {
        $items = $this->getPreModels();

        foreach ($items as $item) {
            $this->_export->newRow();
            foreach ($this->_exportColumns as $key => $exportColumn) {
                $this->_export->setField(
                    $exportColumn->getId(),
                    $item->getCell($item->getColumn($exportColumn->getId()))
                );
            }
            $this->_export->saveRow();
        }
    }

    /**
     * generateRows
     *
     * @return int
     */
    public function generateRows()
    {
        $countModels = $this->getCountModels();
        $tick = $this->createTickFunction($countModels);

        foreach ($this->getModels() as $modelId => $rawModel) {
            $this->buildRow(
                $this->createReportModel($rawModel),
                function ($columnId) use($modelId, $tick) {
                    call_user_func($tick, $modelId, $columnId);
                }
            );
        }
        return $countModels;

    }

    /**
     * @param int $countModels
     * @return callable
     */
    private function createTickFunction($countModels)
    {
        $countColumns = count($this->_row->getColumns());
        $fullCount = $countColumns * $countModels;

        return function ($modelId, $columnId) use ($fullCount, $countColumns) {
            $key = $modelId * $countColumns + $columnId;
            call_user_func($this->_tick, $key, $fullCount);
        };
    }


    /**
     * saveFile
     *
     * @param mixed $filePath
     * @param mixed $fileName
     * @return void
     */
    public function saveFile($filePath, $fileName)
    {
        $this->_export->saveFile($filePath, $fileName);
    }

    /**
     * init
     *
     * @return void
     */
    protected function init()
    {

    }

    /**
     * getTemplate
     *
     * @return \Report\Template\RowTemplate
     */
    abstract protected function getTemplate();

    
    /**
     * getFormatters
     *
     * array(
     *    'date.time' => array('DurationHelper', 'formatHuman')
     * )
     *
     * @return array
     */
    abstract protected function getFormatters();

    /**
     * getFilter
     *
     * @return CModel
     */
    abstract protected function createFilter();

    /**
     * getModels
     *
     * Operator::model()->findAll();
     *
     * @return Iterator
     */
    abstract protected function getModels();

    /**
     * @return ModelInterface[]
     */
    protected function getPreModels()
    {
        return array();
    }


    /**
     * getCountModels
     *
     * @return int
     */
    protected function getCountModels()
    {
        if (!isset($this->_countModels)) {
            $this->_countModels = count($this->getModels());
        }
        return $this->_countModels;
    }

    /**
     * getTitles
     *
     * "От {$this->_filter->startDate} по {$this->_filter->endDate}"
     *
     * @return array
     */
    abstract protected function getTitles();

    /**
     *
     * comppoundName of hide columns
     *
     * @return array
     */
    protected function getHides()
    {
        return array();
    }


    /**
     * @param mixed $model
     * @return ModelInterface
     */
    private function createReportModel($model)
    {
        if (!$model instanceof ModelInterface) {
            $model = new RowModel($this->_row, array($model));
        }
        return $model;
    }

    /**
     * buildRow
     *
     * @param ModelInterface $model
     * @param callable $tick
     * @return void
     */
    private function buildRow(ModelInterface $model, callable $tick)
    {
        if ($model->getRow() === null) {
            throw new \Exception('not set row');
        }
        $this->_export->newRow();
        foreach ($this->_exportColumns as $key => $exportColumn) {
            $this->_export->setField(
                $exportColumn->getId(),
                $this->getValue($model, $model->getColumn($exportColumn->getId()))
            );

            call_user_func($tick, $key);
        }
        $this->_export->saveRow();
    }

    /**
     * @param ModelInterface $model
     * @param ColumnInterface $column
     * @return mixed
     */
    private function getValue(ModelInterface $model, ColumnInterface $column)
    {
        $cell = $model->getCell($column);
        return $cell
            ->setValue(
                $this->formatValue($model, $column->getId(), $cell->getValue()
            )
        );
    }

    /**
     * @param ColumnInterface $column
     * @return bool
     */
    private function isShowColumn(ColumnInterface $column)
    {
        return !in_array($column->getId(), $this->getHides());
    }


    /**
     * formatValue
     *
     * @param ModelInterface $model
     * @param int $columnId
     * @param mixed $value
     * @return mixed
     */
    private function formatValue(ModelInterface $model, $columnId, $value)
    {
        $formatters = $model->getFormatters();
        if (!isset($formatters)) {
            $formatters = $this->_formatters;
        }

        if (isset($formatters[$columnId])) {
            return call_user_func($formatters[$columnId], $value);
        }

        return $value;
    }


    /**
     * buildFilter
     *
     * @param array $attributes
     * @return \CModel
     */
    private function buildFilter(array $attributes)
    {
        $filter = $this->createFilter();
        $filter->setScenario('console');
        $filter->attributes = $attributes;
        if (!$filter->validate()) {
            throw new \Exception('Фильтр для отчета с ошибками - ' . print_r($filter->errors, true));
        }

        return $filter;
    }



}
