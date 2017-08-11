<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Service;

use Bolt\Extension\TwoKings\TNTSearch\Config\Config;
// use Bolt\Filesystem\Manager;
use Bolt\Storage\Query\Query;
use Monolog\Logger;
use Silex\Application;
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

    /** @var Query $query */
    private $query;

    /** @var Logger $logger */
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
     * Constructor
     *
     * @param Config       $config
     * @param \Bolt\Config $boltConfig
     * @param TNTSearch    $tntsearch,
     * @param Logger       $logger
     */
    public function __construct(
        Config       $config,
        \Bolt\Config $boltConfig,
        TNTSearch    $tntsearch,
        Query        $query,
        Logger       $logger
    )
    {
        $this->config     = $config;
        $this->boltConfig = $boltConfig;
        $this->tntsearch  = $tntsearch;
        $this->query      = $query;
        $this->logger     = $logger;
    }

    /**
     *
     */
    public function index($contenttype = null)
    {
        if (empty($contenttype)) {
            $contenttypes = $this->boltConfig->get('contenttypes');
            foreach ($contenttypes as $contenttype => $v) {
                $this->indexContenttype($contenttype);
            }
        }

        $this->indexContenttype($contenttype);
    }

    /**
     *
     */
    private function indexContenttype($contenttype)
    {
        // Define rules whether to index or not.
        // -- check status = 'published'
        // -- check viewless = true
        // -- check searchable = true
        // -- check taxonomy
        // -- check relations
        // -- check fields
        //    -- check text-ish fields
        //    -- check repeaters
        //    -- check custom fields
        //dump($this->boltConfig->get('contenttypes')); exit;

        $config    = $this->boltConfig->get('contenttypes/' . $contenttype);
        $taxonomy  = isset($config['taxonomy']) ? $config['taxonomy'] : [];
        $relations = isset($config['relations']) ? $config['relations'] : [];
        $fields    = isset($config['fields']) ? $config['fields'] : [];

        // By default: viewless = false
        if (isset($config['viewless']) && $config['viewless']) {
            return;
        }
        // Be default: searchable = true
        if (isset($config['searchable']) && !$config['searchable']) {
            return;
        }

        $fieldNames = [];
        foreach ($fields as $key => $field) {
            // Simple fields are ready to go
            if (in_array($field['type'], $this->simpleFields)) {
                $fieldNames[] = $key;
            }
            elseif ($field['type'] == 'somethingelse') {
                // do something else
            }
        }

        if (empty($fieldNames)) {
            return;
        }
        // 'checkbox'
        // 'date'
        // 'datetime'
        // 'file'
        // 'filelist'
        // 'float'
        // 'geolocation'
        // 'image'
        // 'imagelist'
        // 'integer'
        // 'repeater'
        // 'seo'
        // 'templateselect'
        // 'video'

        $indexer = $this->tntsearch->createIndex($contenttype . '.index');
        $sql = sprintf(
            "SELECT `id`, `%s` FROM `%s` WHERE `status` = 'published'",
            implode('`, `', $fieldNames),
            $this->boltConfig->get('general/database/prefix', 'bolt_') . $config['tablename'],
            'published'
        );
        $indexer->query($sql);

        // todo: bolt-translate integration ???
        // $indexer->setLanguage('german');

        $indexer->run();
    }

    public function insert($contenttype, $id, $data)
    {
        //
    }

    /**
     *
     */
    public function update($contenttype, $id)
    {
        // Check if the index exists:
            // Attempt to index the contenttype
            // $this->index($contenttype);
        // $this->tntsearch->
    }

    /**
     *
     */
    public function delete($contenttype, $id)
    {
        //
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

        // todo: let's see how we can fix this for searching through all contenttypes
        $contenttype = 'pages';

        if (!empty($contenttype)) {
            $this->tntsearch->selectIndex($contenttype . '.index');
        } else {
            // all contenttypes ?? is that even possible???
            $this->tntsearch->selectIndex('pages.index');
        }

        $results = $this->tntsearch->search($query, $limit);

        $ids  = $results['ids'];
        $hits = $results['hits']; // todo: total, use for pagination and slice it ????
        // $results['execution_time'];

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
