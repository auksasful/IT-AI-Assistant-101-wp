<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader {
    private $inputFileName;

    public function __construct($inputFileName) {
        $this->inputFileName = $inputFileName;
    }

    public function readData() {
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($this->inputFileName);
        // Get the active sheet
        $sheet = $spreadsheet->getActiveSheet();
        // Read data from the sheet
        $data = $sheet->toArray();
        return $data;
    }

    public function readFormulas() {
        $spreadsheet = IOFactory::load($this->inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $formulas = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rowFormulas = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowFormulas[] = $cell->getValue(); // This will also capture formulas
            }
            $formulas[] = $rowFormulas;
        }
        return $formulas;
    }

    public function readDataWithCoordinates() {
        $spreadsheet = IOFactory::load($this->inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = [];
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $coordinate = $cell->getCoordinate();
                $value = $cell->getValue();
                
                // Check if the cell contains a rich text object
                if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $value = $value->getPlainText();  // Get plain text value
                }

                // Check if the cell contains a formula
                if ($cell->isFormula()) {
                    $calculatedValue = $cell->getCalculatedValue();
                    $value .= " ( $calculatedValue )";
                }

                $data[$coordinate] = $value;
            }
        }
        return $data;
    }
}

?>
