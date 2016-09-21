<?php namespace ObisConcept\NeosBlog\TypoScript\Eel\FlowQueryOperations;

/*
 * Class by Lelesys.News
 * https://github.com/lelesys/Lelesys.News/blob/master/Classes/Lelesys/News/TypoScript/Eel/FlowQueryOperations/FilterOperation.php
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;


class FilterOperation extends \TYPO3\TYPO3CR\Eel\FlowQueryOperations\FilterOperation {

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    static protected $priority = 200;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context) {

        return (isset($context[0]) && ($context[0] instanceof NodeInterface) && $context[0]->getNodeType()->isOfType('ObisConcept.NeosBlog:Post'));

    }

    /**
     * {@inheritdoc}
     *
     * @param object $element
     * @param string $propertyPath
     * @return mixed
     */
    protected function getPropertyPath($element, $propertyPath) {

        switch($propertyPath) {
            case 'categories':
                // this returns array of node identifiers of references and not the nodes itself
                return $element->getProperty($propertyPath, TRUE);
                break;
        }

        return parent::getPropertyPath($element, $propertyPath);

    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param string $operator
     * @param mixed $operand
     * @return boolean
     */
    protected function evaluateOperator($value, $operator, $operand) {

        if ($operator === '*=') {
            if (is_array($value)) {
                if (is_string($operand)) {
                    $operandValues = explode(',', $operand);
                    return count(array_intersect($value, $operandValues)) > 0;
                }
                return FALSE;
            }
        }

        return parent::evaluateOperator($value, $operator, $operand);

    }

}
