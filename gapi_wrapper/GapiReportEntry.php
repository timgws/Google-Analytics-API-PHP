<?php

/**
 * Class GapiReportEntry.
 *
 * Storage for individual Gapi report entries
 */
class GapiReportEntry
{
    private $metrics = array();
    private $dimensions = array();

    /**
     * @param array $metrics    the metrics included in this report row.
     * @param array $dimensions the dimensions from this report row.
     */
    public function __construct(array $metrics, array $dimensions)
    {
        $this->metrics = $metrics;
        $this->dimensions = $dimensions;
    }

    /**
     * toString function to return the name of the result
     * this is a concatenated string of the dimensions chosen.
     *
     * For example:
     * 'Firefox 3.0.10' from browser and browserVersion
     *
     * @return string
     */
    public function __toString()
    {
        if (is_array($this->dimensions)) {
            return implode(' ', $this->dimensions);
        } else {
            return '';
        }
    }

    /**
     * Get an associative array of the dimensions
     * and the matching values for the current result.
     *
     * @return array
     */
    public function getDimesions()
    {
        return $this->dimensions;
    }

    /**
     * Get an array of the metrics and the matching
     * values for the current result.
     *
     * @return array
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * Call method to find a matching metric or dimension to return.
     *
     * @param $name String name of function called
     * @param $parameters
     *
     * @return string
     *
     * @throws \InvalidArgumentException if not a valid metric or dimensions, or not a 'get' function
     */
    public function __call($name, $parameters)
    {
        if (!preg_match('/^get/', $name)) {
            throw new \InvalidArgumentException('No such function "'.$name.'"');
        }

        $name = preg_replace('/^get/', '', $name);

        $metric_key = Gapi::array_key_exists_nc($name, $this->metrics);

        if ($metric_key) {
            return $this->metrics[$metric_key];
        }

        $dimension_key = Gapi::array_key_exists_nc($name, $this->dimensions);

        if ($dimension_key) {
            return $this->dimensions[$dimension_key];
        }

        throw new \InvalidArgumentException('No valid metric or dimesion called "'.$name.'"');
    }
}
