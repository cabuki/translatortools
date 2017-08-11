<?php
/**
 * Created by IntelliJ IDEA.
 * User: Benjamin
 * Date: 10-08-17
 * Time: 12:04
 */

namespace Translation;

class Key
{
    /** @var string */
    public $name;

    /**
     * @var array|Translation[string]
     *
     */
    public $translations = [];

    /**
     * Key constructor.
     *
     * @param string $name
     */
    public function __construct( $name )
    {
        $this->name = $name;
    }

    public function getTranslation($locale)
    {
        if(isset($this->translations[$locale])) return $this->translations[$locale];
        $translation = new Translation($locale);
        $this->addTranslation($translation);
        return $translation;
    }

    public function addValueForLocale($locale, $value)
    {
        $translation = $this->getTranslation($locale);
        $translation->setValue($value);
    }

    public function addTranslation(Translation $translation)
    {
        $this->translations[$translation->getLocale()] = $translation;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Key
     */
    public function setName( $name )
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array|Translation
     */
    public function getTranslations()
    {
        return $this->translations;
    }


}