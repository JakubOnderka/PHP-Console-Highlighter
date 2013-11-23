<?php
use Colors\Color;
use JakubOnderka\PhpConsoleHighlighter\Highlighter;

$colors = new Color();
$colors->setTheme(array(
    Highlighter::TOKEN_STRING => 'red',
    Highlighter::TOKEN_COMMENT => 'yellow',
    Highlighter::TOKEN_KEYWORD => 'green',
    Highlighter::TOKEN_DEFAULT => 'white',
    Highlighter::TOKEN_HTML => 'cyan'
));
$highlighter = new Highlighter($colors);

$fileContent = file_get_contents(__DIR__ . '/example.php');
echo $highlighter->getWholeFile($fileContent);