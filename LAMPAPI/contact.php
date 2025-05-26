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
        default:
            http_response_code(204);
            sendResultInfoAsJson('{"method: "' . $method . '"}');
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
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Access-Control-Request-Headers, Access-Control-Allow-Methods, Access-Control-Allow-Origin, Origin, Accept, Content-Type, Authorization, x-ijt');
    echo $obj;
}

function returnWithError($err)
{
    $retValue = '{"error":"' . $err . '"}';
    sendResultInfoAsJson($retValue);
}

function returnWithInfo($results)
{
    $retValue = '{"results": ' . $results . '}';
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

    if ($firstName == null or $lastName == null) {
        http_response_code(400);
        returnWithError("Request is missing Contact name");
        return;
    }

    $create = $conn->prepare("INSERT INTO Contacts (FirstName, LastName, Email, Phone, UserLogin) VALUES (?, ?, ?, ?, ?)");
    $create->bind_param("sssss", $firstName, $lastName, $email, $phone, $username);
    $create->execute();

    if ($create->affected_rows == 1) {
        http_response_code(201);
    } else {
        http_response_code(500);
        returnWithError("Failed to create Contact");
    }
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
    $contacts = $get->get_result();
    $get->close();

    while ($row = $contacts->fetch_assoc()) {
        if ($count > 0) {
            $results .= ',';
        }
        $count++;
        $results .= '{"id": "' . $row["ID"] . '", "name": {"first": "' . $row["FirstName"] . '", "last": "' . $row["LastName"] . '"}, "email": "' . $row["Email"] . '", "phone": "' . $row["Phone"] . '"}';
    }

    returnWithInfo("[" . $results . "]");
}

function putContact()
{
    global $conn;
    global $firstName;
    global $lastName;
    global $username;
    global $contactId;
    global $inData;

    $getCurrent = $conn->prepare("SELECT * FROM Contacts WHERE UserLogin = ? AND ID = ?");
    $getCurrent->bind_param("si", $username, $contactId);
    $getCurrent->execute();
    $current = $getCurrent->get_result();
    $getCurrent->close();

    if ($contact = $current->fetch_assoc()) {

        if ($firstName == null) $firstName = $contact["FirstName"];
        if ($lastName == null) $lastName = $contact["LastName"];

        $email = $inData["email"];
        if ($email == null) $email = $contact["Email"];

        $phone = $inData["phone"];
        if ($phone == null) $phone = $contact["Phone"];

        if (
            $firstName == $contact["FirstName"] and
            $lastName == $contact["LastName"] and
            $email == $contact["Email"] and
            $phone == $contact["Phone"]
        ) {
            http_response_code(400);
            returnWithError("Request contains no data to change");
            return;
        }

        $update = $conn->prepare("UPDATE Contacts SET FirstName = ?, LastName = ?, Email = ?, Phone = ? WHERE UserLogin = ? AND ID = ?");
        $update->bind_param("sssssi", $firstName, $lastName, $email, $phone, $username, $contactId);
        $update->execute();

        if ($update->affected_rows == 1) http_response_code(201); else {
            http_response_code(500);
            returnWithError("Failed to update Contact");
        }

        $update->close();
    } else {
        http_response_code(404);
        returnWithError("No Contact found for ID " . $contactId);
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
        returnWithError("No Contact found for ID " . $contactId);
    }

    $delete->close();
}