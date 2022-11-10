var questions = [];
var username, userURI;

$(document).ready(function(){

    // Add listeners to the buttons.
    $(document).on('click', '.new-quiz', addQuiz);

    $(document).on('click', '#add-question', addQuestion);
    $(document).on('click', '.remove-question', removeQuestion);
    $('.remove-question').click(removeQuestion);

    $(document).on('click', '#save-quiz', saveQuiz);
    $(document).on('click', '#reset-quiz', function(){populateQuiz(questions)});
    $(document).on('click', '#check-quiz', checkQuiz);

    $(window).on('hashchange', renderView);

    $(document).on('click', '.signout', signout);

    loadUserOrBoot();
    $('.username').html(username);

    renderView();
});

/**
 * Determines which panel to show: the quiz or the admin panel. If the 
 * URI's hash is '#admin', the admin panel is displayed, otherwise the
 * quiz view is shown.
 */
 function renderView(){
    var hash = window.location.hash.match(/^#?([^?]*)/)[1];
    $('.panel').addClass('hidden');

    if(hash === 'admin'){
        $('#quiz-admin-panel').removeClass('hidden');
    } else if(hash == 'quiz') {
        $('#quiz-panel').removeClass('hidden');
    } else {
        $('#home-panel').removeClass('hidden');
    }
}

/**
 * Load the user's information from localStorage.
 */
function loadUserOrBoot(){
    // Redirect the user to sign in if they aren't already signed in.
    if(localStorage.getItem('username') === null){
        window.location.href = 'signin.html';
    }
    username = localStorage.getItem('username');
    userURI = localStorage.getItem('userURI');
}

/**
 * Saves the quiz questions from the admin panel, updates the quiz panel.
 */
function saveQuiz(){
    // Extract all of the questions and answers.
    questions = []; // Resets the questions.
    $('#quiz-admin-questions .question').each(function(i, elm){
        var $row = $(elm).parents('tr');
        var question = $(elm).val();
        var answer = $row.find('.answer').val();

        // Skip the template question.
        if(!$row.hasClass('hidden')){
            questions.push({question: question, answer: answer});
        }
    });

    // Save quiz.
    localStorage.setItem('questions', JSON.stringify(questions));

    // // Update quiz panel.
    // populateQuiz(questions);
    // TODO: send data to quiz.php
}

/**
 * Re-populates the quiz with the given questions.
 * 
 * @param questions A list of question/answer pairs (each item is an object
 *                  with the fields 'question' and 'answer').
 */
function populateQuiz(questions){
    var $quiz = $('#quiz')
    $quiz.html('');
    $('#score').html('');

    for(var i = 0; i < questions.length; i++){
        $quiz.append(`<li data-id="${i}">${questions[i].question}<br/>`+
            '<textarea rows="3" class="response"></textarea></li>');
    }
}

/**
 * Populates the quiz admin table with the given questions.
 * 
 * @param questions A list of question/answer pairs (each item is an object
 *                  with the fields 'question' and 'answer').
 */
function populateQuizAdmin(question){
    var $quizAdminTable = $('#quiz-admin-questions')
    for(var i = 0; i < questions.length; i++){
        var $newRow = $('#question-admin-template').clone();
        $newRow.attr('id', '');
        $newRow.removeClass('hidden');
        // set the value of the column in newRow 
        // that has the class "question" to the
        // text of the current question we're
        // iterating over.
        $newRow.find('.question').val(questions[i].question);
                                    // questions[i]['question']
        $newRow.find('.answer').val(questions[i].answer);
        $quizAdminTable.append($newRow);
    }
}

/**
 * Adds a new quiz to the server and the UI.
 */
function addQuiz(){
    // Add a new quiz to the server.
    // Upon hearing back, change the view to #admin.
    // Clear the admin page.
}

/**
 * Adds a new row to the quiz admin question editor table.
 */
function addQuestion(){
    // var newRow = '<tr><td><textarea rows="2" class="question"></textarea></td>'+
    //     '<td><textarea rows="2" class="answer"></textarea></td>'+
    //     '<td><button class="remove-question">Delete</button></td></tr>';
    var newRow = $('#question-admin-template').clone();
    newRow.attr('id', '');
    newRow.removeClass('hidden');
    $('#quiz-admin-questions').append(newRow);
}

/**
 * Removes a new row to the quiz admin question editor table. It is assumed that
 * this is called with the context (this) of the specific "remove" button that
 * was clicked.
 */
function removeQuestion(){
    $(this).parents('tr').remove();
}

/**
 * Checks each of the answers in the quiz and marks them as correct/incorrect.
 * Also tallies up a score and records it.
 */
function checkQuiz(){
    var responses = [];
    $('#quiz .response').each(function(i, elm){
        var $questionItem = $(elm).parents('li');
        var response = $(elm).val();
        var questionId = parseInt($questionItem.data('id'));
        responses.push({
            id: questionId,
            answer: response
        });
    });
    
    // var $form = $('#response-submission-form');
    // $form.find('[name=responses]').val(JSON.stringify(responses));
    // $form.submit();

    $.ajax('grade.php', {
        method: 'post',
        data: {responses: JSON.stringify(responses)},
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            // data = JSON.parse(data);
            console.log(data, typeof(data));
            gradeQuiz(data);

        },
        error: function(jqXHR, textStatus, errorThrown){
            alert(`There was an error with your request! ${textStatus}: ${errorThrown}`);
        }
    });
}

/**
 * Marks questions in the UI as correct or not.
 * 
 * @param questions A list of question item data, where each question item is 
 *                  an object with at least the key 'correct' which should have
 *                  a boolean value.
 */
function gradeQuiz(questions){
    var correct = 0;
    $('#quiz .response').each(function(i, elm){
        var $questionItem = $(elm).parents('li');
        var questionIndex = parseInt($questionItem.data('id'));
        if(questions[questionIndex].correct){
            correct++;
            $questionItem.addClass('correct');
            $questionItem.removeClass('incorrect');
        } else {
            $questionItem.addClass('incorrect');
            $questionItem.removeClass('correct');
        }
    });
    $('#score').html(`Score: ${correct}/${questions.length} = ${correct/questions.length}`);
}

/**
 * Signs the user out and removes their data from localStorage.
 */
function signout(){
    $.ajax('/sessions', {
        method: 'post',
        data: {_method: 'delete'},
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            localStorage.removeItem('username');
            localStorage.removeItem('userURI');
            loadUserOrBoot(); // This will boot the user over to the signin page.
        },
        error: function(jqXHR, textStatus, errorThrown){
            alert(`There was an error with your request! ${textStatus} `+
                `${errorThrown}: ${jqXHR.responseJSON.error}.`);
        }
    });
}