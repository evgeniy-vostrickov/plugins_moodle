<?php
	define("CURLDOMENONLINEEDU", "https://test.online.edu.ru");
	// define("CURLDOMENONLINEEDU", "http://localhost:3001");
	define("MAILUSERERRORONLINEEDU", 2);
	
	function curlsend($path, $jsonstr, $reqtype='POST'){
		$ch = curl_init();
		//$verbose = fopen(__DIR__.'/_temp.log', 'a');
		
		$options = array(
			CURLOPT_URL => CURLDOMENONLINEEDU.$path,
			// CURLOPT_VERBOSE => true,
			//CURLOPT_STDERR => $verbose,
			
			//CURLOPT_SSL_VERIFYHOST => 2, //Рекоментует PHP
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_CUSTOMREQUEST => $reqtype,
			CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $jsonstr,
			// CURLOPT_RETURNTRANSFER => true
		);

		curl_setopt_array($ch, $options);

		
		if(!$response = curl_exec($ch))
		{
			trigger_error(curl_error($ch));
			mtrace("Ошибка запроса CURL: ");
		}
		mtrace(" Запрос выполнился");
		//var_dump($response);
		// print_r($ch);
		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		mtrace('HTTP_CODE ' . $httpcode);
		$arr_return = array('httpcode' => $httpcode, 'responce' => $response);
		
		curl_close($ch);
		return($arr_return);
	}
	
	
	// function errormail($mailtext='Ошибка')
	// {
	// 	GLOBAL $DB;
	// 	$userObj = $DB->get_record("user", ['id' => MAILUSERERRORONLINEEDU]); // ID администратора
		
	// 	email_to_user($userObj, $userObj, 'Важно! от Vodin online.edu.ru', $mailtext, $mailtext, ", ", true);
		
	// }
?>