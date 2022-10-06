<!-- 
Name: 
Date: 
 -->
<?php
$WORDS_FILE = 'dict.txt';
$phraseWords = []; // An array to hold the words in the phrase.
$phrase = '';

// Generate a new phrase as long as the word_count and delimiter parameters
// have been passed in.
if(array_key_exists("word_count", $_GET) && array_key_exists("delimiter", $_GET)){
    // Reads in the words from a file. Don't worry about understanding this.
    $words = file($WORDS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Converts the word_count string into an int.
    $wordCount = intval($_GET['word_count']); 

    // Sets the delimiter.
    $delimiter = $_GET['delimiter'];

    // Generates the random words.
    for($i = 0; $i < $wordCount; $i++){
        array_push($phraseWords, $words[rand(0, count($words)-1)]);
    }

    $phrase = implode($delimiter, $phraseWords);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random phrase generator</title>

    <style>
        .warning {
            font-weight: bold;
            color: red;
        }

        body.phrase-history-view .no-phrase-history-view,
        body.no-phrase-history-view .phrase-history-view {
            display: none;
        }

        
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
        var savedPhrases = [];

        /**
         * Loads all saved phrases.
         */
        function loadPhrases(){
            if (localStorage.getItem('randomPhrases') !== null) {
                savedPhrases = JSON.parse(localStorage.getItem('randomPhrases'));
            }
        }

        /**
         * If a new phrase is included at the top of the page, save it. 
         */
        function saveGeneratedPhrase(){
            $newPhrase = $('#new-phrase');
            if($newPhrase.length > 0){
                savedPhrases.unshift({
                    phrase: $newPhrase.html(),
                    // Gets the URL of the current page, including GET params.
                    url: window.location.href
                });
                savePhrases();
            }
        }

        /**
         * Saves savedPhrases to localStorage.
         */
        function savePhrases(){
            localStorage.randomPhrases = JSON.stringify(savedPhrases);
        }

        /**
         * List all the phrases at the bottom of the page.
         */
        function displayPhrases(){
            $phrasesList = $('#phrases-list');
            $phrasesList.html('');
            for(var i = 0; i < savedPhrases.length; i++){
                $phrasesList.append(`<li>${savedPhrases[i].phrase} `+
                    `(<a href="">generate another `+
                    `like this</a>)</li>`);
            }
        }

        /**
         * Removes phrases from localstorage.
         */
        function clearPhrases(){
            localStorage.removeItem('randomPhrases');
            localStorage.randomPhrases = [];
        }

        /**
         * Updates the view based on the URL hash. 
         * 
         *      #show-history -- Displays a list of past phrases.
         *      # or empty    -- Past phrases are not displayed.
         */
        function updateView(){
            
        }

        // Main.
        $(document).ready(function(){
            $(document).on('click', '#clear-saved-phrases', clearPhrases);
            $(window).on('hashchange', updateView);

            loadPhrases();
            saveGeneratedPhrase();
            displayPhrases();            
            updateView();
        });
    </script>
</head>
<body>
    <h1>Random phrase generator</h1>

    <!-- We can span php logic across blocks. The html that's between the
         curly braces will only appear if the if statement below is true. -->
    <?php if(strlen($phrase) > 0){ ?>

    <h2>New phrase, hot off the presses!</h2>
    <span id="new-phrase"><?= $phrase ?></span>

    <?php } // End if ?>

    <h2>Generate a new phrase</h2>  
    <p> 
        <span class="warning">Warning:</span> The phrases randomly generated 
        with this app are based on dictionary entries, including some words you 
        may find offensive or that make you unconfortable. There is no offense 
        or discomfort intended; word selections are random.
    </p>
    
    <form action="phrases.php" method="get">

        <strong>Step 1.</strong> choose the length of the phrase (# words) <br/>
            <input type="number" name="word_count"/><br/>

        <strong>Step 2.</strong> choose the delimiter to put between the words 
            (space, underscore, dash, etc.)<br/>
            <input type="text" name="delimiter"/><br/>

        <strong>Step 3.</strong> click "Generate" and the new phrase will appear at the top 
            of the page<br/>
        <input type="submit" value="Generate"/>
    </form> 
    <br/>
    <a href="#show-history" class="no-phrase-history-view">Show past phrases</a>
    <a href="#" class="phrase-history-view">Hide past phrases</a>
    <div id="phrase-history" class="phrase-history-view">
        <h2>Previously generated phrases</h2>
        <button id="clear-saved-phrases">Clear all</button>
        <ul id="phrases-list">
        </ul>
    </div>

</body>
</html>