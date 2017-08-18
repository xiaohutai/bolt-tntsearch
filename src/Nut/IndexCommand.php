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
class IndexCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('tntsearch:index')
            ->setDescription('Create an index of the database')
            ->addArgument(
                'contenttype',
                InputArgument::OPTIONAL,
                'Create an index for a given contenttype'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $contenttype = $input->getArgument('contenttype');

        if (empty($contenttype)) {
            $this->app['tntsearch.sync']->sync();
            $this->app['tntsearch.sync']->index(); // sync should be implicit per index?
        } else {
            $this->app['tntsearch.sync']->sync($contenttype);
            $this->app['tntsearch.sync']->index($contenttype); // sync should be implicit per index?
        }

        $time = microtime(true) - $start;
        $text = sprintf('Took %s seconds.', number_format($time, 2));
        $output->writeln($text);
    }
}
