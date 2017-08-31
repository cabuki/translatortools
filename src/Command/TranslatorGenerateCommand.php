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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml;
use Translation\CollectionFactory;
use Translation\CollectionFactoryObserverInterface;
use Translation\DomainCollection;
use Util\YamlFlattener;

class TranslatorGenerateCommand extends Command implements CollectionFactoryObserverInterface
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName( "translator:generate" )
             ->setDescription( "Find translations and generate clean and complete files." )
             ->addArgument( 'path', InputArgument::REQUIRED, 'Where to search' )
             ->addArgument( 'name', InputArgument::REQUIRED, 'The name to match (please read https://symfony.com/doc/current/components/finder.html)' )
             ->addArgument( 'output', InputArgument::REQUIRED, 'Where to store new generated files' )
             ->addArgument( 'required', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Specify required languages, so it will create file even if the domain is empty for that language.' );;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->output = $output;

        $path = $input->getArgument( 'path' );
        $name = $input->getArgument( 'name' );
        $outputPath = $input->getArgument( 'output' );
        $requiredLanguages = $input->getArgument( 'required' );

        $output->writeln( sprintf( "<comment>Searching for : %s</comment>\n", $path . $name ) );
        $output->writeln( "" );

        $fileFinder = new FileFinder();
        $finder = $fileFinder->findFilesIn( $path, $name );
        $collection = CollectionFactory::createFromFinder( $finder, $this );

        if ( $collection->getDomains() == NULL )
        {
            die( "No domain found! Check the entered path and name are correct.\n" );
        }

        $this->generateFileFromCollection( $collection, $outputPath, $output, $requiredLanguages );
    }

    private function generateFileFromCollection( DomainCollection $collection, $outputPath, OutputInterface $output, $requiredLanguages = [] )
    {
        $associativeArray = [];
        $associativeArray = $this->createArrayFromCollection( $collection, $output, $associativeArray );

        foreach ( $collection->getDomains() as $domain )
        {
            $locales = array_merge( $requiredLanguages, $domain->getLocales() );
            foreach ( $locales as $locale )
            {
                $path = sprintf( "%s%s%s.%s.yml", $outputPath, DIRECTORY_SEPARATOR, $domain->getName(), $locale );

                if ( isset( $associativeArray[ $domain->getName() ][ $locale ] ) )
                {
                    // if the collection exist, use it!
                    $this->createFile( $associativeArray[ $domain->getName() ][ $locale ], $path, $output );
                }
                else
                {
                    // if not, create an empty collection!
                    $this->createFile( $this->createEmptyCollection($associativeArray[ $domain->getName() ]), $path, $output );
                }
            }
        }
    }

    public function createFile( $array, $outputPath, OutputInterface $output )
    {
        $dumper = new Dumper();
        //$dumper->setIndentation(2);
        if ( file_exists( $outputPath ) )
        {
            unlink( $outputPath );
        }
        $file = fopen( $outputPath, 'x+' );
        $array = YamlFlattener::flatten( $array );
        ksort( $array, SORT_STRING | SORT_FLAG_CASE );
        foreach ( $array as $key => $value )
        {
            $line = "";
//            if (is_array($value)) {
//                $line = $this->createKeyRecursively($key, $value);
//            } else {
            $value = empty( $value ) ? "#FIXME" : $dumper->dump( $value, 10 );
            $line = sprintf( "%s: %s" . PHP_EOL, $key, $value );
//            }

            if ( $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE )
            {
                $output->write( $line );
            }
            fwrite( $file, $line );
        }
        fclose( $file );
    }

    public static function createKeyRecursively( $prefix, Array $keys )
    {
        $dumper = new Dumper();
        $res = "";
        foreach ( $keys as $key => $value )
        {
            $new_prefix = $prefix . "." . $key;
            if ( is_array( $value ) )
            {
                $res .= self::createKeyRecursively( $new_prefix, $value );
            }
            else
            {
                $value = empty( $value ) ? "#FIXME" : $dumper->dump( $value, 10 );
                $res .= $new_prefix . ': ' . $value . PHP_EOL;
            }
        }

        return $res;
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

    /**
     * @param DomainCollection $collection
     * @param OutputInterface  $output
     * @param                  $associativeArray
     *
     * @return array
     */
    private function createArrayFromCollection( DomainCollection $collection, OutputInterface $output, $associativeArray )
    {
        foreach ( $collection->getDomains() as $domain )
        {
            $output->writeln( sprintf( "Domain '%s' has %s keys and supports : %s", $domain->getName(), count( $domain->getKeys() ), implode( $domain->getLocales(), ", " ) ) );
            $domainName = $domain->getName();

            $locales = $domain->getLocales();
            $keys = $domain->getKeys();
            $associativeArray[ $domainName ] = [];
            foreach ( $locales as $locale )
            {
                $associativeArray[ $domainName ][ $locale ] = [];

                foreach ( $keys as $key )
                {
                    $keyValue = $key->getTranslation( $locale )->getValue();
                    $associativeArray[ $domainName ][ $locale ][ $key->getName() ] = $keyValue;
                }
            }
        }

        return $associativeArray;
    }

    protected function createEmptyCollection( $domain )
    {
        if(empty($domain))
        {
            throw new \LogicException("Cannot create empty collection if the domain is empty");
        }
        $aCollection = array_pop($domain);
        $emptyCollection = [];
        array_walk($aCollection, function($value, $key) use (&$emptyCollection){
            $emptyCollection[$key] = null;
        });
        return $emptyCollection;
    }

}