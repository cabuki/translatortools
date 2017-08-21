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
use Symfony\Component\Yaml\Dumper;
use Translation\CollectionFactory;
use Translation\CollectionFactoryObserverInterface;
use Translation\DomainCollection;

class TranslatorConvertCommand extends Command implements CollectionFactoryObserverInterface
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName( "translator:convert" )
            ->setDescription( "Find document according to specific path." )
            ->addArgument( 'convertType', InputArgument::REQUIRED, 'The result of the conversion' )
            ->addArgument( 'path', InputArgument::REQUIRED, 'Where to search' )
            ->addArgument( 'name', InputArgument::REQUIRED, 'The name to match (please read https://symfony.com/doc/current/components/finder.html)' )
            ->addArgument( 'output', InputArgument::REQUIRED, 'Where to store new generated files' );
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->output = $output;

        $convertType = $input->getArgument( 'convertType' );
        $path = $input->getArgument( 'path' );
        $name = $input->getArgument( 'name' );
        $outputPath = $input->getArgument( 'output' );

        $output->writeln( sprintf( "<comment>Searching for : %s</comment>\n", $path . $name) );
        $output->writeln( "" );

        $fileFinder = new FileFinder();
        $finder = $fileFinder->findFilesIn( $path, $name );

        if ( strcmp($convertType, "csv") == 0 )
        {
            $collection = CollectionFactory::createFromFinder( $finder, $this );

            if ($collection->getDomains() == NULL)
            {
                die( "No domain found! Check the entered paths and names are correct.\n"  );
            }
            $this->generateFileFromCollection( $collection, $outputPath, $output );
        }
        else if ( strcmp($convertType, "yml") == 0)
        {
            $fileList = [];
            foreach ( $finder as $file )
            {
                $fileList[] = $file->getRelativePathname(); //$fileList
            }
            $this->generateFileFromCSV($fileList, $path, $outputPath, $output);
        }
        else
        {
            die( "This conversion is not supported.\n"  );
        }
    }



    private function generateFileFromCollection(DomainCollection $collection, $outputPath, OutputInterface $output)
    {
        $associativeArray = [];
        $associativeArray = $this->createArrayFromCollection($collection, $output, $associativeArray);

        foreach ($collection->getDomains() as $domain)
        {
            $locales = $domain->getLocales();
            foreach ($locales as $locale)
            {
                $path = sprintf("%s%s%s.%s.csv", $outputPath, DIRECTORY_SEPARATOR, $domain->getName(), $locale);
                $this->createCSVFile($associativeArray[$domain->getName()][$locale], $path, $output);
            }
        }
    }

    public function createCSVFile( $array, $outputPath, OutputInterface $output )
    {
        if ( file_exists( $outputPath ) )
        {
            unlink( $outputPath );
        }
        $file = fopen( $outputPath, 'x+' );
        ksort( $array, SORT_STRING | SORT_FLAG_CASE );
        foreach ( $array as $key => $value )
        {
            $value = str_replace("\n", "\\n", $value); // To write '\n' and not interpret it
            $value = str_replace('"', '""', $value); // To not interpret double quotes in the csv
            $line = sprintf('%s;"%s"' . PHP_EOL, $key, $value); // Add quotes to not interpret semi-colon in $value

            if ( $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE )
            {
                $output->write($line);
            }
            fwrite($file, $line);
        }
        fclose( $file );
    }



    private function generateFileFromCSV($fileList, $inputPath, $outputPath, OutputInterface $output)
    {
        foreach ($fileList as $file)
        {
            $f = explode(".", $file);
            $ip = $inputPath . DIRECTORY_SEPARATOR . $file;
            $op = $outputPath . DIRECTORY_SEPARATOR  . $f[0] . '.' . $f[1] . '.yml';

            $this->createYMLFile($ip, $op, $output);
        }
    }

    public function createYMLFile( $inputPath, $outputPath, OutputInterface $output )
    {
        if ( file_exists( $outputPath ) )
        {
            unlink( $outputPath );
        }
        $dumper = new Dumper();
        $ymlFile = fopen( $outputPath, 'x+' );

        $csvFile = fopen($inputPath, "r");
        if ( $csvFile ) {
            while (($data = fgetcsv($csvFile, 3000, ";")) == true) {
                $value = $dumper->dump($data[1], 10); // To add adequat quotes around string
                fwrite($ymlFile,  $data[0] . ': ' . $value . PHP_EOL);
            }
            fclose($csvFile);
        }
        fclose( $ymlFile );
    }


    /**
     * @param DomainCollection $collection
     * @param OutputInterface $output
     * @param                  $associativeArray
     *
     * @return array
     */
    private function createArrayFromCollection(DomainCollection $collection, OutputInterface $output, $associativeArray)
    {
        foreach ($collection->getDomains() as $domain) {
            $output->writeln(sprintf("Domain '%s' has %s keys and support : %s", $domain->getName(), count($domain->getKeys()), implode($domain->getLocales(), ", ")));
            $domainName = $domain->getName();

            $locales = $domain->getLocales();
            $keys = $domain->getKeys();
            $associativeArray[$domainName] = [];
            foreach ($locales as $locale) {
                $associativeArray[$domainName][$locale] = [];

                foreach ($keys as $key) {
                    $keyValue = $key->getTranslation($locale)->getValue();
                    $associativeArray[$domainName][$locale][$key->getName()] = empty($keyValue) ? '#fixme' : $keyValue;
                }
            }
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