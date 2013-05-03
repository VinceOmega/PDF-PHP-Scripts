<?php
ini_set('max_execution_time', 0); //300 seconds = 5 minutes
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/config/tcpdf_config.php');
require_once('../../header.inc.php');
/*
PHP PDF generation script written by Larry Stanfield
Contact @ vince.omega@gmail.com
Powered by the open source pdf library tcpdf
www.tcpdf.org
There you will find documentation on the use of TCPDF
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

$today = "Produced on:".Date('M d, Y - G:i:s');
$custNBranch = "Customer: ".$_GET['custcodes']." Branch: ".$branch;
$month = date('m');
$year = date('Y');

//new PDF

// create new PDF document
ob_clean(); //Begins to buffer.
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information, header data, margins, page breaks...etc
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SearchTec');
$pdf->SetTitle('Results for'.$_GET['custcodes'].'on'.$today);
$pdf->SetSubject('Tax Watch Results');
$pdf->SetKeywords('Tax, Watch, Search, Results');

$pdf->setHeaderData('', '', 'Search Tec', $today." ".$custNBranch, array(0, 0, 0), array(0, 0, 0));
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$l = '';
$pdf->setLanguageArray($l);
//$pdf->setImageScale(.50);
//$pdf->setCellPadding(0);

// set Font and create Page
$pdf->SetFont('helvetica', 'B', 20);
$pdf->AddPage();
$pdf->Write(0, 'Tax Watch Report', '', 0, 'L', true, 0, false, false, 0);
$pdf->SetFont('helvetica', '', 8);

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
	
$css = <<<EOF
<style type="text/css">

td{
margin-bottom: 75px;
margin-top: 75px;
}

.notes{

width: 400px;
font-weight: bold;
}


</style>
EOF;

$pdf->writeHTML($css, true, false, false, false, ''); 	
	
/*------------------------------------------------------------------------------------------------------------------------*/

//run query, check next_run, write tables


$table =<<<TBL
<table cellspacing="3" cellpadding ="1" border="0">
<thead>
<tr nobr="true" width="148px">
	<td><h3>Loan ID</h3></td>
	<td><h3>Borrower</h3></td>
	<td><h3>Address</h3></td>
	<td><h3>Delinquency</h3></td>
	<td><h3>Cover Date</h3></td>
	<td><h3>Next Run</h3></td>
</tr>
<hr>
</thead>
TBL;


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
		$cover_date = $current_row['cover_date'];
		$notes = $current_row['notes'];
		
		
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
	
$table .=  <<<TBL
<tr nobr="true" valign="top" class="entry" width="148px">
TBL;
$table .=<<<TBL
<td>
TBL;
$table .= $loan_id;
$table .= <<<TBL
</td>
TBL;
$table .=<<<TBL
<td>
TBL;
$table .= $borrower;
$table .= <<<TBL
</td>
TBL;
$table .=<<<TBL
<td>
TBL;
$table .= $address;
$table .=<<<TBL
<br/>
TBL;
$table .= $city;
$table .=<<<TBL
, &#32;
TBL;
$table .= $state;
$table .=<<<TBL
 &#32;
TBL;
$table .=<<<TBL
</td>
TBL;
$table .=<<<TBL
<td>
TBL;
if(is_numeric($dStatus)){
						setlocale(LC_MONETARY, 'en_US');
						$table .= "$".number_format($dStatus, 2);
				}
					else {
						$table .= $dStatus;
				}
$table .=<<<TBL
</td>
TBL;
$table .=<<<TBL
<td>
TBL;
$table .= $cover_date;
$table .=<<<TBL
</td>
TBL;
$table .=<<<TBL
<td>
TBL;
$table .= $next_run;
$table .=<<<TBL
</td>
TBL;
if($notes != null){ //Checks for notes
				$table .= <<<EOD
				</tr><br/><tr nobr="true" width="148px"><td class="notes" colspan="4"  align="left"> Notes: &#32;&#32;
EOD;

			$table .= $notes;
				$table .=  <<<EOD
				</td>
EOD;
}
	$table .= <<<TBL
	</tr><hr><br/><br/>
TBL;

		unset($current_row);
	}
	
}

$table .= <<<TBL
</table>
TBL;

//exit;
$pdf->writeHTML($table, true, false, false, true, ''); // writes the data into the pdf

ob_end_clean(); //Ends buffering
$pdf->Output($filename, 'I');//Close
	
	?>	