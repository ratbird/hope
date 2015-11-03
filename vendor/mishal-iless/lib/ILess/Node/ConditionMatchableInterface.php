<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;

/**
 * Condition matchable interface
 *
 * @package ILess\Node
 */
interface ConditionMatchableInterface
{
    /**
     * Match a condition
     *
     * @param array $arguments
     * @param Context $context
     * @return boolean
     */
    public function matchCondition(array $arguments, Context $context);
}
