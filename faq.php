<?php
/*
 * FAQ Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body {
            background: #e0e0e0;
            padding: 15px 0;
        }
        .content {
            background: #fff;
            border-radius: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.075), 0 2px 4px rgba(0, 0, 0, 0.0375);
            padding: 30px;
        }
        .panel-group {
            margin-bottom: 0;
        }
        .panel-group .panel {
            border-radius: 0;
            box-shadow: none;
        }
        .panel-group .panel .panel-heading {
            padding: 0;
        }
        .panel-group .panel .panel-heading h4 a {
            background: #f8f8f8;
            display: block;
            font-size: 12px;
            font-weight: bold;
            padding: 15px;
            text-decoration: none;
            transition: 0.15s all ease-in-out;
        }
        .panel-group .panel .panel-heading h4 a:hover,
        .panel-group .panel .panel-heading h4 a:not(.collapsed) {
            background: #fff;
            transition: 0.15s all ease-in-out;
        }
        .panel-group .panel .panel-heading h4 a:not(.collapsed) i:before {
            content: "\f068";
        }
        .panel-group .panel .panel-heading h4 a i {
            color: #999;
        }
        .panel-group .panel .panel-body {
            padding-top: 0;
        }
        .panel-heading + .panel-collapse > .list-group,
        .panel-heading + .panel-collapse > .panel-body {
            border-top: none;
        }
        .panel + .panel {
            border-top: none;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <a href="index.php">Back to Index</a>
            <div id="accordion" class="panel-group" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div id="headingOne" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Question #1
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <p>
                                Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus.
                                Food truck quinoa nesciunt laborum eiusmod.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div id="headingTwo" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Question #2
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                        <div class="panel-body">
                            <p>
                                Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus.
                                Creative artisan coffee nulla assumenda shoreditch et.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div id="headingThree" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Question #3
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                        <div class="panel-body">
                            <p>
                                Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus.
                                Nihil anim keffiyeh helvetica, craft beer labore.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
</html>