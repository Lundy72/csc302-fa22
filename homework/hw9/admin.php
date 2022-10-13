<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDO demo</title>
    <style>
        table, tr, td, th {
            border: 1px solid gray;
        }
    </style>
</head>
<body>

<?php
// For debugging:
error_reporting(E_ALL);
ini_set('display_errors', '1');

// TODO Change this as needed. SQLite will look for a file with this name, or
// create one if it can't find it.
$dbName = 'hw9data.db';

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

// Put your other code here.

// Create the QuizItems table.
try{
    $dbh->exec('create table if not exists QuizItems('. 
        'id integer primary key autoincrement, '. 
        'question text, answer text)');
} catch(PDOException $e){
    echo "There was an error creating the Books table: $e";
}

// Add a book.
if(array_key_exists('question', $_POST)){
    try {
        $statement = $dbh->prepare('insert into QuizItems(question, answer) '.
            'values (:question, :answer)');
        $statement->execute([
            ':question' => $_POST['question'], 
            ':answer'  => $_POST['answer']]);
    } catch(PDOException $e){
        echo "There was an error adding a book: $e";
    }
}

?>
    <h1>Add book</h1>
    <form method="post">
        Title: <input type="text" name="title"/><br/>
        Author: <input type="text" name="author"/><br/>
        Year: <input type="text" name="year"/><br/>
        Copies: <input type="number" name="copies"/><br/>
        <input type="submit" value="Add book"/>
    </form>

    <h1>Books table</h1>
    <table>
        <tr><th>id</th><th>author</th><th>title</th><th>year</th><th>copies</th></tr>

        <?php
        try{
            $statement = $dbh->prepare("select * from Books");
            $statement->execute();
            $columns = ['id', 'author', 'title', 'year', 'copies'];
            while($row = $statement->fetch(PDO::FETCH_ASSOC)){
                echo "<tr>";
                foreach($columns as $col){
                    echo "<td>${row[$col]}</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } catch(PDOException $e){
            echo "There was an error fetching rows from Books.";
        }
        ?>
    </table>

</body>
</html>