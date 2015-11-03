<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Node;

/**
 * Makeable important interface
 *
 * @package ILess\Node
 */
interface MakeableImportantInterface
{
    /**
     * Makes the node important
     *
     * @return Node
     */
    public function makeImportant();
}
