<?php
    $inData = getRequestInfo();

    $username = $inData["username"];
    $firstName = $inData["name"]["first"];
    $lastName = $inData["name"]["last"];
    $password = $inData["password"];
    $id = $_SERVER['PHP_AUTH_USER'];

    $conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");
	if ($conn->connect_error) 
	{
	    returnWithError( $conn->connect_error );
    } 
    else
    {
        $newusername = $conn->prepare("SELECT Login FROM Users WHERE Login= ?");
        $newusername->bind_param("s", $username);
        $newusername->execute();

        if($username != NULL && $newusername == NULL)
        {
            $stmt = $conn->prepare("UPDATE Contact SET Name = ? WHERE Name = ?");
            $stmt->bind_param("ss", $username, $id);
            $stmt->execute();
        }

        if($firstName != NULL)
        {
            $updatefirst = $conn->prepare("UPDATE User SET FirstName = ? WHERE Login = ?");
            $updatefirst->bind_param("ss", $firstName, $id);
            $updatefirst->execute();
        }
        if($lastName != NULL)
        {
            $updateLast = $conn->prepare("UPDATE User SET LastName = ? WHERE Login = ?");
            $updateLast->bind_param("ss", $lastName, $id);
            $updateLast->execute();
        }
        if($password != NULL)
        {
            $updatepass = $conn->prepare("UPDATE User SET Password = ? WHERE Login = ?")
            $updatepass->bind_param("ss", $password, $id);
            $updatepass->execute();
        }
        $stmt->close();
        $newusername->close();
        $updatefirst->close();
        $updateLast->close();
        $updatepass->close();
		$conn->close();

    }

    function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}
?>