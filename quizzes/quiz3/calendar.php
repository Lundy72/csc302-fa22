<?php
// File:  calendar.php
// Name:  Carl Lund
// Date:  10-27-22

// Responses will be in JSON.
header('Content-type: application/json');

// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start the session.
session_start();

// TODO Change this as needed. SQLite will look for a file with this name, or
// create one if it can't find it.
$dbName = 'calpal.db';

// Leave this alone. It checks if you have a directory named www-data in
// you home directory (on a *nix server). If so, the database file is
// sought/created there. Otherwise, it uses the current directory.
// The former works on digdug where I've set up the www-data folder for you;
// the latter should work on your computer.
$matches = [];
preg_match('#^/~([^/]*)#', $_SERVER['REQUEST_URI'], $matches);
$homeDir = count($matches) > 1 ? $matches[1] : '';
$dataDir = "/home/$homeDir/www-data";
if(!file_exists($dataDir)){
    $dataDir = __DIR__;
}
$dbh = new PDO("sqlite:$dataDir/$dbName")   ;
// Set our PDO instance to raise exceptions when errors are encountered.
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

createTables();

$actionHandlers = [
    'add-event' => 'addEvent',
    'update-event' => 'updateEvent',
    'remove-event' => 'removeEvent',
    'get-events' => 'getEvents',
    'signin-status' => 'getSigninStatus',
    // Question 16
    'signin' => 'signin',
    'signout' => 'signout'
];

// Handle incoming requests.
if(array_key_exists('action', $_POST)){
    $action = $_POST['action'];
    if(array_key_exists($action, $actionHandlers)){
        $actionHandlers[$action]($_POST);
    } else {
        error("Invalid action: $action");
    }
}

/**
 * Creates the tabls we need (just one currently).
 */
function createTables(){
    global $dbh;

    try{
        // Create the Users table.
        $dbh->exec('create table if not exists Users('. 
            'id integer primary key autoincrement, '. 
            'user text unique, '. 
            'pass text)');

        // Create the Events table.
        $dbh->exec('create table if not exists Events('. 
            'id integer primary key autoincrement, '. 
            'name text, '. 
            'start datetime, '. 
            'end datetime, '. 
            'notes text)');

    } catch(PDOException $e){
        error("There was an error creating the tables: $e");
    }
}

/**
 * Returns whether the user is logged in and returns their username.
 * 
 * @param data An associative array holding parameters and their values.
 */
function getSigninStatus($data){
    if($_SESSION['signed-in']){
        die(json_encode([
            'success' => true,
            'signed-in' => true,
            'username' => $_SESSION['username']
        ]));
    } else {
        die(json_encode([
            'success' => true,
            'signed-in' => false
        ]));
    }
}

/**
 * Adds an event to the calendar. Requires the parameters:
 *  - name
 *  - start (starging time/date, e.g., '2020-09-30 07:00:00')
 *  - end (ending time/date, e.g., '2020-09-30 07:00:00')
 *  - notes
 * 
 * On success, emits an object with two fields:
 *  - success (set to true)
 *  - id (the id of the event in the database)
 * 
 * On failure, emits an object with two fields:
 *  - success (set to false)
 *  - error (the error message)
 * 
 * @param data An associative array holding parameters and their values.
 */
function addEvent($data){
    global $dbh;

    try {
        $statement = $dbh->prepare('insert into Events(name, start, end, notes) '.
            'values (:name, :start, :end, :notes)');
        $statement->execute([
            ':name' => $data['name'], 
            ':start'  => $data['start'], 
            ':end'   => $data['end'], 
            ':notes' => $data['notes']]);

        // This gets the id of the row that was just added.
        $id = $dbh->lastInsertId();

        die(json_encode([
            'success' => true,
            'id' => $id
        ]));

    } catch(PDOException $e){
        error("There was an error adding an event: $e");
    }
}

/**
 * Adds an event to the calendar. Requires the parameters:
 *  - name
 *  - start (starging time/date, e.g., '2020-09-30 07:00:00')
 *  - end (ending time/date, e.g., '2020-09-30 07:00:00')
 *  - notes
 * 
 * On success, emits an object with one field:
 *  - success (set to true)
 * 
 * On failure, emits an object with two fields:
 *  - success (set to false)
 *  - error (the error message)
 * 
 * @param data An associative array holding parameters and their values.
 */
function updateEvent($data){
    global $dbh;

    $statement = $dbh->prepare('update Events set name = :name, '.
        'start = :start, end = :end, notes = :notes where id = :id');
    $statement->execute([
        ':id'    => $data['id'],
        ':name'  => $data['name'], 
        ':start' => $data['start'], 
        ':end'   => $data['end'], 
        ':notes' => $data['notes']]);


    die(json_encode(['success' => true]));

}


/**
 * Outputs all the events in the Events database, filtered *optionally* by
 * just those that start or end between:
 *   - range-start
 *   - range-end
 * If those two are not present, then the results are not filtered.
 * 
 * On success, emits an object with two fields:
 *  - success (set to true)
 *  - data (an array of matching rows, each with a key per column: id, name, 
 *          start, end, and notes)
 * 
 * On failure, emits an object with two fields:
 *  - success (set to false)
 *  - error (the error message)
 * 
 * @param data An associative array holding parameters and their values.
 */
function getEvents($data){
    global $dbh;

    try {
        // Filter only those events that happen within the specified range.
        if(array_key_exists('range-start', $data) && 
            array_key_exists('range-end', $data)){

            $statement = $dbh->prepare('select * from Events where '. 
                '(start >= datetime(:rangeStart) and start <= datetime(:rangeEnd)) or '. 
                '(end >= datetime(:rangeStart) and end <= datetime(:rangeEnd)) '. 
                'order by start asc');
            
            $statement->execute([
                ':rangeStart' => $data['range-start'],
                ':rangeEnd'   => $data['range-end']
            ]);

        // Otherwise, grab all events.
        } else {
            $statement = $dbh->prepare("select * from Events order by start asc");
            $statement->execute();
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        die(json_encode(['success' => true, 'data' => $rows]));

    } catch(PDOException $e){
        error("There was an error fetching events: $e");
    }
}


/**
 * Removes an event from the calendar. Requires the parameter:
 *  - id (the id of the event to remove)
 * 
 * On success, emits an object with one field:
 *  - success (set to true)
 * 
 * On failure, emits an object with two fields:
 *  - success (set to false)
 *  - error (the error message)
 * 
 * @param data An associative array holding parameters and their values.
 */
function removeEvent($data){
    global $dbh;

    try {
        $statement = $dbh->prepare('...something goes here..');
        

    } catch(PDOException $e){
        error("There was an error removing an event: $e");
    }
}

/**
 * Authenticates the given username and password. It dies with errors if
 * either parameter is missing, the username cannot be found, or if there is a 
 * database error.
 * 
 * @param $username The username to validate the password for.
 * @param $password The clear text password to validate.
 * 
 * @return The user's information (id and username) if successfully
 *          authenticated. Otherwise, dies with an error.
 */
function authenticate($username, $password){
    global $dbh;

    // check that username and password are not null.
    if($username == null || $password == null){
        error('Bad request -- both a username and password are required');
    }

    // grab the row from Users that corresponds to $username
    try {
        $statement = $dbh->prepare('select id,user,pass from Users '.
            'where user = :username');
        $statement->execute([
            ':username' => $username,
        ]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Check password.
        // if($password == $user['pass']){
        if(password_verify($password, $user['pass'])){
            return [
                'id' => $user['id'], 
                'username' => $user['user']
            ];
        }
        error('Could not authenticate username and password.', 401);
        

    } catch(Exception $e){
        error('Could not authenticate username and password: '. $e);
    }
}

// Question 16
/**
 * Log a user in. Requires the parameters:
 *  - username
 *  - password
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function signin($data){
    if(authenticate($data['username'], $data['password'])){
        $_SESSION['signedin'] = true;
        $_SESSION['user-id'] = getUserByUsername($data['username'])['id'];
        $_SESSION['username'] = $data['username']; 

        die(json_encode([
            'success' => true
        ]));
    } else {
        error('Username or password not found.', 401);
    }
}

// Question 16
/**
 * Logs the user out if they are logged in.
 * 
 * @param data An JSON object with these fields:
 *               - success -- whether everything was successful or not
 *               - error -- the error encountered, if any (only if success is false)
 */
function signout($data){
    session_destroy();
    die(json_encode([
        'success' => true
    ]));
}

/**
 * Dies emitting a JSON object with two fields below and set the response code:
 *  - success: false
 *  - error: $error
 * 
 * @param $message The message to emit.
 * @param $responseCode The value to set the response code to (default: 400).
 */
function error($message, $responseCode=400){
    http_response_code($responseCode);
    die(json_encode([
        'success' => false, 
        'error' => $message
    ]));
}


?>