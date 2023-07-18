<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Hospital;
use App\HospitalInvoice;
use function App\Start\hospital_report_path;

class HospitalLawsonInterfacedInvoiceCommand extends ReportingCommand
{
    protected $name = "lawson_interfaced_invoices:hospital";
    protected $description = "Generates a DYNAFIOS hospital Lawson interfaced invoices report.";

    private $cell_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffffff']],
        'font' => ['color' => ['rgb' => '000000']],
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $totals_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'f2f2f2']],
        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $period_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'vertical' => Alignment::VERTICAL_CENTER,
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $header_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'a09284']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'vertical' => Alignment::VERTICAL_CENTER,
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $cell_left_border_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $cell_right_border_style = [
        'borders' => [
            'right' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $cell_left_right_border_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $cell_top_border_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];
    private $cell_bottom_border_style = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $all_border = [
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
        ]
    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();
        $hospital = Hospital::findOrFail($arguments->hospital_id);
        // $workbook = $this->loadTemplate('hospital_lawson_interfaced_invoice.xlsx');

        $reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/hospital_lawson_interfaced_invoice.xlsx");

        $run_date = format_date('now', 'm/d/Y \a\t h:i:s A');

        $templateSheet = $workbook->getSheet(0);
        $sheetIndex = 0;
        $current_row = 0;
        $last_invoice_no = 0;
        $CM_signature = "NA";
        $FM_signature = "NA";

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        foreach($arguments->data as $agreement){
//            $words = explode(" ", $hospital->name);
//            $acronym = "";
//
//            if(count($words) > 0) {
//                foreach ($words as $w) {
//                    $acronym .= $w[0];
//                }
//            }
//            $invoice_no = $acronym."_".date('F').'_'.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
            $last_invoice_no = $agreement["agreement_data"]["invoice_no"]+1;
            $invoice_no_period = $agreement["agreement_data"]["invoice_no_period"];

//            $invoice_no = date('F').' '.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
            $invoice_no = $invoice_no_period;

            $sheetIndex++;
            $nextWorksheet = clone $templateSheet;
            //$nextWorksheet->setTitle($agreement["agreement_data"]["name"]);
            $title = strlen($hospital->name) > 31 ? substr($hospital->name,0,31): $hospital->name;
            $invalidCharacters = $nextWorksheet->getInvalidCharacters();//added for remove special charachters
            $title = str_replace($invalidCharacters, '', $title);//added for remove special charachters
            $nextWorksheet->setTitle($title);
            $workbook->addSheet($nextWorksheet);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A5")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B5")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("A5", "");
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("B5", $run_date)
                ->setCellValue("B7", $hospital->name)
                ->setCellValue("B8", $hospital->address)
                ->setCellValue("B9","{$hospital->city}, {$hospital->state->name}");
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C5", "Date Range:".$agreement["agreement_data"]["period"]);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A3:A9")->applyFromArray($this->cell_left_border_style);
            $current_row=10;
            //$invoice_notes = InvoiceNote::getInvoiceNotes($hospital->id, InvoiceNote::HOSPITAL); /*hospital invoice notes*/
            /*foreach($invoice_notes as $index => $invoice_note){
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $invoice_note);
                $current_row++;
            }*/
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->cell_top_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:N{$current_row}");
            $current_row++;
            foreach($agreement["practices"] as $practice){
                $grant_total = 0;
                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:N{$current_row}");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $practice["name"]);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->period_style);
                $current_row++;
                /*foreach($practice['practice_invoice_notes'] as $index => $practice_invoice_note){
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:N{$current_row}");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("A{$current_row}:N{$current_row}")->getFont()->setBold(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("A{$current_row}")->getAlignment()->setWrapText(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $practice_invoice_note);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->period_style);
                    $current_row++;
                }*/
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->header_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getRowDimension($current_row)
                    ->setRowHeight(30);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Contract");
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$current_row}", "Physician");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$current_row}", "Actions");
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$current_row}", "Hours/Days");
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "Rate");
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$current_row}", "Calculated Payments");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}")->applyFromArray($this->cell_left_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", "Actual Payments");
//                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("H{$current_row}:H{$current_row}");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$current_row}", "Physician");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("I{$current_row}", "Level 1");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$current_row}", "Level 2");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("K{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("K{$current_row}", "Level 3");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("L{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("L{$current_row}", "Level 4");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("M{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("M{$current_row}", "Level 5");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("N{$current_row}", "Level 6");
                $current_row++;
                foreach($practice["contract_data"] as $contract_data){
                    if(count($contract_data["breakdown"]) > 0){
                        $new_current_row = count($contract_data["breakdown"]) + $current_row;
                        $physician_row = ($new_current_row) - ((($new_current_row) - $current_row) / 2);
                        $physician_row = floor($physician_row);
                        $new_current_row = $new_current_row-1;
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:A{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:B{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("G{$current_row}:G{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("H{$current_row}:H{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("I{$current_row}:I{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("J{$current_row}:J{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("K{$current_row}:K{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("L{$current_row}:L{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("M{$current_row}:M{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("N{$current_row}:N{$new_current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:H{$new_current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:H{$new_current_row}")
                            ->getAlignment()
                            ->setVertical(Alignment::VERTICAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:A{$new_current_row}")->applyFromArray($this->all_border);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $contract_data["contract_name"]);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$current_row}", $contract_data["physician_name"]);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}")->applyFromArray($this->all_border);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                        foreach($contract_data["breakdown"] as $breakdown) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("C{$current_row}:F{$current_row}")->applyFromArray($this->all_border);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$current_row}", $breakdown["action"]);
                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$current_row}", $breakdown["worked_hours"]);
                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", $breakdown["rate"]);
                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$current_row}", $breakdown["calculated_payment"]);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("G{$current_row}")->applyFromArray($this->cell_left_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("C{$current_row}")->getAlignment()->setWrapText(true);
                            //$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", $contract_data["amount_paid"]);
//                            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("H{$current_row}:H{$current_row}");
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("K{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("L{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("M{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:F{$current_row}")->getNumberFormat()->setFormatCode('$#,##0.00');
                            //$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$current_row}", "E-Signature");
                            $Physician_signature = $breakdown["Physician_approve_signature"];
                            $Physician_date = $breakdown["Physician_signature_date"];
//                            $CM_date = $breakdown["CM_signature_date"];
//                            $CM_signature = $breakdown["CM_approve_signature"];
//                            $FM_date = $breakdown["FM_signature_date"];
//                            $FM_signature = $breakdown["FM_approve_signature"];
                            $levels=$breakdown["levels"];
                            $current_row++;
                        }
                        if ($Physician_signature != "NA" && $Physician_signature != "") {
                            if(count($contract_data["breakdown"]) >1) {
                                $signature_row = $physician_row-1;
                            }else{
                                $signature_row = $physician_row;
                            }
                            $dataSP = "data:image/png;base64," . $Physician_signature;
                            list($type, $dataS) = explode(';', $dataSP);
                            list(, $dataSP) = explode(',', $dataSP);

                            $dataSP = base64_decode($dataSP);
                            //echo storage_path()."/image.png";die;
                            file_put_contents(storage_path() . "/image" . $contract_data["physician_id"] . ".png", $dataSP);
                            $objDrawingPType = new Drawing();
                            $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                            $objDrawingPType->setName("Signature");
                            $objDrawingPType->setPath(storage_path() . "/image" . $contract_data["physician_id"] . ".png");
                            $objDrawingPType->setCoordinates("H".$signature_row);
                            $objDrawingPType->setOffsetX(1);
                            $objDrawingPType->setOffsetY(5);
                            $objDrawingPType->setWidthAndHeight(100,70);
                            $objDrawingPType->setResizeProportional(false);
//                            $workbook->setActiveSheetIndex($sheetIndex)
//                                ->getColumnDimension('D')
//                                ->setWidth(40);
                            if(count($contract_data["breakdown"]) >1) {
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getRowDimension($signature_row)
                                    ->setRowHeight(30);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getRowDimension($physician_row)
                                    ->setRowHeight(30);
                            }else{
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getRowDimension($signature_row)
                                    ->setRowHeight(60);
                            }
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("B{$current_row}")
                                ->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("B{$current_row}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_CENTER);
                            $l=0;
                            foreach($levels as $level_sign) {
                                $l++;
                                switch ($l) {
                                    case 1:
                                        $column = "I";
                                        break;
                                    case 2:
                                        $column = "J";
                                        break;
                                    case 3:
                                        $column = "K";
                                        break;
                                    case 4:
                                        $column = "L";
                                        break;
                                    case 5:
                                        $column = "M";
                                        break;
                                    case 6:
                                        $column = "N";
                                        break;
                                    default:
                                        $column = "I";
                                }
                                if ($level_sign["signature"] != "NA" && $level_sign["signature"] != "") {
                                    $dataSCM = "data:image/png;base64," . $level_sign["signature"];
                                    list($type, $dataSCM) = explode(';', $dataSCM);
                                    list(, $dataSCM) = explode(',', $dataSCM);

                                    $dataSCM = base64_decode($dataSCM);
                                    //echo storage_path()."/image.png";die;
                                    file_put_contents(storage_path() . "/imageUserM" .$column.$signature_row. date("mdYHis") . ".png", $dataSCM);
                                    $objDrawingPType = new Drawing();
                                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                                    $objDrawingPType->setName("Signature");
                                    $objDrawingPType->setPath(storage_path() . "/imageUserM" .$column.$signature_row. date("mdYHis") . ".png");
                                    $objDrawingPType->setCoordinates($column . $signature_row);
                                    $objDrawingPType->setOffsetX(1);
                                    $objDrawingPType->setOffsetY(5);
                                    $objDrawingPType->setWidthAndHeight(100, 70);
                                    $objDrawingPType->setResizeProportional(true);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getColumnDimension('D')
//                    ->setWidth(40);
                                    /*$workbook->setActiveSheetIndex($sheetIndex)
                                        ->getRowDimension($current_row)
                                        ->setRowHeight(80);*/
//                    unset($count_prev);
//                    $count_prev = array();


//                                if ($FM_signature != "NA" && $FM_signature != "") {
//                                    $dataSFM = "data:image/png;base64," . $FM_signature;
//                                    list($type, $dataSFM) = explode(';', $dataSFM);
//                                    list(, $dataSFM) = explode(',', $dataSFM);
//
//                                    $dataSFM = base64_decode($dataSFM);
//                                    //echo storage_path()."/image.png";die;
//                                    file_put_contents(storage_path() . "/imageUserFM" . date("mdYH") . ".png", $dataSFM);
//                                    $objDrawingPType = new PHPExcel_Worksheet_Drawing();
//                                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
//                                    $objDrawingPType->setName("Signature");
//                                    $objDrawingPType->setPath(storage_path() . "/imageUserFM" . date("mdYH") . ".png");
//                                    $objDrawingPType->setCoordinates("J" . $signature_row);
//                                    $objDrawingPType->setOffsetX(1);
//                                    $objDrawingPType->setOffsetY(5);
//                                    $objDrawingPType->setWidthAndHeight(100, 70);
//                                    $objDrawingPType->setResizeProportional(true);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getColumnDimension('D')
//                    ->setWidth(40);
                                    /* $workbook->setActiveSheetIndex($sheetIndex)
                                         ->getRowDimension($current_row)
                                         ->setRowHeight(80);*/
//                    unset($count_prev);
//                    $count_prev = array();
                                    /*$nexrRow= $signature_row+1;
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("I{$nexrRow}", $CM_date);
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$nexrRow}", $FM_date);*/
//                                } else {
//                                    //$current_row += 2;
//                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$current_row}", "NA");
//                                    $workbook->setActiveSheetIndex($sheetIndex)
//                                        ->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//                                    $workbook->setActiveSheetIndex($sheetIndex)
//                                        ->getStyle("J{$current_row}")
//                                        ->getAlignment()
//                                        ->setVertical(Alignment::VERTICAL_CENTER);
//                                }
                                } else {
                                    //$current_row += 2;
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "NA");
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("$column{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("$column{$current_row}")
                                        ->getAlignment()
                                        ->setVertical(Alignment::VERTICAL_CENTER);
//                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$current_row}", "NA");
//                                    $workbook->setActiveSheetIndex($sheetIndex)
//                                        ->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//                                    $workbook->setActiveSheetIndex($sheetIndex)
//                                        ->getStyle("J{$current_row}")
//                                        ->getAlignment()
//                                        ->setVertical(Alignment::VERTICAL_CENTER);
                                }
                                $nextl= $signature_row+1;
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", $level_sign["sign_date"]);
                            }
                        }else{
                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$physician_row}", "NA");
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("H{$physician_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("H{$physician_row}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_CENTER);
                        }
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}:F{$current_row}")->applyFromArray($this->all_border);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->cell_top_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}:N{$current_row}")->applyFromArray($this->cell_bottom_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:N{$current_row}")
                            ->getAlignment()
                            ->setVertical(Alignment::VERTICAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$current_row}", "TOTAL");
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$current_row}", $contract_data["sum_worked_hour"]);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "-");
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$current_row}", $contract_data["total_calculated_payment"]);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("G{$current_row}")->applyFromArray($this->cell_left_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", $contract_data["amount_paid"]);
                        $grant_total = $grant_total+$contract_data["amount_paid"];
                        //$workbook->setActiveSheetIndex($sheetIndex)->mergeCells("H{$current_row}:H{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("K{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("L{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("M{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$current_row}", $Physician_date);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("H{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("I{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("J{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("K{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("L{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("M{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("N{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:G{$current_row}")->getNumberFormat()->setFormatCode('$#,##0.00');
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getRowDimension($current_row)
                            ->setRowHeight(30);
                        /*if(count($contract_data['contract_invoice_notes']) > 0 || count($contract_data['physician_invoice_notes']) > 0) {
                            for ($n = 1; $n <= (count($contract_data['contract_invoice_notes']) > count($contract_data['physician_invoice_notes']) ? count($contract_data['contract_invoice_notes']) : count($contract_data['physician_invoice_notes'])); $n++) {
                                if($n != 1) {
                                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:N{$current_row}");
                                }
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:B{$current_row}")
                                    ->getAlignment()
                                    ->setVertical(Alignment::VERTICAL_CENTER);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}")->getAlignment()->setWrapText(true);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}")->getAlignment()->setWrapText(true);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", isset($contract_data['contract_invoice_notes'][$n]) ? $contract_data['contract_invoice_notes'][$n] : '');
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$current_row}", isset($contract_data['physician_invoice_notes'][$n]) ? $contract_data['physician_invoice_notes'][$n] : '');
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:B{$current_row}")->applyFromArray($this->all_border);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}")->applyFromArray($this->cell_right_border_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                                if($n == (count($contract_data['contract_invoice_notes']) > count($contract_data['physician_invoice_notes']) ? count($contract_data['contract_invoice_notes']) : count($contract_data['physician_invoice_notes']))) {
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->cell_bottom_border_style);
                                }
                                $current_row++;
                            }
                        }else {
                            $current_row++;
                        }*/
                        $current_row++;
                    }
                    //$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $practice["name"]);
                }
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}:F{$current_row}")->applyFromArray($this->all_border);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->applyFromArray($this->cell_top_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}:N{$current_row}")->applyFromArray($this->cell_bottom_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:N{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$current_row}", "Practice TOTAL");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}")->applyFromArray($this->cell_left_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", $grant_total);
                //$workbook->setActiveSheetIndex($sheetIndex)->mergeCells("H{$current_row}:H{$current_row}");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("I{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("J{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("K{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("K{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("L{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("L{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("M{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("M{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")->getAlignment()->setWrapText(true);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->getNumberFormat()->setFormatCode('$#,##0.00');
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getRowDimension($current_row)
                    ->setRowHeight(30);
                $current_row++;
            }

            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}:B{$current_row}")->applyFromArray($this->cell_top_border_style);
            /* remove for contract manager level signature start*/
            /*$workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
            $current_row++;*/
            /*$workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}:G{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "Approved By:");
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", "Approved By:");
            $current_row++;*/
            /*$workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
            if ($CM_signature != "NA" && $CM_signature != "") {
                $dataSCM = "data:image/png;base64," . $CM_signature;
                list($type, $dataSCM) = explode(';', $dataSCM);
                list(, $dataSCM) = explode(',', $dataSCM);

                $dataSCM = base64_decode($dataSCM);
                //echo storage_path()."/image.png";die;
                file_put_contents(storage_path() . "/imageUserCM" . date("mdYH") . ".png", $dataSCM);
                $objDrawingPType = new PHPExcel_Worksheet_Drawing();
                $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                $objDrawingPType->setName("Signature");
                $objDrawingPType->setPath(storage_path() . "/imageUserCM" . date("mdYH") . ".png");
                $objDrawingPType->setCoordinates("F" . $current_row);
                $objDrawingPType->setOffsetX(1);
                $objDrawingPType->setOffsetY(5);
                $objDrawingPType->setWidthAndHeight(350, 110);
                $objDrawingPType->setResizeProportional(true);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getColumnDimension('D')
//                    ->setWidth(40);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getRowDimension($current_row)
                    ->setRowHeight(80);
//                    unset($count_prev);
//                    $count_prev = array();


                if ($FM_signature != "NA" && $FM_signature != "") {
                    $dataSFM = "data:image/png;base64," . $FM_signature;
                    list($type, $dataSFM) = explode(';', $dataSFM);
                    list(, $dataSFM) = explode(',', $dataSFM);

                    $dataSFM = base64_decode($dataSFM);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserFM" . date("mdYH") . ".png", $dataSFM);
                    $objDrawingPType = new PHPExcel_Worksheet_Drawing();
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature");
                    $objDrawingPType->setPath(storage_path() . "/imageUserFM" . date("mdYH") . ".png");
                    $objDrawingPType->setCoordinates("H" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(350, 110);
                    $objDrawingPType->setResizeProportional(true);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getColumnDimension('D')
//                    ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(80);
//                    unset($count_prev);
//                    $count_prev = array();
                    $nexrRow= $current_row+1;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$nexrRow}", $CM_date);
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$nexrRow}", $FM_date);
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("H{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
            } else {
                //$current_row += 2;
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("F{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }*/
            /*$nexrRow= $current_row+1;
//            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("F{$nexrRow}:F{$nexrRow}");
//            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("H{$nexrRow}:H{$nexrRow}");
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("F{$nexrRow}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("H{$nexrRow}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}:E{$nexrRow}")->applyFromArray($this->header_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("G{$current_row}:G{$nexrRow}")->applyFromArray($this->header_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}:E{$nexrRow}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("G{$current_row}:G{$nexrRow}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}:J{$current_row}")->applyFromArray($this->cell_top_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$nexrRow}:H{$nexrRow}")->applyFromArray($this->cell_bottom_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("G{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}:J{$current_row}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:E{$nexrRow}");
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "Contract Manager");
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("G{$current_row}:G{$nexrRow}");
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", "Financial Manager");
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("H{$current_row}:H{$nexrRow}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("I{$current_row}:I{$nexrRow}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("J{$current_row}:J{$nexrRow}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("F{$nexrRow}")->applyFromArray($this->cell_top_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("H{$nexrRow}")->applyFromArray($this->cell_top_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$nexrRow}")->applyFromArray($this->cell_left_border_style);*/
            /* removal end*/
        }


        $workbook->removeSheetByIndex(0);
        if ($workbook->getSheetCount() == 0) {
            $this->failure("breakdowns.logs_unavailable");
            return;
        }

        $report_path = hospital_report_path($hospital);
        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        // $report_filename = "Interfaced_invoices_".date('mdYhis').".xlsx";
        $report_filename = "Interfaced_invoices_" . $hospital->name . "_" . date('mdY') . "_" . str_replace(":", "", $timestamp) . "_" . $timezone . ".xlsx";

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $hospital_invoice = new HospitalInvoice;
        $hospital_invoice->hospital_id = $hospital->id;
        $hospital_invoice->filename = $report_filename;
        $hospital_invoice->contracttype_id = $arguments->contract_type;
        $hospital_invoice->period = mysql_date(date("m/d/Y"));
        $hospital_invoice->last_invoice_no = $last_invoice_no;
        $hospital_invoice->save();

        return $this->success('hospitals.generate_invoice_success', $hospital_invoice->id, $hospital_invoice->filename);
    }

    protected function parseArguments()
    {
        $result = new \stdClass();
        $result->hospital_id = $this->argument('hospital');
        $result->contract_type = $this->argument('contract_type');
        $result->practice_ids = $this->argument('practices');
        $result->practices = explode(',', $result->practice_ids);
        $result->data = $this->argument('data');
        $result->agreements = parent::parseArguments();
        return $result;
    }

    protected function getArguments()
    {
        return [
            ["hospital", InputArgument::REQUIRED, "The hospital ID."],
            ["contract_type", InputArgument::REQUIRED, "The contract type."],
            ["practices", InputArgument::REQUIRED, "The practice IDs."],
            ["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
            ["months", InputArgument::REQUIRED, "The agreement months."],
            ["data", InputArgument::REQUIRED, "No Logs for display."],
            ["localtimeZone", InputArgument::REQUIRED, "The agreement localtimeZone."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}