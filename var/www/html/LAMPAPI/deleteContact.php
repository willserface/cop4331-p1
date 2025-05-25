<?php

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    $conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");
    if ($conn->connect_error) 
    {
        returnWithError( $conn->connect_error );
    } 
    else
    {
        $stmt = $conn->prepare("DELETE FROM Contacts WHERE Login=? AND Password=?");
        $stmt->bind_param("ss", $username , $password);
	    $stmt->execute();
        $stmt->close();
		$conn->close();
        returnWithError("204");

    }

    function returnWithError( $err )
	{
		$retValue = '{"error":"' . $err . '"}';
		sendResultInfoAsJson( $retValue );
	}

?>