<?php
$inData = getRequestInfo();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$contactId = intval($request[0]);

$firstName = $inData["name"]["first"];
$lastName = $inData["name"]["last"];

$email = $inData["email"];
$phone = $inData["phone"];

$search = "%" . $inData["search"] . "%";

$conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");

if ($conn->connect_error) {
    http_response_code(500);
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
    http_response_code(401);
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
    global $email;
    global $phone;
    global $firstName;
    global $lastName;

    $create = $conn->prepare("INSERT INTO Contacts (FirstName, LastName, Email, Phone, UserLogin) VALUES (?, ?, ?, ?, ?)");
    $create->bind_param("sssss", $firstName, $lastName, $email, $phone, $username);
    $create->execute();

    http_response_code(201);
    $create->close();
}

function getContacts()
{
    global $conn;
    global $username;
    global $search;

    $results = "";
    $count = 0;

    $get = $conn->prepare("SELECT * FROM Contacts WHERE UserLogin = ? AND CONCAT(FirstName, LastName) LIKE ?");
    $get->bind_param("ss", $username, $search);
    $get->execute();
    $result = $get->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($count > 0) {
            $results .= ',';
        }
        $count++;
        $results .= '{
            "name": {
                "first": "' . $row["FirstName"] . '",
                "last": "' . $row["LastName"] . '",
            },
            "email": "' . $row["Email"] . '",
            "phone": "' . $row["Phone"] . '"
        }';
    }

    returnWithInfo("[" . $results . "]");
    $get->close();
}

function putContact()
{
    global $conn;
    global $contactId;
    global $username;
    global $inData;

    $changed = 0;

    if ($name = $inData["name"]) {
        $updateName = $conn->prepare("UPDATE Contacts SET FirstName = ?, LastName = ? WHERE UserLogin = ? AND ID = ?");
        $updateName->bind_param("sssi", $name["first"], $name["last"], $username, $contactId);
        $updateName->execute();
        if ($updateName->affected_rows == 1) $changed++;
        else $changed = -3;
        $updateName->close();
    }

    if ($email = $inData["email"]) {
        $updateEmail = $conn->prepare("UPDATE Contacts SET Email = ? WHERE UserLogin = ? AND ID = ?");
        $updateEmail->bind_param("ssi", $email, $username, $contactId);
        $updateEmail->execute();
        if ($updateEmail->affected_rows == 1) $changed++;
        else $changed = -3;
        $updateEmail->close();
    }

    if ($phone = $inData["phone"]) {
        $updatePhone = $conn->prepare("UPDATE Contacts SET Phone = ? WHERE UserLogin = ? AND ID = ?");
        $updatePhone->bind_param("ssi", $phone, $username, $contactId);
        $updatePhone->execute();
        if ($updatePhone->affected_rows == 1) $changed++;
        else $changed = -3;
        $updatePhone->close();
    }

    if ($changed >= 0) {
        http_response_code(201);
        returnWithError("201");
    } else {
        http_response_code(404);
        returnWithError($changed);
    }
}

function deleteContact()
{
    global $conn;
    global $contactId;
    global $username;

    $delete = $conn->prepare("DELETE FROM Contacts WHERE UserLogin = ? AND ID = ?");
    $delete->bind_param("si", $username, $contactId);
    $delete->execute();

    if ($delete->affected_rows == 1) {
        http_response_code(204);
    } else {
        http_response_code(404);
    }

    $delete->close();
}