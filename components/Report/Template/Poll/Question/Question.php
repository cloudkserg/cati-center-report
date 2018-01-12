<?php
namespace Report\Template\Poll\Question;
/**
 * Description of Question
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
use \Export\Schema\Column as ExportColumn;
use Question as TemplateQuestion;
use Report\Row\Column;
use Report\Row\Context;


class Question implements QuestionInterface
{
    /**
     *
     * @var TemplateQuestion
     */
    protected $_question;

    /**
     * @var bool
     */
    protected $_textInValue;

    /**
     * @var bool
     */
    protected $_showControl;

    /**
     * @param \Question $question
     * @param bool $textInValue
     * @param bool $showControl
     */
    public function __construct(TemplateQuestion $question, $textInValue = false, $showControl = false)
    {
        $this->_question = $question;
        $this->_textInValue = $textInValue;
        $this->_showControl = $showControl;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        $columns = [];

        if ($this->_question->numeric_cod) {
            $column = $this->createColumn($this->getKey(), $this->getLabel(), 'getValue', ExportColumn::NUMERIC);
        } else {
            $column = $this->createColumn($this->getKey(), $this->getLabel(), 'getValue');
        }

        $column->getExportColumn()->setList(
            \CHtml::listData(
                $this->_question->answerTemplates,
                'cod',
                'title'
            )
        );
        
        $columns[] = $column;

        if ($this->hasTextAnswer()) {
            $columns[] = $this->createColumn($this->getTextKey(), $this->getTextLabel(), 'getTextValue');

        }
        
        return $columns;
    }

    /**
     * @return string
     */
    protected function getKey()
    {
        return $this->_question->cod;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->_question->getCodTitle();
    }

    /**
     * @return string
     */
    protected function getTextKey()
    {
        return $this->_question->cod . '_text';
    }

    /**
     * @return string
     */
    protected function getTextLabel()
    {
        return 'Текст ' . $this->_question->getCodTitle();
    }

    /**
     * @param \Poll $row
     * @return string
     */
    public function getValue(\Poll $row)
    {
        return $this->getValueFromAnswer($row->getAnswerForQuestion($this->_question->id));
    }

    /**
     * @param \Poll $row
     * @return string
     */
    public function getTextValue(\Poll $row)
    {
        $answer = $row->getAnswerForQuestion($this->_question->id);
        if (!isset($answer)) {
            return '';
        }
        if (!$answer->getRelation('answerTemplate')->isText()) {
            return '';
        }
        return $answer->raw_value;
    }



    /**
     * hasTextAnswer
     * 
     * @return boolean
     */
    protected function hasTextAnswer()
    {
        foreach ($this->_question->answerTemplates as $answerTemplate) {
            if (in_array($answerTemplate->type, \AnswerType::model()->getTextTypes())) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * createColumn
     *
     * @param mixed $key
     * @param mixed $label
     * @param mixed $value
     * @param string $type
     * @return Column
     */
    protected function createColumn($key, $label, $value, $type = 'text')
    {
        $valueFunc = $value;
        if (is_string($value)) {
            $valueFunc = array($this, $value);
        }
        return new Column(ExportColumn::create($type, $key, $label), new Context(), $valueFunc);
    }


    /**
     * @param \Answer $answer
     * @return string|\type
     */
    protected function getValueFromAnswer(\Answer $answer = null)
    {
        if (!isset($answer)) {
            return '';
        }

        if ($this->_showControl) {
            return $answer->getDuration();
        }

        if ($this->_textInValue) {
            return $answer->getRelation('answerTemplate')->cod . '.' .
                $answer->getRelation('answerTemplate')->title;
        }



        return $answer->getRelation('answerTemplate')->cod;
    }



}
