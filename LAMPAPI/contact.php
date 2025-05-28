<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, x-ijt");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$username = $_SERVER['PHP_AUTH_USER'] ?? '';
$password = $_SERVER['PHP_AUTH_PW'] ?? '';

$conn = new mysqli("localhost", "Swimmer", "Swim1", "COP4331");

if ($conn->connect_error) {
    http_response_code(500);
    returnWithError("Failed to connect to MySQL: " . $conn->connect_error);
} else if (authenticated($username, $password)) {
    $method = $_SERVER['REQUEST_METHOD'];
    $request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));
    $contactId = intval($request[0] ?? 0);

    $inData = [];
    if ($method == 'POST' || $method == 'PUT') {
        $inData = getRequestInfo();
    }

    $firstName = $inData["name"]["first"] ?? null;
    $lastName = $inData["name"]["last"] ?? null;
    $email = $inData["email"] ?? null;
    $phone = $inData["phone"] ?? null;

    $search = "%" . ($_GET["search"] ?? '') . "%";

    //$username = $_SERVER['PHP_AUTH_USER'] ?? '';
    //$password = $_SERVER['PHP_AUTH_PW'] ?? '';

    switch ($method) {
        case 'POST':
            postContact();
            break;
        case 'GET':
            if ($contactId > 0) {
                getContactById();
            } else {
                getContacts();
            }
            break;
        case 'PUT':
            putContact();
            break;
        case 'DELETE':
            deleteContact();
            break;
        default:
            http_response_code(405);
            returnWithError("Method not allowed: " . $method);
    }
} else {
    http_response_code(401);
    returnWithError("Unauthorized: Invalid or missing credentials.");
}

$conn->close();

function getRequestInfo() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function sendResultInfoAsJson($obj) {
    header('Content-type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, x-ijt');
    echo $obj;
}

function returnWithError($err) {
    $retValue = '{"error":"' . $err . '"}';
    sendResultInfoAsJson($retValue);
}

function returnWithInfo($results) {
    $retValue = '{"results": ' . $results . '}';
    sendResultInfoAsJson($retValue);
}

function authenticated($username, $password) {
    global $conn;

    if (empty($username) || empty($password)) {
        return false;
    }

    $sql = "SELECT Login FROM Users WHERE Login = ? AND Password = ?";

    $auth = $conn->prepare($sql);

    if ($auth === false) {
        return false;
    }

    $auth->bind_param("ss", $username, $password);

    $executeSuccess = $auth->execute();
    if ($executeSuccess === false) {
        $auth->close();
        return false;
    }

    $result = $auth->get_result();
    $num_rows = $result->num_rows;

    $authenticated = ($num_rows == 1);

    $auth->close();
    return $authenticated;
}

function postContact() {
    global $conn;
    global $username;
    global $email;
    global $phone;
    global $firstName;
    global $lastName;

    if ($firstName == null || $lastName == null) {
        http_response_code(400);
        returnWithError("Request is missing Contact name (first and/or last name required).");
        return;
    }

    $create = $conn->prepare("INSERT INTO Contacts (FirstName, LastName, Email, Phone, UserLogin) VALUES (?, ?, ?, ?, ?)");
    if ($create === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for creating contact: " . $conn->error);
        return;
    }
    $create->bind_param("sssss", $firstName, $lastName, $email, $phone, $username);
    $create->execute();

    if ($create->affected_rows == 1) {
        http_response_code(201);
        echo json_encode(array("id" => $conn->insert_id, "name" => ["first" => $firstName, "last" => $lastName], "email" => $email, "phone" => $phone));
    } else {
        http_response_code(500);
        returnWithError("Failed to create Contact: " . $create->error);
    }
    $create->close();
}

function getContacts() {
    global $conn;
    global $username;
    global $search;

    $results = [];

    $searchTerm = trim($search, '%');

    $sql = "SELECT ID, FirstName, LastName, Email, Phone FROM Contacts WHERE UserLogin = ?";
    $params = [$username];
    $types = "s";

    if (!empty($searchTerm)) {
        $sql .= " AND (FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ? OR Phone LIKE ?)";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $types .= "ssss";
    }

    $get = $conn->prepare($sql);
    if ($get === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for getContacts: " . $conn->error);
        return;
    }

    $get->bind_param($types, ...$params);
    $get->execute();
    $contacts = $get->get_result();
    $get->close();

    while ($row = $contacts->fetch_assoc()) {
        $results[] = [
            "id" => (string)$row["ID"],
            "name" => ["first" => $row["FirstName"], "last" => $row["LastName"]],
            "email" => $row["Email"],
            "phone" => $row["Phone"]
        ];
    }

    http_response_code(200);
    echo json_encode(["results" => $results]);
}

function getContactById() {
    global $conn;
    global $username;
    global $contactId;

    if ($contactId <= 0) {
        http_response_code(400);
        returnWithError("Invalid contact ID provided for retrieval.");
        return;
    }

    $get = $conn->prepare("SELECT ID, FirstName, LastName, Email, Phone FROM Contacts WHERE UserLogin = ? AND ID = ?");
    if ($get === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for getContactById: " . $conn->error);
        return;
    }
    $get->bind_param("si", $username, $contactId);
    $get->execute();
    $contact = $get->get_result();
    $get->close();

    if ($row = $contact->fetch_assoc()) {
        http_response_code(200);
        echo json_encode([
            "results" => [
                [
                    "id" => (string)$row["ID"],
                    "name" => ["first" => $row["FirstName"], "last" => $row["LastName"]],
                    "email" => $row["Email"],
                    "phone" => $row["Phone"]
                ]
            ]
        ]);
    } else {
        http_response_code(404);
        returnWithError("Contact not found for ID " . $contactId . " or does not belong to user.");
    }
}

function putContact() {
    global $conn;
    global $username;
    global $contactId;
    global $inData;

    if ($contactId <= 0) {
        http_response_code(400);
        returnWithError("Invalid contact ID for update.");
        return;
    }

    $getCurrent = $conn->prepare("SELECT FirstName, LastName, Email, Phone FROM Contacts WHERE UserLogin = ? AND ID = ?");
    if ($getCurrent === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for fetching current contact: " . $conn->error);
        return;
    }
    $getCurrent->bind_param("si", $username, $contactId);
    $getCurrent->execute();
    $currentResult = $getCurrent->get_result();
    $getCurrent->close();

    if (!$currentContact = $currentResult->fetch_assoc()) {
        http_response_code(404);
        returnWithError("No Contact found for ID " . $contactId . " or does not belong to user.");
        return;
    }

    $newFirstName = $inData["name"]["first"] ?? $currentContact["FirstName"];
    $newLastName = $inData["name"]["last"] ?? $currentContact["LastName"];
    $newEmail = $inData["email"] ?? $currentContact["Email"];
    $newPhone = $inData["phone"] ?? $currentContact["Phone"];

    if (
        $newFirstName == $currentContact["FirstName"] &&
        $newLastName == $currentContact["LastName"] &&
        $newEmail == $currentContact["Email"] &&
        $newPhone == $currentContact["Phone"]
    ) {
        http_response_code(200);
        returnWithInfo('{"message": "Request contains no new data to change."}');
        return;
    }

    $update = $conn->prepare("UPDATE Contacts SET FirstName = ?, LastName = ?, Email = ?, Phone = ? WHERE UserLogin = ? AND ID = ?");
    if ($update === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for updating contact: " . $conn->error);
        return;
    }
    $update->bind_param("sssssi", $newFirstName, $newLastName, $newEmail, $newPhone, $username, $contactId);
    $update->execute();

    if ($update->affected_rows == 1) {
        http_response_code(200);
        returnWithInfo('{"message": "Contact updated successfully."}');
    } else {
        http_response_code(500);
        returnWithError("Failed to update Contact: " . $update->error);
    }
    $update->close();
}

function deleteContact() {
    global $conn;
    global $username;
    global $contactId;

    if ($contactId <= 0) {
        http_response_code(400);
        returnWithError("Invalid contact ID for deletion.");
        return;
    }

    $delete = $conn->prepare("DELETE FROM Contacts WHERE UserLogin = ? AND ID = ?");
    if ($delete === false) {
        http_response_code(500);
        returnWithError("Database prepare failed for deleting contact: " . $conn->error);
        return;
    }
    $delete->bind_param("si", $username, $contactId);
    $delete->execute();

    if ($delete->affected_rows == 1) {
        http_response_code(204);
    } else {
        http_response_code(404);
        returnWithError("No Contact found for ID " . $contactId . " or does not belong to user.");
    }

    $delete->close();
}

?>
