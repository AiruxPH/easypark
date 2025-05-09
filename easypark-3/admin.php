<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            /* Keep sidebar fixed */
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100; /* Ensure it's above other content */
        }

        .sidebar a {
            padding: 10px 15px;
            color: white;
            display: block;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .content {
            padding: 20px;
            /* Add margin to the left to accommodate the sidebar */
            margin-left: 250px; /* Adjust based on sidebar width */
        }

        /* Styles from index.php */
        .custom-size {
            color: #ffc107;
            transition: text-shadow 0.3s ease-in-out, color 0.3s ease-in-out;
        }

        .custom-size:hover {
            text-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700, 0 0 30px #ffd700;
            color: white;
        }

        .bg-image-dark {
            background-image: url('nav-bg.jpg');
            background-size: 100% auto;
            background-position: top left;
            background-repeat: repeat-y;
        }

        @media (max-width: 768px) {
            .bg-image-dark {
                background-size: cover;
            }

            .sidebar {
                /* Collapse sidebar on smaller screens */
                width: 0;
                overflow: hidden;
                transition: width 0.3s;
            }

            .sidebar.active {
                width: 250px; /* Adjust based on sidebar width */
            }

            .content {
                /* Adjust margin when sidebar is collapsed */
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar bg-image-dark">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-home"></i>
                                Dashboard <span class="sr-only">(current)</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-users"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-chart-bar"></i>
                                Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <!-- Hamburger Menu (Visible on small screens) -->
                <nav class="navbar navbar-dark bg-dark fixed-top d-md-none">
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidebarMenu"
                        aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </nav>

                <div class="content">
                    <h1 class="custom-size">Dashboard</h1>
                    <p>Welcome to the admin dashboard!</p>
                    <!-- Add more content here -->
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="js/jquery.slim.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/ef9baa832e.js"></script>
    <script>
        // JavaScript to toggle the sidebar
        $(document).ready(function() {
            $('.navbar-toggler').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>

</html>
