<?php
    $inData = getRequestInfo();

    $phone = $inData["phone"];
    $username = $inData["username"];
    $firstName = $inData["name"]["first"];
    $lastName = $inData["name"]["last"];
    $email = $inData["email"];
    $id = $_SERVER['PHP_AUTH_USER'];

    $conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");
	if ($conn->connect_error) 
	{
	    returnWithError( $conn->connect_error );
    } 
    else
    {
        if($firstName != NULL)
        {
            $updatefirst = $conn->prepare("UPDATE Contacts SET FirstName = ? WHERE ID = ?");
            $updatefirst->bind_param("ss", $firstName, $id);
            $updatefirst->execute();
        }
        if($lastName != NULL)
        {
            $updateLast = $conn->prepare("UPDATE Contacts SET LastName = ? WHERE ID = ?");
            $updateLast->bind_param("ss", $lastName, $id);
            $updateLast->execute();
        }
        if($email != NULL)
        {
            $updatemail = $conn->prepare("UPDATE Contacts SET Email = ? WHERE ID = ?");
            $updatemail->bind_param("ss", $email, $id);
            $updatemail->execute();
        }
        if($phone != NULL)
        {
            $updatephone = $conn->prepare("UPDATE Contacts SET Phone = ? WHERE ID =?");
            $updatephone->bind_param("ss", $phone, $id);
            $updatephone->execute();
        }
        $stmt->close();
        $newusername->close();
        $updatefirst->close();
        $updateLast->close();
        $updatemail->close();
        $updatephone->close();
		$conn->close();

    }

    function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}
?>