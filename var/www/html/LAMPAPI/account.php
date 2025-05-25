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
    $retValue = '{"username":' . $username . ',"name": {"first": ' . $firstName . ',"last": ' . $lastName . '},"error":null"}';
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
        returnWithError("201");
    } else {
        $insert->close();
        returnWithError("400");
    }
}

function authenticated()
{
    global $conn;

    $auth = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    $auth->bind_param("ss", $username, $password);
    $auth->execute();
    $result = $auth->get_result();

    return $result->num_rows == 1;
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
        returnWithError("404");
    }
}

function putAccount()
{
    global $inData;
    global $username;
    global $firstName;
    global $lastName;
    global $conn;

    $newUsername = $inData["username"];
    $newPassword = $inData["password"];

    if (authenticated()) {

        if ($newUsername != null) {
            $updatePrimaryKey = $conn->prepare("UPDATE Users SET Login = ? WHERE Login = ?");
            $updatePrimaryKey->bind_param("ss", $newUsername, $username);
            $updatePrimaryKey->execute();
            $updatePrimaryKey->close();

            $updateForeignKey = $conn->prepare("UPDATE Contacts SET UserID = ? WHERE UserID = ?");
            $updateForeignKey->bind_param("ss", $newUsername, $username);
            $updateForeignKey->execute();
            $updateForeignKey->close();
        }

        if ($newPassword != null) {
            $updatePassword = $conn->prepare("UPDATE Users SET Password = ? WHERE Login = ?");
            $updatePassword->bind_param("ss", $newPassword, $username);
            $updatePassword->execute();
            $updatePassword->close();
        }

        if ($firstName != null) {
            $updateFirstName = $conn->prepare("UPDATE Users SET FirstName = ? WHERE Login = ?");
            $updateFirstName->bind_param("ss", $firstName, $username);
            $updateFirstName->execute();
            $updateFirstName->close();
        }

        if ($lastName != null) {
            $updateLastName = $conn->prepare("UPDATE Users SET LastName = ? WHERE Login = ?");
            $updateLastName->bind_param("ss", $lastName, $username);
            $updateLastName->execute();
            $updateLastName->close();
        }

        returnWithError("201");

    } else {
        returnWithError("401");
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
        $delete->close();
    }
}