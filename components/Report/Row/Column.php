<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 13:35
 */

namespace Report\Row;


use Export\Schema\Column as ExportColumn;

class Column extends \CComponent implements ColumnInterface, PartInterface
{
    /**
     * @var ExportColumn
     */
    private $_exportColumn;

    /**
     * @var callable
     */
    private $_value;

    /**
     * @var null|string
     */
    private $_id;

    /**
     * @var Context
     */
    protected $_context;




    /**
     * @param ExportColumn $exportColumn
     * @param callable $value
     * @param null|string $id
     * @param array $params
     */
    public function __construct(
        ExportColumn $exportColumn, Context $context, callable $value,
        $id = null
    ) {
        $this->_exportColumn = $exportColumn;
        $this->_context = $context;
        $this->_value = $value;
        $this->_id = isset($id) ? $id : $exportColumn->getId();
    }


    /**
     * @return ExportColumn
     */
    public function getExportColumn()
    {
        return $this->_exportColumn;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->_context;
    }


    /**
     * @param ModelInterface $model
     * @return mixed
     */
    public function computeValue(ModelInterface $model)
    {
        $workContext = clone($this->_context);
        $workContext->unshiftParam($model->getParams());
        $workContext->addParam($model);

        return call_user_func_array(
            $this->_value,
            $workContext->getParams()
        );
    }

    /**
     * @param Context $context
     * @return ColumnInterface[]
     */
    public function getColumns(Context $context)
    {
        return array($this);
    }

    /**
     *
     */
    function __clone()
    {
        $this->_exportColumn = clone $this->_exportColumn;
        $this->_context = clone $this->_context;
    }

    /**
     * @param $id
     * @return void
     */
    public function setId($id)
    {
        $this->_id = $id;
    }


    /**
     * @param ExportColumn $column
     * @return void
     */
    public function setExportColumn(ExportColumn $column)
    {
        $this->_exportColumn = $column;
    }


}
