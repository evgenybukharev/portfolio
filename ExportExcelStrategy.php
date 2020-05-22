<?php
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use yii\helpers\ArrayHelper;
use function sprintf;

/**
 * Class ExportExcelExportStrategy
 *
 * @package common\components\export
 */
class ExportExcelStrategy extends ExportBaseStrategy
{

    /**
     * @return mixed
     * @throws Exception
     */
    public function export()
    {
        $data = $this->getData();
        $config = $this->getConfig();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->disconnectWorksheets();

        $docTitle = ArrayHelper::getValue($config, 'title', 'document');
        $callback = ArrayHelper::getValue($config, 'callback');

        $spreadsheet->getProperties()->setTitle($docTitle);

        $pages = ArrayHelper::getValue($data, 'pages', []);

        for ($i = 0; $i < count($pages); $i++) {
            $page = $pages[$i];
            $title = ArrayHelper::getValue($page, 'title', 'page - ' . ($i + 1));
            $sheet = $spreadsheet->createSheet($i);
            $sheet->setTitle($title);
            $rows = ArrayHelper::getValue($page, 'rows', []);

            for ($z = 0, $rowIndex = 1; $z < count($rows); $z++, $rowIndex++) {
                $row = $rows[$z];
                for ($c = 0, $colIndex = 1; $c < count($row); $c++, $colIndex++) {
                    $cell = $row[$c];
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $cell);
                }
            }
        }

        if (\is_callable($callback)) {
            $spreadsheet = \call_user_func($callback, $this, $spreadsheet, $data);
        }


        return $this->output($spreadsheet, $docTitle);
    }


    /**
     * @param array  $config
     * @param        $spreadsheet
     */
    public function setStyle(array $config, &$spreadsheet)
    {
        $union = ArrayHelper::getValue($config, 'union', []);
        $header = ArrayHelper::getValue($config, 'header', []);
        $table = ArrayHelper::getValue($config, 'table', []);
        $custom = ArrayHelper::getValue($config, 'custom', []);
        $autoWidth = ArrayHelper::getValue($config, 'autoWidth', []);

        $calculateColumn = function (int $num): string {
            $column = 'A';
            while ($num > 1) {
                $column++;
                $num--;
            }
            return $column;
        };

        $setStyleByConfig = function ($sheet, $config, $style) use ($calculateColumn) {

            for ($i = 0; $i < count($config); $i++) {
                $columnStart = ArrayHelper::getValue($config, [$i, 0]);
                $rowStart = ArrayHelper::getValue($config, [$i, 1]);
                $width = ArrayHelper::getValue($config, [$i, 2]);
                $rowEnd = ArrayHelper::getValue($config, [$i, 3], $rowStart);
                $applyStyle = ArrayHelper::getValue($config, [$i, 4], $style);

                $sheet->getStyle(\sprintf('%s%d:%s%d', $calculateColumn($columnStart), $rowStart, $calculateColumn($width), $rowEnd))->applyFromArray($applyStyle);
            }
        };

        $activeSheet = $spreadsheet->getActiveSheet();
        $borderStyle = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => [
                'rgb' => '9999ff',
            ],
        ];
        $headerStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'ffffcc'],
            ],
            'font' => [
                'bold' => true,
            ],
            'borders' => [
                'allBorders' => $borderStyle,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'quotePrefix' => true,
        ];
        $tableStyle = [
            'borders' => [
                'allBorders' => $borderStyle,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        // AutoWidth
        if (!empty($autoWidth)) {
            for ($i = 0; $i < count($autoWidth); $i++) {
                $columnAutoWidth = $calculateColumn(ArrayHelper::getValue($autoWidth, [$i, 0]));
                $widthAutoWidth = $calculateColumn(ArrayHelper::getValue($autoWidth, [$i, 1]));

                while (true) {
                    $activeSheet->getColumnDimension($columnAutoWidth)->setAutoSize(true);
                    if ($columnAutoWidth == $widthAutoWidth) {
                        break;
                    }
                    $columnAutoWidth++;
                }
            }
        }

        // Table
        if (!empty($table)) {
            $setStyleByConfig($activeSheet, $table, $tableStyle);
        }
        // Header
        if (!empty($header)) {
            $setStyleByConfig($activeSheet, $header, $headerStyle);
        }
        // Custom
        if (!empty($table)) {
            $setStyleByConfig($activeSheet, $custom, []);
        }

        if (!empty($union)) {
            foreach ($union as $unionRow => $unionColumns) {
                $startUnionRow = $endUnionRow = $unionRow;
                foreach ($unionColumns as $startUnionColumnNum => $endUnionColumnNum) {
                    $startUnionColumn = $endUnionColumn = 'A';
                    for ($x = 1; $x < $startUnionColumnNum; $x++) {
                        $startUnionColumn++;
                    }
                    for ($x = 1; $x < ($startUnionColumnNum + $endUnionColumnNum - 1); $x++) {
                        $endUnionColumn++;
                    }

                    $spreadsheet->getActiveSheet()->mergeCells(\sprintf('%s%d:%s%d', $startUnionColumn, $startUnionRow, $endUnionColumn, $endUnionRow));
                }
            }
        }

    }

    /**
     * @param $spreadsheet
     * @param $fileName
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    private function output(Spreadsheet $spreadsheet, $fileName)
    {
        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header(sprintf('Content-Disposition: attachment;filename="%s.xlsx"', $fileName));
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: no-store'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        File::setUseUploadTempDirectory(true);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        die();
    }
}
