<?php
/*
 * This file is part of the GetOptionKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace LeePHP\OptionKit;

use LeePHP\OptionKit\OptionSpecCollection;
use LeePHP\OptionKit\OptionResult;
use LeePHP\OptionKit\OptionSpec;
use LeePHP\OptionKit\Argument;
use \Exception;

class OptionParser {
    public $specs;
    public $longOptions;
    public $shortOptions;

    function __construct($specs) {
        if ($specs) {
            if (!is_a($specs, '\LeePHP\OptionKit\OptionSpecCollection'))
                throw new Exception('not option spec collection class.');
            $this->specs = $specs;
        } else {
            $this->specs = new OptionSpecCollection();
        }
    }

    function setSpecs($specs) {
        $this->specs = $specs;
    }

    /**
     * A helper to build option specification object from string spec.
     * 
     * @param string $specString
     * @param string $description
     * @param string $key
     * @return OptionSpec
     */
    function addSpec($specString, $description, $key = null) {
        $spec = $this->specs->add($specString, $description, $key);
        return $spec;
    }

    /**
     * Get option specification by Id.
     * 
     * @param string $id
     * @return OptionSpec
     */
    function getSpec($id) {
        return $this->specs->getSpec($id);
    }

    /**
     * Detect option.
     * 
     * @param string $arg
     * @return boolean
     */
    function isOption($arg) {
        return substr($arg, 0, 1) === '-';
    }

    /**
     * Take option value from current argument or from the next argument.
     * 
     * @param string $spec
     * @param string $arg
     * @param string $next
     */
    function takeOptionValue($spec, $arg, $next) {
        if ($arg->containsOptionValue()) {
            $spec->setValue($arg->getOptionValue());
        } elseif (!$next->isOption()) {
            $spec->setValue($next->arg);
        }
    }
    
    /**
     * Push value to multipl value option.
     * 
     * @param string $spec
     * @param string $arg
     * @param string $next
     */
    function pushOptionValue($spec, $arg, $next) {
        if ($arg->containsOptionValue())
            $spec->pushValue($arg->getOptionValue());
        elseif (!$next->isOption())
            $spec->pushValue($next->arg);
    }

    function foundRequireValue($spec, $arg, $next) {
        /* argument doesn't contain value and next argument is option */
        if ($arg->containsOptionValue())
            return true;

        if (!$arg->containsOptionValue() && !$next->isEmpty() && !$next->isOption())
            return true;

        return false;
    }

    function parse($argv) {
        $result = new OptionResult();
        $len    = count($argv);
        for ($i = 0; $i < $len; ++$i) {
            $arg = new Argument($argv[$i]);
            if (!$arg->isOption()) {
                $result->addArgument($arg);
                continue;
            }

            // if the option is with extra flags,
            //   split it out, and insert into the argv array
            if ($arg->withExtraFlagOptions()) {
                $extra    = $arg->extractExtraFlagOptions();
                array_splice($argv, $i + 1, 0, $extra);
                $argv[$i] = $arg->arg; // update argument to current argv list.
                $len      = count($argv);   // update argv list length
            }

            $next = new Argument($argv[$i + 1]);
            $spec = $this->specs->getSpec($arg->getOptionName());
            if (!$spec)
                throw new Exception("Invalid option: " . $arg);

            if ($spec->isAttributeRequire()) {
                if (!$this->foundRequireValue($spec, $arg, $next))
                    throw new Exception("Option `{$arg->getOptionName()}` require a value.");

                $this->takeOptionValue($spec, $arg, $next);
                if (!$next->isOption())
                    $i++;
                $result->set($spec->getId(), $spec);
            }
            elseif ($spec->isAttributeMultiple()) {
                $this->pushOptionValue($spec, $arg, $next);
                if ($next->isOption())
                    $i++;
                $result->set($spec->getId(), $spec);
            }
            elseif ($spec->isAttributeOptional()) {
                $this->takeOptionValue($spec, $arg, $next);
                if ($spec->value && !$next->isOption())
                    $i++;
                $result->set($spec->getId(), $spec);
            }
            elseif ($spec->isAttributeFlag()) {
                $spec->value = true;
                $result->set($spec->getId(), $spec);
            } else {
                throw new Exception('Unknown attribute.');
            }
        }
        
        return $result;
    }
}
