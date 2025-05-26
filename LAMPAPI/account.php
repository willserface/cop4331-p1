<?php
$inData = getRequestInfo();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$firstName = $inData["name"]["first"];
$lastName = $inData["name"]["last"];

$conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");

if ($conn->connect_error) {
    http_response_code(500);
    returnWithError("Failed to connect to MySQL");
} else switch ($method) {
    case 'POST':
        postAccount();
        break;
    case 'GET':
        getAccount();
        break;
    case 'PUT':
        putAccount();
        break;
    case 'DELETE':
        deleteAccount();
        break;
    default:
        http_response_code(204);
        sendResultInfoAsJson('{"method":"' . $method . '"}');
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
    header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Access-Control-Request-Headers, Access-Control-Allow-Methods, Accept, Origin, Content-Type, Authorization');
    echo $obj;
}

function returnWithError($err)
{
    $retValue = '{"error":"' . $err . '"}';
    sendResultInfoAsJson($retValue);
}

function returnWithInfo($username, $firstName, $lastName)
{
    $retValue = '{"username": "' . $username . '", "name": { "first": "' . $firstName . '", "last": "' . $lastName . '"}}';
    sendResultInfoAsJson($retValue);
}


function postAccount()
{
    global $inData;
    global $firstName;
    global $lastName;
    global $conn;

    $username = $inData["username"];
    $password = $inData["password"];

    $check = $conn->prepare("SELECT * FROM Users WHERE Login = ?");
    $check->bind_param("s", $username);
    $check->execute();

    if ($check->get_result()->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO Users (Login, FirstName, LastName, Password) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $username, $firstName, $lastName, $password);
        $insert->execute();

        if ($insert->affected_rows == 1) {
            http_response_code(200);
        } else {
            http_response_code(500);
            returnWithError("Failed to create Account");
        }
        $insert->close();
        http_response_code(201);
        returnWithInfo($username, $firstName, $lastName);
    } else {
        http_response_code(409);
        returnWithError("Username already taken");
    }
}

function getAccount()
{
    global $username;
    global $password;
    global $conn;

    $login = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    $login->bind_param("ss", $username, $password);
    $login->execute();
    $result = $login->get_result();
    $login->close();

    if ($row = $result->fetch_assoc()) {
        returnWithInfo($row["Login"], $row["FirstName"], $row["LastName"]);
    } else {
        http_response_code(401);
    }
}

function putAccount()
{
    global $inData;
    global $username;
    global $password;
    global $firstName;
    global $lastName;
    global $conn;

    $getCurrent = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    $getCurrent->bind_param("si", $username, $password);
    $getCurrent->execute();
    $current = $getCurrent->get_result();
    $getCurrent->close();

    if ($user = $current->fetch_assoc()) {

        $newPassword = $inData["password"];

        if ($newPassword == null) $newPassword = $password;
        if ($firstName == null) $firstName = $user["FirstName"];
        if ($lastName == null) $lastName = $user["LastName"];

        if ($inData["username"] != null) {
            http_response_code(400);
            returnWithError("Username cannot be changed");
        } else if (
            $newPassword == $password and
            $firstName == $user["FirstName"] and
            $lastName == $user["LastName"]
        ) {
            http_response_code(400);
            returnWithError("Request contains no data to change");
        } else {
            $update = $conn->prepare("UPDATE Users SET FirstName = ?, LastName = ?, Password = ? WHERE Login = ?");
            $update->bind_param("ssss", $firstName, $lastName, $newPassword, $username);
            $update->execute();

            if ($update->affected_rows == 1) {
                http_response_code(201);
            } else {
                http_response_code(500);
                returnWithError("Failed to update Account data");
            }
            $update->close();
        }
    } else http_response_code(401);
}

function deleteAccount()
{
    global $username;
    global $password;
    global $conn;

    $auth = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    $auth->bind_param("ss", $username, $password);
    $auth->execute();
    $result = $auth->get_result();

    if ($result->num_rows == 1) {
        $delete = $conn->prepare("DELETE FROM Contacts WHERE UserLogin = ?");
        $delete->bind_param("s", $username);
        $delete->execute();
        $delete->close();

        $delete = $conn->prepare("DELETE FROM Users WHERE Login = ?");
        $delete->bind_param("s", $username);
        $delete->execute();

        if ($delete->affected_rows == 1) {
            http_response_code(204);
        } else {
            http_response_code(400);
        }
        $delete->close();
    } else {
        http_response_code(401);
    }

    $auth->close();
}