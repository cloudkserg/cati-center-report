<?php
namespace Report\Template\Poll\Question;
/**
 * Description of AgetQuestion
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
use Export\Schema\Column as ExportColumn;

class AgeQuestion extends Question
{


    /**
     * getColumns
     * 
     * @return array
     */
    public function getColumns()
    {
        $columns = [];

        $columns[] = $this->createColumn(
            $this->getKey(),
            $this->getLabel(),
            'getAgeValue',
            ExportColumn::NUMERIC
        );


        $type = ($this->_question->numeric_cod ? ExportColumn::NUMERIC : ExportColumn::TEXT);
        $column = $this->createColumn(
            $this->getAgeKey(),
            $this->getAgeLabel(),
            'getValue',
            $type
        );
        $column->getExportColumn()->setList(\CHtml::listData(
                $this->_question->answerTemplates,
                'cod',
                'title'
        ));

        $columns[] = $column;

        return $columns;
    }

    /**
     * @return string
     */
    private function getAgeKey()
    {
        return $this->_question->cod . '_age';
    }

    /**
     * @return string
     */
    private function getAgeLabel()
    {
        return $this->_question->cod . ' Ранг возраста';
    }

    /**
     * @param \Poll $row
     * @return string
     */
    public function getAgeValue(\Poll $row)
    {
        
        $answer = $row->getAnswerForQuestionWithParent($this->_question);
        if (!isset($answer)) {
            return '';
        }
        return $answer->raw_value;
    }

}
