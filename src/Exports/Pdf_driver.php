<?php
namespace simitsdk\phpjasperxml\Exports;
use Com\Tecnick\Color\Model\Rgb;
use Throwable;
use TCPDF;
use TCPDF_FONT_DATA;
use TCPDF_FONTS;
use TCPDF_STATIC;
// use \tecnickcom\tcpdf;

class Pdf_driver extends TCPDF implements ExportInterface
{
    use \Simitsdk\phpjasperxml\Tools\Toolbox;
    protected bool $debugband=false;
    protected array $pagecolumnoccupation=[];
    protected array $pagesettings=[];
    protected array $bands=[];
    protected string $lastdetailband='';
    protected array $elements=[];    
    public array $groups=[];
    public array $groupnames=[];
    protected int $currentY=0;
    protected int $lastBandEndY=0;
    protected int $lastColumnEndX=0;
    protected int $maxY=0;
    protected int $columnno=0;
    protected int $columnWidth;
    protected int $columnCount;
    protected string $defaultfont='helvetica';
    protected int $currentRowNo=0;
    protected string $lastBand='';
    protected string $groupbandprefix = 'report_group_';
    protected int $printbandcount=0;
    public bool $islastrow = false;
    protected array $rows = [];
    protected string $balancetext='';
    protected $defaultDetailbeginY=0;
    protected $pageOffSetY=0;
    protected $limitY=0;
    protected $limitY_last=0;
    protected $longtextrepeatcount = 0 ;
    protected $parentobj = null;
    protected $drawtarget = null;
    protected $offsetby=0;
    
    public function __construct($prop)
    {           
        // print_r($prop);
        $this->pagesettings=$prop;
        // $this->console($prop);
        $name = $prop['name'];
        $title = $prop['title'];
        $subject = $prop['subject'];
        $author = $prop['author'];
        $creator = $prop['creator'];
        $keywords = $prop['keywords'];

        $description = $prop['com.jaspersoft.studio.report.description']??'';
        // $fontfolder = sys_get_temp_dir().'/phpjasperxml/fonts';        
        $orientation = isset($prop['orientation'])? $this->left($prop['orientation'],1):'P';
        $unit='pt';
        $format=[(int)$prop['pageWidth'],(int)$prop['pageHeight']];
        $encoding='UTF-8';
        parent::__construct($orientation,$unit,$format,$encoding);  
        $this->SetAutoPageBreak(false);

        $this->setPrintHeader(false);        
        $this->setPrintFooter(false);
        $this->SetCreator($creator);
        $this->SetAuthor($author);
        $this->SetTitle($title);
        $this->SetSubject($subject);
        $this->SetKeywords($keywords);
        
        $this->drawtarget = $this;
    }

    public function setLastBandEndY(int $lastBandEndY)
    {
        $this->lastBandEndY=$lastBandEndY;
    }
    public function getLastBandEndY()
    {
        return $this->lastBandEndY;
    }

    public function NewPage()
    {
        $this->AddPage();        
    }
    
    public function isFontExists(string $fontname): bool
    {
        return true;
    }
    public function nextColumn()
    {
        $this->columnno++;
        // echo "\n nextColumn $this->columnno\n";
    }
    public function getColumnNo()
    {
        return $this->columnno;
    }
    public function setColumnNo(int $columnno)
    {        
        $this->columnno = $columnno;
        // echo "\nsetColumnNo $this->columnno\n";
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
        foreach($bands as $b=>$setting)
        {
            if(str_contains($b,'detail'))
            {
                $this->lastdetailband = $b;
            }
        }  
        $page = $this->pagesettings;              
        $this->defaultDetailbeginY = $page['topMargin'] + $this->bands['pageHeader']['height'] + $this->bands['columnHeader']['height'];
        $this->limitY = $page['pageHeight'] - $page['bottomMargin'] - $this->bands['columnFooter']['height'] - $this->bands['pageFooter']['height'];
        $laspageheight = $this->bands['lastPageFooter']['height'] >0 ? $this->bands['lastPageFooter']['height'] :$this->bands['pageFooter']['height'] ;
        $this->limitY_last = $page['pageHeight'] - $page['bottomMargin'] - $this->bands['columnFooter']['height'] - $laspageheight;
    }
    public function getMargin(string $location)
    {
        return $this->pagesettings[$location.'Margin'];
    }
    public function setData(array $data)
    {
        $this->rows = $data;
    }
    public function getBandHeight(string $bandname):int
    {
        return isset($this->bands[$bandname]['height']) ? $this->bands[$bandname]['height'] : 0;
    }
    public function ColumnNo():int
    {
        return $this->columnno;
    }
    public function PageNo():int
    {
        return parent::PageNo();
    }
    public function export(string $filename='')
    {     
        // $this->console($this->pagecolumnoccupation);
        if(!empty($filename))
        {
            $this->Output($filename,'F');
        }
        else
        {
            // echo 'asdad';
            $filename='sample.pdf';
            $this->Output($filename,'I');
        }
        
        // echo $filename;
    }

    //*********************************************** draw elements ***************************************************/    
    //*********************************************** draw elements ***************************************************/    
    //*********************************************** draw elements ***************************************************/    
    
    public function draw_line(string $uuid,array $prop)
    {
        $x1=$this->GetX();
        $y1=$this->GetY();
        $x2=$x1+$prop['width'];
        $y2=$y1+$prop['height'];
        $forecolor = $this->convertColorStrToRGB($prop['lineColor']??'');        
        $dash="";
        $lineWidth = $prop['lineWidth']?? '1';
        $prop["lineStyle"]=$prop["lineStyle"]?? '';
        switch($prop["lineStyle"])
        {
            case "Dotted":
                $dash=sprintf("%d,%d",$lineWidth,$lineWidth);
            break;
            case "Dashed":
                $dash=sprintf("%d,%d",$lineWidth*4,$lineWidth*2);
                break;
            default:
                $dash="";
            break;
        }
        
        $style=[
            'width'=> $lineWidth,
            'color'=>$forecolor,
            'dash'=>$dash,
            'cap'=>'butt',
            'join'=>'miter',
        ];
        $this->drawtarget->Line($x1,$y1,$x2,$y2,$style);        
    }

    public function draw_image(string $uuid,array $prop)
    {
        $x=$this->GetX();
        $y=$this->GetY();
        $height = $prop['height'];
        $width = $prop['width'];
        $imageExpression = $prop['imageExpression'];//str_replace('"','',$prop['imageExpression']);
        // $border=['TLBR'];
        $scaleImage = $prop['scaleImage']?? 'RetainShape';
        $hAlign = $prop['hAlign']?? 'Left';
        $hAlign =  $this->left($hAlign,1);        
        $vAlign = $prop['vAlign']?? 'Top';
        $vAlign =  $this->left($vAlign,1);        
        $fitbox = $hAlign.$vAlign;
        // $fitbox = '';

        $link = $prop['hyperlinkReferenceExpression']?? '';
        // $this->console($link);
        $border = $this->getBorderStyles($prop,1);
        // $this->console("draw image, resize: $resize ");
        $imageh=$height;
        $imagew = $width;
        switch($scaleImage)
        {
            //if the dimensions of the actual image do not fit those specified for the image element that displays it, 
            //the image is forced to obey them and stretch itself so that it fits in the designated output area. It will be deformed if necessary.
            case 'FillFrame':
                $resize=1;
                $fitbox=false;
                break;
            //if the actual image does not fit into the image element, it can be adapted to those dimensions while keeping its original undeformed proportions.
            case 'RetainShape':
                $resize=true; 
                break;
                
            //if the actual image is larger than the image element size, it will be cut off so that it keeps its original resolution, 
            //and only the region that fits the specified size will be displayed.
            case 'Clip':
                $resize=false;          
                die("image $uuid use scaleImage method Clip which is not supported");      
            break;            
            //the image can be stretched vertically to match the actual image height, while preserving the declared width of the image element.
            case 'RealHeight':
                $resize=true;
                // $imagew=0;
                die("image $uuid use scaleImage method RealHeight which is not supported");
                break;
            //the image can be stretched vertically to match the actual image height, while adjusting the width of the image element to match the actual image width.
            case 'RealSize':
                // $resize=true;
                die("image $uuid use scaleImage method RealSize which is not supported");
                break;
            default:
                $resize=true;
                $imagew=0;
            break;
        }
        $palign=false;
        $ismask=false;
        $imgmask = false;
        $dpi = 300;
        $align = '';
        $imagetype='';
        if($this->left($imageExpression,1) =='@')
        {
            // echo "\n\n".$imageExpression."\n\n";
            $this->drawtarget->Image( $imageExpression,$x,$y,$imagew,$imageh,$imagetype,$link);
        }
        else
        {
            $this->drawtarget->Image( $imageExpression,$x,$y,$imagew,$imageh,$imagetype,$link,$align,$resize,$dpi,$palign,$ismask,$imgmask,$border,$fitbox);
        }
        
        
    }

    public function draw_barcode(string $uuid,array $prop)
    {
        
        $barcodetype = str_replace('Code128','C128',$prop['barcodetype']);
        $barcodetype = str_replace('Code39 (Extended)','C39+',$barcodetype);
        $barcodetype = str_replace('Code39','C39',$barcodetype);
        $barcodetype = str_replace('USPSIntelligentMail','IMB',$barcodetype);
        $barcodetype = str_replace('USPS','IMB',$barcodetype);
        
        
        $prop['barcodetype'] = strtoupper($barcodetype);
        // $this->console("$uuid draw_barcode ".$prop['barcodetype']);
        // print_r($prop);
        // echo "<hr/>";
        switch($prop['barcodetype'])
        {
            case 'C39':    
            case 'C39+':    
            case 'CODABAR':
            case 'UPCA':
            case 'UPCE':
            case 'C128':    
            case 'C128A':    
            case 'C128B':
            case 'C128C':       
            case 'EAN13':
            case 'POSTNET':            
            case 'IMB':
            
                    $this->draw_barcode1D($uuid,$prop);
                break;
            case 'QRCODE':            
            case 'DATAMATRIX':            
            case 'PDF417':
                $this->draw_barcode2D($uuid,$prop);
            break;
            case 'USD3':
            case 'USD4':
            case 'NW7':
            case 'MONARCH':
            case 'BOOKLAND':
            case '3OF9':
            case '2OF7':
            case 'STD2OF5':
            case 'SSCC18':
            case 'EAN128':     
            case 'GLOBALTRADEOTEMNUMBER':
            case 'ROYALMAILCUSTOMER':
            case 'INT2OF5':
            case 'UCC128':
            default:
                $this->draw_unsupportedElement($uuid,$prop);
            break;
        }
        
    }
    public function draw_barcode1D(string $uuid,array $prop)
    {
        $code=$prop['codeExpression'];
        $barcodetype=$prop['barcodetype'];
        
        $x=$prop['x'];
        $y=$prop['y'];
        $w=$prop['width'];
        $h=$prop['height'];
        $x+=$w/2;
        $y+=$h/2;
        $xres=0.4;//$prop[''];
        // print_r($prop);
        // echo gettype($prop['drawText'])."<hr/>";
        $style=$style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => ($prop['drawText'] == 'true') ? true : false,
            'font' => $this->defaultfont,
            'fontsize' => 8,
            'stretchtext' => 4
        );
        $align='N';//$prop[''];
        try{
            $this->drawtarget->write1DBarcode($code, $barcodetype, $x , $y, $w, $h , $xres, $style , $align );
        }
        catch(Throwable $e)
        {
            $this->console("draw_barcode1D $uuid, $barcodetype value:  $code failed");
        }        
    }

    public function draw_barcode2D(string $uuid,array $prop)
    {
        $code=$prop['codeExpression'];
        $barcodetype=$prop['barcodetype'];
        if($barcodetype=='QRCODE' && isset($prop['errorCorrectionLevel']))
        {
            $barcodetype .=','.$prop['errorCorrectionLevel'];
        }
        // print_r($prop);
        // echo "<hr/>";
        $x=$prop['x'];
        $y=$prop['y'];
        $w=$prop['width'];
        $h=$prop['height'];
        $x+=$w/2;
        $y+=$h/2;
        $style = array(
            // 'border' => 2,
            'padding' => 'auto',
            // 'fgcolor' => array(0,0,255),
            // 'bgcolor' => array(255,255,64)
        );
        // $style=$style = array(
        //     'position' => '',
        //     'align' => 'C',
        //     'stretch' => false,
        //     'fitwidth' => true,
        //     'cellfitalign' => '',
        //     'border' => true,
        //     'hpadding' => 'auto',
        //     'vpadding' => 'auto',
        //     'fgcolor' => array(0,0,0),
        //     'bgcolor' => false, //array(255,255,255),
        //     'text' => true,
        //     'font' => 'helvetica',
        //     'fontsize' => 8,
        //     'stretchtext' => 4
        // );
        $align='N';//$prop[''];
        try{
            // $this->write1DBarcode($code, $barcodetype, $x , $y, $w, $h , $xres, $style , $align );
            $this->drawtarget->write2DBarcode($code, $barcodetype, $x, $y, $w, $h, $style, '');

        }
        catch(Throwable $e)
        {
            $this->console("draw_barcode1D $uuid, $barcodetype value:  $code failed");
        }        
    }

    public function draw_rectangle(string $uuid,array $prop)
    {
        $x=$this->GetX();
        $y=$this->GetY();
        $w = $prop['width'];
        $h = $prop['height'];
        $prop['forecolor'] = $prop['forecolor'] ??'';
        $prop['backcolor'] = $prop['backcolor'] ??'#FFFFFF';
        $lineColor = $prop['lineColor'] ?? $prop['forecolor'];        
        $backcolor = $this->convertColorStrToRGB($prop['backcolor']);                
        $radius=$prop['radius']??0;

        if(isset($prop['mode']) && $prop['mode'] == 'Transparent')
        {
            $style='';
        }
        else
        {
            $style='FD';
        }        
        
        
        
        $borderstyle = [ 'TBLR'=> $this->getBorderStyles($prop)];//$this->getLineStyle($lineStyle,$lineWidth,$lineColor) ];      
        // if(!empty($prop['radius']))
        // {
        //     print_r($prop);
        //     echo "<br/>=====<br/>";
        //     print_r($borderstyle);        
        //     echo "<hr/>";
        // }
        $this->SetLineStyle($borderstyle);
        $this->drawtarget->RoundedRect($x,$y,$w,$h,$radius,'1111',$style,$borderstyle,$backcolor);
    }
    public function draw_frame(string $uuid,array $prop)
    {
        $x=$this->GetX();
        $y=$this->GetY();
        $w = $prop['width'];
        $h = $prop['height'];
        $prop['forecolor'] = $prop['forecolor'] ??'';
        $prop['backcolor'] = $prop['backcolor'] ??'#FFFFFF';
        // $lineColor = $prop['lineColor'] ?? $prop['forecolor'];
        // $color = $this->convertColorStrToRGB($lineColor);
        $backcolor = $this->convertColorStrToRGB($prop['backcolor']);        
        $lineWidth = $prop['lineWidth']??0;
        // $lineStyle = $prop['lineStyle']??'';
        // $radius=$prop['radius']??0;

        if(isset($prop['mode']) && $prop['mode'] == 'Transparent')
        {
            $style='';
        }
        else
        {
            $style='FD';
        }        
        // $borderstyle =[ 'TBLR'=> $this->getLineStyle($lineStyle,$lineWidth,$lineColor) ];      
        // if($lineWidth>0)
        // {
            // $border =['BTLR'=> $this->getBorderStyles($prop)];
            $border = $this->getBorderStyles($prop,1);
            $this->drawtarget->Rect($x,$y,$w,$h,$style,$border,$backcolor);
        // }
        
    }
    public function draw_ellipse(string $uuid,array $prop)
    {
        
        $w = $prop['width'];
        $h = $prop['height'];
        $rx =   $w/2;
        $ry =   $h/2;
        $x=$this->GetX() + $rx;
        $y=$this->GetY() + $ry;
    
        $prop['forecolor'] = $prop['forecolor'] ??'';
        $prop['backcolor'] = $prop['backcolor'] ??'#FFFFFF';
        $lineWidth = $prop['lineWidth']??1;
        $lineStyle = $prop['lineStyle']??'';
        $lineColor = $prop['lineColor']?? $prop['forecolor'];
        $forecolor = $this->convertColorStrToRGB($prop['forecolor']);
        $backcolor = $this->convertColorStrToRGB($prop['backcolor']);
        
        // $fillcolors = [$backcolor['r'], $backcolor['g'],$backcolor['b']];
        $this->SetDrawColor($forecolor['r'], $forecolor['g'],$forecolor['b']);        
        $this->SetFillColor($backcolor['r'], $backcolor['g'],$backcolor['b']);
        if(isset($prop['mode']) && $prop['mode'] == 'Transparent')
        {
            $style='';
        }
        else
        {
            $style='FD';
        } 
        $ellipsestyle = $this->getLineStyle($lineStyle,$lineWidth,$lineColor) ;
        // if($prop['uuid']=='dc63d535-bbe8-4c76-8a7c-27b733429e22')
        // {
            // $this->console("draw_rectangle $x, $y");
            // print_r($prop);
            // print_r($ellipsestyle);
        // }
        $this->Ellipse($x,$y,$rx,$ry,0,0,360,$style,$ellipsestyle);
    }
    protected function updateLastEndY(int $y)
    {
        $page = $this->PageNo();
        $column = $this->getColumnNo();

    }
    protected function useFont(string $fontName, string $fontstyle, int $fontsize,mixed $text)
    {
        // \p{Common}
        // \p{Arabic}
        // \p{Armenian}
        // \p{Bengali}
        // \p{Bopomofo}
        // \p{Braille}
        // \p{Buhid}
        // \p{CanadianAboriginal}
        // \p{Cherokee}
        // \p{Cyrillic}
        // \p{Devanagari}
        // \p{Ethiopic}
        // \p{Georgian}
        // \p{Greek}
        // \p{Gujarati}
        // \p{Gurmukhi}
        // \p{Han}
        // \p{Hangul}
        // \p{Hanunoo}
        // \p{Hebrew}
        // \p{Hiragana}
        // \p{Inherited}
        // \p{Kannada}
        // \p{Katakana}
        // \p{Khmer}
        // \p{Lao}
        // \p{Latin}
        // \p{Limbu}
        // \p{Malayalam}
        // \p{Mongolian}
        // \p{Myanmar}
        // \p{Ogham}
        // \p{Oriya}
        // \p{Runic}
        // \p{Sinhala}
        // \p{Syriac}
        // \p{Tagalog}
        // \p{Tagbanwa}
        // \p{TaiLe}
        // \p{Tamil}
        // \p{Telugu}
        // \p{Thaana}
        // \p{Thai}
        // \p{Tibetan}
        // \p{Yi}

        $fontName = $fontName ?? $this->defaultfont;
        
        if(preg_match("/\p{Han}+/u", $text)){
            $fontName="cid0cs";
        }        
        else if(preg_match("/\p{Katakana}+/u", $text) || preg_match("/\p{Hiragana}+/u", $text)){
            $fontName="cid0jp";
        }            
        else if(preg_match("/\p{Hangul}+/u", $text)){
            $fontName="cid0kr";
        }                        
        $this->drawtarget->SetFont($fontName, $fontstyle, $fontsize);
    }
    public function draw_break(string $uuid,array $prop,mixed $callback=null)
    {
        if(gettype($callback)!='Null')
        {
            $callback();
            // $this->SetY(10);
        }
        // $this->AddPage();
        // $x=$this->GetX();
        // $y=$this->GetY();
        // $w = $prop['width'];
        // $h = $prop['height'];
        // $this->Rect($x,$y,$w,$h);
        // echo "\ndraw_rectangle  $uuid ".print_r($prop,true)."\n";
    }
    public function draw_staticText(string $uuid,array $prop,bool $isTextField=false,mixed $callback=null,$iscontinue=false)
    {
        // print_r($prop);
        // echo "<hr/>";
        $w=$prop['width'];
        $h=$prop['height'];       
        $x=$this->GetX();
        $y=$this->GetY();
        $beginingX=$x;
        $beginingY=$y;
        $beginingPage =$this->PageNo();
        $target = $this->drawtarget;
        $forecolor = $this->convertColorStrToRGB($prop['forecolor']??'');
        $target->SetTextColor($forecolor["r"],$forecolor["g"],$forecolor["b"]);                
        $backcolor = $this->convertColorStrToRGB($prop['backcolor']??'');
        $fill=false;
        $prop['mode'] = $prop['mode']?? 'Transparent';        
        if($prop['mode'] == 'Opaque')
        {
            $fill = $backcolor;
        }
        $target->SetFillColor($backcolor['r'], $backcolor['g'],$backcolor['b']);
        $halign = !empty($prop['textAlignment']) ? $prop['textAlignment'] : 'L';
        $halign = $this->left($halign,1);        
        $valign = !empty($prop['verticalAlignment']) ? $prop['verticalAlignment'] : 'Top'; 
        $valign = $this->left($valign,1);
        //B,C,T
        $markup = !empty($prop['markup']) ? $prop['markup'] : '';
        $prop['fontName'] = $prop['fontName']?? $this->defaultfont;
        $fontName=strtolower($prop['fontName']);        
        $fontstyle='';
        $fontstyle.= isset($prop['isBold']) && $prop['isBold']=='true' ? 'B':'';
        $fontstyle.= isset($prop['isItalic']) && $prop['isItalic']=='true' ? 'I':'';
        $fontstyle.= isset($prop['isUnderline']) && $prop['isUnderline']=='true'  ? 'U':'';
        $fontstyle.= isset($prop['isStrikeThrough']) && $prop['isStrikeThrough']=='true' ? 'D':'';
        $fontsize = isset($prop['size']) ? $prop['size'] : 8;                
        
        // echo $fontstyle."<br/>";
        // print_r($prop);
        // echo "<hr/>";

        //text can scale at detail and summary band only
        if(! (str_contains($prop['band'],'detail_')))
        {
            $prop['textAdjust']='';
        }
        
        $textAdjust = !empty($prop['textAdjust']) ? $prop['textAdjust'] : ''; 
        $border = $this->getBorderStyles($prop,1);
        
        
        
        if($isTextField)
        {
            $text = $prop['textFieldExpression'];
        }
        else
        {
            $text = $prop['text'];
        }

        $this->useFont($fontName, $fontstyle, $fontsize,$text);        
        $topPadding=$prop['topPadding']??0;
        $leftPadding=$prop['leftPadding']??0;
        $rightPadding=$prop['rightPadding']??0;
        $bottomPadding=$prop['bottomPadding']??0;
        $prop['markup']=$prop['markup']??'';
        $link = $prop['hyperlinkReferenceExpression']??'';        
        $pattern = $prop['pattern']??'';
        if(!empty($pattern))
        {
            $text = $this->formatValue($text,$pattern);
        }
        // $this->console("hyperlink $link");
        $target->setCellPaddings( $leftPadding, $topPadding, $rightPadding, $bottomPadding);
        $ishtml=0;
        if($prop['markup']=='html')
        {
            $ishtml=1;
            $finaltxt=$text;
        }
        else
        {
            if(!empty($link))
            {
                $finaltxt = $this->convertToLink($text,$link);
                $ishtml=1;
            }
            else
            {
                $finaltxt=$text;
            }
        }
        
        $rotation = $prop['rotation']?? '';        
        $target->StartTransform();
        $tmpborder=$border;
        switch($rotation)
        {
            case 'Left':                
                $y+=$h;                
                $tmpw=$w;
                $w=$h;
                $h=$tmpw;                
                $target->SetXY($x,$y);
                $target->Rotate(90);
                
                $border['T']=$tmpborder['L'];
                $border['B']=$tmpborder['R'];
                $border['L']=$tmpborder['B'];
                $border['R']=$tmpborder['T'];
                break;
            case 'Right':
                $x+=$w;                
                $tmpw=$w;
                $w=$h;
                $h=$tmpw;
                $this->SetXY($x,$y);
                $target->Rotate(270);
                $border['T']=$tmpborder['R'];
                $border['B']=$tmpborder['L'];
                $border['L']=$tmpborder['T'];
                $border['R']=$tmpborder['B'];
                break;
            case 'UpsideDown':
                $x+=$w;
                $y+=$h;
                $target->SetXY($x,$y);
                $target->Rotate(180);
                $border['T']=$tmpborder['B'];
                $border['B']=$tmpborder['T'];
                $border['L']=$tmpborder['R'];
                $border['R']=$tmpborder['L'];
                break;
            default:
            break;
        }
        $stretchtype=0;
        $limitY = $this->islastrow ?  $this->limitY_last: $this->limitY;
        
        
        if($textAdjust=='StretchHeight')
        {
            $stretchtype=0;
            $maxheight = $limitY-$y;//         
            $allowscale=true;
        }
        else if($textAdjust=='ScaleFont')
        {     
            $maxheight=$h;      
            $stretchtype=2;
            $allowscale=false;
        }
        else
        {
            $maxheight=$h;
            $stretchtype=0;
            $allowscale=false;
        }
        
        //dry run, get estimated $newY
        // $target->startTransaction();
        // $target->MultiCell($w,0,$finaltxt,$border,$halign,$fill,0,$x,$y,true,$stretchtype,$ishtml,true,$maxheight,$valign);
        // $newY=$target->GetY();
        // $target = $target->rollbackTransaction();
        if(!$ishtml && $textAdjust=='StretchHeight')
        {
            $this->offsetby += 10;
            $estimateHeight =  max($target->estimateHeight($w,$finaltxt),$h);        
            $newY = $y+$estimateHeight;
            // $target->Line(0+$this->offsetby,$y,10+$this->offsetby,$newY);
            
        }
        else{
            $estimateHeight = $h;
        }
        // $newY= $y+$estimateHeight;
        $target->MultiCell($w,$h,$finaltxt,$border,$halign,$fill,0,$x,$y,true,$stretchtype,$ishtml,true,$maxheight,$valign);
        if($textAdjust!='StretchHeight' || $ishtml)
        {
            $this->balancetext='';
        }
        $target->StopTransform();
        $newY= (int) ($beginingY+$estimateHeight);
        

        if($iscontinue)
        {
            
            $target->lastBandEndY = $newY;
            $this->pageOffSetY=$newY;            
            // echo "<br/>until  page: ".$this->PageNo().", Y=$newY <br/> ";
        }
        else if( $newY > $target->lastBandEndY)
        {
            $newY =(int) $newY;
            $target->lastBandEndY = $newY;
            $this->bands[$prop['band']]['endY']=$newY;

        }
        
        
        $balancetxtlength = strlen($target->balancetext);
        
        
        if($balancetxtlength > 0 && $allowscale==true)
        {            
                // echo "<br/>print long text cross page: ".$this->PageNo()." ====> ".$prop['textFieldExpression']."<br/> ";
                // $this->longtextrepeatcount++;                
                $prop['textFieldExpression'] =$this->balancetext;    
                $this->balancetext='';
            //     // $this->AddPage();
                
                if(gettype($callback)=='object')
                {
                    $originalEndY = $this->lastBandEndY;
                    $callback();
                }
                
                
                $this->SetXY($x,$this->lastBandEndY);
                
                $this->draw_staticText($uuid,$prop,$isTextField,$callback,true);                                           
                $this->setPage($beginingPage);
                $this->SetXY($x,$beginingY);
        }
        
    }
    public function setParentObj(object $parentobj)
    {
        $this->parentobj = $parentobj;
        $this->drawtarget = $parentobj;
    }
    public function getBorderStyles(array $prop=[],string $sides=''): array
    {
        $style=[];                
        if(empty($sides))
        {
            $prop['lineStyle']=$prop['lineStyle']??'';
            $prop['lineColor']=$prop['lineColor']??'';
            $prop['lineWidth']=$prop['lineWidth']??0;
            
            
            // print_r($prop);
            // echo "<hr/>";
            $style = $this->getLineStyle( $prop['lineStyle'],$prop['lineWidth'],$prop['lineColor']);
            
        }
        else
        {
            $borders=['T'=>'top','L'=>'left','R'=>'right','B'=>'bottom'];
            foreach($borders as $borderkey=>$bordername)
            {
                $width_name= $bordername.'PenlineWidth';
                $color_name= $bordername.'PenlineColor';
                $style_name= $bordername.'PenlineStyle';

                
                if(isset($prop[$width_name]) && $prop[$width_name]>0)
                {
                    $prop[$style_name]=$prop[$style_name]??'';
                    $prop[$color_name]=$prop[$color_name]??'';
                    $style[$borderkey] = $this->getLineStyle( $prop[$style_name],$prop[$width_name],$prop[$color_name]);
                }

            }

        }
        return $style;
    }
    public function draw_textField(string $uuid,array $prop,mixed $callback=null)
    {
        $this->draw_staticText($uuid,$prop,true,$callback);        
    }

    public function reduceString(mixed $txt, int $width): string
    {
        $txt = (string)$txt;
        while($this->GetStringWidth($txt) > $width) 
        {          
            $txt=substr_replace($txt,"",-1);                            
        }
        return $txt;
    }
    public function draw_unsupportedElement(string $uuid,array $prop)
    {

        $type = $prop['elementtype'];
        $x=$this->GetX();
        $y=$this->GetY();
        $w=$prop['width'];
        $h=$prop['height'];  
        $this->SetFontSize(8);
        $color1=100;
        $color2=100;
        $this->SetDrawColor($color1,$color2 , 0, 0);
        $this->SetTextColor($color1, $color2, 0, 0);           
        $style=[
            'width'=> 1,
            'dash'=>'',
            'cap'=>'butt',
            'join'=>'miter',
        ];
        $this->SetLineStyle($style);
        $this->Rect($x,$y,$w ,$h);
        $subtypetxt = '';
        if(isset($prop['subtype']))
        {
            $subtypetxt =  $prop['subtype'];            
        }
        if(isset($prop['barcodetype']))
        {
            $subtypetxt .= ' - '. $prop['barcodetype'];    
        }
        if(!empty($subtypetxt))
        {
            $subtypetxt = "($subtypetxt)";
        }
        
        $this->drawtarget->MultiCell($w,10,"element $type $subtypetxt is not support",0);          
            // $offsetx = isset($offsets['x']) ? $offsets['x']: 0;
            // $offsetx = (int)$offsetx;
            // $offsety = isset($offsets['y']) ? $offsets['y']: 0 ;
            // $offsety = (int)$offsety;
            // $this->maxY=$offsety+$height;
            // $this->currentY=$offsety;
            // $this->SetXY($offsetx,$offsety);
            // $offsets = ['x'=>$offsetx,'y'=>$offsety];
            // if($this->debugband)
            // {
                
            //     if(str_contains($bandname,$this->groupbandprefix))
            //     {
            //         $color1=100;
            //         $color2=100;
            //     }
            //     else
            //     {
            //         $color1=50;
            //         $color2=0;
            //     }

            //     if(in_array($bandname,['columnHeader','columnFooter']) || str_contains($bandname,$this->groupbandprefix) || str_contains($bandname,'detail_'))
            //     {
            //         $width = $this->columnWidth;
            //     }
            //     $this->printbandcount++;  
            //     $this->SetFontSize(8);
            //     $this->SetDrawColor($color1,$color2 , 0, 0);
            //     $this->SetTextColor($color1, $color2, 0, 0);           
            //     // $linestyle = ['dash'=>'','width'=>1];
            //     // $this->SetLineStyle(); 
            //     $this->Rect($offsetx,$offsety,$width ,$height);    
            //     $this->lastBandEndY=$offsety+$height;; 
            //     $this->Cell($width,10,$bandname."--$this->printbandcount",0);    
            // }
            
        
    }
    /****************************** draw all bands ********************************/
    /****************************** draw all bands ********************************/
    /****************************** draw all bands ********************************/
    /****************************** draw all bands ********************************/
    /****************************** draw all bands ********************************/
    public function getColumnBeginingX()
    {
        // echo "\n getColumnBeginingX $this->columnno\n";
        $x = $this->columnno * $this->columnWidth + $this->pagesettings['leftMargin'];
        return $x;
    }
    public function defineColumns(int $columnCount,mixed $columnWidth)
    {
        $this->columnWidth = $columnWidth;
        $this->columnCount = $columnCount;
    }
    public function prepareColumn()
    {
        
        $beginY=$this->bands['pageHeader']['endY'];
        $this->columnno=0;
        $endY=$this->draw_columnFooter()['y']+$this->bands['columnFooter']['height'];
        $this->SetDrawColor(40,10,10,0);
        $this->SetTextColor(40,10,10,0);
        $columnCount = $this->columnCount;
        $columnWidth = $this->columnWidth;
        if($this->debugband)
        {        
            $target = $this->drawtarget;
            for($i=0;$i<$columnCount;$i++)
            {
                $colname='column '.$i;
                $x=$this->pagesettings['leftMargin'] + $i*$columnWidth;
                $target->SetAlpha(0.5);
                $target->Rect($x,$beginY,$columnWidth ,($endY - $beginY),'FD','',[5,5,5,0.1] );     
                $target->SetAlpha(1);
                $target->SetXY($x,$beginY);
                $target->Cell($columnWidth,10,$colname,0,'','C');    
            }
        }
    }

    public function endBand(string $bandname)
    {
        $pageno = $this->PageNo();
        $columnno = $this->getColumnNo();
        if($this->pageOffSetY>0)
        {            
            // echo "<br/>end band  $bandname have pageOffSetY $this->pageOffSetY<br/>";
            $this->lastBandEndY =$this->pageOffSetY;
            $this->bands[$bandname]['endY'] = $this->pageOffSetY;
            $this->pageOffSetY=0;  
            
            $this->SetPage($this->getNumPages());
        }
        if(empty($this->pagecolumnoccupation[$pageno]))
        {
            $this->pagecolumnoccupation[$pageno] = [];
        }

        if(empty($this->pagecolumnoccupation[$pageno][$columnno]))
        {
            $this->pagecolumnoccupation[$pageno][$columnno]=[];
        }

        $this->pagecolumnoccupation[$pageno][$columnno] = $this->lastBandEndY;
        
        

        

    }
    /**
     * prepare band in pdf, and return x,y offsets
     * @param 
     */
    public function prepareBand(string $bandname, mixed $callback=null):array
    {      
        
        $offsets=[];
        
        $this->lastBand=$bandname;
        // $this->console("early $bandname ..$this->lastBandEndY");
        
        if(str_contains($bandname,'detail'))
        {
            $methodname = 'draw_detail';            
            $band = $this->bands[$bandname];
            $offsets = call_user_func([$this,$methodname],$bandname,$callback);            
            
        }
        else if(str_contains($bandname,$this->groupbandprefix))
        {
            $methodname = 'draw_group';
            $band = $this->bands[$bandname];
            $groupname = str_replace([$this->groupbandprefix,'_header','_footer'],'',$bandname);
            $groupno = $this->groups[$groupname]['groupno'];
            if(str_contains($bandname,'_header'))
            {
                $offsets = $this->draw_groupHeader($bandname,$callback);
            }
            else
            {
                $offsets = $this->draw_groupHeader($bandname,$callback);
            }
        }        
  
        else
        {
            $methodname = 'draw_'.$bandname;
            $band = $this->bands[$bandname];
            $offsets = call_user_func([$this,$methodname],$callback);
            // print_r($offsets);
        }
        $offsetx=0;
        

        $width = $this->getPageWidth() - $this->pagesettings['leftMargin'] - $this->pagesettings['rightMargin'];
        $height = isset($band['height'])? $band['height'] : 0;

        //define y position if height = 0;
        
        
        
        if($height==0)
        {
            $offsety=$offsets['y'];//$this->pagesettings['topMargin'];
        }
        else
        {
            $offsetx = isset($offsets['x']) ? $offsets['x']: 0;
            $offsetx = (int)$offsetx;
            $offsety = isset($offsets['y']) ? $offsets['y']: 0 ;
            $offsety = (int)$offsety;
            $this->maxY=$offsety+$height;
            $this->currentY=$offsety;
            $this->SetXY($offsetx,$offsety);
            $offsets = ['x'=>$offsetx,'y'=>$offsety];
            if($this->debugband)
            {
                $target = $this->drawtarget;
                if(str_contains($bandname,$this->groupbandprefix))
                {
                    $color1=100;
                    $color2=100;
                }
                else
                {
                    $color1=50;
                    $color2=0;
                }

                if(in_array($bandname,['columnHeader','columnFooter']) || str_contains($bandname,$this->groupbandprefix) || str_contains($bandname,'detail_'))
                {
                    $width = $this->columnWidth;
                }
                $this->printbandcount++;  
                $target->SetFontSize(8);
                // $this->SetDrawColor($color1,$color2 , 0, 0);
                
                $target->SetTextColor($color1, $color2, 0, 0);           
                // $linestyle = ['dash'=>'','width'=>1];
                
                $style= $this->getLineStyle('Solid',1,'#cccccc');
                // $this->SetLineStyle($style); 
                $target->Rect($offsetx,$offsety,$width ,$height,'',['TBLR'=>$style]);    
                $this->lastBandEndY=$offsety+$height;; 
                $target->Cell($width,10,$bandname."--$this->printbandcount ($offsety,$height,".($offsety+$height).")",0);    
            }
            
        }
        
        $this->lastBandEndY=$offsety+$height;;
        // $this->console("after $bandname ..$this->lastBandEndY");
        $this->bands[$bandname]['endY']=$this->lastBandEndY;
        $pageno=$this->PageNo();
        
        // echo "\n Print band($pageno) --$this->printbandcount $bandname, column: $this->columnno, $offsetx:$offsety, height:$height = endY = $this->lastBandEndY \n";
        return $offsets;

    }
    
    public function draw_background()
    {        
        $offsety=$this->pagesettings['topMargin'];
        $offset = ['x'=>$this->pagesettings['leftMargin'],'y'=>$offsety];
        return $offset;
    }
    public function draw_title()
    {
        $offsety=$this->pagesettings['topMargin'];
        $offset = ['x'=>$this->pagesettings['leftMargin'],'y'=>$offsety];
        return $offset;
        
    }
    public function draw_pageHeader()
    {
        if($this->PageNo() == 1)
        {
            $offsety = $this->pagesettings['topMargin'] + $this->getBandHeight('title');
        }
        else
        {
            $offsety = $this->pagesettings['topMargin'];
        }
        
        $offset = ['x'=>$this->pagesettings['leftMargin'],'y'=>$offsety];
        return $offset;
    }
    public function draw_columnHeader()
    {
        // $offsety = $this->lastBandEndY;
        if($this->PageNo() == 1)
        {
            $offsety = $this->pagesettings['topMargin'] + $this->getBandHeight('title') +  $this->getBandHeight('pageHeader');
        }
        else
        {
            $offsety = $this->pagesettings['topMargin'] + $this->getBandHeight('pageHeader');
        }
        // $offsety=$this->lastBandEndY;
        $offset = ['x'=>$this->getColumnBeginingX(),'y'=>$offsety];
        // print_r($offset);
        //$this->drawBand($bandname,$offset);
        return $offset;
    }

    protected function getLastGroupName():string
    {
        
        if($this->groupCount()>0)
        {
            return array_key_last($this->groups);
        }
        else
        {
            return '';
        }
    }
    protected function getFirstGroupName():string
    {
        
        if($this->groupCount()>0)
        {
            return array_key_first($this->groups);
        }
        else
        {
            return '';
        }
    }
    public function setDetailNextPage(string $detailname)
    {

    }
    public function draw_detail(string $detailbandname,mixed $callback=null)
    {
        $detailno = (int)str_replace('detail_','',$detailbandname);
        $totaldetailheight = 0;
        
        $offsety = $this->lastBandEndY;       
        // echo "draw detail lastbandendy =$offsety<hr>";
        $estimateY=$offsety+$this->getBandHeight($detailbandname);
        if($this->isEndDetailSpace($estimateY) && gettype($callback)=='object' && $this->currentRowNo >0)
        {            
            $callback();
            $offsety = $this->bands['columnHeader']['endY'];    
        }
        $offsetx = $this->getColumnBeginingX();
        // echo "\nprintdetail at column: $$offsetx\n";
        
        $offset = ['x'=>$offsetx, 'y'=>$offsety];
        return $offset;
    }


    protected function isEndDetailSpace(int $estimateY)
    {
        $offsets = $this->draw_columnFooter();
        $offsety=$offsets['y'];
        $buffer = 0;
        if($estimateY >=$offsety+$buffer)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function setRowNumber(int $no)
    {
        $this->currentRowNo=$no;    
    }
    public function draw_columnFooter()
    {        
        $pageheight = $this->getPageHeight();//1000
        $pagefooterheight =  $this->getBandHeight('pageFooter');
        $bottommargin =  $this->pagesettings['bottomMargin'];        
        $columnfooterheight = $this->getBandHeight('columnFooter');
        $offsety = $pageheight - $pagefooterheight - $bottommargin - $columnfooterheight;
        $offset = ['x'=>$this->getColumnBeginingX() ,'y'=>$offsety];
        
        return $offset;
        
    }
    public function draw_pageFooter()
    {
        
        $pageheight = $this->getPageHeight();
        $pagefooterheight =  $this->getBandHeight('pageFooter');
        $bottommargin =  $this->pagesettings['bottomMargin'];
        
        $offsety = $pageheight - $pagefooterheight - $bottommargin ;
        $offset = ['x'=>$this->pagesettings['leftMargin'],'y'=>$offsety];

        return $offset;
        
    }
    public function draw_lastPageFooter()
    {
        return $this->draw_pageFooter();        
    }
    public function draw_summary(mixed $callback=null)
    {
        if($this->groupCount()>0)
        {            
            $endgroupname = $this->getHashKeyFromIndex($this->groups,0);
            $lastband = $this->groupbandprefix. $endgroupname.'_footer';
            
            $offsety = $this->bands[$lastband]['endY'];
        }
        else
        {
            // $offsety = $this->bands[$lastband]['endY'];
            // $offsety = $this->lastBandEndY;
            // $offsety = $this->bands[$this->lastdetailband]['endY'];
            // if($this->offsetby > 0 )
            // {
            //     // echo  $this->offsetby;
            //     $offsety = $this->offsetby;
            //     $this->offsetby=0;
            //     $this->SetY($offsety);
                
            //     // $this->SetPage( $this->PageNo()-1);
            //     // $this->SetPage($this->getNumPages());
            //     // $this->deletePage($this->getNumPages());

            // }
            // else
            // {
                $offsety = $this->bands[$this->lastdetailband]['endY'];
            // }
        }
        
        $estimateY=$offsety+$this->getBandHeight('summary');
        // print_r($this->bands);
        // echo $estimateY;die;
        // if(($this->columnno >0 || $this->isEndDetailSpace($estimateY) ) && gettype($callback)=='object')
        if($this->isEndDetailSpace($estimateY) && gettype($callback) == 'object')
        {            

            $offsety = $callback();//$this->bands['columnHeader']['endY'];    
        }
        $offset = ['x'=>$this->pagesettings['leftMargin'],'y'=>$offsety];        
        return $offset;
    }
    public function draw_noData()
    {
        
        $offsety = $this->pagesettings['topMargin'];
        $offset = ['x'=>$this->pagesettings['leftMargin'],'y'=>$offsety];
        return $offset;
    }

    public function draw_groupHeader(string $bandname,mixed $callback=null) : array
    {
        $offsetx=$this->getColumnBeginingX();//$this->pagesettings['leftMargin'];
        // $offsety=$this->pagesettings['leftMargin'];
        
        $offsety = $this->lastBandEndY;
        // $this->console("$bandname offsety : $offsety ");
        $estimateY=$offsety+$this->getBandHeight($bandname);
        if($this->isEndDetailSpace($estimateY) && gettype($callback)=='object')
        {            
            $callback();
            // $this->SetPage($this->PageNo()-1);
            $offsety = $this->bands['columnHeader']['endY'];    
        }
        $offset=['x'=>$offsetx,'y'=>$offsety];
        // print_r($offset);
        return $offset;
    }

    public function draw_groupFooter(string $bandname,mixed $callback=null) : array
    {
        
        $offsetx=$this->getColumnBeginingX();//$this->pagesettings['leftMargin'];
        // $offsety=$this->pagesettings['leftMargin'];
        $offsety = $this->lastBandEndY;
        $estimateY=$offsety+$this->getBandHeight($bandname);
        if($this->isEndDetailSpace($estimateY) && gettype($callback)=='object')
        {            
            $callback();
            $offsety = $this->bands['columnHeader']['endY'];    
        }
        // echo "\n draw_groupFooter: $offsetx\n";
        $offset=['x'=>$offsetx,'y'=>$offsety];
        return $offset;
    }


    /*************** misc function ****************/
    public function supportSubReport(): bool
    {
        return true;
    }
    public function setPosition(int $x,int $y,array $prop)
    {
        $this->SetXY($x,$y);
    }

    protected function getLineStyle(string $lineStyle,float $lineWidth=0,string $lineColor='')
    {
        $forecolor = $this->convertColorStrToRGB($lineColor);        
        switch($lineStyle)
        {
            case "Dotted":
                $dash=sprintf("%d,%d",$lineWidth,$lineWidth);
            break;
            case "Dashed":
                $dash=sprintf("%d,%d",$lineWidth*4,$lineWidth*2);
                break;
            default:
                $dash="";
            break;
        }
        
        $style=[
            'width'=> $lineWidth,
            'color'=>$forecolor,
            'dash'=>$dash,
            'cap'=>'butt',
            'join'=>'miter',
        ];
        return $style;
    }

    public function columnCount(): int
    {
        return $this->pagesettings['columnCount'];
    }

    protected function convertColorStrToRGB(string $colorstr):array
    {
        return array("r"=>hexdec(substr($colorstr,1,2)),"g"=>hexdec(substr($colorstr,3,2)),"b"=>hexdec(substr($colorstr,5,2)));
    }
    public function groupCount(): int
    {
        return $groupcount = count($this->groups);
    }

    protected function formatValue(mixed $value, string $pattern) : string
    {
        // scientific
        $data = $value;
        $prepattern = $pattern;
        if(str_contains($pattern,'E0'))
        {
            
        }
        //date
        else if(str_contains($pattern,'d') || str_contains($pattern,'h')|| str_contains($pattern,'H') || str_contains($pattern,'M')) //date
        {
            // $arrdate = gmdate(strtotime($value));
            $vars = [
                'yyyy'=>'Y',
                'yy'=>'y',
                'd'=>'d',
                'hh'=>'h',
                'h'=>'g',
                'HH'=>'H',
                'H'=>'G',
                'mm'=>'i',
                'ss'=>'s',
                // 'a '=>'a',
                // 'a'=>'',
                'zzzz'=>'',
                'z'=>'e',
            ];
            $varmonth = [
                'MMMM'=>'F',
                'MMM'=>'M',
                'M'=>'n',
            ];
            foreach($vars as $key=>$replace)
            {
                $pattern = str_replace($key,$replace,$pattern);
            }

            $i=0;
            foreach($varmonth as $key=>$replace)
            {
                $i++;
                // $this->console( "$i chechmonth $key");
                if(str_contains($pattern,$key))
                {
                    // $this->console( "exists");
                    $pattern = str_replace($key,$replace,$pattern);
                    break;
                }                
                
            }

            // $this->console("Old pattern $prepattern, new pattern $pattern");
            $data = date($pattern,strtotime($value));
            // M/d/yy
            // MMM d, yyyy
            // MMMM d, yyyy
            // M/d/yy h:mm a
            // MMM d, yyyy h:mm:ss a
            // MMM d, yyyy h:mm:ss a z
            // HH:mm:ss a
            // HH:mm:ss zzzz
        }
        //number
        else if(str_contains($pattern,'#') ) 
        {
            $fmt = numfmt_create( 'en_US', \NumberFormatter::DECIMAL );
            numfmt_set_pattern($fmt,$pattern);
            try{
                $data = numfmt_format($fmt,$value);
            }
            catch(Throwable $e)
            {
                return $data;
            }
            

        }
        return $data;
    }
    protected function convertToLink(string $text='',string $link='')
    {
        if(!empty($link))
        {
            return '<a href="'.$link.'">'.$text.'</a>';
        }
        else
        {
            return $text;
        }
    }
    

    public function estimateHeight(mixed $w,mixed $txt)
    {
        // return $this->getNumLines($txt,$w);
        return $this->getStringHeight($w, $txt, $reseth = false, $autopadding = true, $cellMargin = '', $lineWidth = '');
    }


    /***************************************************************************************************************/
    /***************************************************************************************************************/
    /****************************************** override tcpdf *****************************************************/
    /***************************************************************************************************************/
    /***************************************************************************************************************/
    /**
	 * This method prints text from the current position.<br />
	 * @param float $h Line height
	 * @param string $txt String to print
	 * @param mixed $link URL or identifier returned by AddLink()
	 * @param boolean $fill Indicates if the cell background must be painted (true) or transparent (false).
	 * @param string $align Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
	 * @param boolean $ln if true set cursor at the bottom of the line, otherwise set cursor at the top of the line.
	 * @param int $stretch font stretch mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
	 * @param boolean $firstline if true prints only the first line and return the remaining string.
	 * @param boolean $firstblock if true the string is the starting of a line.
	 * @param float $maxh maximum height. It should be >= $h and less then remaining space to the bottom of the page, or 0 for disable this feature.
	 * @param float $wadj first line width will be reduced by this amount (used in HTML mode).
	 * @param array $margin margin array of the parent container
	 * @return mixed Return the number of cells or the remaining string if $firstline = true.
	 * @public
	 * @since 1.5
	 */
	public function Write($h, $txt, $link='', $fill=false, $align='', $ln=false, $stretch=0, $firstline=false, $firstblock=false, $maxh=0, $wadj=0, $margin='') {
		// check page for no-write regions and adapt page margins if necessary
		list($this->x, $this->y) = $this->checkPageRegions($h, $this->x, $this->y);
        
        // $this->console("       tmpy = $tmpy");
		if (strlen($txt) == 0) {
			// fix empty text
			$txt = ' ';
		}
		if ($margin === '') {
			// set default margins
			$margin = $this->cell_margin;
		}
		// remove carriage returns
		$s = str_replace("\r", '', $txt);
		// check if string contains arabic text
		if (preg_match(TCPDF_FONT_DATA::$uni_RE_PATTERN_ARABIC, $s)) {
			$arabic = true;
		} else {
			$arabic = false;
		}
		// check if string contains RTL text
		if ($arabic OR ($this->tmprtl == 'R') OR preg_match(TCPDF_FONT_DATA::$uni_RE_PATTERN_RTL, $s)) {
			$rtlmode = true;
		} else {
			$rtlmode = false;
		}
		// get a char width
		$chrwidth = $this->GetCharWidth(46); // dot character
		// get array of unicode values
		$chars = TCPDF_FONTS::UTF8StringToArray($s, $this->isunicode, $this->CurrentFont);
		// calculate maximum width for a single character on string
		$chrw = $this->GetArrStringWidth($chars, '', '', 0, true);
		array_walk($chrw, array($this, 'getRawCharWidth'));
		$maxchwidth = max($chrw);
		// get array of chars
		$uchars = TCPDF_FONTS::UTF8ArrayToUniArray($chars, $this->isunicode);
		// get the number of characters
		$nb = count($chars);
		// replacement for SHY character (minus symbol)
		$shy_replacement = 45;
		$shy_replacement_char = TCPDF_FONTS::unichr($shy_replacement, $this->isunicode);
		// widht for SHY replacement
		$shy_replacement_width = $this->GetCharWidth($shy_replacement);
		// page width
		$pw = $w = $this->w - $this->lMargin - $this->rMargin;
		// calculate remaining line width ($w)
		if ($this->rtl) {
			$w = $this->x - $this->lMargin;
		} else {
			$w = $this->w - $this->rMargin - $this->x;
		}
		// max column width
		$wmax = ($w - $wadj);
		if (!$firstline) {
			$wmax -= ($this->cell_padding['L'] + $this->cell_padding['R']);
		}
		if ((!$firstline) AND (($chrwidth > $wmax) OR ($maxchwidth > $wmax))) {
			// the maximum width character do not fit on column
			return '';
		}
		// minimum row height
		$row_height = max($h, $this->getCellHeight($this->FontSize));
		// max Y
		$maxy = $this->y + $maxh - max($row_height, $h);
		$start_page = $this->page;
		$i = 0; // character position
		$j = 0; // current starting position
		$sep = -1; // position of the last blank space
		$prevsep = $sep; // previous separator
		$shy = false; // true if the last blank is a soft hypen (SHY)
		$prevshy = $shy; // previous shy mode
		$l = 0; // current string length
		$nl = 0; //number of lines
		$linebreak = false;
		$pc = 0; // previous character
		// for each character
        $last_i=0;
		while ($i < $nb) {
            $last_i++;
            
			if (($maxh > 0) AND ($this->y > $maxy) ) {
                $this->balancetext=TCPDF_FONTS::UniArrSubString($uchars,$j); //phpjasperxml code                
				break;
			}
			//Get the current character
			$c = $chars[$i];
            
			if ($c == 10) { // 10 = "\n" = new line
				//Explicit line break
                
				if ($align == 'J') {
					if ($this->rtl) {
						$talign = 'R';
					} else {
						$talign = 'L';
					}
				} else {
					$talign = $align;
				}
                
				$tmpstr = TCPDF_FONTS::UniArrSubString($uchars, $j, $i);
                
				if ($firstline) {
					$startx = $this->x;
					$tmparr = array_slice($chars, $j, ($i - $j));
					if ($rtlmode) {
						$tmparr = TCPDF_FONTS::utf8Bidi($tmparr, $tmpstr, $this->tmprtl, $this->isunicode, $this->CurrentFont);
					}
					$linew = $this->GetArrStringWidth($tmparr);
					unset($tmparr);
					if ($this->rtl) {
						$this->endlinex = $startx - $linew;
					} else {
						$this->endlinex = $startx + $linew;
					}
					$w = $linew;
					$tmpcellpadding = $this->cell_padding;
					if ($maxh == 0) {
						$this->SetCellPadding(0);
					}
				}
				if ($firstblock AND $this->isRTLTextDir()) {
					$tmpstr = $this->stringRightTrim($tmpstr);
				}
				// Skip newlines at the beginning of a page or column
				if (!empty($tmpstr) OR ($this->y < ($this->PageBreakTrigger - $row_height))) {
					$this->Cell($w, $h, $tmpstr, 0, 1, $talign, $fill, $link, $stretch);
				}
				unset($tmpstr);
				if ($firstline) {
					$this->cell_padding = $tmpcellpadding;
					return (TCPDF_FONTS::UniArrSubString($uchars, $i));
				}
				++$nl;
				$j = $i + 1;
				$l = 0;
				$sep = -1;
				$prevsep = $sep;
				$shy = false;
				// account for margin changes
				if ((($this->y + $this->lasth) > $this->PageBreakTrigger) AND ($this->inPageBody())) {
					$this->AcceptPageBreak();
                    
					if ($this->rtl) {
						$this->x -= $margin['R'];
					} else {
						$this->x += $margin['L'];
					}
					$this->lMargin += $margin['L'];
					$this->rMargin += $margin['R'];
				}
				$w = $this->getRemainingWidth();
				$wmax = ($w - $this->cell_padding['L'] - $this->cell_padding['R']);
			} 
            else {
				// 160 is the non-breaking space.
				// 173 is SHY (Soft Hypen).
				// \p{Z} or \p{Separator}: any kind of Unicode whitespace or invisible separator.
				// \p{Lo} or \p{Other_Letter}: a Unicode letter or ideograph that does not have lowercase and uppercase variants.
				// \p{Lo} is needed because Chinese characters are packed next to each other without spaces in between.
				if (($c != 160)
					AND (($c == 173)
						OR preg_match($this->re_spaces, TCPDF_FONTS::unichr($c, $this->isunicode))
						OR (($c == 45)
							AND ($i < ($nb - 1))
							AND @preg_match('/[\p{L}]/'.$this->re_space['m'], TCPDF_FONTS::unichr($pc, $this->isunicode))
							AND @preg_match('/[\p{L}]/'.$this->re_space['m'], TCPDF_FONTS::unichr($chars[($i + 1)], $this->isunicode))
						)
					)
				) {
                    
					// update last blank space position
					$prevsep = $sep;
					$sep = $i;
					// check if is a SHY
					if (($c == 173) OR ($c == 45)) {
						$prevshy = $shy;
						$shy = true;
						if ($pc == 45) {
							$tmp_shy_replacement_width = 0;
							$tmp_shy_replacement_char = '';
						} else {
							$tmp_shy_replacement_width = $shy_replacement_width;
							$tmp_shy_replacement_char = $shy_replacement_char;
						}
					} else {
						$shy = false;
					}
				}
				// update string length
				if ($this->isUnicodeFont() AND ($arabic)) {
					// with bidirectional algorithm some chars may be changed affecting the line length
					// *** very slow ***
					$l = $this->GetArrStringWidth(TCPDF_FONTS::utf8Bidi(array_slice($chars, $j, ($i - $j)), '', $this->tmprtl, $this->isunicode, $this->CurrentFont));
				} else {
					$l += $this->GetCharWidth($c, ($i+1 < $nb));
				}
                
				if (($l > $wmax) OR (($c == 173) AND (($l + $tmp_shy_replacement_width) >= $wmax))) {
					if (($c == 173) AND (($l + $tmp_shy_replacement_width) > $wmax)) {
						$sep = $prevsep;
						$shy = $prevshy;
					}
					// we have reached the end of column
					if ($sep == -1) {
						// check if the line was already started
						if (($this->rtl AND ($this->x <= ($this->w - $this->rMargin - $this->cell_padding['R'] - $margin['R'] - $chrwidth)))
							OR ((!$this->rtl) AND ($this->x >= ($this->lMargin + $this->cell_padding['L'] + $margin['L'] + $chrwidth)))) {
							// print a void cell and go to next line
							$this->Cell($w, $h, '', 0, 1);
							$linebreak = true;
							if ($firstline) {
								return (TCPDF_FONTS::UniArrSubString($uchars, $j));
							}
						} else {
							// truncate the word because do not fit on column
							$tmpstr = TCPDF_FONTS::UniArrSubString($uchars, $j, $i);
							if ($firstline) {
								$startx = $this->x;
								$tmparr = array_slice($chars, $j, ($i - $j));
								if ($rtlmode) {
									$tmparr = TCPDF_FONTS::utf8Bidi($tmparr, $tmpstr, $this->tmprtl, $this->isunicode, $this->CurrentFont);
								}
								$linew = $this->GetArrStringWidth($tmparr);
								unset($tmparr);
								if ($this->rtl) {
									$this->endlinex = $startx - $linew;
								} else {
									$this->endlinex = $startx + $linew;
								}
								$w = $linew;
								$tmpcellpadding = $this->cell_padding;
								if ($maxh == 0) {
									$this->SetCellPadding(0);
								}
							}
							if ($firstblock AND $this->isRTLTextDir()) {
								$tmpstr = $this->stringRightTrim($tmpstr);
							}
							$this->Cell($w, $h, $tmpstr, 0, 1, $align, $fill, $link, $stretch);
							unset($tmpstr);
							if ($firstline) {
								$this->cell_padding = $tmpcellpadding;
								return (TCPDF_FONTS::UniArrSubString($uchars, $i));
							}
							$j = $i;
							--$i;
						}
					} else {
						// word wrapping
						if ($this->rtl AND (!$firstblock) AND ($sep < $i)) {
							$endspace = 1;
						} else {
							$endspace = 0;
						}
						// check the length of the next string
						$strrest = TCPDF_FONTS::UniArrSubString($uchars, ($sep + $endspace));
						$nextstr = TCPDF_STATIC::pregSplit('/'.$this->re_space['p'].'/', $this->re_space['m'], $this->stringTrim($strrest));
						if (isset($nextstr[0]) AND ($this->GetStringWidth($nextstr[0]) > $pw)) {
							// truncate the word because do not fit on a full page width
							$tmpstr = TCPDF_FONTS::UniArrSubString($uchars, $j, $i);
							if ($firstline) {
								$startx = $this->x;
								$tmparr = array_slice($chars, $j, ($i - $j));
								if ($rtlmode) {
									$tmparr = TCPDF_FONTS::utf8Bidi($tmparr, $tmpstr, $this->tmprtl, $this->isunicode, $this->CurrentFont);
								}
								$linew = $this->GetArrStringWidth($tmparr);
								unset($tmparr);
								if ($this->rtl) {
									$this->endlinex = ($startx - $linew);
								} else {
									$this->endlinex = ($startx + $linew);
								}
								$w = $linew;
								$tmpcellpadding = $this->cell_padding;
								if ($maxh == 0) {
									$this->SetCellPadding(0);
								}
							}
							if ($firstblock AND $this->isRTLTextDir()) {
								$tmpstr = $this->stringRightTrim($tmpstr);
							}
							$this->Cell($w, $h, $tmpstr, 0, 1, $align, $fill, $link, $stretch);
							unset($tmpstr);
							if ($firstline) {
								$this->cell_padding = $tmpcellpadding;
								return (TCPDF_FONTS::UniArrSubString($uchars, $i));
							}
							$j = $i;
							--$i;
						} else {
							// word wrapping
							if ($shy) {
								// add hypen (minus symbol) at the end of the line
								$shy_width = $tmp_shy_replacement_width;
								if ($this->rtl) {
									$shy_char_left = $tmp_shy_replacement_char;
									$shy_char_right = '';
								} else {
									$shy_char_left = '';
									$shy_char_right = $tmp_shy_replacement_char;
								}
							} else {
								$shy_width = 0;
								$shy_char_left = '';
								$shy_char_right = '';
							}
							$tmpstr = TCPDF_FONTS::UniArrSubString($uchars, $j, ($sep + $endspace));
							if ($firstline) {
								$startx = $this->x;
								$tmparr = array_slice($chars, $j, (($sep + $endspace) - $j));
								if ($rtlmode) {
									$tmparr = TCPDF_FONTS::utf8Bidi($tmparr, $tmpstr, $this->tmprtl, $this->isunicode, $this->CurrentFont);
								}
								$linew = $this->GetArrStringWidth($tmparr);
								unset($tmparr);
								if ($this->rtl) {
									$this->endlinex = $startx - $linew - $shy_width;
								} else {
									$this->endlinex = $startx + $linew + $shy_width;
								}
								$w = $linew;
								$tmpcellpadding = $this->cell_padding;
								if ($maxh == 0) {
									$this->SetCellPadding(0);
								}
							}
							// print the line
							if ($firstblock AND $this->isRTLTextDir()) {
								$tmpstr = $this->stringRightTrim($tmpstr);
							}
							$this->Cell($w, $h, $shy_char_left.$tmpstr.$shy_char_right, 0, 1, $align, $fill, $link, $stretch);
							unset($tmpstr);
							if ($firstline) {
								if ($chars[$sep] == 45) {
									$endspace += 1;
								}
								// return the remaining text
								$this->cell_padding = $tmpcellpadding;
								return (TCPDF_FONTS::UniArrSubString($uchars, ($sep + $endspace)));
							}
							$i = $sep;
							$sep = -1;
							$shy = false;
							$j = ($i + 1);
						}
					}
                    
					// account for margin changes
					if ((($this->y + $this->lasth) > $this->PageBreakTrigger) AND ($this->inPageBody())) {
						$this->AcceptPageBreak();
						if ($this->rtl) {
							$this->x -= $margin['R'];
						} else {
							$this->x += $margin['L'];
						}
						$this->lMargin += $margin['L'];
						$this->rMargin += $margin['R'];
					}
                    
					$w = $this->getRemainingWidth();
                    
					$wmax = $w - $this->cell_padding['L'] - $this->cell_padding['R'];
					if ($linebreak) {
						$linebreak = false;
					} else {
						++$nl;
						$l = 0;
					}
				}
			}
            
			// save last character
			$pc = $c;
			++$i;
		} // end while i < nb

        // $this->console("printed string length $l");
		// print last substring (if any)
		if ($l > 0) {
			switch ($align) {
				case 'J':
				case 'C': {
					break;
				}
				case 'L': {
					if (!$this->rtl) {
						$w = $l;
					}
					break;
				}
				case 'R': {
					if ($this->rtl) {
						$w = $l;
					}
					break;
				}
				default: {
					$w = $l;
					break;
				}
			}
            
			$tmpstr = TCPDF_FONTS::UniArrSubString($uchars, $j, $nb);        
			if ($firstline) {
				$startx = $this->x;
				$tmparr = array_slice($chars, $j, ($nb - $j));
				if ($rtlmode) {
					$tmparr = TCPDF_FONTS::utf8Bidi($tmparr, $tmpstr, $this->tmprtl, $this->isunicode, $this->CurrentFont);
				}
				$linew = $this->GetArrStringWidth($tmparr);
				unset($tmparr);
				if ($this->rtl) {
					$this->endlinex = $startx - $linew;
				} else {
					$this->endlinex = $startx + $linew;
				}
				$w = $linew;
				$tmpcellpadding = $this->cell_padding;
				if ($maxh == 0) {
					$this->SetCellPadding(0);
				}
			}
            
			if ($firstblock AND $this->isRTLTextDir()) {
				$tmpstr = $this->stringRightTrim($tmpstr);
			}
			$this->Cell($w, $h, $tmpstr, 0, $ln, $align, $fill, $link, $stretch);
			unset($tmpstr);
			if ($firstline) {
				$this->cell_padding = $tmpcellpadding;
				return (TCPDF_FONTS::UniArrSubString($uchars, $nb));
			}
			++$nl;
            
		}
		if ($firstline) {
			return '';
		}
        // $this->console("Printed Line = $nl");
		return $nl;
	}
}