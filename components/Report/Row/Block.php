<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 13:35
 */

namespace Report\Row;

use Export\Schema\Column as ExportColumn;

class Block implements BlockInterface, PartInterface
{
    /**
     * @var string
     */
    private $_id;

    /**
     * @var array
     */
    private $_parts = array();

    /**
     * @var callable
     */
    private $_itemFunc;

    /**
     * @var TotalColumn[]
     */
    private $_totalColumns = array();

    /**
     * @var ColumnInterface[][]
     */
    private $_columnsById = [];



    /**
     * function for getId by Item
     * @var callable
     */
    private $_getIdFromItem;

    /**
     *
     * array(date => 22.01.2014, project => 6161), 'duration'
     *
     *
     * @param array $blockValues
     * @param string $columnId
     * @return string
     */
    public static function compoundColumnId(array $blockValues, $columnId)
    {
        $id = '';
        foreach ($blockValues as $blockId => $blockValue) {
            $id .= $blockId . '.' . $blockValue;
        }
        return $id . '.' . $columnId;
    }

    /**
     * @param $id
     * @param callable $itemFunc
     * @param callable|null $getIdFromItem
     */
    public function __construct($id, callable $itemFunc, callable $getIdFromItem = null)
    {
        $this->_id = $id;
        $this->_itemFunc = $itemFunc;
        $this->_getIdFromItem = isset($getIdFromItem) ? $getIdFromItem : function ($val) { return $val; };
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }


    /**
     * @param ColumnInterface $column
     * @return $this
     */
    public function addColumn(ColumnInterface $column)
    {
        $this->_parts[] = $column;
        return $this;

    }

    /**
     * @param BlockInterface $block
     * @return $this
     */
    public function addBlock(BlockInterface $block)
    {
        $this->_parts[] = $block;
        return $this;

    }

    /**
     * @param TotalColumn $column
     * @return mixed
     */
    public function addTotalColumn(TotalColumn $column)
    {
        $this->_totalColumns[$column->getColumnId()] = $column;
    }


    /**
     * @param Context $context
     * @return ColumnInterface[]
     */
    public function getColumns(Context $context)
    {
        $columns = array();

        $this->resetColumnsById();

        foreach ($this->getItems($context) as $item) {
            $itemContext = new Context();
            $itemContext->addParams($context->getParams());
            $itemContext->addParam($item);

            foreach ($this->cloneParts($this->_parts) as $part) {
                $columns = array_merge($columns, $this->getFillPartColumns($part, $itemContext, $item));
            }

        }

        return $columns;
    }

    /**
     * @param array $parts
     * @return array
     */
    private function cloneParts(array $parts)
    {
        return array_map(function (PartInterface $part) {
            return clone($part);
        }, $parts);
    }

    /**
     * @param PartInterface $part
     * @param Context $context
     * @param $item
     * @return ColumnInterface[]
     */
    private function getFillPartColumns(PartInterface $part, Context $context, $item)
    {
        $partColumns = $part->getColumns($context);
        foreach ($partColumns as $column) {
            $templateColumnId = $column->getId();

            $column->getContext()->unshiftParam($item);
            $column->setId($this->generateId($templateColumnId));
            $column->setExportColumn($this->generateExportColumn($column->getExportColumn(), $item));

            $this->addColumnsById($templateColumnId, $column);
        }

        return $partColumns;
    }



    /**
     * @param $columnId
     * @param ColumnInterface $column
     */
    private function addColumnsById($columnId, ColumnInterface $column)
    {
        $this->_columnsById[$columnId][] = $column;
    }

    /**
     *
     */
    private function resetColumnsById()
    {
        foreach ($this->_parts as $part) {
            if ($part instanceof Column) {
                $this->_columnsById[$part->getId()] = [];
            }
        }
    }


    /**
     * @param $columnId
     * @return ColumnInterface[]
     * @throws \Exception
     */
    public function getColumnsById($columnId)
    {
        if (!isset($this->_columnsById[$columnId])) {
            throw new \Exception('Not known column by id ' . $columnId);
        }
        return $this->_columnsById[$columnId];
    }

    /**
     * @param Context $context
     * @return array
     */
    private function getItems(Context $context)
    {
        $items = call_user_func_array($this->_itemFunc, $context->getParams());
        return $items;
    }



    /**
     * @param $id
     * @return string
     */
    private function generateId($id)
    {
        return $this->_id . '.' . $id;
    }


    /**
     * @param ExportColumn $column
     * @param $item
     * @return ExportColumn
     */
    private function generateExportColumn(ExportColumn $column, $item)
    {
        $label = $column->getLabel();
        if (is_callable($label)) {
            $label = call_user_func($label, $item);
            if (!isset($label)) {
                $label = '';
            }
        }

        $itemId =  call_user_func($this->_getIdFromItem, $item);

        return $column->createNew(
            self::compoundColumnId(
                array($this->_id => $itemId),
                $column->getId()
            ),
            $label
        );
    }

    /**
     *
     */
    public function __clone()
    {
        foreach ($this->_totalColumns as $column) {
            $column->setBlock($this);
        }
    }


}
