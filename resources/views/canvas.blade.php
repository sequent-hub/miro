<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Moodboard</title>
    <link rel="stylesheet" href="/build/assets/font-awsome/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .panel{
            background: #14213D;
            height: 600px;
            width: 100px;
            color: #E5E5E5;
            border-radius: 10px 0 0 10px;
            border: 2px solid #14213D;
        }
        .btn{
            text-align: center;
            padding: 10px;
            cursor: pointer;
            font-family: "Roboto", sans-serif;
            font-optical-sizing: auto;
            font-weight: 100;
            font-style: normal;
            font-variation-settings:
                "wdth" 100;
        }
        .btn span{
            font-size: 12px;
        }
        .btn i{
            font-size: 16px;
        }
    </style>
</head>
<body style="padding: 0; margin: 0; ">
    <div style="display: flex; justify-content: center;  height: 100%; width: 100%; position: fixed; padding-top: 60px;">
        <div class="panel">
            <div class="btn" id="triangleBtn">
                <i class="fa fa-play" aria-hidden="true"></i>
                <span>Треугольник</span>
            </div>
            <div class="btn" id="rectBtn">
                <i class="fa fa-stop" aria-hidden="true"></i>
                <span>Прямоугольник</span>
            </div>
            <div class="btn" id="circleBtn">
                <i class="fa fa-circle" aria-hidden="true"></i> <br>
                <span>Круг</span>
            </div>
            <div class="btn" id="imgTxt">
                <i class="fa fa-font" aria-hidden="true"></i><br>
                <span>Текст</span>
            </div>
            <div class="btn" id="imgBtn">
                <i class="fa fa-file-image-o" aria-hidden="true"></i> <br>
                <span>Картинка</span>
            </div>
        </div>
        <canvas id="c" width="1200" height="600" style="border: 2px solid #14213D"></canvas>
        <img src="https://i.pinimg.com/originals/c8/27/e1/c827e1b2599695b629786a93713bfafb.jpg" alt="" id="img" style="width: 0px; height: 0px">
    </div>
@vite('resources/js/app.js')
</body>
</html>
