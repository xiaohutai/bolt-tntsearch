<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Service;

use Bolt\Extension\TwoKings\TNTSearch\Config\Config;
use Bolt\Storage\Database\Connection;
use Psr\Log\LoggerInterface;
use Bolt;
use TeamTNT\TNTSearch\TNTSearch;

/**
 * Helper class for synchronising the internal lookup tables with TNTSearch
 * indices. There are two main functions in this class:
 * - *Sync*: Makes sure that the internal lookup table is synced up with the
 *           Bolt records in the database.
 * - *Index*: Makes a TNTSearch index file for searching in.
 *
 * These functions each can be called in three modes:
 * - all contenttypes: Specify no parameters.
 * - single contenttype: Specify the first parameter with the contenttype slug.
 * - single record: Specify both parameters, with the contenttype slug and record id.
 *
 * - `sync()`
 * - `sync(contenttype)`
 * - `sync(contenttype, id)`
 * - `index()`
 * - `index(contenttype)`
 * - `index(contenttype, id)`
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchSyncService
{
    /** @var Config $config */
    private $config;

    /** @var \Bolt\Config $boltConfig */
    private $boltConfig;

    /** @var TNTSearch $tntsearch */
    private $tntsearch;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Connection $db */
    private $db;

    /** @var string $lookupTableName */
    private $lookupTableName;

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
     * @param Config          $config
     * @param Bolt\Config     $boltConfig
     * @param TNTSearch       $tntsearch
     * @param Connection      $db
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config       $config,
        Bolt\Config  $boltConfig,
        TNTSearch    $tntsearch,
        Connection   $db,
        LoggerInterface       $logger
    ) {
        $this->config     = $config;
        $this->boltConfig = $boltConfig;
        $this->tntsearch  = $tntsearch;
        $this->db         = $db;
        $this->logger     = $logger;

        $this->databasePrefix  = $boltConfig->get('general/database/prefix', 'bolt_');
        $this->lookupTableName = $this->databasePrefix . 'tntsearch_lookup';
    }

    /**
     * Synces all contenttypes, a single contenttype or a single record with
     * the internal lookup table.
     *
     * @param $contenttype (optional) the contenttype to sync
     * @param $id          (optional) the id of the record to sync
     */
    public function sync($contenttype = null, $id = null)
    {
        if (empty($contenttype)) {
            $this->syncAll();
        }
        elseif (empty($id)) {
            $this->syncContenttype($contenttype);
        }
        else {
            $this->syncItem($contenttype, $id);
        }
    }

    /**
     * Syncs all contenttypes.
     */
    private function syncAll()
    {
        $contenttypes = $this->boltConfig->get('contenttypes');
        foreach ($contenttypes as $contenttype => $v) {
            $this->syncContenttype($contenttype);
        }
    }

    /**
     * Syncs a single contenttype.
     *
     * @param string $contenttype The contenttype to sync
     */
    private function syncContenttype($contenttype)
    {
        $sql = sprintf(
            "SELECT `id` FROM `%s%s` WHERE `status` = :status AND `id` NOT IN ( SELECT `contentid` FROM `%s` u WHERE `u`.`contenttype` = :contenttype )",
            $this->databasePrefix,
            $this->boltConfig->get('contenttypes/' . $contenttype . '/tablename'),
            $this->lookupTableName
        );

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('status', 'published');
        $stmt->bindValue('contenttype', $contenttype);
        $stmt->execute();
        // \PDO::FETCH_COLUMN removes the unnecessary array wrapper
        $missing = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($missing)) {
            return;
        }

        $pre = "'$contenttype', ";
        $bulkInsert = sprintf(
            "INSERT INTO `%s` (`contenttype`, `contentid`) VALUES (%s%s);",
            $this->lookupTableName,
            $pre,
            implode("), (" . $pre, $missing)
        );

        $this->db->exec($bulkInsert);

        /*
        // Bulk insert is not available via DBAL
        foreach ($missing as $id) {
            $this->db->insert(
                $this->lookupTableName,
                [
                    'contenttype' => $contenttype,
                    'contentid'   => $missing['id']
                ]
            );
        }
        //*/
    }

    /**
     * Syncs a single record.
     *
     * @param string $contenttype ContentType of the record to sync
     * @param int    $id          ID of the record to sync
     */
    private function syncItem($contenttype, $id)
    {
        // todo: check if exists and published?
        // Do we want to check for published items, or don't care?

        $sql = sprintf(
            "INSERT IGNORE INTO `%s` (`contenttype`, `contentid`) VALUES ('%s', %s)",
            $this->lookupTableName,
            $contenttype,
            $id
        );

        $this->db->exec($sql);
    }

    /**
     * Indexes all contenttypes, a single contenttype or a single record.
     */
    public function index($contenttype = null, $id = null)
    {
        if (empty($contenttype)) {
            $this->indexAll();
            $this->indexGlobal();
        }
        elseif (empty($id)) {
            $this->indexContenttype($contenttype);
            $this->indexGlobal();
        }
        else {
            $this->indexItem($contenttype, $id);
        }
    }

    /**
     * Indexes `all.index` for global search
     */
    private function indexGlobal()
    {
        $indexer = $this->tntsearch->createIndex('all.index');

        $sql = sprintf("SELECT * FROM %s", $this->lookupTableName);

        $contenttypes = $this->boltConfig->get('contenttypes');
        // Far from ideal:
        // This will introduce a lot of columns with `NULL` values. Doing a
        // `UNION ALL` could work, but you need to filter out all the columns
        // and add `NULL AS` projections for every contenttype.
        // Not entirely sure if it will take a performance hit. But this is
        // where it would happen.
        foreach ($contenttypes as $contenttype => $v) {
            /*
            // For every contenttype:
            LEFT JOIN `bolt_pages`
                ON `bolt_tntsearch_lookup`.`contentid` = `bolt_pages`.`id`
                AND `bolt_tntsearch_lookup`.`contenttype` = 'pages'
                AND `bolt_pages`.`status` = 'published'
            //*/
            $ctTableName = $this->databasePrefix . $this->boltConfig->get('contenttypes/' . $contenttype . '/tablename');
            $sql .= sprintf(" LEFT JOIN `%s`", $ctTableName);
            $sql .= sprintf(" ON `%s`.`contentid` = `%s`.`id`", $this->lookupTableName, $ctTableName);
            $sql .= sprintf(" AND `%s`.`contenttype` = '%s'", $this->lookupTableName, $contenttype);
            $sql .= sprintf(" AND `%s`.`status` = 'published'", $ctTableName);
        }

        $indexer->query($sql);
        $indexer->run();
    }

    /**
     * Indexes all contenttypes.
     */
    private function indexAll()
    {
        $contenttypes = $this->boltConfig->get('contenttypes');
        foreach ($contenttypes as $contenttype => $v) {
            $this->indexContenttype($contenttype);
        }
    }

    /**
     * Indexes a single ContentType.
     *
     * @param string $contenttype ContentType to index
     */
    private function indexContenttype($contenttype)
    {
        $fields = $this->getSearchableFields($contenttype);
        if (!empty($fields)) {
            $indexer = $this->tntsearch->createIndex($contenttype . '.index');
            $sql = sprintf(
                "SELECT `id`, `%s` FROM `%s%s` WHERE `status` = 'published'",
                implode('`, `', $fields),
                $this->databasePrefix,
                $this->boltConfig->get('contenttypes/' . $contenttype . '/tablename')
            );
            $indexer->query($sql);
            $indexer->run();
        }
    }

    /**
     * Indexes a single record.
     *
     * @param $contenttype the contenttype of the record to index
     * @param $id          the id of the record to index
     */
    private function indexItem($contenttype, $id)
    {
        $this->tntsearch->selectIndex($contenttype . '.index');
        $index = $this->tntsearch->getIndex();

        // todo

        // $index->insert();
        // $index->update();
        // $index->delete();

        // $index->insert(['id' => '11', 'title' => 'new title', 'article' => 'new article']);
        // $index->update(11, ['id' => '11', 'title' => 'updated title', 'article' => 'updated article']);
        // $index->delete(12);
    }

    /**
     * Returns all searchable fields when the given contenttype is eligible to
     * be synced and indexed, otherwise an empty array.
     *
     * @param string $contenttype ContentType to check for
     *
     * @return array       An array with fields for the contenttype
     */
    private function getSearchableFields($contenttype)
    {
        $config    = $this->boltConfig->get('contenttypes/' . $contenttype);
        $taxonomy  = isset($config['taxonomy'])  ? $config['taxonomy']  : [];
        $relations = isset($config['relations']) ? $config['relations'] : [];
        $fields    = isset($config['fields'])    ? $config['fields']    : [];
        $result    = [];

        // By default: viewless = false
        if (isset($config['viewless']) && $config['viewless']) {
            return $result;
        }
        // Be default: searchable = true
        if (isset($config['searchable']) && !$config['searchable']) {
            return $result;
        }

        // Add all _searchable_ contenttype fields
        foreach ($fields as $key => $field) {
            // Simple text fields are ready to go
            if (in_array($field['type'], $this->simpleFields)) {
                $result[] = $key;
            }
            // other fields
        }

        // do something with repeaters
        // do something with taxonomies
        // do something with relatinos
        // do something with custom fields

        return $result;
    }
}
