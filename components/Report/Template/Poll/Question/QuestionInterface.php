<?php
namespace Report\Template\Poll\Question;
/**
 * Description of QuestionInterface
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
interface QuestionInterface
{


    /**
     * @return array of eport\Row\ColumnInterface
     */
    public function getColumns();


}
