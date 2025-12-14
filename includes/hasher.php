<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasher</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #232526 0%, #414345 100%);
            min-height: 100vh;
            color: #fff;
        }
        .hasher-container {
            max-width: 420px;
            margin: 60px auto;
            background: rgba(0,0,0,0.85);
            border-radius: 16px;
            box-shadow: 0 4px 32px #0005;
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .hasher-title {
            color: #ffc107;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-control, .btn {
            border-radius: 25px;
        }
        .btn-hash {
            background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
            color: #222;
            font-weight: 600;
            border: none;
            transition: background 0.2s;
        }
        .btn-hash:hover {
            background: linear-gradient(90deg, #ff9800 0%, #ffc107 100%);
            color: #111;
        }
        .hash-result {
            background: #222;
            border-radius: 12px;
            padding: 1.5rem 1rem;
            margin-top: 2rem;
            color: #ffc107;
            box-shadow: 0 2px 12px #0003;
        }
        .hash-result strong {
            color: #fff;
        }
        .go-back {
            margin-top: 1.5rem;
            display: block;
            text-align: center;
        }
        .fa-lock {
            color: #ffc107;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="hasher-container">
        <i class="fa fa-lock"></i>
        <h2 class="hasher-title">Password Hasher</h2>
        <form action="hasher.php" method="POST" autocomplete="off">
            <div class="form-group">
                <input type="text" name="password" class="form-control form-control-lg" placeholder="Enter Password" required autofocus>
            </div>
            <button type="submit" class="btn btn-hash btn-block btn-lg mt-3"><i class="fa fa-key"></i> Hash Password</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['password']) && empty($_POST['password'])) {
                // Do nothing
            } else {
                $password = $_POST['password'];
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                echo '<div class="hash-result">';
                echo '<h4 class="mb-3"><i class="fa fa-shield"></i> Hashed Password</h4>';
                echo '<p>Original Password: <strong>' . htmlspecialchars($password) . '</strong></p>';
                echo '<p>Hashed Password: <strong>' . htmlspecialchars($hashed) . '</strong></p>';
                echo '<p>Hash Algorithm: <strong>' . htmlspecialchars(password_get_info($hashed)['algoName']) . '</strong></p>';
                echo '<p>Hash Options: <strong>' . htmlspecialchars(json_encode(password_get_info($hashed)['options'])) . '</strong></p>';
                echo '<a href="hasher.php" class="btn btn-secondary go-back">Go Back</a>';
                echo '</div>';
            }
        }
        ?>
    </div>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/ef9baa832e.js"></script>
</body>
</html>