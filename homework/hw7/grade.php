
<?php
    require "questions.php";

    if(key_exists("responses", $_POST)){
        $responses = json_decode($_POST["responses"], true);

        for ($i = 0; $i < count($responses); $i++) {
            $responses[$i]["correct"] = false;

            if($questions[$i]["answer"] === $responses[$i]["answer"]){
                $responses[$i]["correct"] = true;
            }
        }
    }

    print json_encode($_POST["responses"]);

?>