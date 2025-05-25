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

function returnWithInfo($username, $firstName, $lastName)
{
    $retValue = '{"username":' . $username . ',"name": {"first": ' . $firstName . ',"last": ' . $lastName . '}}';
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

    $insert = $conn->prepare("SELECT * FROM Users WHERE Login = ?");
    $insert->bind_param("s", $username);
    $insert->execute();

    if ($insert->num_rows == 0) {
        $insert->close();
        $insert = $conn->prepare("INSERT INTO Users (Login, FirstName, LastName, Password) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $username, $firstName, $lastName, $password);
        $insert->execute();
        $insert->close();
        http_response_code(201);
        returnWithInfo($username, $firstName, $lastName);
    } else {
        $insert->close();
        http_response_code(500);
        returnWithError("Failed to create Account");
    }
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
    global $firstName;
    global $lastName;
    global $conn;

    $newPassword = $inData["password"];

    if (authenticated()) {

        if ($inData["username"] != null) {
            http_response_code(400);
            returnWithError("Username can't be modified");
            return;
        }

        if ($newPassword != null and $inData["name"] != null) {
            $update = $conn->prepare("UPDATE Users SET FirstName = ?, LastName = ?, Password = ? WHERE Login = ?");
            $update->bind_param("ssss", $firstName, $lastName, $newPassword, $username);
            $update->execute();
            if ($update->affected_rows < 1) {
                http_response_code(500);
                returnWithError("Failed to update multiple Account fields");
            }
            $update->close();
        } else if ($newPassword != null) {
            $update = $conn->prepare("UPDATE Users SET Password = ? WHERE Login = ?");
            $update->bind_param("ss", $newPassword, $username);
            $update->execute();
            if ($update->affected_rows < 1) {
                http_response_code(400);
                returnWithError("Failed to update password");
            }
            $update->close();
        } else if ($inData["name"] != null) {
            $update = $conn->prepare("UPDATE Users SET FirstName = ?, LastName = ? WHERE Login = ?");
            $update->bind_param("sss", $firstName, $lastName, $username);
            $update->execute();
            if ($update->affected_rows < 1) {
                http_response_code(400);
                returnWithError("Failed to update profile name");
            }
            $update->close();
        } else {
            http_response_code(400);
            returnWithError("No changes were provided");
        }

        http_response_code(201);
    } else {
        http_response_code(401);
    }
}

function deleteAccount()
{
    global $username;
    global $conn;

    if (authenticated()) {
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
}