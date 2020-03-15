<?php
namespace app\common;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Excel表格
 * Tjun
 * //https://packagist.org/packages/phpoffice/phpspreadsheet 表格生成
 */
class Excel
{
    /**
     * 导出Excel
     * @param string $expTitle 文件名称
     * @param array $expCellName 表头
     * @param array $expTableData 数据
     * @param array $mergeCells 合并单元格
     * @author Jun
     * @alter Jun
     */
    public static function export($expTitle = '', $cellName = [], $expTableData = [], $mergeCells = [])
    {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=$expTitle.xlsx");
        header("Content-Transfer-Encoding:binary");


        //https://blog.csdn.net/gc258_2767_qq/article/details/81003656 所有设置
        $cellName_arr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'EC', 'ED', 'EE', 'EF', 'EG', 'EH', 'EI', 'EJ', 'EK', 'EL', 'EM', 'EN', 'EO', 'EP', 'EQ', 'ER', 'ES', 'ET', 'EU', 'EV', 'EW', 'EX', 'EY', 'EZ', 'FA', 'FB', 'FC', 'FD', 'FE', 'FF', 'FG', 'FH', 'FI', 'FJ', 'FK', 'FL', 'FM', 'FN', 'FO', 'FP', 'FQ', 'FR', 'FS', 'FT', 'FU', 'FV', 'FW', 'FX', 'FY', 'FZ', 'GA', 'GB', 'GC', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GJ', 'GK', 'GL', 'GM', 'GN', 'GO', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GV', 'GW', 'GX', 'GY', 'GZ', 'HA', 'HB', 'HC', 'HD', 'HE', 'HF', 'HG', 'HH', 'HI', 'HJ', 'HK', 'HL', 'HM', 'HN', 'HO', 'HP', 'HQ', 'HR', 'HS', 'HT', 'HU', 'HV', 'HW', 'HX', 'HY', 'HZ'];
        $lie_count= count($cellName);
        $lie_count_b=  $cellName_arr[$lie_count-1];
 
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        // 设置表头0就是左下第一份表
        $biaotou = $spreadsheet->setActiveSheetIndex(0);

        foreach ($cellName as $k => $v) {
            //设置表头名
            $biaotou->setCellValue($cellName_arr[$k] . '1', $v[2]);
            //根据内容设置单元格宽度 如果是auto 自动识别长度+5
            $cellWidth = $cellName[$k][1] == 'auto' ? strlen($cellName[$k][2]) + 5 : $cellName[$k][1];
            $spreadsheet->getActiveSheet()->getColumnDimension($cellName_arr[$k])->setWidth($cellWidth);
            // 设置数字转换非科学显示 比如3500000000000000
            $spreadsheet->getActiveSheet()->getStyle($cellName_arr[$k])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
        }
        //副表名 非文件名
        $spreadsheet->getActiveSheet()->setTitle("爱果经销商管理");
        $i = 2;
        foreach ($expTableData as $rs) {
            // 添加数据
            foreach ($cellName as $k => $v) {
                //如果是#的时候就自动根据123 编号
                $内容 = $v[0] == '#' ? $i - 1 : $rs[$v[0]];

                $spreadsheet->getActiveSheet()->setCellValue($cellName_arr[$k] . $i, $内容);
            }

            $i++;
        }
        //在工作表上设置自动筛选区域
        $spreadsheet->getActiveSheet()->setAutoFilter('A1:'.$lie_count_b.'1');
        //设置剧中
        // $spreadsheet->getActiveSheet()->getStyle('A1:O' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        //设置靠左边
        $spreadsheet->getActiveSheet()->getStyle('C2:C' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        /**   颜色填充 自己加入*/
        //设置文字颜色
        $spreadsheet->getActiveSheet()->getStyle('A1:'.$lie_count_b.'1')->getFont()->getColor()->setARGB("#000");
        //设置背景颜色
        $spreadsheet->getActiveSheet()->getStyle('A1:'.$lie_count_b.'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('#0cedffb');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        return self::exportExcel($spreadsheet, 'xls', $expTitle);
    }
    /**
     * 导出Excel 下载
     * @param  object $spreadsheet  数据
     * @param  string $format       格式:excel2003 = xls, excel2007 = xlsx
     * @param  string $savename     保存的文件名
     * @return filedownload         浏览器下载
     */
    public static function exportExcel($spreadsheet, $format = 'xls', $savename = 'export')
    {
        if (!$spreadsheet) {
            return false;
        }
        if ($format == 'xls') {
            //输出Excel03版本
            //header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }
        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');

        header("Pragma: public");
        header("Expires: 0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header("Content-Transfer-Encoding:binary");
        $writer = new $class($spreadsheet);
        $filePath = env('runtime_path') . "temp/" . time() . microtime(true) . ".tmp";
        $writer->save($filePath);
        readfile($filePath);
        unlink($filePath);
    }
}
