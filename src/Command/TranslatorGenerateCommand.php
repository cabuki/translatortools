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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translation\CollectionFactory;
use Translation\CollectionFactoryObserverInterface;

class TranslatorGenerateCommand extends Command implements CollectionFactoryObserverInterface
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName( "translator:generate" )
             ->setDescription( "Find document according to specific path." )
             ->addArgument( 'path', InputArgument::REQUIRED, 'Where to search' )
             ->addArgument( 'name', InputArgument::REQUIRED, 'The name to match (please read https://symfony.com/doc/current/components/finder.html)' )
             ->addArgument( 'output', InputArgument::REQUIRED, 'Where to store new generated files' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {

        $this->output = $output;
        /*
        $table = new Table($output);
        $table
            ->setHeaders(array('ISBN', 'Title', 'Author'))
            ->setRows(array(
                array('99921-58-10-7', 'Divine Comedy', 'Dante Alighieri'),
                array('9971-5-0210-0', 'A Tale of Two Cities', 'Charles Dickens'),
                array('960-425-059-0', 'The Lord of the Rings', 'J. R. R. Tolkien'),
                array('80-902734-1-6', 'And Then There Were None', 'Agatha Christie'),
            ))
        ;
        $table->render();
        return;
        */

        $path = $input->getArgument( 'path' );
        $name = $input->getArgument( 'name' );
        $outputPath = $input->getArgument( 'output' );


        $output->writeln( sprintf( "<comment>Searching for : %s</comment>\n", $path . $name ) );
        $output->writeln( "" );

        $fileFinder = new FileFinder();
        $finder = $fileFinder->findFilesIn( $path, $name );
        $collection = CollectionFactory::createFromFinder( $finder, $this );

        if ($collection->getDomains() == NULL) //$output->writeln( var_dump($collection) );
        {
            die( "No domain found! Check the entered path and name are correct.\n"  );
        }

        foreach ( $collection->getDomains() as $domain )
        {
            $output->writeln( sprintf( "Domain '%s' has %s keys and support : %s", $domain->getName(), count( $domain->getKeys() ), implode( $domain->getLocales(), ", " ) ) );

            $locales = $domain->getLocales();
            $keys = $domain->getKeys();
            foreach ( $locales as $locale )
            {
                $file = fopen($outputPath . $domain->getName() . '.' . $locale . '.yml', 'x+');

                foreach ( $keys as $key )
                {


                    $keyValue = $key->getTranslation( $locale )->getValue();

                    if (!is_array($keyValue)) //Sometimes, the value is an array.
                    {
                        fputs( $file, $key->getName() . ": " );
                        if (isset($keyValue))
                        {
                            fputs($file, '"' . $keyValue . '"' . "\n");
                        }
                        else
                        {
                            fputs($file, "#fixme\n");
                        }
                    } else {
                        var_dump($keyValue) ;

                        //TODO
                        //$this->createKeyRecursively( $file,  $keyValue );
                    }
                }



                fclose($file);
            }

        }
    }

    /* //TODO
    protected function createKeyRecursively( File $file, Array $keys )
    {
        foreach( $keys as $key)
        {
            if (!is_array($key))
            {

                if (isset($key))
                {
                    return $key . ": " . $key->getTranslation( $locale )->getValue() . '"' . "\n";
                }
                else
                {
                    return $key . ": #fixme\n";
                }
            }
            else
            {
                fputs($file, $this->createKeyRecursively( $file, $key ) . "\n");
            }
        }
    }
    */

    public function foundKeys( $keys, $filename )
    {
        $this->output->writeln( sprintf( "Found %s key in total in %s.", count( $keys ), $filename ) );
    }

    public function foundNewKeys( $keys, $filename )
    {
        if ( count( $keys ) == 0 )
        {
            $this->output->writeln( sprintf( "No new keys found in %s, great!", $filename ) );

            return;
        }
        $this->output->writeln( sprintf( "Found %s new keys.", count( $keys ) ) );
    }

    public function dealingWith( $source )
    {
        $this->output->writeln( sprintf( "Gathering translation keys in <info>%s</info>", $source ) );
    }

}