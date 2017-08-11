<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Nut;

use Bolt\Nut\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class SearchCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        // InputArgument = REQUIRED | OPTIONAL | IS_ARRAY
        // InputOption   = VALUE_NONE | VALUE_REQUIRED | VALUE_OPTIONAL | VALUE_IS_ARRAY
        $this
            ->setName('tntsearch:search')
            ->setDescription('Create an index of the database')
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'The search terms to look for'
            )
            ->addArgument(
                'contenttype',
                InputArgument::OPTIONAL,
                'The contenttype to search in'
            )
            ->addOption('fuzzy'          , 'f', InputOption::VALUE_NONE     , '<comment>fuzziness</comment>')
            ->addOption('prefix_length'  , 'p', InputOption::VALUE_REQUIRED , '<comment>fuzzy_prefix_length</comment>')
            ->addOption('max_expansions' , 'm', InputOption::VALUE_REQUIRED , '<comment>fuzzy_max_expansions</comment>')
            ->addOption('distance'       , 'd', InputOption::VALUE_REQUIRED , 'The levenshtein distance <comment>fuzzy_distance</comment>')
            // ->addOption('limit'       , 'l', InputOption::VALUE_REQUIRED , 'Limit the amount of search results returned')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $query       = $input->getArgument('query');
        $contenttype = $input->getArgument('contenttype');
        $fuzzy       = $input->getOption('fuzzy'); // `true` or `false`
        $prefix      = $input->getOption('prefix_length');
        $expansions  = $input->getOption('max_expansions');
        $distance    = $input->getOption('distance');
        // $limit       = $input->getOption('limit');

        // dump($query);
        // dump($contenttype);
        // dump($fuzzy);
        // dump($prefix);
        // dump($expansions);
        // dump($distance);
        // exit;

        $options = [
            'fuzziness'      => $fuzzy,
        ];
        if (!empty($prefix)) {
            $options['prefix_length'] = $prefix;
        }
        if (!empty($expansions)) {
            $options['max_expansions'] = $expansions;
        }
        if (!empty($distance)) {
            $options['distance'] = $distance;
        }
        if (empty($contenttype)) {
            $this->app['tntsearch.service']->search($query, null, $options);
        } else {
            $this->app['tntsearch.service']->search($query, $contenttype, $options);
        }

        $time = microtime(true) - $start;
        $text = sprintf('Took %s seconds.', number_format($time, 2));
        $output->writeln($text);
    }
}
