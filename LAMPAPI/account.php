<?php
$inData = getRequestInfo();

$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

$conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");

if ($conn->connect_error) {
    http_response_code(500);
    returnWithError("Failed to connect to MySQL");
} else {
    switch ($method) {
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
}

$conn->close();

function getRequestInfo()
{
    return json_decode(file_get_contents('php://input'), true);
}

function sendResultInfoAsJson($obj)
{
    header('Content-type: application/json');
    header('Access-Control-Allow-Origin: http://www.shark-paradise.com');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers:
    Access-Control-Allow-Headers,
    Access-Control-Request-Headers,
    Access-Control-Allow-Methods,
    Access-Control-Allow-Origin,
    Accept,
    Origin,
    Content-Type,
    Authorization,
    x-ijt'
    );
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
    global $conn;

    $username = $inData["username"] ?? '';
    $password = $inData["password"] ?? '';
    $firstName = $inData["firstName"] ?? '';
    $lastName = $inData["lastName"] ?? '';

    if (empty($username) || empty($password) || empty($firstName) || empty($lastName)) {
        http_response_code(400);
        returnWithError("Missing required signup fields (username, password, first name, or last name).");
        return;
    }

    $check = $conn->prepare("SELECT * FROM Users WHERE Login = ?");
    if ($check === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for check: " . $conn->error);
        return;
    }
    $check->bind_param("s", $username);
    $check->execute();

    if ($check->get_result()->num_rows == 0) {
        $check->close();

        $insert = $conn->prepare("INSERT INTO Users (Login, FirstName, LastName, Password) VALUES (?, ?, ?, ?)");
        if ($insert === false) {
            http_response_code(500);
            returnWithError("Database prepare failed for insert: " . $conn->error);
            return;
        }
        $insert->bind_param("ssss", $username, $firstName, $lastName, $password);
        $insert->execute();

        if ($insert->affected_rows == 1) {
            http_response_code(201);
            returnWithInfo($username, $firstName, $lastName);
        } else {
            http_response_code(500);
            returnWithError("Failed to create Account: " . $insert->error);
        }
        $insert->close();
    } else {
        $check->close();
        http_response_code(409);
        returnWithError("Username already taken");
    }
}

function getAccount()
{
    global $conn;
    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(401);
        returnWithError("Unauthorized: Missing credentials for getAccount.");
        return;
    }

    $login = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    if ($login === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for getAccount: " . $conn->error);
        return;
    }
    $login->bind_param("ss", $username, $password);
    $login->execute();
    $result = $login->get_result();
    $login->close();

    if ($row = $result->fetch_assoc()) {
        returnWithInfo($row["Login"], $row["FirstName"], $row["LastName"]);
    } else {
        http_response_code(401);
        returnWithError("Unauthorized: Invalid credentials for getAccount.");
    }
}

function putAccount()
{
    global $inData;
    global $conn;

    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(401);
        returnWithError("Unauthorized: Missing credentials for putAccount.");
        return;
    }

    $getCurrent = $conn->prepare("SELECT FirstName, LastName, Password FROM Users WHERE Login = ? AND Password = ?");
    if ($getCurrent === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for fetching current account: " . $conn->error);
        return;
    }
    $getCurrent->bind_param("ss", $username, $password);
    $getCurrent->execute();
    $current = $getCurrent->get_result();
    $getCurrent->close();

    if ($user = $current->fetch_assoc()) {
        $newPassword = $inData["password"] ?? $user["Password"];
        $newFirstName = $inData["firstName"] ?? $user["FirstName"];
        $newLastName = $inData["lastName"] ?? $user["LastName"];

        if ($inData["username"] != null && $inData["username"] !== $username) {
            http_response_code(400);
            returnWithError("Username cannot be changed.");
            return;
        } else if (
            $newPassword == $user["Password"] &&
            $newFirstName == $user["FirstName"] &&
            $newLastName == $user["LastName"]
        ) {
            http_response_code(200);
            returnWithInfo($username, $newFirstName, $newLastName);
            return;
        } else {
            $update = $conn->prepare("UPDATE Users SET FirstName = ?, LastName = ?, Password = ? WHERE Login = ?");
            if ($update === false) {
                http_response_code(500);
                returnWithError("Database prepare failed for updating account: " . $conn->error);
                return;
            }
            $update->bind_param("ssss", $newFirstName, $newLastName, $newPassword, $username);
            $update->execute();

            if ($update->affected_rows == 1) {
                http_response_code(200);
                returnWithInfo($username, $newFirstName, $newLastName);
            } else {
                http_response_code(500);
                returnWithError("Failed to update Account data: " . $update->error);
            }
            $update->close();
        }
    } else {
        http_response_code(401);
        returnWithError("Unauthorized: Invalid credentials for putAccount.");
    }
}

function deleteAccount()
{
    global $conn;
    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(401);
        returnWithError("Unauthorized: Missing credentials for deleteAccount.");
        return;
    }

    $auth = $conn->prepare("SELECT * FROM Users WHERE Login = ? AND Password = ?");
    if ($auth === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for deleteAccount auth: " . $conn->error);
        return;
    }
    $auth->bind_param("ss", $username, $password);
    $auth->execute();
    $result = $auth->get_result();
    $auth->close();

    if ($result->num_rows == 1) {
        $deleteContacts = $conn->prepare("DELETE FROM Contacts WHERE UserLogin = ?");
        if ($deleteContacts === false) {
            http_response_code(500);
            returnWithError("Database prepare failed for deleting contacts: " . $conn->error);
            return;
        }
        $deleteContacts->bind_param("s", $username);
        $deleteContacts->execute();
        $deleteContacts->close();

        $deleteUser = $conn->prepare("DELETE FROM Users WHERE Login = ?");
        if ($deleteUser === false) {
            http_response_code(500);
            returnWithError("Database prepare failed for deleting user: " . $conn->error);
            return;
        }
        $deleteUser->bind_param("s", $username);
        $deleteUser->execute();

        if ($deleteUser->affected_rows == 1) {
            http_response_code(204);
        } else {
            http_response_code(500);
            returnWithError("Failed to delete user account: " . $deleteUser->error);
        }
        $deleteUser->close();
    } else {
        http_response_code(401);
        returnWithError("Unauthorized: Invalid credentials for deleteAccount.");
    }
}
