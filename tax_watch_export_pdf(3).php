<?php
ini_set('max_execution_time', 0); //300 seconds = 5 minutes
require_once('fpdf/fpdf/fpdf.php');
require_once('../../header.inc.php');

/**
@author: Larry Stanfield
PHP PDF generation script written by Larry Stanfield
Contact @ vince.omega@gmail.com
Powered by the open source pdf php class fpdf
www.fpdf.org
There you will find documentation on the use of pdf
*/


if (!isset($_GET['custcodes']))
    $_GET['custcodes'] = '';
if (!isset($_GET['borrower']))
    $_GET['borrower'] = '';
if (!isset($_GET['address']))
    $_GET['address'] = '';
if (!isset($_GET['tax_search_loan_officer_id']))
    $_GET['tax_search_loan_officer_id'] = '';
if (!isset($_GET['loan_identifier']))
    $_GET['loan_identifier'] = '';
if (!isset($_GET['sort_by']))
    $_GET['sort_by'] = '';
	if (!isset($_GET['limit_by_status']))
    $_GET['limit_by_status'] = '';
if (!isset($_GET['limit_by_branch']))
    $_GET['limit_by_branch'] = '';
	if (!isset($_GET['sort_by_qrt']))
    $_GET['sort_by_qrt'] = '';
$branch = '';
$loan_officer = '';
if($_GET['limit_by_branch'] == ''){
	$branch = 'All';
} else {
$branch = $_GET['limit_by_branch'];
}
if(is_numeric($_GET['tax_search_loan_officer_id'])){
$loan_officer = $_GET['tax_search_loan_officer_id'];
} else {
$loan_officer = null;
}



//DEBUG
//echo    $_GET['limit_by_branch'];
//exit;

$today = "Produced on: ".Date('M d, Y - G:i:s');
$custNBranch = "Customer: ".$_GET['custcodes']." Branch: ".$branch;
$month = date('m');
$year = date('Y');


class PDF extends FPDF{

//Page Header
function Header(){

global $today, $custNBranch;

$this->SetFont('Helvetica', 'B', 12);
//Set Title
$this->Cell(250, 10, 'Results for '.$today, 0, 1, 'L');
//Subject
$this->Cell(250, 10, 'SearchTec - '.$custNBranch, 'B', 1, 'L');
//Break Line
$this->Cell(250, 10, 'Tax Watch Report' , 0, 0, 'L');
$this->Ln(10);
$this->SetFont('Helvetica', 'B', 10);
$this->Cell(35, 10, "Loan ID/Officer", 'B', 0, 'L');
$this->Cell(40, 10, "Borrower", 'B', 0, 'L');
$this->Cell(43, 10, "Address", 'B', 0, 'L');
$this->Cell(30, 10, "Delinquency", 'B', 0, 'L');
$this->Cell(25, 10, "Cover Date", 'B', 0, 'L');
$this->Cell(50, 10, "Next Run", 'B', 2, 'L');
$this->SetFont('Helvetica', '', 8);
$this->Ln(3);
}

function entry($margin, $info, $border, $spacing){

	$this->Cell($margin, 8, $info, $border, $spacing, 'L');
	

}
// entryAdjust will adjust the Loan ID and Borrower fields based on length or delimiter.
function entryAdjust($space, $content, $bottom, $flag){
		$i = 0;
		$content = (string)$content;
		
			if(strlen($content) > 23){
				$conArray = array();
				$conArray = str_split($content, 23);
				$k = sizeof($conArray);
					foreach($conArray as $key){
						$i++;
							if($i == $k){
						$this->MultiCell($space + 8, 4, $key."\n", $bottom, 'L');
							$this->SetX(35);
						
						} else {
						$this->MultiCell($space, 4, $key."\n", 0,  'L');
							$this->SetX(35);
						}
							
					}
						if($bottom == 0){

								$this->Ln(-8);
								$this->SetX(75);
								
								} elseif($bottom == 'B') {
							
								
								$this->Ln(-7);
								$this->SetX(70);
								
							}
					
			} elseif($flag == 1) {
					if(strrpos($content, "/") == true){
					$this->SetFont('Helvetica', '', 5);
					$conArray = explode("/", $content);
					foreach($conArray as $key){
					$this->entry($space, $key, $bottom, 0);
					$this->SetX(34);
					}
					$this->SetFont('Helvetica', '', 8);
						}else{
					$this->MultiCell($space, 4,  $content, 0, 'L');
					$this->SetFont('Helvetica', '', 8);
					$y = $this->GetY();
					$this->SetY($y - 4.2);
					$this->SetX(34);
							}
					
			
			}else {
				
				$this->entry($space, $content, $bottom, 0);
			
			}
			
							
}	

function br(){
$this->Ln(10);
}

function Footer()
{
    $this->SetY(-15);
    $this->SetFont('Helvetica', 'I', 8);
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}

}


$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

/*---------------------------------------------------------------------------------------------------------------------------*/

//Connect to database, name file and write sql query

$DB = DB::getCon();
//$DB = new mysqli('StLinux1', 'root', 'pass', '', '22');

$_GET['custcodes'] = $DB->cleanString($_GET['custcodes']);
$_GET['borrower'] = $DB->cleanString($_GET['borrower']);
$_GET['address'] = $DB->cleanString($_GET['address']);
$_GET['tax_search_loan_officer_id'] = $DB->cleanString($_GET['tax_search_loan_officer_id']);
$_GET['loan_identifier'] = $DB->cleanString($_GET['loan_identifier']);
$_GET['sort_by'] = $DB->cleanString($_GET['sort_by']);
$_GET['limit_by_status'] = $DB->cleanString($_GET['limit_by_status'] );
$_GET['limit_by_branch'] = $DB->cleanString($_GET['limit_by_branch']);
$_GET['sort_by_qrt'] = $DB->cleanString($_GET['sort_by_qrt']);

$filename = 'c:/php_temp/download_' . preg_replace('/[^a-z0-9]/i', '_', $DB->cleanString($_GET['custcodes'])) . '.pdf';


$sql = "
    SELECT
        website_userid,
        loan_officer,
        tax_search_loan_id,
        branch,
		loan_identifier,
        borrower,
        address,
        city,
        state_abbreviation,
        zip,
        delinquency_status,
		cover_date,
		next_run,
        notes
    FROM
        (SELECT
            ts.tax_search_id,
            ts.current_tax_search_status_id,
            tslo.name as loan_officer_name,
            IF(tsl.notes IS NULL, '', IF(tsl.notes = '', '', '*')) as has_notes,
            IF(tsr.tax_sales IS NULL, '', IF(tsr.tax_sales = '', '', '*')) as has_tax_sales,
            tsr.delinquent_date,
            tslo.pick_userid as website_userid,
            tslo.name as loan_officer,
            tsl.tax_search_loan_id,
            tsl.branch,
			tsl.loan_identifier,
            tsl.borrower,
            tsl.address,
            tsl.city,
            states.abbreviation as state_abbreviation,
            tsl.zip,
            tsr.delinquency_status,
			DATE_FORMAT(tsr.cover_date,'%m-%d-%Y') as cover_date,
			IF (tsl.frequency_id IS NULL, null, tsl.cycle_id) as next_run,
			tsl.notes
            
        FROM
            tax_search_loans tsl
        INNER JOIN
            tax_search_loan_officers tslo ON tsl.tax_search_loan_officer_id = tslo.tax_search_loan_officer_id
        LEFT JOIN
            states ON tsl.state_id = states.state_id
        LEFT JOIN
            tax_searches ts ON tsl.tax_search_loan_id = ts.tax_search_loan_id
        LEFT JOIN
            tax_search_results tsr ON tsr.tax_search_id = ts.tax_search_id
        WHERE
            tsl.active = 'Y' AND
            tslo.active = 'Y' AND
            (ts.active IS NULL OR ts.active = 'Y') AND
            (tsr.active IS NULL OR tsr.active = 'Y')
			";

if($_GET['limit_by_branch'] != '') {
				 
	$sql .= " AND tsl.branch IN ('" .$_GET['limit_by_branch']. "' ) ";
	} 
      
	$sql.=  " AND tslo.customer_code IN ('" .$_GET['custcodes']. "')
    ";

if ("" != $_GET['tax_search_loan_officer_id']) {
    $sql .= "
                AND tsl.tax_search_loan_officer_id =  " .$loan_officer. "
        ";
}

$sql .= "
        ORDER BY
            tax_search_loan_id DESC, tax_search_id DESC, tax_search_result_id DESC
        ) as t1
    ";
	
if($_GET['sort_by_qrt'] != "" || $_GET['limit_by_status'] != ""){

$sql .= "WHERE
";

}

if($_GET['sort_by_qrt'] != ""){
			
				if($_GET['sort_by_qrt'] == 'march'){
					
						if(date('m') < 3 && date('m') >= 9){
						 $sql .= " t1.next_run IN (1, 5)";
						}
						elseif(date('m') == 12 || date('m') < 3){
							 $sql .= " t1.next_run IN (1, 7)";
						}
						else	{
							$sql .= " t1.next_run = 1";
								}
				}
				
				if($_GET['sort_by_qrt'] == 'june'){
						if(date('m') < 6 || date('m') == 12){
						 $sql .= " t1.next_run IN (2, 6)";
						}
						elseif(date('m') >= 3 && date('m') < 6){
							 $sql .= " t1.next_run IM (2, 7)";
						}
						else {
							$sql .= " t1.next_run = 2";
							}
				}
				
				if($_GET['sort_by_qrt'] == 'september'){
						if(date('m') >= 6 && date('m') < 9){
						 $sql .= " t1.next_run IN (3, 7)";
						}
						else {
							$sql .= " t1.next_run IN (3, 5)";
							}
				}
				
				if($_GET['sort_by_qrt'] == 'december'){
						if(date('m') > 6 && date('m') < 9){
						 $sql .= " t1.next_run IN (4, 6)";
						}
						elseif(date('m') >= 9 && date('m') < 12){
							 $sql .= " t1.next_run = (4, 7)";
						}
						else{
							$sql .= " t1.next_run = 4";
							}
				}

}

if ("" != $_GET['limit_by_status']) {

	if($_GET['sort_by_qrt'] != ""){
		$sql .= " AND
		";
		}
		
    if ('current' == $_GET['limit_by_status']) {
        $sql .= "
            t1.delinquency_status = 'Current'
            ";
    } else if ('open' == $_GET['limit_by_status']) {
        $sql .= "
            t1.current_tax_search_status_id = 1
            ";
    } else if ('delinquent' == $_GET['limit_by_status']) {
        $sql .= "
            t1.delinquency_status != 'Current'
            ";
    } else {
	
	if($_GET['sort_by_qrt'] != ""){
		$sql .= "
            (t1.tax_sales IS NOT NULL
            AND t1.tax_sales != '')
            ";
	
	}
	else {
        $sql .= "
            t1.tax_sales IS NOT NULL
            AND t1.tax_sales != ''
            ";
		}
    }
}

$sql .= "
    GROUP BY
        tax_search_loan_id
    ORDER BY
        '".$_GET['sort_by']."'
    ";
	
/*------------------------------------------------------------------------------------------------------------------------*/

//run query, check next_run, write tables



	$rows = $DB->getRows($sql);
if (count($rows)) {

    $row_cnt = count($rows);

    // write data from the rest of the rows
    for ($i=0; $i < $row_cnt; $i++) {
	
        $current_row = $rows[$i];
        
        $next_run = '[' . $current_row['next_run'] . ']';
		$borrower = $current_row['borrower'];
		$address = $current_row['address'];
		$loan_id = $current_row['loan_identifier'];
		$loan_officer = $current_row['loan_officer'];
		$branch = $current_row['branch'];
		$dStatus = $current_row['delinquency_status'];
		$city = $current_row['city'];
		$state = $current_row['state_abbreviation'];
		$zip = $current_row['zip'];
		$cover_date = $current_row['cover_date'];
		$notes = $current_row['notes'];
		$fulladdress = $address."\n".$city." ".$state." , ".$zip;
		$fulladdress = (string)$fulladdress;
		$idandofficer = $loan_id."\n".$loan_officer;
		$idandofficer = ltrim(rtrim((string)$idandofficer));
		$adjust = 0;
		$k = 0;
		
		$borrower = ltrim(rtrim($borrower));
		
		/*if(strlen($borrower) > 23){
				$conArray = array();
				$conArray = str_split($borrwer, 23);
				$k = sizeof($conArray);
			}
		*/

		
		
	/*	foreach($current_row as $key){
		$list = each($current_row);	
		print_r($list);	
		}
		exit; //DEBUG
		*/
        if ('' != $current_row['next_run']) {
            switch($current_row['next_run']) {
                case 1:
                    $next_run_month = 3;
                    break;
                case 2:
                    $next_run_month = 6;
                    break;
                case 3:
                    $next_run_month = 9;
                    break;
                case 4:
                    $next_run_month = 12;
                    break;
                case 5:
                    if ($month < 3 || $month >= 9) {
                        $next_run_month = 3;
                    } else {
                        $next_run_month = 9;
                    }
                    break;
                case 6:
                    if ($month < 6 || $month == 12) {
                        $next_run_month = 6;
                    } else {
                        $next_run_month = 12;
                    }
                    break;
                case 7:
                    if ($month == 12 || $month < 3) {
                        $next_run_month = 3;
                    } else if ($month >= 3 && $month < 6) {
                        $next_run_month = 6;
                    } else if ($month >= 6 && $month < 9) {
                        $next_run_month = 9;
                    } else if ($month >= 9 && $month < 12) {
                        $next_run_month = 12;
                    }
                    break;
            }
            
            $next_run_year = $year;
            if ($next_run_month <= $month) {
                $next_run_year++;
            }
            $next_run = str_pad($next_run_month,2,'0',STR_PAD_LEFT) . '-' . $next_run_year;
        } else {
            $next_run = 'On Hold';
        }
        
        $current_row['next_run'] = $next_run;
		
//This section loops through the query results and attempts to structure them through table rows and cells. 

if($notes != null){ //Checks for notes

$pdf->entryAdjust(25, $idandofficer, 0, 1);
$pdf->entryAdjust(40, $borrower, 0);
$pdf->MultiCell(60, 4, $fulladdress, 0, 'L');
$pdf->Ln(-7);
$pdf->SetX(135);
//$pdf->entry(60, $fulladdress, 0, 0);
//$pdf->entry(5, $state, 0, 0);
//$pdf->entry(25, $city, 0, 0);
//$pdf->entry(15, $zip, 0, 0);

	
if(is_numeric($dStatus)){
						setlocale(LC_MONETARY, 'en_US');
						$pdf->entry(25, "$".number_format($dStatus, 2), 0, 0);
				}
					else {
						$pdf->entry(25, $dStatus, 0, 0);
				}
$pdf->entry(25, $cover_date, 0, 0);
$pdf->entry(25, $next_run, 0, 0);
$pdf->br();

$pdf->entry(300, $notes, 'B', 1);

} else {
$pdf->entryAdjust(25, $idandofficer, 0, 1);
$pdf->entryAdjust(40, $borrower, 0);
$pdf->MultiCell(60, 4, $fulladdress, 0, 'L');
$pdf->Ln(-8);
$pdf->SetX(135);
//$pdf->entry(60, $fulladdress, 'B', 0);
//$pdf->entry(5, $state, 'B', 0);
//$pdf->entry(25, $city, 'B', 0);
//$pdf->entry(15, $zip, 'B', 0);

	
if(is_numeric($dStatus)){
						setlocale(LC_MONETARY, 'en_US');
						$pdf->entry(25, "$".number_format($dStatus, 2), 0, 0);
				}
					else {
						$pdf->entry(25, $dStatus, 0, 0);
				}
$pdf->entry(25, $cover_date, 0, 0);
$pdf->entry(25, $next_run, 0, 1);
$pdf->Cell(900, 4, "", 'B', 1, 'L');
}

		unset($current_row);
	}
	
}


//exit;

ob_end_clean(); //Ends buffering
$pdf->Output();
$pdf->Close();
?>