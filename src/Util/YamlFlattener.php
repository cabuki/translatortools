<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 31-08-17
 * Time: 10:12
 */

namespace Util;


class YamlFlattener
{

    public static function flatten( $array, $prefix = '' )
    {
        $result = [];
        foreach ( $array as $key => $value )
        {
            if ( is_array( $value ) )
            {
                $result = $result + self::flatten( $value, $prefix . $key . '.' );
            }
            else
            {
                $result[ $prefix . $key ] = $value;
            }
        }

        return $result;
    }
}