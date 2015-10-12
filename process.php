<?php

require 'WebBot/autoloader.php';
use WebBot\lib\WebBot\Bot as Bot;

function getSearchUrl($zip, $speciality, $offset) {
	return 'https://www.zocdoc.com/search/searchresults?Address='.$zip.'&ForceReskin=false&Gender=-1&HospitalId=-1&InsuranceId=-1&InsurancePlanId=-1&LanguageId=1&ProcedureId=12&SpecialtyId='.$speciality.'&SubSpecialtyId=-1&LimitToThisSpecialty=false&ExcludedSpecialtyIds=&Offset='.$offset.'&PatientTypeChild=false&genderChanged=false&languageChanged=false&IsPolarisRevealed=False&StartDate=null&_=1444650384181';
}

function getDoctorsList($ids) {
	$date_end = date("Y-m-d", strtotime(date("Y-m-d")."+3 day"));
	return 'https://www.zocdoc.com/api/1/appointments/doctor_location/'.$ids.'?start='.$date_end.'&length=3&procedure_id=12&refinement_id=-1&insurance_plan_id=-1&fullDoctorInformation=false';
}

function filter_result($result) {
	$result = str_replace("for(;;);", "", $result);
	return $result;
}

function execute_request($key, $url) {
	$urls = array(
		"$key" => $url
	);
	$bot = new Bot($urls);
	$bot->execute();
	$document = array_pop($bot->getDocuments());

	if ($document) {
		return $document->getHttpResponse()->getBody();	
	}
	return false;
	
}

function process_list($zip, $cat) {
	$results = array();
	$response = array();

	for ($i = 0; $i <= 90; $i +=10) {
		$body = execute_request('search', getSearchUrl($zip, $cat, $i));

		$search = json_decode(filter_result($body));
		$ids = $search->ids;

		$body = execute_request('doctors', getDoctorsList($ids));

		if ($body) {
			$result = json_decode(filter_result($body));

			$list = $result->doctor_locations;
			foreach ($list as $key => $value) {
				$results[] = $value;
			}	
		} else {
			break;
		}
	}
	$response['doctor_locations'] = $results;
	return json_encode($response);
}

if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'crawl_zocdoc':
			$zip = $_GET["zip"]; $cat = $_GET["cat"];
			
			$response = process_list($zip, $cat);
			echo $response;
			break;
	}
} else {
	die('action not set');
}
	
?>