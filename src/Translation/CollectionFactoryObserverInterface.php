<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 10-08-17
 * Time: 10:59
 */

namespace Translation;

interface CollectionFactoryObserverInterface
{
    public function dealingWith($source);
    public function foundKeys($keys, $source);
    public function foundNewKeys($keys, $source);

}