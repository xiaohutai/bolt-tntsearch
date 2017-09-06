<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Service;

use Bolt;
use Bolt\Extension\TwoKings\TNTSearch\Config\Config;
use Bolt\Storage\Query\Query;
use Psr\Log\LoggerInterface;
use TeamTNT\TNTSearch\TNTSearch;

/**
 * Helper class for TNTSearch extension.
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchService
{
    /** @var Config $config */
    private $config;

    /** @var \Bolt\Config $boltConfig */
    private $boltConfig;

    /** @var TNTSearch $tntsearch */
    private $tntsearch;

    /** @var TNTSearchSyncService $tntsearchSync */
    private $tntsearchSync;

    /** @var Query $query */
    private $query;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var array $fuzzyConfig */
    private $fuzzyConfig = [
        'fuzzy_prefix_length'  => 2,
        'fuzzy_max_expansions' => 50,
        'fuzzy_distance'       => 2,
        'fuzziness'            => false,
    ];

    /** @var array $simpleFields An array with field types that can be indexed directly */
    private $simpleFields = [
        'html',
        'markdown',
        'select',
        'slug',
        'text',
        'textarea',
    ];

    /**
     * Constructor.
     *
     * @param Config               $config
     * @param Bolt\Config          $boltConfig
     * @param TNTSearch            $tntsearch
     * @param TNTSearchSyncService $tntsearchSync
     * @param Query                $query
     * @param LoggerInterface      $logger
     */
    public function __construct(
        Config               $config,
        Bolt\Config          $boltConfig,
        TNTSearch            $tntsearch,
        TNTSearchSyncService $tntsearchSync,
        Query                $query,
        LoggerInterface      $logger
    ) {
        $this->config        = $config;
        $this->boltConfig    = $boltConfig;
        $this->tntsearch     = $tntsearch;
        $this->tntsearchSync = $tntsearchSync;
        $this->query         = $query;
        $this->logger        = $logger;
    }

    /**
     *
     */
    public function search($query, $contenttype, $fuzzyOptions = [], $limit = 100)
    {
        // -----------------------------------------------------------------------------------------
        // Set up TNTSearch
        // -----------------------------------------------------------------------------------------

        foreach ($this->fuzzyConfig as $key => $defaultValue) {
            $this->tntsearch->$key = isset($fuzzyOptions[$key]) ? $fuzzyOptions[$key] : $defaultValue;
        }

        if (!empty($contenttype)) {
            $this->tntsearch->selectIndex($contenttype . '.index');
        } else {
            $this->tntsearch->selectIndex('all.index');
        }

        $results = $this->tntsearch->search($query, $limit);

        $ids  = $results['ids'];
        $hits = $results['hits']; // todo: total, use for pagination and slice it ????
        $time = $results['execution_time'];

        // dump($ids); // searching in all contenttypes goes wrong ...

        if (empty($ids)) {
            return [];
        }

        // -----------------------------------------------------------------------------------------
        // Convert IDs to Bolt Records
        // -----------------------------------------------------------------------------------------

        /** @var \Bolt\Storage\Entity\Content[] $records */
        $records = $this->query->getContent($contenttype, [ 'id' => implode(' || ', array_values($results['ids'])) ]);
        $records = iterator_to_array($records);

        usort($records, function($a, $b) use ($ids) {
            return array_search($a->id, $ids) - array_search($b->id, $ids);
        });

        // -----------------------------------------------------------------------------------------
        // Debug information
        // -----------------------------------------------------------------------------------------

        // $records = array_map(function($record){
        //     return $record->id;
        // }, $records);
        // dump($results);
        // dump($records);

        return $records;
    }

    /**
     *
     */
    public function searchBoolean()
    {
        // wip, cant do boolean with fuzziness it seems.
    }

    /**
     * Find the nearest items of a specific type $contenttype at location
     * ($latitude, $longitude) within a distance of $distance in km limited to
     * a maximum of $limit records.
     *
     * @param string $contenttype
     * @param float  $latitude
     * @param float  $longitude
     * @param int    $distance
     * @param int    $limit
     */
    public function findNearest($contenttype, $latitude, $longitude, $distance, $limit)
    {
        // $this->tntsearch->

        $results = $this->tntsearch->findNearest([
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ], $distance, $limit);

        return $results;
    }
}
