<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 10-08-17
 * Time: 10:07
 */

namespace Logic;

use Symfony\Component\Finder\Finder;

class FileFinder
{
    /**
     * @param $path
     * @param $name
     *
     * @return Finder
     */
    public function findFilesIn( $path, $name )
    {
        $finder = new Finder();
        $finder->files()->in( $path )->name( $name );

        return $finder;
    }
}