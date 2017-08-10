<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 10-08-17
 * Time: 10:13
 */

namespace Command;

use Logic\FileFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translation\CollectionFactory;
use Translation\CollectionFactoryObserverInterface;

class TranslatorStatusCommand extends Command implements CollectionFactoryObserverInterface
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName( "translator:status" )
             ->setDescription( "Find document according to specific path." )
             ->addArgument( 'path', InputArgument::REQUIRED, 'Where to search' )
             ->addArgument( 'name', InputArgument::REQUIRED, 'The name to match (please read https://symfony.com/doc/current/components/finder.html)' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->output = $output;
        $path = $input->getArgument( 'path' );
        $name = $input->getArgument( 'name' );

        $output->writeln( sprintf( "<comment>Searching for : %s</comment>\n", $path . $name ) );
        $output->writeln( "" );

        $fileFinder = new FileFinder();
        $finder = $fileFinder->findFilesIn( $path, $name );
        CollectionFactory::createFromFinder( $finder, $this );
    }

    public function foundKeys( $keys, $filename )
    {
        $this->output->writeln(sprintf("Found %s key in total in %s.", count($keys), $filename));
    }

    public function foundNewKeys( $keys, $filename )
    {
        if ( count( $keys ) == 0 )
        {
            $this->output->writeln(sprintf("No new keys found in %s, great!", $filename));
            return;
        }
        $this->output->writeln(sprintf("Found %s new keys.", count($keys)));
    }

    public function dealingWith( $source )
    {
        $this->output->writeln(sprintf("Gathering translation keys in <info>%s</info>", $source));
    }

}