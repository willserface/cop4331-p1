<?php
	$inData = getRequestInfo();
	
    $username = $inData["username"];
    $firstName = $inData["name"]["first"];
    $lastName = $inData["name"]["last"];
    $password = $inData["password"];

	$conn = new mysqli("localhost", "Swimmer", "Swiml", "shark-paradise");
	if ($conn->connect_error) 
	{
		returnWithError( $conn->connect_error );
	} 
	else
	{
		$stmt = $conn->prepare("INSERT into Users (FirstName,LastName,Login,Password) VALUES(?,?,?,?)");
		$stmt->bind_param("ssss", $firstName, $lastName, $username, $password);
		$stmt->execute();
		$stmt->close();
		$conn->close();
		returnWithError("201");
	}

	function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	function returnWithError( $err )
	{
		$retValue = '{"error":"' . $err . '"}';
		sendResultInfoAsJson( $retValue );
	}
	
?>
