<?php
/**
 * Created by PhpStorm.
 * User: Margaux Seoane
 * Date: 16-08-17
 * Time: 10:49
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

class TranslatorDiffCommand extends Command implements CollectionFactoryObserverInterface
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName( "translator:diff" )
            ->setDescription( "Find document according to specific path." )
            ->addArgument( 'path', InputArgument::REQUIRED, 'Where to search' )
            ->addArgument( 'path2', InputArgument::REQUIRED, 'Where to search' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name to match (please read https://symfony.com/doc/current/components/finder.html)' )
            ->addArgument( 'output', InputArgument::REQUIRED, 'Where to store new generated files' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->output = $output;

        $path = $input->getArgument( 'path' );
        $path2 = $input->getArgument( 'path2' );
        $name = $input->getArgument( 'name' );
        $outputPath = $input->getArgument( 'output' );


        $output->writeln( sprintf( "<comment>Searching for : %s and %s</comment>\n", $path . $name, $path2 . $name ) );
        $output->writeln( "" );

        $fileFinder = new FileFinder();
        $finder = $fileFinder->findFilesIn( $path, $name );
        $collection = CollectionFactory::createFromFinder( $finder, $this );
        $domains = $collection->getDomains();

        $finder = $fileFinder->findFilesIn( $path2, $name );
        $collection2 = CollectionFactory::createFromFinder( $finder, $this );
        $domains2 = $collection2->getDomains();

        $collections = [$collection, $collection2];

        if ($domains == NULL && $domains2 == NULL)
        {
            die( "No domain found! Check the entered paths and names are correct.\n"  );
        }

        $this->findDiffFromCollections( $collections, $outputPath, $output );
    }




    private function findDiffFromCollections( $collections, $outputPath, OutputInterface $output )
    {
        $associativeArray = [];
        $associativeArray = $this->createArrayFromCollections( $collections, $output, $associativeArray );
        foreach ($associativeArray as $domain => $value)
        {
            foreach ($value as $locale => $val)
            {
                $path = sprintf( "%s%s%s.%s.csv", $outputPath, DIRECTORY_SEPARATOR, $domain, $locale );

                $this->createFile( $val, count($collections), $path, $output );
            }
        }
    }


    public function createFile( $array, $nbCollections, $outputPath, OutputInterface $output )
    {
        if ( file_exists( $outputPath ) )
        {
            unlink( $outputPath );
        }
        $file = fopen( $outputPath, 'x+' );
        ksort( $array, SORT_STRING | SORT_FLAG_CASE );
        foreach ( $array as $key => $value )
        {
            // Can be better ?
            {
                //*
                $bool = 0;
                $tmp = isset($value[0]) ? $value[0] : '';
                for ($i = 1 ; $i < $nbCollections && !$bool; $i++)
                {
                    $bool = strcmp($tmp, isset($value[$i]) ? $value[$i] : '' );
                }
                //*/

                if ($bool) // ( strcmp(isset($value[0]) ? $value[0] : '', isset($value[1]) ? $value[1] : '' ) )
                {
                    //$line = sprintf("%s;%s;%s" . PHP_EOL, $key,isset($value[0]) ? $value[0] : '', isset($value[1]) ? $value[1] : '' );

                    //*
                    $line = sprintf("%s", $key);
                    for ($i = 0; $i < $nbCollections; $i++)
                    {
                        $line .= sprintf(";%s", isset($value[$i]) ? $value[$i] : '');
                    }
                    $line .= sprintf(PHP_EOL);
                    //*/
                    if ( $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE )
                    {
                        $output->write($line);
                    }
                    fwrite($file, $line);
                }
            }
        }
        fclose( $file );
    }



    /**
     * @param DomainCollection[]    $collections
     * @param OutputInterface       $output
     * @param                       $associativeArray
     *
     * @return array
     */

    private function createArrayFromCollections( $collections, OutputInterface $output, $associativeArray )
    {
        $i = 0;
        foreach ( $collections as $collection )
        {
            foreach ($collection->getDomains() as $domain)
            {
                $domainName = $domain->getName();

                $locales = $domain->getLocales();
                $keys = $domain->getKeys();
                if ( !isset( $associativeArray[$domainName] ) )
                {
                    $associativeArray[$domainName] = [];
                }
                foreach ($locales as $locale)
                {
                    if ( !isset( $associativeArray[$domainName][$locale] ) )
                    {
                        $associativeArray[$domainName][$locale] = [];
                    }

                    foreach ($keys as $key)
                    {
                        $keyValue = $key->getTranslation($locale)->getValue();
                        if ( !isset( $associativeArray[$domainName][$locale][$key->getName()] ) )
                        {
                            $associativeArray[$domainName][$locale][$key->getName()] = [];
                        }
                        $associativeArray[$domainName][$locale][$key->getName()][$i] = empty( $keyValue ) ? '#fixme' : $keyValue;;
                    }
                }
            }
            $i++;
        }

        return $associativeArray;
    }




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