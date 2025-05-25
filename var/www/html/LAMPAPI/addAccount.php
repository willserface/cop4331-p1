<?php
	$inData = getRequestInfo();
	
    $username = $inData["username"];
    $firstName = $inData["name"]["first"];
    $lastName = $inData["name"]["last"];
    $password = $inData["password"];

	$conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");
	if ($conn->connect_error) 
	{
		returnWithError( $conn->connect_error );
	} 
	else
	{
		$checkingname = $conn->prepare("SELECT Login From Users WHERE Login = ?");
		$checkingname = bind_param("s", $username);
		$checkingname->execute();
		if($checkingname == NULL)
		{
			$stmt = $conn->prepare("INSERT into Users (FirstName,LastName,Login,Password) VALUES(?,?,?,?)");
			$stmt->bind_param("ssss", $firstName, $lastName, $username, $password);
			$stmt->execute();
			$checkingname->close();
			$stmt->close();
			$conn->close();
			returnWithError("201");
		}
		else
		{
			$checkingname->close();
			returnWithError("400");
		}
		
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