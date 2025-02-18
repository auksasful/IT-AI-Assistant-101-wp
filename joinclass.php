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
    elseif ($message === 'cancel-tied-request') {
        error_log('Cancelling tied request');
        $user_manager->update_user_tied_request($username, '', false);
        echo $lang['request_cancelled'];
        error_log('Tied request cancelled');
        exit();
    }
}

function joinClass($teacher_username) {
    global $username;
    global $user_manager;
    global $lang;
    $teacher_user = $user_manager->get_user_by_username($teacher_username);
    if ($teacher_user) {
        $user_manager->update_user_tied_request($username, $teacher_username, true);
        echo $teacher_user->user_username;
        error_log('Class join request successful');
        exit();
    }
    else {
        echo $lang['teacher_not_found'];
        error_log($lang['teacher_not_found']);
        exit();
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Class</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@3.0.7/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/6.0.0/bootbox.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            width: 50%;
            margin: 50px auto;
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
        #bottom-task-info {
            position: sticky;
            bottom: 0;
            background-color: #F9FBD3;
            padding: 10px;
            border-top: 1px solid #dee2e6;
            margin-top: auto;
            width: 100%;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            text-align: left;
            transition: background-color 0.3s ease;
            display: inline-block;
            text-align: center;
        }
        #bottom-task-info:hover {
            background-color:rgb(220, 255, 164);
        }

    </style>
</head>
<body>

<div class="container">
    <div id="itaiassistant101_join_class_Form">
        <?php if(empty($current_user->tied_request)): ?>
            <h2><?php echo $lang['join_class_to_start'] ?></h2>
            <div class="form-group">
                <label for="teacher_username"><?php echo $lang['teacher_username'] ?></label>
                <input type="text" id="teacher_username" name="teacher_username" class="form-control" required>
            </div>
            <button class="btn btn-primary" onclick="joinClass()"><?php echo $lang['login'] ?></button>
        <?php else: ?>
            <h2><?php echo $lang['request_pending'] ?></h2>
            <p class="text-center"><?php echo sprintf($lang['request_sent_to'], $current_user->tied_request) ?></p>
            <div class="text-center">
                <button class="btn btn-danger" onclick="cancelTiedRequest()"><?php echo $lang['cancel_request'] ?></button>
            </div>
        <?php endif; ?>
    </div>

    <br>
    <a id="bottom-task-info" href="<?php echo home_url('/itaiassistant101/faq'); ?>">
        <?php echo $lang['faq']; ?>
    </a>
    <br>
<div class="text-center mb-3">
    <button class="button is-danger" onclick="confirmLogout()"><?php echo $lang['logout']; ?></button>
</div>
<div class="text-center">
    <a href="login.php?lang=en"><?php echo $lang['lang_en'] ?></a>
    | <a href="login.php?lang=lt"><?php echo $lang['lang_lt'] ?></a>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
    $(document).ready(function() {

      
    });

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
        .then(response => response.text())
        .then(message => {
            console.log(message);
            console.log("<?php echo $lang['teacher_not_found']; ?>");
            if (message === "<?php echo $lang['teacher_not_found']; ?>") {
                bootbox.alert('<?php echo $lang['teacher_not_found']; ?>');
                return;
            }
            else {
                bootbox.alert({
                    message: '<?php echo $lang['class_join_request_sent']; ?> ' + message,
                    callback: function() {
                        window.location.reload();
                    }
                });
            }
        })
        .catch(error => {
            bootbox.alert('<?php echo $lang['error_sending_join_request']; ?>');
        });
    }

    function cancelTiedRequest() {
        bootbox.confirm({
            title: "<?php echo $lang['cancel_request_confirmation_header']; ?>",
            message: "<?php echo $lang['cancel_request_confirmation']; ?>",
            buttons: {
                cancel: {
                    label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                },
                confirm: {
                    label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                }
            },
            callback: function (result) {
                if (result) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'message=cancel-tied-request',
                    })
                    .then(response => response.text())
                    .then(message => {
                        console.log(message);
                        if (message.includes("<?php echo $lang['request_cancelled']; ?>")) {
                            bootbox.alert({
                                message: "<?php echo $lang['request_cancelled']; ?>",
                                callback: function() {
                                    window.location.reload();
                                }
                            });
                        }
                    })
                    .catch(error => {
                        bootbox.alert('<?php echo $lang['error_cancelling_request']; ?>');
                    });
                }
            }
        });
    }
    
    function confirmLogout() {
        bootbox.confirm({
            title: "<?php echo $lang['logout_confirmation_header']; ?>",
            message: "<?php echo $lang['logout_confirmation']; ?>",
            buttons: {
                cancel: {
                    label: '<i class="fa fa-times"></i> <?php echo $lang['cancel']; ?>'
                },
                confirm: {
                    label: '<i class="fa fa-check"></i> <?php echo $lang['confirm']; ?>'
                }
            },
            callback: function (result) {
                if (result) {
                    window.location.href = "<?php echo home_url('/itaiassistant101/logout'); ?>";
                }
            }
        });
    }

</script>
</body>
</html>
