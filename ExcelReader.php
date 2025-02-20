<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader {
    private $inputFileName;

    public function __construct($inputFileName) {
        $this->inputFileName = $inputFileName;
    }

    public function readDataWithCoordinates() {
        $spreadsheet = IOFactory::load($this->inputFileName);
        $data = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $sheetData = [];
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

                    $sheetData[$coordinate] = $value;
                }
            }
            $data[$sheetName] = $sheetData;
        }
        return $data;
    }
}

?>
