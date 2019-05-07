<?php

namespace Idimption;

use Chameleon\PhpDiff\LevenshteinDiffCalculator;
use Chameleon\PhpDiff\OperationCostCalculator;
use Chameleon\PhpDiff\StringDiffOperation;

class Differ
{
    /**
     * @return self
     */
    public static function getInstance()
    {
        static $instance = null;
        return $instance = $instance ?: new self();
    }

    /** @var LevenshteinDiffCalculator */
    private $_differ;

    /** @var string[] */
    private $_cache = [];

    private function __construct()
    {
        $lineDiffer = new LevenshteinDiffCalculator(LevenshteinDiffCalculator::SPLIT_WORDS_REGEX);
        $this->_differ = new LevenshteinDiffCalculator(
            LevenshteinDiffCalculator::SPLIT_LINES_REGEX,
            (new OperationCostCalculator())->setReplaceDistanceCalculator($lineDiffer),
            $lineDiffer
        );
    }

    /**
     * @param string $from
     * @param string $to
     * @return StringDiffOperation[]
     */
    public function getDiff($from, $to)
    {
        $key = sha1($from) . sha1($to);
        return $this->_cache[$key] = $this->_cache[$key] ?? $this->_differ->calcDiff($from, $to)->diff;
    }
}
