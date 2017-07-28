<?php

//require_once("tcpdf/tcpdf.php");
require_once("fpdi/fpdi.php");
 
class concat_pdf extends FPDI {
     var $files = array();
     function setFiles($files) {
          $this->files = $files;
     }
     function concat() {
          foreach($this->files AS $file) {
               $pagecount = $this->setSourceFile($file);
               for ($i = 1; $i <= $pagecount; $i++) {
                    $tplidx = $this->ImportPage($i);
                    $s = $this->getTemplatesize($tplidx);
                    $this->filetype = $s['h'] > $s['w'] ? 'P' : 'L';
                    $this->AddPage($this->filetype, array($s['w'], $s['h']));
                    $this->useTemplate($tplidx);
               }
          }
     }
}

?>