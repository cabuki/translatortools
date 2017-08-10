<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 10-08-17
 * Time: 10:52
 */

namespace Translation;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class CollectionFactory
{

    public static function createFromFinder(Finder $finder, CollectionFactoryObserverInterface $observer)
    {
        $keys = [];
        foreach ( $finder as $file )
        {
            $yaml = Yaml::parse(file_get_contents($file->getRealPath()));
            $sourceName = $file->getFilename();
            $observer->dealingWith($sourceName);
            $newKeys = array_keys($yaml);
            $observer->foundKeys($newKeys, $sourceName);
            $observer->foundNewKeys(array_diff($newKeys, $keys), $sourceName);
            $keys = array_merge($keys, $newKeys);
        }
        return $keys;
    }
}