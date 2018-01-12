<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 07.07.15
 * Time: 13:48
 */

namespace Report\Row;



use Export\Schema\Column as ExportColumn;

class TotalColumn extends Column
{

    /**
     * @var BlockInterface
     */
    private $_block;


    /**
     * @var mixed
     */
    private $_columnId;

   /**
     * @param ExportColumn $exportColumn
     * @param BlockInterface $block
     * @param mixed $columnId
     */
    public function __construct(
        ExportColumn $exportColumn,
        BlockInterface $block,
        $columnId
    )
    {
        $this->setBlock($block);
        $this->_columnId = $columnId;
        parent::__construct($exportColumn, new Context(), function () {});
    }


    /**
     * @param BlockInterface $block
     */
    public function setBlock(BlockInterface $block)
    {
        $this->_block = $block;
        $this->_block->addTotalColumn($this);
    }


    /**
     * @param ModelInterface $model
     * @return mixed
     */
    public function computeValue(ModelInterface $model)
    {
        $value = array_reduce(
            //прогружаем колонки, чтобы они выстроились в плоскую схему
            $this->_block->getColumnsById($this->_columnId),

            function ($sum, ColumnInterface $column) use ($model) {
                    return $sum + $model->getValue($column);
            },
            0
        );
        return $value;
    }

    /**
     * @return mixed
     */
    public function getColumnId()
    {
        return $this->_columnId;
    }

    function __clone()
    {
        $this->_block->addTotalColumn($this);
        parent::__clone();
    }


} 