<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 10-08-17
 * Time: 12:04
 */

namespace Translation;

class Translation
{
    /** @var  string */
    protected $value = null;

    /** @var  string */
    protected $locale;

    /**
     * Translation constructor.
     *
     * @param string $value
     * @param string $locale
     */
    public function __construct( $locale )
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Translation
     */
    public function setValue( $value )
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return Translation
     */
    public function setLocale( $locale )
    {
        $this->locale = $locale;

        return $this;
    }


}