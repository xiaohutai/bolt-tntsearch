<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Model;

/**
 * A simple container with some useful metadata.
 *
 * Page numbers are always 1-indexed, because web pages never (source?) show a
 * page 0.
 */
class SearchResults
{
    /** @var string $query */
    private $query;

    /** @var int $limit */
    private $limit;

    /** @var int $page */
    private $page;

    /** @var int $total */
    private $total;

    /** @var \Bolt\Storage\Entity\Content[] $records */
    private $records;

    /**
     * Constructs a new instance
     */
    public function __construct($data)
    {
        $allowedKeys = [
            'query',
            'limit',
            'page',
            'total',
            'records',
        ];

        foreach ($data as $k => $v) {
            if (in_array($k, $allowedKeys)) {}
                $this->$k = $v;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Pagination helpers
    // -------------------------------------------------------------------------

    public function getFirstPage()
    {
        return 1;
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    public function getLastPage()
    {
        return ceil($total / $limit);
    }

    /**
     * Alias for `getLimit()`
     */
    public function getPageSize()
    {
        return $this->getLimit();
    }

    // -------------------------------------------------------------------------
    // Miscelleanous
    // -------------------------------------------------------------------------

    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Gets the current offset expressed in number of records.
     */
    public function getOffset()
    {
        return ($this->pageNumber - 1) * $this->limit;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getRecords()
    {
        return $this->records;
    }
}
