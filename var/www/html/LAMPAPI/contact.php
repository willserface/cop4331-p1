<?php
$inData = getRequestInfo();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$firstName = $inData["name"]["first"];
$lastName = $inData["name"]["last"];

$email = $inData["email"];
$phone = $inData["phone"];

$conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");

if ($conn->connect_error) {
    returnWithError("Failed to connect to MySQL");
} else if (authenticated()) {
    switch ($method) {
        case 'POST':
            postContact();
            break;
        case 'GET':
            getContacts();
            break;
        case 'PUT':
            putContact();
            break;
        case 'DELETE':
            deleteContact();
            break;
    }
} else {
    returnWithError("401");
}

$conn->close();

function getRequestInfo()
{
    return json_decode(file_get_contents('php://input'), true);
}

function sendResultInfoAsJson($obj)
{
    header('Content-type: application/json');
    echo $obj;
}

function returnWithError($err)
{
    $retValue = '{"error":"' . $err . '"}';
    sendResultInfoAsJson($retValue);
}

function returnWithInfo($results)
{
    $retValue = '{"results": ' . $results . ',"error":' . null . '}';
    sendResultInfoAsJson($retValue);
}


function authenticated()
{
    global $conn;
    global $username;
    global $password;

    $auth = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    $auth->bind_param("ss", $username, $password);
    $auth->execute();
    $result = $auth->get_result();

    $authenticated = $result->num_rows == 1;
    $auth->close();
    return $authenticated;
}

function postContact()
{
    global $conn;
    global $username;
    global $name;
    global $email;
    global $phone;

    $create = $conn->prepare("INSERT INTO Contacts (FirstName, LastName, Phone, Email, UserLogin) VALUES (?, ?, ?, ?)");
    $create->bind_param("sssss", $name["first"], $name["last"], $phone, $email, $username);
    $create->execute();

    returnWithError("201");
    $create->close();
}

function getContacts()
{
    global $conn;
    global $username;
    $searchResults = "";
    $searchCount = 0;

    $search = $conn->prepare("SELECT * FROM Contacts WHERE UserLogin = ? AND FirstName,LastName LIKE ?");
    $search->bind_param("ss", $username, $search);
    $search->execute();
    $result = $search->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($searchCount > 0) {
            $searchResults .= ',';
        }
        $searchCount++;
        $searchResults .= json_encode($row);
    }

    returnWithInfo($searchResults);
    $search->close();
}

function putContact()
{
    global $conn;
    global $username;
    global $inData;

    if ($name = $inData["name"]) {
        $updateName = $conn->prepare("UPDATE Contacts SET FirstName = ?, LastName = ? WHERE UserLogin = ? AND ID = ?");
        $updateName->bind_param("ssi", $name["first"], $name["last"], $inData["id"]);
        $updateName->execute();
        $updateName->close();
    }

    if ($email = $inData["email"]) {
        $updateEmail = $conn->prepare("UPDATE Contacts SET Email = ? WHERE UserLogin = ? AND ID = ?");
        $updateEmail->bind_param("ssi", $email, $username, $inData["id"]);
        $updateEmail->execute();
        $updateEmail->close();
    }

    if ($phone = $inData["phone"]) {
        $updatePhone = $conn->prepare("UPDATE Contacts SET Phone = ? WHERE UserLogin = ? AND ID = ?");
        $updatePhone->bind_param("ssi", $phone, $username, $inData["id"]);
        $updatePhone->execute();
        $updatePhone->close();
    }

    returnWithError("201");
}

function deleteContact()
{
    global $conn;
    global $username;
    global $inData;

    $delete = $conn->prepare("DELETE FROM Contacts WHERE UserLogin = ? AND ID = ?");
    $delete->bind_param("si", $username, $inData["id"]);
    $delete->execute();
    $delete->close();

    returnWithError("204");
}