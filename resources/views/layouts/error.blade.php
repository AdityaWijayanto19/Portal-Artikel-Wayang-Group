<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Terjadi Kesalahan' }} - Portal Artikel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700;800&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #050505;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            position: relative;
            overflow: hidden;
        }

        .stars-bg {
            position: absolute;
            inset: 0;

            width: 100%;
            height: 100%;

            background-image:
                radial-gradient(1.5px 1.5px at 10% 15%, #000000 100%, transparent),
                radial-gradient(1px 1px at 20% 35%, #000000 100%, transparent),
                radial-gradient(2px 2px at 30% 70%, #000000 100%, transparent),
                radial-gradient(1.5px 1.5px at 40% 25%, #000000 100%, transparent),
                radial-gradient(1px 1px at 50% 85%, #000000 100%, transparent),
                radial-gradient(2px 2px at 60% 10%, #000000 100%, transparent),
                radial-gradient(1.5px 1.5px at 70% 50%, #000000 100%, transparent),
                radial-gradient(1px 1px at 80% 30%, #000000 100%, transparent),
                radial-gradient(2px 2px at 88% 80%, #000000 100%, transparent),
                radial-gradient(1.5px 1.5px at 95% 15%, #000000 100%, transparent);

            z-index: 1;
            pointer-events: none;
        }

        .planet {
            position: absolute;

            width: 36px;
            height: 36px;

            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath stroke='none' d='M0 0h24v24H0z' fill='none'/%3E%3Cpath d='M18.816 13.58c2.292 2.138 3.546 4 3.092 4.9c-.745 1.46 -5.783 -.259 -11.255 -3.838c-5.47 -3.579 -9.304 -7.664 -8.56 -9.123c.464 -.91 2.926 -.444 5.803 .805'/%3E%3Cpath d='M5 12a7 7 0 1 0 14 0a7 7 0 1 0 -14 0'/%3E%3C/svg%3E");

            background-size: contain;
            background-repeat: no-repeat;

            opacity: 0.8;

            z-index: 1;
            pointer-events: none;
        }

        .planet-1 {
            top: 32%;
            left: 12%;
            transform: rotate(-15deg);
        }

        .planet-2 {
            top: 12%;
            left: 45%;
            transform: rotate(10deg) scale(0.7);
        }

        .planet-3 {
            top: 36%;
            right: 19%;
            transform: rotate(-25deg) scale(0.8);
        }

        .planet-4 {
            bottom: 20%;
            right: 27%;
            transform: rotate(15deg);
        }

        .container {
            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 600px;

            padding: 20px;

            text-align: center;
        }

        .error-code {
            display: flex;

            justify-content: center;
            align-items: center;

            font-family: 'Fredoka', sans-serif;

            font-size: clamp(140px, 22vw, 240px);

            font-weight: 700;

            line-height: 0.85;

            color: #9d00ff;

            user-select: none;

            margin-bottom: 24px;
        }

        .error-code .digit {
            position: relative;

            display: inline-block;

            -webkit-text-stroke: 3px #5a0091;

            paint-order: stroke fill;

            filter:
                drop-shadow(0px 12px 18px rgba(157, 0, 255, 0.25));

            transition: transform 0.3s ease;
        }

        .error-code .digit:hover {
            transform: scale(1.04);
        }

        .digit-1 {
            z-index: 1;
            margin-right: -0.12em;
        }

        .digit-2 {
            z-index: 2;
            margin-right: -0.12em;
        }

        .digit-3 {
            z-index: 3;
        }

        .error-title {
            font-size: 22px;

            font-weight: 600;

            margin-bottom: 10px;

            color: #050505;

            letter-spacing: 0.3px;
        }

        .error-description {
            font-size: 14px;

            color: #52525b;

            line-height: 1.5;

            margin-bottom: 32px;

            font-weight: 400;
        }

        .btn-home {
            display: inline-block;

            background-color: #9d00ff;

            color: #ffffff;

            padding: 12px 36px;

            border-radius: 50px;

            font-size: 12px;

            font-weight: 700;

            text-decoration: none;

            text-transform: uppercase;

            letter-spacing: 1px;

            border: none;

            cursor: pointer;

            box-shadow:
                0 4px 20px rgba(157, 0, 255, 0.4);

            transition: all 0.2s ease;
        }

        .btn-home:hover {
            background-color: #b128ff;

            box-shadow:
                0 6px 28px rgba(157, 0, 255, 0.7);

            transform: translateY(-2px);
        }

        .btn-home:active {
            transform: translateY(0);
        }

        @media (max-width: 640px) {

            .container {
                padding: 16px;
            }

            .error-code {
                margin-bottom: 20px;
            }

            .error-title {
                font-size: 20px;
            }

            .error-description {
                font-size: 13px;
                margin-bottom: 28px;
            }

            .btn-home {
                padding: 11px 30px;
            }

            .planet-1 {
                left: 5%;
            }

            .planet-3 {
                right: 7%;
            }

            .planet-4 {
                right: 10%;
            }
        }
    </style>
</head>

<body>

    <div class="stars-bg"></div>

    <div class="planet planet-1"></div>
    <div class="planet planet-2"></div>
    <div class="planet planet-3"></div>
    <div class="planet planet-4"></div>


    <main class="container">
        <div class="error-code">
            @yield('error-code')
        </div>

        <h1 class="error-title">
            @yield('title')
        </h1>

        <p class="error-description">
            @yield('description')
        </p>

        @yield('action')
    </main>

</body>

</html>
