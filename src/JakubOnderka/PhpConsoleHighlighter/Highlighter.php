<?php
namespace JakubOnderka\PhpConsoleHighlighter;

use Colors\Color;

class Highlighter
{
    const TOKEN_DEFAULT = 'd',
        TOKEN_COMMENT = 'c',
        TOKEN_STRING = 's',
        TOKEN_HTML = 'h',
        TOKEN_KEYWORD = 'k';

    /** @var Color */
    private $color;

    public function __construct(Color $color)
    {
        $this->color = $color;
    }

    /**
     * @param string $source
     * @param int $lineNumber
     * @param int $linesBefore
     * @param int $linesAfter
     * @return string
     */
    public function getCodeSnippet($source, $lineNumber, $linesBefore = 2, $linesAfter = 2)
    {
        $tokenLines = $this->getHighlightedLines($source);

        $offset = $lineNumber - $linesBefore - 1;
        $length = $linesAfter + $linesBefore + 1;
        $tokenLines = array_slice($tokenLines, $offset, $length, $preserveKeys = true);

        $lines = $this->colorLines($tokenLines);

        end($lines);
        $lineStrlen = strlen(key($lines) + 1);

        $snippet = '';
        foreach ($lines as $i => $line) {
            $snippet .= ($lineNumber === $i + 1 ? $this->color->apply('red', '  > ') : '    ');
            $snippet .= str_pad($i + 1, $lineStrlen, ' ', STR_PAD_LEFT) . '| ' . rtrim($line) . PHP_EOL;
        }

        return $snippet;
    }

    /**
     * @param string $source
     * @return string
     */
    public function getWholeFile($source)
    {
        $tokenLines = $this->getHighlightedLines($source);
        return $this->output($tokenLines);
    }

    /**
     * @param string $source
     * @return array
     */
    private function getHighlightedLines($source)
    {
        $source = str_replace(array("\r\n", "\r"), "\n", $source);
        $tokens = $this->tokenize($source);
        return $this->splitToLines($tokens);
    }

    /**
     * @param string $source
     * @return array
     */
    private function tokenize($source)
    {
        $tokens = token_get_all($source);

        $output = array();
        $currentType = null;
        $buffer = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_INLINE_HTML:
                        $newType = self::TOKEN_HTML;
                        break;

                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        $newType = self::TOKEN_COMMENT;
                        break;

                    case T_ENCAPSED_AND_WHITESPACE:
                    case T_CONSTANT_ENCAPSED_STRING:
                        $newType = self::TOKEN_STRING;
                        break;

                    case T_WHITESPACE:
                        break;

                    case T_OPEN_TAG:
                    case T_OPEN_TAG_WITH_ECHO:
                    case T_CLOSE_TAG:
                    case T_STRING:
                    case T_VARIABLE:
                    case T_DIR:
                    case T_DNUMBER:
                    case T_LNUMBER:
                        $newType = self::TOKEN_DEFAULT;
                        break;

                    default:
                        $newType = self::TOKEN_KEYWORD;
                }
            } else {
                $newType = $token === '"' ? self::TOKEN_STRING : self::TOKEN_KEYWORD;
            }

            if ($currentType === null) {
                $currentType = $newType;
            }

            if ($currentType != $newType) {
                $output[] = array($currentType, $buffer);
                $buffer = '';
                $currentType = $newType;
            }

            $buffer .= is_array($token) ? $token[1] : $token;
        }

        $output[] = array($newType, $buffer);

        return $output;
    }

    /**
     * @param array $tokens
     * @return array
     */
    private function splitToLines(array $tokens)
    {
        $lines = array();

        $line = array();
        foreach ($tokens as $token) {
            foreach (explode("\n", $token[1]) as $count => $tokenLine) {
                if ($count > 0) {
                    $lines[] = $line;
                    $line = array();
                }

                if ($tokenLine === '') {
                    continue;
                }

                $line[] = array($token[0], $tokenLine);
            }
        }

        $lines[] = $line;

        return $lines;
    }

    /** @param array $tokenLines
     * @return array
     */
    private function colorLines(array $tokenLines)
    {
        $lines = array();
        foreach ($tokenLines as $lineCount => $tokenLine) {
            $line = '';
            foreach ($tokenLine as $token) {
                $line .= $this->color->apply($token[0], $token[1]);
            }
            $lines[$lineCount] = $line;
        }

        return $lines;
    }

    /**
     * @param array $tokenLines
     * @return string
     */
    private function output(array $tokenLines)
    {
        return implode("\n", $this->colorLines($tokenLines));
    }
}