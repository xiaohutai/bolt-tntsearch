<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * TNTSearchLookupTable class
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchLookupTable extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('contenttype', 'string', ['notnull' => false]);
        $this->table->addColumn('contentid', 'integer', ['notnull' => false]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['contenttype', 'contentid']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
