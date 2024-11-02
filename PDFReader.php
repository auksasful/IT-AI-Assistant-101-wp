<?php

require_once 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

class PdfReader
{
    private $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function getTextFromFile($filePath)
    {
        $pdf = $this->parser->parseFile($filePath);
        $text = $pdf->getText();
        return $text;
    }

    public function getTextFromPages($filePath)
    {
        $pdf = $this->parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $texts = [];

        foreach ($pages as $page) {
            $texts[] = $page->getText();
        }

        return $texts;
    }
}

// Example usage:
// $pdfReader = new PdfReader();
// $text = $pdfReader->getTextFromFile('path/to/your/document.pdf');
// echo $text;

?>
