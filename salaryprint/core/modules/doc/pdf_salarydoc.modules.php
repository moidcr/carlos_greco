<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018-2020	Frédéric France    	<frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/expedition/doc/pdf_labelsexpd.modules.php
 *	\ingroup    expedition
 *	\brief      Class file used to generate the dispatch slips for the labelsexpd model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';


/**
 *	Class to build sending documents with model labelsexpd
 */
class pdf_salarydoc extends ModelePdfExpedition
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	public $update_main_doc_field ;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe    object that emits
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db = 0)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = "labelsexpd";
		$this->description = $langs->trans("DocumentModelStandardPDF");

		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = 2159;
		$this->page_hauteur = 2794;
		$this->update_main_doc_field = 1;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->emetteur = $mysoc;
		/*$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Display logo

		// Get source company
		
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
		$this->posxweightvol = $this->page_largeur - $this->marge_droite - 82;
		$this->posxqtyordered = $this->page_largeur - $this->marge_droite - 60;
		$this->posxqtytoship = $this->page_largeur - $this->marge_droite - 28;
		$this->posxpuht = $this->page_largeur - $this->marge_droite;

		if (!empty($conf->global->SHIPPING_PDF_DISPLAY_AMOUNT_HT)) {	// Show also the prices
			$this->posxweightvol = $this->page_largeur - $this->marge_droite - 118;
			$this->posxqtyordered = $this->page_largeur - $this->marge_droite - 96;
			$this->posxqtytoship = $this->page_largeur - $this->marge_droite - 68;
			$this->posxpuht = $this->page_largeur - $this->marge_droite - 40;
			$this->posxtotalht = $this->page_largeur - $this->marge_droite - 20;
		}

		if (!empty($conf->global->SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME)) $this->posxweightvol = $this->posxqtyordered;

		$this->posxpicture = $this->posxweightvol - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH); // width of images

		// To work with US executive format
		if ($this->page_largeur < 210) {
			$this->posxweightvol -= 20;
			$this->posxpicture -= 20;
			$this->posxqtyordered -= 20;
			$this->posxqtytoship -= 20;
		}

		if (!empty($conf->global->SHIPPING_PDF_HIDE_ORDERED)) {
			$this->posxweightvol += ($this->posxqtytoship - $this->posxqtyordered);
			$this->posxpicture += ($this->posxqtytoship - $this->posxqtyordered);
			$this->posxqtyordered = $this->posxqtytoship;
		}*/
	}

	public function numberTowords($num)
{

$ones = array(
0 =>"Zero",
1 => "One",
2 => "Two",
3 => "Three",
4 => "Four",
5 => "Five",
6 => "Six",
7 => "Seven",
8 => "Eight",
9 => "Nine",
10 => "Ten",
11 => "Eleven",
12 => "Twelve",
13 => "Thirteen",
14 => "Fourteen",
15 => "Fifteen",
16 => "Sixtenn",
17 => "Seventeen",
18 => "Eighteen",
19 => "Nineteen",
"014" => "Fourteen"
);
$tens = array( 
0 => "Zero",
1 => "Ten",
2 => "Twenty",
3 => "Thirty", 
4 => "Forty", 
5 => "Fifty", 
6 => "Sixty", 
7 => "Seventy", 
8 => "Eighty", 
9 => "Ninety" 
); 
$hundreds = array( 
"Hundred", 
"Thousand", 
"Million", 
"Billion", 
"Trillion", 
"Quardrillion"
); /*limit t quadrillion */
$num = number_format($num,2,".",","); 
$num_arr = explode(".",$num); 
$wholenum = $num_arr[0]; 
$decnum = $num_arr[1]; 
$whole_arr = array_reverse(explode(",",$wholenum)); 
krsort($whole_arr,1); 
$rettxt = ""; 
foreach($whole_arr as $key => $i){
	
while(substr($i,0,1)=="0")
		$i=substr($i,1,5);
if($i < 20){ 
/* echo "getting:".$i; */
$rettxt .= $ones[$i]; 
}elseif($i < 100){ 
if(substr($i,0,1)!="0")  $rettxt .= $tens[substr($i,0,1)]; 
if(substr($i,1,1)!="0") $rettxt .= " ".$ones[substr($i,1,1)]; 
}else{ 
if(substr($i,0,1)!="0") $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0]; 
if(substr($i,1,1)!="0")$rettxt .= " ".$tens[substr($i,1,1)]; 
if(substr($i,2,1)!="0")$rettxt .= " ".$ones[substr($i,2,1)]; 
} 
if($key > 0){ 
$rettxt .= " ".$hundreds[$key]." "; 
}
} 
if($decnum > 0){
$rettxt .= " and ";
if($decnum < 20){
$rettxt .= $ones[$decnum];
}elseif($decnum < 100){
$rettxt .= $tens[substr($decnum,0,1)];
$rettxt .= " ".$ones[substr($decnum,1,1)];
}
}
return $rettxt."/".$decnum ;
}

public function fechaCastellano ($fecha1, $fecha2) {
  $fecha = substr($fecha1, 0, 10);
  $numeroDia = date('d', strtotime($fecha));
  $dia = date('l', strtotime($fecha));
  $mes = date('F', strtotime($fecha));
  $anio = date('Y', strtotime($fecha));
  $dias_ES = array("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo");
  $dias_EN = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
  $nombredia = str_replace($dias_EN, $dias_ES, $dia);
$meses_ES = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
  $meses_EN = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
  $nombreMes = str_replace($meses_EN, $meses_ES, $mes);
  
  $fecha2 = substr($fecha2, 0, 10);
  $numeroDia2 = date('d', strtotime($fecha2));
  $dia2 = date('l', strtotime($fecha2));
  $mes2 = date('F', strtotime($fecha2));
  $anio2 = date('Y', strtotime($fecha2));
  $nombredia2 = str_replace($dias_EN, $dias_ES, $dia2);
  $nombreMes2 = str_replace($meses_EN, $meses_ES, $mes2);
  
  if($nombreMes==$nombreMes2 && $anio == $anio2)
  	return "Del ".$numeroDia." al ".$numeroDia2." de ".$nombreMes." de ".$anio;
  elseif($anio == $anio2)
  	return "Del ".$numeroDia." de ".$nombreMes." al ".$numeroDia2." de ".$nombreMes2." de ".$anio;
  else
  	return "Del ".$numeroDia." de ".$nombreMes." de ".$anio." al ".$numeroDia2." de ".$nombreMes2." de ".$anio2;
}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build pdf onto disk
	 *
	 *	@param		Expedition	$object				Object expedition to generate (or id if old method)
	 *	@param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $conf, $langs, $hookmanager, $mysoc, $db;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		

		if ($conf->salaryprint->dir_output)
		{
			// Definition de $dir et $file
			if ($object->id)
			{

				$dir = $conf->salaryprint->dir_output."/".$object->id;
				$file = $dir."/salary_".$object->id.".pdf";
			} else {
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->salaryprint->dir_output."/sending/".$expref;
				$file = $dir."/Etiquetas_".$expref.".pdf";
			}

			if (!file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				//echo $dir
				$pdf = pdf_getInstance($this->format);
				
				
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Shipment"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);
				
				
				// remove default header/footer
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
				
				// set default monospaced font
				$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
				
				// set margins
				$pdf->SetMargins(1, 1, 1);
				
				// set auto page breaks
				$pdf->SetAutoPageBreak(TRUE, 1);

				// --- START GRAPHIC TRANFORMATIONS TEST -------------------
// Code Provided by Moritz Wagner and Andreas Würmser
//$pdf->setPrintHeader(false);
$pdf->AddPage('L','LETTER');

// Logo
		/*$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, 12, 10, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(20, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}*/

//$pdf->Bookmark('Transformations', 0, 0);
//$pdf->setPrintFooter(false);
//Scaling
//$pdf->Bookmark('Scaling', 1, 4);
$pdf->SetDrawColor(000);
$pdf->SetTextColor(000);

$default_font_type = "Times";

$default_font_size = pdf_getPDFFontSize($outputlangs);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 1);

$mov = 0;

//echo "<pre>";
//print_r($object);


	/*
	 * Payments
	 */
	$sql = "SELECT p.rowid, p.num_payment as num_payment, p.datep as dp, SUM(p.amount) as Amount,";
	$sql .= " c.code as type_code,c.libelle as paiement_type,";
	$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.currency_code as bacurrency_code, ba.fk_accountancy_journal';
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepayment = c.id";
	$sql .= ", ".MAIN_DB_PREFIX."salary as salaire";
	$sql .= " WHERE p.fk_salary = ".((int) $object->id);
	$sql .= " AND p.fk_salary = salaire.rowid";
	$sql .= " ORDER BY dp DESC limit 1";

	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		$totalpaye = 0;

		$num = $db->num_rows($resql);

		if ($num > 0) {

				$objp = $db->fetch_object($resql);
		}
	}
	else
	{
		$objp->datep = $object->datesp;
	}



$FechaDesde = date('d-m-Y', $object->datesp);
$FechaHasta = date('d-m-Y', $object->dateep);

$userstatic = new User($db);
$userstatic->fetch($object->fk_user);
$nombre = $userstatic->firstname." ".$userstatic->lastname;

$movb = 0;
$pdf->SetFont($default_font_type, 'B', $default_font_size);
$pdf->SetXY(25, 15+$movb);
$pdf->MultiCell(100, 5, 'INVERSIONES GENERALES ARIAS S.A.', 0, 'C', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size - 1);

$pdf->SetXY(25, 20+$movb);
$pdf->MultiCell(100, 5, 'RUC: 2141703-1-763891 dv 58', 0, 'C', 0);

$pdf->SetXY(25, 26+$movb);
$pdf->MultiCell(100, 5, 'Pies Saludables', 0, 'C', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size);
$pdf->SetXY(25, 35+$movb);
$pdf->MultiCell(100, 5, 'BOLETA DE PAGO DE SALARIOS', 0, 'C', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size-1);
$pdf->SetXY(25, 45+$movb);
$pdf->MultiCell(100, 5, $this->fechaCastellano($FechaDesde, $FechaHasta), 0, 'C', 0);

$pdf->SetXY(15, 60+$movb);
$pdf->MultiCell(100, 5, "Nombre: ".$nombre, 0, 'L', 0);

$pdf->SetXY(15, 65+$movb);
$pdf->MultiCell(100, 5, "C.I.P.: ".$object->array_options["options_cip001"], 0, 'L', 0);

$pdf->SetXY(15, 70+$movb);
$pdf->MultiCell(100, 5, "Cargo: ".$object->array_options["options_cargo001"], 0, 'L', 0);

$pdf->SetXY(15, 80+$movb);
$pdf->MultiCell(100, 5, $object->label.":", 0, 'L', 0);
$pdf->SetXY(95, 80+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_monto001"]), 0, 'R', 0);

$pdf->SetXY(15, 90+$movb);
$pdf->MultiCell(100, 5, "Total Ganado:", 0, 'L', 0);
$pdf->SetXY(95, 90+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_monto001"]), 0, 'R', 0);
$pdf->SetXY(95, 92+$movb);
$pdf->MultiCell(22, 5, "=========", 0, 'R', 0);

$pdf->SetXY(15, 100+$movb);
$pdf->MultiCell(100, 5, "(-) DESCUENTOS:", 0, 'L', 0);

$pdf->SetXY(15, 105+$movb);
$pdf->MultiCell(105, 5, "Seg. Social:", 0, 'L', 0);
$pdf->SetXY(95, 105+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_segsoc001"]), 0, 'R', 0);

$pdf->SetXY(15, 110+$movb);
$pdf->MultiCell(105, 5, "Seg. Educativo:", 0, 'L', 0);
$pdf->SetXY(95, 110+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_segedu001"]), 0, 'R', 0);

$pdf->SetXY(15, 115+$movb);
$pdf->MultiCell(105, 5, "Imp. S/R:", 0, 'L', 0);
$pdf->SetXY(95, 115+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_impsr001"]), 0, 'R', 0);

$pdf->SetXY(15, 120+$movb);
$pdf->MultiCell(105, 5, "Vales Efectivo:", 0, 'L', 0);
$pdf->SetXY(95, 120+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_vale001"]), 0, 'R', 0);

$pdf->SetXY(15, 125+$movb);
$pdf->MultiCell(105, 5, "Otros:", 0, 'L', 0);
$pdf->SetXY(95, 125+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_otros001"]), 0, 'R', 0);

$pdf->SetXY(15, 130+$movb);
$pdf->MultiCell(105, 5, "Total Descuentos:", 0, 'L', 0);
$pdf->SetXY(95, 130+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_descu001"]), 0, 'R', 0);

$pdf->SetXY(15, 135+$movb);
$pdf->MultiCell(105, 5, "SUELDO NETO A PAGAR:", 0, 'L', 0);
$pdf->SetXY(95, 135+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->amount), 0, 'R', 0);

$pdf->SetXY(95, 137+$movb);
$pdf->MultiCell(22, 5, "=========", 0, 'R', 0);

$pdf->SetXY(15, 143+$movb);
$pdf->MultiCell(105, 5, "Comentario:", 0, 'L', 0);
$pdf->SetXY(20, 148+$movb);
$pdf->MultiCell(95, 5, $object->note, 0, 'L', 0);

$posY = $pdf->getY()+3;

$pdf->SetXY(15, $posY+$movb);
$pdf->MultiCell(105, 5, "Recibí conforme", 0, 'L', 0);

$posY = $pdf->getY();
$pdf->SetXY(15, $posY+$movb);
$pdf->MultiCell(105, 5, "Firma
_____________________________

Cédula: _____/____________/___________
", 0, 'L', 0);

//echo "<pre>";
//print_r($object);

$movd = 140;
$pdf->SetFont($default_font_type, 'B', $default_font_size);

$pdf->SetXY(25+$movd, 15+$movb);
$pdf->MultiCell(100, 5, 'INVERSIONES GENERALES ARIAS S.A.', 0, 'C', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size - 1);
$pdf->SetXY(25+$movd, 20+$movb);
$pdf->MultiCell(100, 5, 'RUC: 2141703-1-763891 dv 58', 0, 'C', 0);

$pdf->SetXY(25+$movd, 26+$movb);
$pdf->MultiCell(100, 5, 'Pies Saludables', 0, 'C', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size);
$pdf->SetXY(25+$movd, 35+$movb);
$pdf->MultiCell(100, 5, 'BOLETA DE PAGO DE SALARIOS', 0, 'C', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size-1);
$pdf->SetXY(25+$movd, 45+$movb);
$pdf->MultiCell(100, 5, $this->fechaCastellano($FechaDesde, $FechaHasta), 0, 'C', 0);

$pdf->SetXY(15+$movd, 60+$movb);
$pdf->MultiCell(100, 5, "Nombre: ".$nombre, 0, 'L', 0);

$pdf->SetXY(15+$movd, 65+$movb);
$pdf->MultiCell(100, 5, "C.I.P.: ".$object->array_options["options_cip001"], 0, 'L', 0);

$pdf->SetXY(15+$movd, 70+$movb);
$pdf->MultiCell(100, 5, "Cargo: ".$object->array_options["options_cargo001"], 0, 'L', 0);

$pdf->SetXY(15+$movd, 80+$movb);
$pdf->MultiCell(100, 5, $object->label.":", 0, 'L', 0);
$pdf->SetXY(95+$movd, 80+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_monto001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 90+$movb);
$pdf->MultiCell(100, 5, "Total Ganado:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 90+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_monto001"]), 0, 'R', 0);

$pdf->SetXY(95+$movd, 92+$movb);
$pdf->MultiCell(22, 5, "=========", 0, 'R', 0);

$pdf->SetXY(15+$movd, 100+$movb);
$pdf->MultiCell(100, 5, "(-) DESCUENTOS:", 0, 'L', 0);


$pdf->SetXY(15+$movd, 105+$movb);
$pdf->MultiCell(105, 5, "Seg. Social:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 105+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_segsoc001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 110+$movb);
$pdf->MultiCell(105, 5, "Seg. Educativo:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 110+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_segedu001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 115+$movb);
$pdf->MultiCell(105, 5, "Imp. S/R:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 115+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_impsr001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 120+$movb);
$pdf->MultiCell(105, 5, "Vales Efectivo:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 120+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_vale001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 125+$movb);
$pdf->MultiCell(105, 5, "Otros:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 125+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_otros001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 130+$movb);
$pdf->MultiCell(105, 5, "Total Descuentos:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 130+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->array_options["options_descu001"]), 0, 'R', 0);

$pdf->SetXY(15+$movd, 135+$movb);
$pdf->MultiCell(105, 5, "SUELDO NETO A PAGAR:", 0, 'L', 0);
$pdf->SetXY(95+$movd, 135+$movb);
$pdf->MultiCell(22, 5, "B/. ".price($object->amount), 0, 'R', 0);

$pdf->SetXY(95+$movd, 137+$movb);
$pdf->MultiCell(22, 5, "=========", 0, 'R', 0);

$pdf->SetXY(15+$movd, 143+$movb);
$pdf->MultiCell(105, 5, "Comentario:", 0, 'L', 0);
$pdf->SetXY(20+$movd, 148+$movb);
$pdf->MultiCell(95, 5, $object->note, 0, 'L', 0);

$posY = $pdf->getY()+3;

$pdf->SetXY(15+$movd, $posY+$movb);
$pdf->MultiCell(105, 5, "Recibí conforme", 0, 'L', 0);

$posY = $pdf->getY();
$pdf->SetXY(15+$movd, $posY+$movb);
$pdf->MultiCell(105, 5, "Firma
_____________________________

Cédula: _____/____________/___________
", 0, 'L', 0);


/*$pdf->Rect(15, 40+$mov, 80, 5, 'D');
$object->fetch_user($object->fk_user);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->Text(15, 41+$mov , "Claimant's name: ");
$pdf->SetFont($default_font_type, 'B', $default_font_size - 2);
$pdf->Text(40, 41+$mov , $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)));
$pdf->Rect(15, 45+$mov, 80, 5, 'D');
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->Text(15, 46+$mov ,'Dept/Branch');

$Fec = dol_print_date($objp->dp , "day", false, $outputlangs, true);
$Gec = explode("/", $Fec);

$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->Rect(120, 40+$mov, 30, 5, 'D');
$pdf->Text(120, 41+$mov ,'Date ');


$pdf->Rect(150, 40+$mov, 7, 5, 'D');
$pdf->Text(151, 41+$mov , $Gec[0]);


$pdf->Rect(157, 40+$mov, 18, 5, 'D');

$pdf->SetXY(157, 41+$mov);
$pdf->MultiCell(18, 5, $month_names[$Gec[1]], 0, 'C', 0);

//$pdf->Text(163, 41+$mov , $month_names[$Gec[1]]);


$pdf->Rect(175, 40+$mov, 17, 5, 'D');
$pdf->Text(179, 41+$mov , $Gec[2]);
//echo "<pre>";
//print_r($conf);
$currency = !empty($currency) ? $currency : $conf->currency;
$titrecurr = $conf->currency; //$outputlangs->transnoentitiesnoconv("Currency".$currency);

$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->Rect(120, 45+$mov, 30, 5, 'D');
$pdf->Text(120, 46+$mov ,'Total Amount ');

$pdf->Rect(150, 45+$mov, 25, 5, 'D');
$pdf->SetXY(154, 46+$mov);
$pdf->MultiCell(15, 5, $titrecurr, 0, 'C', 0);

$pdf->Rect(175, 45+$mov, 17, 5, 'D');
$pdf->SetXY(172.5, 46+$mov);
$pdf->SetFont('', '', $default_font_size - 3.6);
$pdf->MultiCell(20, 10, price($objp->Amount), 0, 'R', 0);//

$pdf->Rect(120, 50+$mov, 30, 5, 'D');
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->Text(120, 51+$mov ,'VOUCHER NUMBER');

$pdf->Rect(150, 50+$mov, 42, 5, 'D');
$pdf->SetXY(154, 51+$mov);
$pdf->MultiCell(30, 5, $object->id, 0, 'C', 0);


if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
$proj = new Project($db);
$proj->fetch($object->fk_project);

//print_r($proj);
$pdf->Rect(120, 55+$mov, 30, 5, 'D');
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->Text(120, 56+$mov ,'Project Ref');

$pdf->Rect(150, 55+$mov, 42, 5, 'D');
$pdf->SetXY(156, 56+$mov);
$pdf->MultiCell(30, 5, $proj->ref, 0, 'C', 0);



$pdf->Rect(15, 65+$mov, 177, 60, 'D');

$pdf->SetXY(15, 66+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 2);
$pdf->MultiCell(60, 5, "Reason for Expenditure ", 0, 'L', 0);

$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->SetXY(156, 66+$mov);
$pdf->MultiCell(30, 5, "Amount ".$titrecurr, 0, 'C', 0);
$pdf->Rect(150, 65+$mov, 42, 5, 'D');


$pdf->Rect(15, 120+$mov, 177, 5, 'D');

$pdf->Rect(150, 65+$mov, 42, 60, 'D');

$pdf->SetXY(15, 75+$mov);
$pdf->SetFont($default_font_type, '', $default_font_size - 2);
$pdf->MultiCell(100, 5, $object->label, 0, 'L', 0);

$pdf->SetXY(15, 80+$mov);
$pdf->SetFont($default_font_type, '', $default_font_size - 2);
$pdf->MultiCell(120, 5, $object->note, 0, 'L', 0);

$pdf->SetXY(161, 75+$mov);
$pdf->SetFont($default_font_type, '', $default_font_size - 2);
$pdf->MultiCell(20, 10, price($objp->Amount), 0, 'C', 0);//

$pdf->SetXY(120, 121+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 2);
$pdf->MultiCell(100, 5, "Total", 0, 'L', 0);

$pdf->SetXY(161, 121+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 2);
$pdf->MultiCell(20, 10, price($objp->Amount), 0, 'C', 0);//

$pdf->Rect(15, 130+$mov, 177, 10, 'D');

$pdf->SetXY(15, 131+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->MultiCell(50, 10, "Total Claim Words", 0, 'L', 0);//

$pdf->SetXY(15, 136+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 2);
$pdf->MultiCell(200, 10, $this->numberTowords($objp->Amount). " " . $titrecurr, 0, 'L', 0);//

}

$object->fetch_user($object->fk_user_author);
$pdf->SetXY(15, 146+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->MultiCell(80, 10, "Claim prepared by: ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)), 0, 'L', 0);//

$pdf->SetXY(15, 151+$mov);
$pdf->MultiCell(50, 10, "Signed / Date: ", 0, 'L', 0);//

$pdf->Rect(15, 145+$mov, 80, 15, 'D');


$object->fetch_user($object->fk_user_author);
$pdf->SetXY(15, 166+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->MultiCell(80, 10, "Claim aproved by: ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)), 0, 'L', 0);//
$pdf->SetXY(15, 171+$mov);
$pdf->MultiCell(50, 10, "Signed / Date: ", 0, 'L', 0);//


$pdf->Rect(15, 165+$mov, 80, 15, 'D');


$object->fetch_user($object->fk_user_author);
$pdf->SetXY(115, 156+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 4);
$pdf->MultiCell(70, 10, "Received in cash the balance stated above", 0, 'C', 0);//

$pdf->SetXY(113, 161+$mov);
$pdf->SetFont($default_font_type, 'B', $default_font_size - 3);
$pdf->MultiCell(70, 10, "Signed: ", 0, 'L', 0);//

$pdf->Rect(112, 155+$mov, 80, 15, 'D');*/
				
				// set image scale factor
				//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				
				/*// set some language-dependent strings (optional)
				if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
				    require_once(dirname(__FILE__).'/lang/eng.php');
				    $pdf->setLanguageArray($l);
				}*/
				
				// ---------------------------------------------------------
				

				

		
				
				// ---------------------------------------------------------
				}
				$pdf->Close();
				
				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0)
				{
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1; // No error
				
//Close and output PDF document
//$pdf->Output('example_002.pdf', 'I');
	
	}

}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF		$pdf           	Object PDF
	 *	@param  Expedition	$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf, $mysoc;

		$sign = 1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', 'B', $default_font_size - 1);

		// Tableau total
		$col1x = $this->posxweightvol - 50; $col2x = $this->posxweightvol;
		/*if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}*/
		if (empty($conf->global->SHIPPING_PDF_HIDE_ORDERED)) $largcol2 = ($this->posxqtyordered - $this->posxweightvol);
		else $largcol2 = ($this->posxqtytoship - $this->posxweightvol);

		$useborder = 0;
		$index = 0;

		$totalWeighttoshow = '';
		$totalVolumetoshow = '';

		// Load dim data
		$tmparray = $object->getTotalWeightVolume();
		$totalWeight = $tmparray['weight'];
		$totalVolume = $tmparray['volume'];
		$totalOrdered = $tmparray['ordered'];
		$totalToShip = $tmparray['toship'];
		// Set trueVolume and volume_units not currently stored into database
		if ($object->trueWidth && $object->trueHeight && $object->trueDepth)
		{
			$object->trueVolume = price(($object->trueWidth * $object->trueHeight * $object->trueDepth), 0, $outputlangs, 0, 0);
			$object->volume_units = $object->size_units * 3;
		}

		if ($totalWeight != '') $totalWeighttoshow = showDimensionInBestUnit($totalWeight, 0, "weight", $outputlangs);
		if ($totalVolume != '') $totalVolumetoshow = showDimensionInBestUnit($totalVolume, 0, "volume", $outputlangs);
		if (!empty($object->trueWeight)) $totalWeighttoshow = showDimensionInBestUnit($object->trueWeight, $object->weight_units, "weight", $outputlangs);
		if (!empty($object->trueVolume)) $totalVolumetoshow = showDimensionInBestUnit($object->trueVolume, $object->volume_units, "volume", $outputlangs);

		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("Total"), 0, 'L', 1);

		if (empty($conf->global->SHIPPING_PDF_HIDE_ORDERED))
		{
			$pdf->SetXY($this->posxqtyordered, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->posxqtytoship - $this->posxqtyordered, $tab2_hl, $totalOrdered, 0, 'C', 1);
		}

		if (empty($conf->global->SHIPPING_PDF_HIDE_QTYTOSHIP))
		{
			$pdf->SetXY($this->posxqtytoship, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->posxpuht - $this->posxqtytoship, $tab2_hl, $totalToShip, 0, 'C', 1);
		}

		if (!empty($conf->global->SHIPPING_PDF_DISPLAY_AMOUNT_HT))
		{
			$pdf->SetXY($this->posxpuht, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->posxtotalht - $this->posxpuht, $tab2_hl, '', 0, 'C', 1);

			$pdf->SetXY($this->posxtotalht, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxtotalht, $tab2_hl, price($object->total_ht, 0, $outputlangs), 0, 'C', 1);
		}

		if (empty($conf->global->SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME))
		{
			// Total Weight
			if ($totalWeighttoshow)
			{
				$pdf->SetXY($this->posxweightvol, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell(($this->posxqtyordered - $this->posxweightvol), $tab2_hl, $totalWeighttoshow, 0, 'C', 1);

				$index++;
			}
			if ($totalVolumetoshow)
			{
				$pdf->SetXY($this->posxweightvol, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell(($this->posxqtyordered - $this->posxweightvol), $tab2_hl, $totalVolumetoshow, 0, 'C', 1);

				$index++;
			}
			if (!$totalWeighttoshow && !$totalVolumetoshow) $index++;
		}

		$pdf->SetTextColor(0, 0, 0);

		return ($tab2_top + ($tab2_hl * $index));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = -1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		if (empty($hidetop))
		{
			$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);

			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxqtyordered - $this->posxdesc, 2, $outputlangs->transnoentities("Description"), '', 'L');
		}

		if (empty($conf->global->SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME))
		{
			$pdf->line($this->posxweightvol - 1, $tab_top, $this->posxweightvol - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxweightvol - 1, $tab_top + 1);
				$pdf->MultiCell(($this->posxqtyordered - $this->posxweightvol), 2, $outputlangs->transnoentities("WeightVolShort"), '', 'C');
			}
		}

		if (empty($conf->global->SHIPPING_PDF_HIDE_ORDERED))
		{
			$pdf->line($this->posxqtyordered - 1, $tab_top, $this->posxqtyordered - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxqtyordered - 1, $tab_top + 1);
				$pdf->MultiCell(($this->posxqtytoship - $this->posxqtyordered), 2, $outputlangs->transnoentities("QtyOrdered"), '', 'C');
			}
		}

		if (empty($conf->global->SHIPPING_PDF_HIDE_QTYTOSHIP))
		{
			$pdf->line($this->posxqtytoship - 1, $tab_top, $this->posxqtytoship - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxqtytoship, $tab_top + 1);
				$pdf->MultiCell(($this->posxpuht - $this->posxqtytoship), 2, $outputlangs->transnoentities("QtyToShip"), '', 'C');
			}
		}

		if (!empty($conf->global->SHIPPING_PDF_DISPLAY_AMOUNT_HT)) {
			$pdf->line($this->posxpuht - 1, $tab_top, $this->posxpuht - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxpuht - 1, $tab_top + 1);
				$pdf->MultiCell(($this->posxtotalht - $this->posxpuht), 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');
			}

			$pdf->line($this->posxtotalht - 1, $tab_top, $this->posxtotalht - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxtotalht - 1, $tab_top + 1);
				$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxtotalht), 2, $outputlangs->transnoentities("TotalHT"), '', 'C');
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Expedition	$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs, $mysoc;

		$langs->load("orders");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->SHIPPING_DRAFT_WATERMARK)))
		{
					pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->SHIPPING_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		// Show barcode
		if (!empty($conf->barcode->enabled))
		{
			$posx = 105;
		} else {
			$posx = $this->marge_gauche + 3;
		}
		//$pdf->Rect($this->marge_gauche, $this->marge_haute, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 30);
		if (!empty($conf->barcode->enabled))
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}

		$pdf->SetDrawColor(128, 128, 128);
		if (!empty($conf->barcode->enabled))
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}


		$posx = $this->page_largeur - $w - $this->marge_droite;
		$posy = $this->marge_haute;

		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("SendingSheet");
		$pdf->MultiCell($w, 4, $title, '', 'R');

		$pdf->SetFont('', '', $default_font_size + 1);

		$posy += 5;

		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($w, 4, $outputlangs->transnoentities("RefSending")." : ".$object->ref, '', 'R');

		// Date planned delivery
		if (!empty($object->date_delivery))
		{
				$posy += 4;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 4, $outputlangs->transnoentities("DateDeliveryPlanned")." : ".dol_print_date($object->date_delivery, "day", false, $outputlangs, true), '', 'R');
		}

		if (!empty($object->thirdparty->code_client))
		{
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}


		$pdf->SetFont('', '', $default_font_size + 3);
		$Yoff = 25;

		// Add list of linked orders
		$origin = $object->origin;
		$origin_id = $object->origin_id;

		// TODO move to external function
		if (!empty($conf->$origin->enabled))     // commonly $origin='commande'
		{
			$outputlangs->load('orders');

			$classname = ucfirst($origin);
			$linkedobject = new $classname($this->db);
			$result = $linkedobject->fetch($origin_id);
			if ($result >= 0)
			{
				//$linkedobject->fetchObjectLinked()   Get all linked object to the $linkedobject (commonly order) into $linkedobject->linkedObjects

				$pdf->SetFont('', '', $default_font_size - 2);
				$text = $linkedobject->ref;
				if ($linkedobject->ref_client) $text .= ' ('.$linkedobject->ref_client.')';
				$Yoff = $Yoff + 8;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - $w, $Yoff);
				$pdf->MultiCell($w, 2, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), 0, 'R');
				$Yoff = $Yoff + 3;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - $w, $Yoff);
				$pdf->MultiCell($w, 2, $outputlangs->transnoentities("OrderDate")." : ".dol_print_date($linkedobject->date, "day", false, $outputlangs, true), 0, 'R');
			}
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur = '';
		 	// Add internal contact of origin element if defined
			$arrayidcontact = array();
			if (!empty($origin) && is_object($object->$origin)) $arrayidcontact = $object->$origin->getIdContact('internal', 'SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user(reset($arrayidcontact));
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;

			$hautcadre = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("Sender").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(255, 255, 255);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');


			// If SHIPPING contact defined, we use it
			$usecontact = false;
			$arrayidcontact = $object->$origin->getIdContact('external', 'SHIPPING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			// Recipient name
			if ($usecontact && ($object->contact->fk_soc != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, (!empty($object->contact) ? $object->contact : null), $usecontact, 'targetwithdetails', $object);

			// Show recipient
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
			if ($this->page_largeur < 210) $widthrecbox = 84; // To work with US executive format
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Recipient").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 0);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF		$pdf     			PDF
	 *  @param	Expedition	$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int			$hidefreetext		1=Hide free text
	 *  @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'SHIPPING_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
