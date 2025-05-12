<?php

        $conn = new mysqli("localhost", "root", "", "myapp");

        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if (isset($_POST['username']) && isset($_POST['password']))
            {
                $username = $_POST['username'];
                $password = $_POST['password'];

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare('');
            }
        }

?>