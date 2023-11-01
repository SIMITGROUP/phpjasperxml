<?php
namespace simitsdk\phpjasperxml\Exports;

use Throwable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;

class Xlsx_driver implements ExportInterface
{
    use \Simitsdk\phpjasperxml\Tools\Toolbox;
    protected bool $debugband=false;
    protected Spreadsheet $spreadsheet;    
    protected $sheet;
    protected array $excelcols=[];
    protected array $excelrows=[];
    protected bool $debug = false;
    protected int $x=0;
    protected int $y=0;
    protected int $lastY=0;
    protected array $groupnames = [];
    protected Cell|null $cell;
    protected Style $cellrangestyle;
    protected int $columnWidth = 0;
    protected int $columnCount = 0;
    public bool $islastrow = false;
    protected object $parentobj ;
    protected object $drawtarget ;
    protected int $currentRowNo = 0;
    protected array $groups = [];
    protected array $bands=[];
    protected array $elements=[];
    protected array $supportedelements = ['staticText','textField'];
    protected string $defaultfont='helvetica';

    public function __construct(array $prop)
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();        
    }
    public function export(string $filename='')
    {
        $filename="MYFILE";
        for($i=1;$i<=$this->lastY;$i++)
        {
            $this->sheet->getRowDimension($i)->setRowHeight(-1);
        }
        if(!$this->debug)                
        {
            $writer = new Xlsx($this->spreadsheet);
            header('Content-Type: application/x-www-form-urlencoded');
            header("Content-disposition: INLINE; filename=\"".$filename.".xlsx\"");        
            $writer->save('php://output');
        }
        
        die;
        
    }
    
    public function mergeCell(int $x1, int $y1, int $x2, int $y2)
    {
            
        $col1 = $this->excelcols[$x1] + 1;
        $col2 = $this->excelcols[$x2] ;
        $row1 = $this->excelrows[$y1]+1+$this->lastY;
        $row2 = $this->excelrows[$y2]+$this->lastY;        
        $this->sheet->mergeCellsByColumnAndRow($col1,$row1,$col2,$row2);
        
    }
    
    public function getStyleByXYRange(int $x1, int $y1, int $x2, int $y2)
    {
       
        $col1 = $this->excelcols[$x1] + 1;
        $col2 = $this->excelcols[$x2] ;
        $row1 = $this->excelrows[$y1]+1+$this->lastY;
        $row2 = $this->excelrows[$y2]+$this->lastY;        

        return  $this->sheet->getStyleByColumnAndRow($col1,$row1,$col2,$row2);                
        // $col1 = $this->excelcols[$x1] + 1;
        // $col2 = $this->excelcols[$x2] ;
        // $row1 = $this->excelrows[$y1]+1+$this->lastY;
        // $row2 = $this->excelrows[$y2]+$this->lastY;        
        // $this->sheet->mergeCellsByColumnAndRow($col1,$row1,$col2,$row2);
        
    }

    protected function getCellByXY(int $x,int $y,array $prop)
    {
        
        $xcol = $this->excelcols[$x] + 1;
        if(isset($this->excelrows[$y]))
        {
            $ycol = $this->excelrows[$y]+1+$this->lastY;
        }
        
        $cell = $this->sheet->getCellByColumnAndRow($xcol,$ycol);
        return $cell;
    
    }


    public function setData(array $data)
    {

    }

    public function defineBands(array $bands,array $elements,array $groups)
    {
        
        $this->bands = $bands;        
        $this->elements = $elements;              
        $this->groups = $groups;
        foreach($groups as $gname=>$gsetting)
        {
            array_push($this->groupnames,$gname);
        }
        

        //loop all elements, define spreadsheet column (not jaspereport column)
        $cols=[0];
        foreach($this->elements as $bandname => $elements)
        {
            foreach($elements as $uuid => $el)
            {
                if(in_array($el['elementtype'],$this->supportedelements))
                {
                    $x1 = (int)$el['x'];
                    $x2 = $x1 + (int)$el['width'];
                    $cols[] = $x1;
                    $cols[] = $x2;
                }                
            }
        }
        $cols=array_unique($cols);
        sort($cols);
        $excelcols=[];
        foreach($cols as $index => $c)
        {
            $excelcols[$c]=$index;
        }
        $this->excelcols = $excelcols;
        // $this->console($excelcols);
        //die;

    }
    public function defineColumns(int $columnCount,mixed $columnWidth)
    {
        $this->columnWidth = $columnWidth;
        $this->columnCount = $columnCount;
    }
    //bands

    public function prepareBand(string $bandname):array
    {
        if($bandname=='background')
        {
            $offsets=['x'=>0,'y'=>0];
        }
        else
        {
            $setting = $this->bands[$bandname];
            $rows=[0];
            $excelrows=[];
            if(isset($this->elements[$bandname]))
            {
                foreach($this->elements[$bandname] as $uuid => $element)
                {
                    $elementtype = $element['elementtype'];
                    
                    if(in_array($elementtype,$this->supportedelements))
                    {
                        $y1=(int)$element['y'];
                        $y2=$y1+(int)$element['height'];
                        $rows[] = $y1;
                        $rows[] = $y2;
                    }
                }
                $rows=array_unique($rows);
                sort($rows);
                
                foreach($rows as $index => $r)
                {
                    $excelrows[$r]=$index;
                }

            }
            
            $this->excelrows = $excelrows;    
            // $this->console($bandname)       ;
            // $this->console($this->excelrows);
            $offsets=['x'=>0,'y'=>0];
        }
        
        
        return $offsets;
    }

    public function endBand(string $bandname)
    {
        if(count($this->excelrows)>0)
        {
            $lastrowkey = array_key_last($this->excelrows);
            $this->lastY += $this->excelrows[$lastrowkey] ;
            // $this->console("lastrowkey $lastrowkey");
            // $this->console($this->excelrows);
        }
        else
        {

        }
        
        
    }

    public function prepareColumn()
    {

    }
    public function draw_background()
    {

    }
    public function draw_title()
    {

    }
    public function draw_pageHeader()
    {

    }
    public function draw_columnHeader()
    {

    }
    // public function draw_group(string $bandname);
    public function draw_detail(string $detailbandname)
    {

    }
    public function draw_columnFooter()
    {

    }
    public function draw_summary()
    {

    }
    public function draw_lastPageFooter()
    {
        
    }
    public function draw_noData()
    {
        
    }
    //draw elements
    // public function drawElement(string $uuid, array $prop,int $offsetx,int $offsety);
    public function draw_line(string $uuid,array $prop)
    {
        
    }
    
    public function draw_rectangle(string $uuid,array $prop)
    {
        
    }
    public function draw_ellipse(string $uuid,array $prop)
    {
    }
    public function draw_break(string $uuid,array $prop)
    {
        
    }
    public function draw_staticText(string $uuid,array $prop,bool $isTextField=false)
    {        
        
        if($isTextField==true)
        {
            $value=$prop['textFieldExpression'];
        }
        else
        {
            $value=$prop['text'];
        }
        if(gettype($this->cell)!='NULL' )
        {
            $this->cell->setValue($value);            
            $pattern = $prop['pattern']??'';
            $prop['fontName'] = $prop['fontName']?? $this->defaultfont;
            $fontName=strtolower($prop['fontName']);        
            $fontsize = isset($prop['size']) ? $prop['size'] : 8;    
            $forecolor = str_replace('#','', $prop['forecolor']??'');
            $backcolor = str_replace('#','', $prop['backcolor']??'FFFFFF');
            $fillsetting=[];
            $prop['mode'] = $prop['mode']?? 'Transparent';        
            if($prop['mode'] == 'Opaque')
            {
                $fillsetting = [
                    'fillType'=>Fill::FILL_SOLID,
                    'color'=>['rgb'=>$backcolor]    
                ];    
            }
            $borders = [];

            $borders = $this->getBorderStyles($prop);
            
            $fontsetting=[
                'name'=>$fontName,
                'size'=>$fontsize
            ];
            
            $fontsetting['color'] =  ['rgb' => $forecolor ];

            if(!empty($pattern))
            {
                $this->cell->getStyle()->getNumberFormat()->setFormatCode($pattern);
            }
            $this->cell->getStyle()->getAlignment()->setWrapText(true);
            // setWrapText(true);
            $endx = $this->x + $prop['width'];
            $endy = $this->y + $prop['height'];
            $this->mergeCell($this->x,$this->y,$endx,$endy);
            $rangecell = $this->getStyleByXYRange($this->x,$this->y,$endx,$endy);;
            
            if(isset($prop['isBold']) && $prop['isBold']=='true' )
            {
                $fontsetting['bold']=true;
            }
            if(isset($prop['isItalic']) && $prop['isItalic']=='true' )
            {
                $fontsetting['italic']=true;
            }
            if(isset($prop['isUnderline']) && $prop['isUnderline']=='true' )
            {
                $fontsetting['underline']=true;
            }
            if(isset($prop['isStrikeThrough']) && $prop['isStrikeThrough']=='true' )
            {
                $fontsetting['strikethrough']=true;
            }
            
            $styleArray = [
                'font'  => $fontsetting,
                'fill' => $fillsetting,
                'borders' => $borders
            ];
                // $phpExcel->getActiveSheet()->getStyle('A3')->applyFromArray($styleArray);

    
            // $this->cell->getStyle()->getFont()->setName($fontName);
            // $this->cell->getStyle()->getFont()->setSize($fontsize);
            // $this->cell->getStyle()->getFont()->setColor($color);

            $rangecell->applyFromArray($styleArray);            
            
        }        
    }


    public function getBorderStyles(array $prop=[]): array
    {
        $style=[];                
        $borderstyles=[];
        $borders=['T'=>'top','L'=>'left','R'=>'right','B'=>'bottom'];
        foreach($borders as $borderkey=>$bordername)
        {
            $width_name= $bordername.'PenlineWidth';
            $color_name= $bordername.'PenlineColor';
            $style_name= $bordername.'PenlineStyle';

            
            if(isset($prop[$width_name]) && $prop[$width_name]>0)
            {
                $linewidth = $prop[$width_name];
                $prop[$style_name]=$prop[$style_name]??'';
                // echo $prop[$style_name];die;
                switch($prop[$style_name])
                {
                    case "Dotted":
                        $style=Border::BORDER_DOTTED;
                    break;
                    case "Dashed":
                        $style=Border::BORDER_DASHED;
                        break;
                    default:
                        if($linewidth<=0)
                        {
                            $style=Border::BORDER_THIN;
                        }
                        else if($linewidth<=0.25)
                        {
                            $style=Border::BORDER_HAIR;
                        }
                        else if($linewidth<=0.5)
                        {
                            $style=Border::BORDER_THIN;
                        }
                        else if($linewidth<=1.5)
                        {
                            $style=Border::BORDER_MEDIUM;
                        }
                        else if($linewidth>1.5)
                        {
                            $style=Border::BORDER_THICK;
                        }
                        else
                        {
                            $style=Border::BORDER_HAIR;
                        }
                    break;
                }
                // $borderstyle
                $prop[$color_name]=str_replace('#','',$prop[$color_name]??'');
                // $style[$borderkey] = $this->getLineStyle( $prop[$style_name],$prop[$width_name],$prop[$color_name]);
                $borderstyles[$bordername]=[
                    'color'=>['rgb'=>$prop[$color_name]],
                    'borderStyle'=>$style,
                ];
            }

        }

            // [
            //     'bottom'=>[
            //         'borderStyle'=>Border::BORDER_THIN,
            //         'color'=>['rgb'=>'121212']
            //     ],
            //     'top'=>[
            //         'borderStyle'=>Border::BORDER_THIN,
            //         'color'=>['rgb'=>'505020']
            //     ],
            //     'left'=>[
            //         'borderStyle'=>Border::BORDER_THIN,
            //         'color'=>['rgb'=>'cccccc']
            //     ],
            //     'right'=>[
            //         'borderStyle'=>Border::BORDER_THIN,
            //         'color'=>['rgb'=>'333333']
            //     ],
            // ];
        return $borderstyles;
    }


    
    public function draw_textField(string $uuid,array $prop)
    {
        $this->draw_staticText($uuid,$prop,true);
    }
    public function draw_image(string $uuid,array $prop)
    {

    }
    
    //others
    public function PageNo():int
    {
        return 1;   
    }
    public function ColumnNo():int
    {
        return 1;
    }
    public function columnCount(): int
    {
        return 1;
    }
    public function setRowNumber(int $no)
    {
        $this->currentRowNo=$no;    
    }
    public function AddPage()
    {

    }
    public function setPosition(int $x,int $y, array $prop)
    {
        if($prop['band'] !='background')
        {        
            $this->x = $prop['x'];
            $this->y = $prop['y'];
            $this->cell = $this->getCellByXY($this->x,$this->y,$prop);
            
        }
        else
        {
            $this->cell = null;
        }
    }

    public function setParentObj(object $parentobj)
    {
        $this->parentobj = $parentobj;
        $this->drawtarget = $parentobj;
    }
    public function supportSubReport(): bool
    {
        return false;
    }
}