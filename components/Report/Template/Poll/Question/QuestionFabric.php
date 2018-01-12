<?php

/**
 * Description of QuestionFabric
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
namespace Report\Template\Poll\Question;

class QuestionFabric
{
    /**
     * create
     * 
     * @param \Question $question
     * @return Question
     */
    public static function create(\Question $question, $textInValue = false, $showControl = false)
    {
        if ($question->type == \QuestionType::AGE) {
            return new AgeQuestion($question, $textInValue, $showControl);
        } elseif ($question->type == \QuestionType::TABLE or $question->type == \QuestionType::TABLE_HORIZ) {
            return new TableQuestion($question, $textInValue, $showControl);
        } elseif ($question->isAllowMany()) {
            return new MultiQuestion($question, $textInValue, $showControl);
        } else {
            return new Question($question, $textInValue, $showControl);
        }
    }
}
