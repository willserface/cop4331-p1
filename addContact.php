<?php
	$inData = getRequestInfo();
	
	$contact = $inData["name"];
	$email = $inData["email"];
	$phone = $inData["phone"];
	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	$conn = new mysqli("localhost", "root", "COP4331COP", "shark-paradise");
	if ($conn->connect_error) 
	{
		returnWithError( $conn->connect_error );
	} 
	else
	{
		$User = $conn->prepare("SELECT ID FROM Users WHERE Login=? AND Password =?")
		$User->bind_param("ss", $username, hash("sha256", $password))
		$User->execute();
		$result = $User->get_result();

		if( $row = $result->fetch_assoc()  )
		{
			$Userid = $row['ID'];
			$stmt = $conn->prepare("INSERT into Contact (UserId,Name,Phone,Email) VALUES(?,?)");
			$stmt->bind_param("ss", $, $contact);
			$stmt->execute();
			returnWithError("201");
		}
		else
		{
			returnWithError("401");
		}
		$User->close();
		$stmt->close();
		$conn->close();
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