<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Json;

use Composer\Repository\RepositoryManager;
use Composer\Composer;
use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Composer\Util\StreamContextFactory;
use Composer\Util\RemoteFilesystem;
use Composer\Downloader\TransportException;

/**
 * Reads/writes json files.
 *
 * @author Konstantin Kudryashiv <ever.zet@gmail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class JsonFile
{
    const LAX_SCHEMA = 1;
    const STRICT_SCHEMA = 2;

    const JSON_UNESCAPED_SLASHES = 64;
    const JSON_PRETTY_PRINT = 128;
    const JSON_UNESCAPED_UNICODE = 256;

    private $path;
    private $rfs;

    /**
     * Initializes json file reader/parser.
     *
     * @param   string  $lockFile   path to a lockfile
     * @param   RemoteFilesystem  $rfs   required for loading http/https json files
     */
    public function __construct($path, RemoteFilesystem $rfs = null)
    {
        $this->path = $path;

        if (null === $rfs && preg_match('{^https?://}i', $path)) {
            throw new \InvalidArgumentException('http urls require a RemoteFilesystem instance to be passed');
        }
        $this->rfs = $rfs;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Checks whether json file exists.
     *
     * @return  Boolean
     */
    public function exists()
    {
        return is_file($this->path);
    }

    /**
     * Reads json file.
     *
     * @return  array
     */
    public function read()
    {
        try {
            if ($this->rfs) {
                $json = $this->rfs->getContents($this->path, $this->path, false);
            } else {
                $json = file_get_contents($this->path);
            }
        } catch (TransportException $e) {
            throw new \RuntimeException('Could not read '.$this->path.', either you or the remote host is probably offline'."\n\n".$e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not read '.$this->path."\n\n".$e->getMessage());
        }

        return static::parseJson($json);
    }

    /**
     * Writes json file.
     *
     * @param array $hash writes hash into json file
     * @param int $options json_encode options (defaults to JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
     */
    public function write(array $hash, $options = 448)
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            if (file_exists($dir)) {
                throw new \UnexpectedValueException(
                    $dir.' exists and is not a directory.'
                );
            }
            if (!mkdir($dir, 0777, true)) {
                throw new \UnexpectedValueException(
                    $dir.' does not exist and could not be created.'
                );
            }
        }
        file_put_contents($this->path, static::encode($hash, $options). ($options & self::JSON_PRETTY_PRINT ? "\n" : ''));
    }

    /**
     * Validates the schema of the current json file according to composer-schema.json rules
     *
     * @param int $schema a JsonFile::*_SCHEMA constant
     * @return Boolean true on success
     * @throws \UnexpectedValueException
     */
    public function validateSchema($schema = self::STRICT_SCHEMA)
    {
        $content = file_get_contents($this->path);
        $data = json_decode($content);

        if (null === $data && 'null' !== $content) {
            self::validateSyntax($content);
        }

        $schemaFile = __DIR__ . '/../../../res/composer-schema.json';
        $schemaData = json_decode(file_get_contents($schemaFile));

        if ($schema === self::LAX_SCHEMA) {
            $schemaData->additionalProperties = true;
            $schemaData->properties->name->required = false;
            $schemaData->properties->description->required = false;
        }

        $validator = new Validator();
        $validator->check($data, $schemaData);

        // TODO add more validation like check version constraints and such, perhaps build that into the arrayloader?

        if (!$validator->isValid()) {
            $errors = array();
            foreach ((array) $validator->getErrors() as $error) {
                $errors[] = ($error['property'] ? $error['property'].' : ' : '').$error['message'];
            }
            throw new JsonValidationException($errors);
        }

        return true;
    }

    /**
     * Encodes an array into (optionally pretty-printed) JSON
     *
     * This code is based on the function found at:
     *  http://recursive-design.com/blog/2008/03/11/format-json-with-php/
     *
     * @param mixed $data Data to encode into a formatted JSON string
     * @param int $options json_encode options (defaults to JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
     * @return string Encoded json
     */
    static public function encode($data, $options = 448)
    {
        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            return json_encode($data, $options);
        }

        $json = json_encode($data);

        $prettyPrint = (Boolean) ($options & self::JSON_PRETTY_PRINT);
        $unescapeUnicode = (Boolean) ($options & self::JSON_UNESCAPED_UNICODE);
        $unescapeSlashes = (Boolean) ($options & self::JSON_UNESCAPED_SLASHES);

        if (!$prettyPrint && !$unescapeUnicode && !$unescapeSlashes) {
            return $json;
        }

        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $outOfQuotes = true;
        $buffer = '';
        $noescape = true;

        for ($i = 0; $i <= $strLen; $i++) {
            // Grab the next character in the string
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ('"' === $char && $noescape) {
                $outOfQuotes = !$outOfQuotes;
            }

            if (!$outOfQuotes) {
                $buffer .= $char;
                $noescape = '\\' === $char ? !$noescape : true;
                continue;
            } elseif ('' !== $buffer) {
                if ($unescapeSlashes) {
                    $buffer = str_replace('\\/', '/', $buffer);
                }

                if ($unescapeUnicode && function_exists('mb_convert_encoding')) {
                    // http://stackoverflow.com/questions/2934563/how-to-decode-unicode-escape-sequences-like-u00ed-to-proper-utf-8-encoded-cha
                    $buffer = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function($match) {
                        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                    }, $buffer);
                }

                $result .= $buffer.$char;
                $buffer = '';
                continue;
            }

            if (':' === $char) {
                // Add a space after the : character
                $char .= ' ';
            } elseif (('}' === $char || ']' === $char)) {
                $pos--;
                $prevChar = substr($json, $i - 1, 1);

                if ('{' !== $prevChar && '[' !== $prevChar) {
                    // If this character is the end of an element,
                    // output a new line and indent the next line
                    $result .= $newLine;
                    for ($j = 0; $j < $pos; $j++) {
                        $result .= $indentStr;
                    }
                } else {
                    // Collapse empty {} and []
                    $result = rtrim($result)."\n\n".$indentStr;
                }
            }

            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line
            if (',' === $char || '{' === $char || '[' === $char) {
                $result .= $newLine;

                if ('{' === $char || '[' === $char) {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
        }

        return $result;
    }

    /**
     * Parses json string and returns hash.
     *
     * @param string $json json string
     *
     * @return  mixed
     */
    public static function parseJson($json)
    {
        $data = json_decode($json, true);
        if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
            self::validateSyntax($json);
        }

        return $data;
    }

    /**
     * Validates the syntax of a JSON string
     *
     * @param string $json
     * @return Boolean true on success
     * @throws \UnexpectedValueException
     */
    protected static function validateSyntax($json)
    {
        $parser = new JsonParser();
        $result = $parser->lint($json);
        if (null === $result) {
            if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
                throw new \UnexpectedValueException('JSON file is not UTF-8 encoded');
            }

            return true;
        }

        throw $result;
    }
}
