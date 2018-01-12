<?php
namespace Report\Template\Poll\Question;
/**
 * Description of MultiQuestion
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
use \Export\Schema\TextColumn as TextColumn;
use \Export\Schema\NumericColumn as NumericColumn;
use Question as TemplateQuestion;

class MultiQuestion extends Question
{


    /**
     * getColumns
     * @return array
     */
    public function getColumns()
    {
        $columns = [];


        foreach ($this->_question->answerTemplates as $answerTemplate) {

            $column = $this->createColumn(
                $this->getMultiKey($answerTemplate),
                $this->getMultiLabel($answerTemplate),
                function (\Poll $row) use ($answerTemplate) {
                    return $this->getMultiValue($row, $answerTemplate);
                }
            );
            $column->getExportColumn()->setList(array(
                    $answerTemplate->cod => $answerTemplate->title
            ));
            $columns[] = $column;

            if ($answerTemplate->isText()) {

                $columns[] = $this->createColumn(
                  $this->getMultiTextKey($answerTemplate),
                  $this->getMultiTextLabel($answerTemplate),
                  function (\Poll $row) use ($answerTemplate) {
                      return $this->getMultiTextValue($row, $answerTemplate);
                  }
                );
            }
        }

        return $columns;
    }


    private function getMultiKey(\AnswerTemplate $item)
    {
        return $this->_question->cod . '_' . $item->cod;
    }

    private function getMultiTextKey(\AnswerTemplate $item)
    {
        return $this->_question->cod . '_' . $item->cod . '_text';
    }

    private function getMultiLabel(\AnswerTemplate $item)
    {
        return $this->_question->getCodTitle() . ' ' . $item->getCodTitle();
    }

    private function getMultiTextLabel(\AnswerTemplate $item)
    {
        return 'Текст ' . $this->_question->getCodTitle() . ' ' . $item->getCodTitle();
    }

    private function getMultiValue(\Poll $row, \AnswerTemplate $item)
    {
        return $this->getValueFromAnswer($row->getAnswer($this->_question->id, $item->id));
    }

    private function getMultiTextValue(\Poll $row, \AnswerTemplate $item)
    {
        return $row->getAnswer($this->_question->id, $item->id, new \Answer())->raw_value;
    }


}
