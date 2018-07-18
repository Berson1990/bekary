
<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Voice Controlled Notes App</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/shoelace-css/1.0.0-beta16/shoelace.css">

</head>
<body>
<div class="container">

    <h1>Voice Controlled Notes App</h1>
    <p class="page-description">A tiny app that allows you to take notes by recording your voice</p>
    <p><a class="tz-link" href="https://tutorialzine.com/2017/08/converting-from-speech-to-text-with-javascript">Read the full article on Tutorialzine »</a></p>

    <h3 class="no-browser-support">Sorry, Your Browser Doesn't Support the Web Speech API. Try Opening This Demo In Google Chrome.</h3>

    <div class="app">
        <h3>Add New Note</h3>
        <div class="input-single">
            <textarea id="note-textarea" placeholder="Create a new note by typing or using voice recognition." rows="6"></textarea>
        </div>
        <button id="start-record-btn" title="Start Recording">Start Recognition</button>
        <button id="pause-record-btn" title="Pause Recording">Pause Recognition</button>
        <button id="save-note-btn" title="Save Note">Save Note</button>
        <p id="recording-instructions">Press the <strong>Start Recognition</strong> button and allow access.</p>

        <h3>My Notes</h3>
        <ul id="notes">
            <li>
                <p class="no-notes">You don't have any notes.</p>
            </li>
        </ul>

    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<!-- Only used for the demos ads. Please ignore and remove. -->
<script src="https://cdn.tutorialzine.com/misc/enhance/v3.js" async></script>


<script>
    try {
        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        var recognition = new SpeechRecognition();
    }
    catch(e) {
        console.error(e);
        $('.no-browser-support').show();
        $('.app').hide();
    }


    var noteTextarea = $('#note-textarea');
    var instructions = $('#recording-instructions');
    var notesList = $('ul#notes');

    var noteContent = '';

    // Get all notes from previous sessions and display them.
    var notes = getAllNotes();
    renderNotes(notes);



    /*-----------------------------
          Voice Recognition
    ------------------------------*/

    // If false, the recording will stop after a few seconds of silence.
    // When true, the silence period is longer (about 15 seconds),
    // allowing us to keep recording even when the user pauses.
    recognition.continuous = true;

    // This block is called every time the Speech APi captures a line.
    recognition.onresult = function(event) {

        // event is a SpeechRecognitionEvent object.
        // It holds all the lines we have captured so far.
        // We only need the current one.
        var current = event.resultIndex;

        // Get a transcript of what was said.
        var transcript = event.results[current][0].transcript;

        // Add the current transcript to the contents of our Note.
        // There is a weird bug on mobile, where everything is repeated twice.
        // There is no official solution so far so we have to handle an edge case.
        var mobileRepeatBug = (current == 1 && transcript == event.results[0][0].transcript);

        if(!mobileRepeatBug) {
            noteContent += transcript;
            noteTextarea.val(noteContent);
        }
    };

    recognition.onstart = function() {
        instructions.text('Voice recognition activated. Try speaking into the microphone.');
    }

    recognition.onspeechend = function() {
        instructions.text('You were quiet for a while so voice recognition turned itself off.');
    }

    recognition.onerror = function(event) {
        if(event.error == 'no-speech') {
            instructions.text('No speech was detected. Try again.');
        };
    }



    /*-----------------------------
          App buttons and input
    ------------------------------*/

    $('#start-record-btn').on('click', function(e) {
        if (noteContent.length) {
            noteContent += ' ';
        }
        recognition.start();
    });


    $('#pause-record-btn').on('click', function(e) {
        recognition.stop();
        instructions.text('Voice recognition paused.');
    });

    // Sync the text inside the text area with the noteContent variable.
    noteTextarea.on('input', function() {
        noteContent = $(this).val();
    })

    $('#save-note-btn').on('click', function(e) {
        recognition.stop();

        if(!noteContent.length) {
            instructions.text('Could not save empty note. Please add a message to your note.');
        }
        else {
            // Save note to localStorage.
            // The key is the dateTime with seconds, the value is the content of the note.
            saveNote(new Date().toLocaleString(), noteContent);

            // Reset variables and update UI.
            noteContent = '';
            renderNotes(getAllNotes());
            noteTextarea.val('');
            instructions.text('Note saved successfully.');
        }

    })


    notesList.on('click', function(e) {
        e.preventDefault();
        var target = $(e.target);

        // Listen to the selected note.
        if(target.hasClass('listen-note')) {
            var content = target.closest('.note').find('.content').text();
            readOutLoud(content);
        }

        // Delete note.
        if(target.hasClass('delete-note')) {
            var dateTime = target.siblings('.date').text();
            deleteNote(dateTime);
            target.closest('.note').remove();
        }
    });



    /*-----------------------------
          Speech Synthesis
    ------------------------------*/

    function readOutLoud(message) {
        var speech = new SpeechSynthesisUtterance();

        // Set the text and voice attributes.
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1;

        window.speechSynthesis.speak(speech);
    }



    /*-----------------------------
          Helper Functions
    ------------------------------*/

    function renderNotes(notes) {
        var html = '';
        if(notes.length) {
            notes.forEach(function(note) {
                html+= `<li class="note">
        <p class="header">
          <span class="date">${note.date}</span>
          <a href="#" class="listen-note" title="Listen to Note">Listen to Note</a>
          <a href="#" class="delete-note" title="Delete">Delete</a>
        </p>
        <p class="content">${note.content}</p>
      </li>`;
            });
        }
        else {
            html = '<li><p class="content">You don\'t have any notes yet.</p></li>';
        }
        notesList.html(html);
    }


    function saveNote(dateTime, content) {
        localStorage.setItem('note-' + dateTime, content);
    }


    function getAllNotes() {
        var notes = [];
        var key;
        for (var i = 0; i < localStorage.length; i++) {
            key = localStorage.key(i);

            if(key.substring(0,5) == 'note-') {
                notes.push({
                    date: key.replace('note-',''),
                    content: localStorage.getItem(localStorage.key(i))
                });
            }
        }
        return notes;
    }


    function deleteNote(dateTime) {
        localStorage.removeItem('note-' + dateTime);
    }




</script>
</body>
</html>