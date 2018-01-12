<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 25.06.15
 * Time: 17:34
 */

namespace Report\Template\Poll\Question;


class DeleteQuestion extends Question
{
    /**
     * @var integer
     */
    private $_questionId;

    /**
     * @var integer
     */
    private $_answerTemplateId;

    /**
     * @param \Project $project
     * @return array
     */
    public static function findAll(\Project $project)
    {
        $conn = \Yii::app()->db;


        $query = 'SELECT DISTINCT ON (answers.question_id, answers.answer_template_id)'.
            ' answers.question_id, answers.answer_template_id' .
            ' FROM answers' .
            ' LEFT JOIN polls ON answers.poll_id = polls.id' .
            ' LEFT JOIN questions ON answers.question_id = questions.id' .
            ' LEFT JOIN answer_templates ON answers.answer_template_id = answer_templates.id' .
            ' WHERE polls.project_id = :project_id AND ' .
            ' (questions.id IS NULL OR answer_templates.id IS NULL)';
        $command = $conn->createCommand($query);
        $rows = $command->queryAll(true, array(':project_id' => $project->id));

        $items = array();
        foreach($rows as $row) {
            $items[] = new self($row['question_id'], $row['answer_template_id']);
        }

        return $items;
    }

    /**
     * @param \Question $questionId
     * @param $answerTemplateId
     */
    public function __construct($questionId, $answerTemplateId)
    {
        $this->_questionId = $questionId;
        $this->_answerTemplateId = $answerTemplateId;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return array(
            $this->createColumn($this->getKey(), $this->getLabel(), 'getValue')
        );
    }


    /**
     * @return string
     */
    public function getKey()
    {
        return "{$this->getQuestionId()}_{$this->getAnswerTemplateId()}";
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return "Вопрос {$this->getQuestionId()} Ответ {$this->getAnswerTemplateId()}";
    }

    /**
     * @param \Poll $row
     * @return string
     */
    public function getValue(\Poll $row)
    {
        $answer = $row->getAnswer($this->getQuestionId(), $this->_answerTemplateId);
        if (!isset($answer)) {
            return '';
        }
        return $answer->getRawValue();
    }


} 
