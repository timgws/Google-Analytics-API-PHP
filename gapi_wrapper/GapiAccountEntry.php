<?php

//namespace Gapi;

/**
 * Class GapiAccountEntry.
 * 
 * Storage for individual Gapi account entries
 */
class GapiAccountEntry
{
    private $properties = array();

    public function __construct($properties)
    {
        $this->properties = $properties;
    }

  /**
   * toString function to return the name of the account.
   *
   * @return string
   */
  public function __toString()
  {
      if (isset($this->properties['title'])) {
          return $this->properties['title'];
      } else {
          return;
      }
  }

  /**
   * Get an associative array of the properties
   * and the matching values for the current result.
   *
   * @return array
   */
  public function getProperties()
  {
      return $this->properties;
  }

  /**
   * Call method to find a matching parameter to return.
   *
   * @param $name String name of function called
   * @param $parameters
   *
   * @return string
   *
   * @throws \InvalidArgumentException if not a valid parameter, or not a 'get' function
   */
  public function __call($name, $parameters)
  {
      if (!preg_match('/^get/', $name)) {
          throw new \InvalidArgumentException('No such function "'.$name.'"');
      }

      $name = preg_replace('/^get/', '', $name);

      $property_key = Gapi::array_key_exists_nc($name, $this->properties);

      if ($property_key) {
          return $this->properties[$property_key];
      }

      throw new \InvalidArgumentException('No valid property called "'.$name.'"');
  }
}
