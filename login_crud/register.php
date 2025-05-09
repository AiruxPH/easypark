<?php

session_start();

if(isset($_SESSION['username'])){
    header("Location: /login_crud/pet_crud/index.php?msg=error2");
    exit();
}

if(isset($_GET['success']))
{
    echo "<script>
        alert('Registration successful!');
    </script>";
}
elseif (isset($_GET["error"]))
{
    echo "<script>
        alert('Registration failed. Username might already exist.');
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
    </style>
</head>
<body>

<div class="con1">
<div class="con">
    <form action="register_handler.php" method="post" autocomplete="off">
        
        
        <h3>Register</h3>
        <label for="username">Username: </label>
                <input id="username" type="text" name="username" placeholder="Username" autocomplete="off" required><br><br>
                <label for="password">Password: </label>
                <input id="password" type="password" name="password" placeholder="Password" autocomplete="new-password" required><br><br>
                Already had an account? <a href="index.php">
                    Login here.
                </a><br><br>
                <button class="btnn" type="submit">Register</button>
        
                
    </form>
    </div>
</div>

</body>
</html>