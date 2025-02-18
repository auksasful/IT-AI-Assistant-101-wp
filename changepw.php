<?php
session_start();
require_once 'UserManager.php';
require_once 'APIConnector.php';
require_once 'languageconfig.php';

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
$user = $user_manager->get_user_by_username($username);
if ($user->temporary_password == '') {
    wp_redirect(home_url('/itaiassistant101/index'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $user_manager->update_password($username, $new_password);
    wp_redirect(home_url('/itaiassistant101/index'));
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['you_have_to_change_your_password'] ?></title>
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
        .fa.fa-eye-slash {
            padding-top: 0.7rem;
            padding-left: 0.3rem;
        }
        .fa.fa-eye {
            padding-top: 0.7rem;
            padding-left: 0.3rem;
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 500px; margin-top: 50px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">

    <h1><?php echo $lang['you_have_to_change_your_password'] ?></h1>
    <form method="POST">
        <div class="form-group">
            <label for="new_password"><?php echo $lang['enter_new_password'] ?></label>
            <div class="input-group" id="show_hide_password">
                <input type="password" class="form-control" id="new_password" name="new_password" required>
                <div class="input-group-addon">
                    <a href=""><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $lang['change'] ?></button>
    </form>

    <br>
    <br>
    <div class="text-center mb-3">
        <button class="button is-danger" onclick="confirmLogout()"><?php echo $lang['logout']; ?></button>
    </div>
    <br>
    <a id="bottom-task-info" href="<?php echo home_url('/itaiassistant101/faq'); ?>">
        <?php echo $lang['faq']; ?>
    </a>
    <br>
    <div class="text-center">
        <a href="login.php?lang=en"><?php echo $lang['lang_en'] ?></a>
        | <a href="login.php?lang=lt"><?php echo $lang['lang_lt'] ?></a>
    </div>
    </div>


</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
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


    $("#show_hide_password a").on('click', function(event) {
        event.preventDefault();
        if($('#show_hide_password input').attr("type") == "text"){
            $('#show_hide_password input').attr('type', 'password');
            $('#show_hide_password i').addClass( "fa-eye-slash" );
            $('#show_hide_password i').removeClass( "fa-eye" );
        }else if($('#show_hide_password input').attr("type") == "password"){
            $('#show_hide_password input').attr('type', 'text');
            $('#show_hide_password i').removeClass( "fa-eye-slash" );
            $('#show_hide_password i').addClass( "fa-eye" );
        }
    });

</script>

</body>
</html>
    
