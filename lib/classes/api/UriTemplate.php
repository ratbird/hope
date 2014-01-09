<?php
/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 */

namespace API;

class UriTemplate
{
    public function __construct($uri_template, $conditions)
    {
        $this->uri_template = $uri_template;
        $this->conditions = $conditions;
    }

    /**
     * Tests whether an uri matches a template.
     *
     * The template may contain placeholders by prefixing an appropriate,
     * unique placeholder name with a colon (:).
     *
     * <code>$template = '/hello/:name';</code>
     *
     * If the uri matches the template, all evaluated placeholders will
     * be stored in the parameters array.
     *
     * @param String $uri        The uri to test
     * @param Array  $parameters Stores evaluated parameters on match (optional)
     *
     * @return bool Returns true if the uri matches the template
     */
    public function match($uri, &$parameters = null)
    {
        // Initialize parameters array
        $parameters = array();

        // Split and normalize uri and template
        $given = array_filter(explode('/', $uri), 'strlen');
        $rules = array_filter(explode('/', $this->uri_template));

        // Leave if uri and template do not contain the same number of
        // elements
        if (count($given) !== count($rules)) {
            return false;
        }

        // Combine uri and template element-wise (simplifies iteration)
        $combined = array_combine($rules, $given);

        // Iterate over uri and template and compare element by element
        foreach ($combined as $rule => $actual) {
            if ($rule[0] === ':') {
                // Rule is a placeholder
                $parameter_name = substr($rule, 1);

                if (isset($this->conditions[$parameter_name])
                    && !preg_match($this->conditions[$parameter_name], $actual)) {
                    return false;
                }

                $parameters[$parameter_name] = $actual;

            } elseif ($actual !== $rule) {
                // Elements do not match
                $parameters = array();
                return false;
            }
        }

        return true;
    }
}
