<?php

session_start();

if(isset($_SESSION['username'])){
    header("Location: /login_crud/pet_crud/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body
        {
            margin: 0;
        }
        .con1
        {
            height: 100vh;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(2, 2, 2, 0.2);
        }
        .con
        {
            background-color: rgba(2, 2, 2, 0.2);
            width: 30%;
            height: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 2vh;
            padding-bottom: 2vh;
            padding-left: 0;
            padding-right: 0;
            text-align: center;
        }

        .form
        {
            width: 70%;
            height: 50%;
        }

        .btnn{
            transition: all 1.0 ease-in-out;
        }
        .btnn:hover
        {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: transform 1.0 ease-in-out;
        }

        .con1 .con .form .label
        {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="con1">
        <div class="con">
            <?php if (isset($_GET['error'])):?>
                <script>
                    alert("Invalid username or password!");
                </script>
                <?php endif;?>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'error2'):?>
                <script>
                    alert("Please log in first!");
                </script>
                <?php endif;?>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'loggedout'):?>
                <script>
                    alert("You have been logged out successfully.");
                </script>
                <?php endif;?>
            <form action="login.php" method="post" autocomplete="off">
                <h3>Login</h3>
                <label for="username">Username: </label>
                <input id="username" type="text" name="username" placeholder="Username" autocomplete="off" required><br><br>
                <label for="password">Password: </label>
                <input id="password" type="password" name="password" placeholder="Password" autocomplete="new-password" required><br><br>
                Don't have an account? <a href="register.php">
                    Register here.
                </a><br><br>
                <button class="btnn" type="submit">Login</button>
            </form>
        </div>
    </div>
    
</body>
</html>