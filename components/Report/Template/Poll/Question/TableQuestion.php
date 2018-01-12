<?php

/**
 * Description of AgetQuestion
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
namespace Report\Template\Poll\Question;

use CDbConnection;
use Export\Schema\Column as ExportColumn;
use Question as TemplateQuestion;
use Report\Data\Column\Column;

class TableQuestion extends Question
{
    /**
     * @var array
     */
    private $_questions = [];

    /**
     * @param \Question $question
     */
    public function __construct(TemplateQuestion $question, $textInValue = false, $showControl = false)
    {
        parent::__construct($question, $textInValue, $showControl);

        $this->_questions = $this->loadQuestions();
    }


    /**
     * getId
     * @return type
     */
    public function getId()
    {
        return $this->_question->id;
    }

    /**
     * loadQuestions
     *
     * @return array of Question
     */
    private function loadQuestions()
    {
        $questions = array();
        foreach ($this->_question->subQuestions as $question) {
            $question->answerTemplates = $this->_question->answerTemplates;
            $questions[] = QuestionFabric::create($question, $this->_textInValue, $this->_showControl);
        }
        

        return $questions;
    }

    /**
     * getColumns
     * 
     * @return type
     */
    public function getColumns()
    {
        $columns = [];

        foreach ($this->_questions as $question) {
            $columns = array_merge($columns, $question->getColumns());
        }


        return $columns;
    }

}
