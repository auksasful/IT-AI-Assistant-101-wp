<?php

session_start();
require_once 'languageconfig.php';
require_once 'APIConnector.php';
require_once 'UserManager.php';


$api_connector = new ApiConnector('');
$user_manager = new UserManager();

if (isset($_SESSION['jwt_token'])) {
    $jwt_token = $_SESSION['jwt_token'];
    $decoded_token = $api_connector->verify_jwt($jwt_token);

    if (!$decoded_token) {
        // Token is invalid, redirect to login
        wp_redirect(home_url('/itaiassistant101/login'));
        exit();
    }
} else {
    // No token found, redirect to login
    wp_redirect(home_url('/itaiassistant101/login'));
    exit();
}

$username = $decoded_token->data->username;
$userType = $decoded_token->data->user_type;

$current_user = $user_manager->get_user_by_username($username);
$class_id = $current_user->last_used_class_id;
if ($class_id) {
    wp_redirect(home_url('/itaiassistant101/index'));
    exit();
}


// Debug statement to check the script execution
error_log('joinclass.php script executed');

$tied_teacher = $user_manager->get_tied_teacher($username);

if (isset($_POST['message'])) {
    $message = $_POST['message'];
    error_log('Message: ' . $message);
    if ($message === 'join-class') {
        error_log('Joining class');
        if (isset($_POST['teacher_username'])) {
            joinClass($_POST['teacher_username']);
        }
    }
}

function joinClass($teacher_username) {
    global $username;
    global $user_manager;
    $teacher_user = $user_manager->get_user_by_username($teacher_username);
    if ($teacher_user) {
        $user_manager->update_user_tied_request($username, $teacher_username, true);
        echo json_encode($teacher_user);
        error_log('Class join request successful');
    }
    else {
        echo 'Teacher not found';
        error_log('Teacher not found');
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Class</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin-top: 50px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn-primary {
            width: 100%;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
        }
        .btn-group .btn {
            width: 48%;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div id="itaiassistant101_join_class_Form">
        <h2><?php echo $lang['join_class_to_start'] ?></h2>
            <div class="form-group">
                <label for="teacher_username"><?php echo $lang['teacher_username'] ?></label>
                <input type="text" id="teacher_username" name="teacher_username" class="form-control" required>
            </div>
            <button class="btn btn-primary" onclick="joinClass()"><?php echo $lang['login'] ?></button>
    </div>

    <br>
    <br>
<div class="text-center"></div>
    <a href="login.php?lang=en" onclick="event.preventDefault(); window.location.href='<?php echo home_url('/itaiassistant101/login?lang=en'); ?>';"><?php echo $lang['lang_en'] ?></a>
    | <a href="login.php?lang=lt" onclick="event.preventDefault(); window.location.href='<?php echo home_url('/itaiassistant101/login?lang=lt'); ?>';"><?php echo $lang['lang_lt'] ?></a>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
    $(document).ready(function() {

      
    });

    function joinClass() {
            teacherUsername = document.getElementById('teacher_username').value;
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=join-class&teacher_username=' + teacherUsername,
            })
            .then(response => response.json())
            .then(message => {
                bootbox.alert('Class joined successfully ' + message);
            })
            .catch(error => {
                bootbox.alert('An error occurred');
            });
        }

</script>
</body>
</html>
