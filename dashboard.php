<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

//if the user in not a client, redirect to index.php
if($_SESSION['user_type'] != 'client' && $_SESSION['user_type'] == 'admin') {
    header("Location: /admin/admin-dashboard.php");
    exit();
}
//if the user is a staff member, redirect to staff-dashboard.php
if($_SESSION['user_type'] != 'client' && $_SESSION['user_type'] == 'staff') {
    header("Location: /staff/staff-dashboard.php");
    exit();
}

require_once 'db.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT image FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = (!empty($user['image']) && file_exists('images/' . $user['image'])) ? 'images/' . $user['image'] : 'images/default.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>EasyPark Dashboard</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.min.css" >

<style>

#myCarousel {
  height: 90vh;
}
.carousel-inner {
  height: 100%;
}

    .fa:hover {
        transform: scale(1.2);
        transition: transform 0.5s ease-in-out;
    }


    .hov {
      transition: transform 0.3s ease-in-out;
    }

      .bg-image-dark {
        background-image: url('nav-bg.jpg');
        background-size: 100% auto;
        background-position: top left;
        background-repeat: repeat-y;
        
        
      }
      .bg-car {
        background-image: url('bg-car.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
      }

      .custom-size {
        color: #ffc107;
        transition: text-shadow 0.3s ease-in-out, color 0.3s ease-in-out;
      }

      .custom-size:hover {
        text-shadow: 0 0 10px #ffd700, 0 0 20px #ffd700, 0 0 30px #ffd700;
        color: white;
      }

      .custom-hover {
  opacity: 0.5;
  transition: opacity 0.3s ease-in-out;
}
.custom-hover:hover {
  opacity: 1;
}

      #navbar {
  transition: background 1s ease-in-out;
}
.scrolled {
  background: rgba(0, 0, 0, 0.3);
}
      #opp {
        opacity: 1 !important;
        transition: opacity 1s ease-in-out;
      }
      #opp.op1 {
        opacity: 1 !important;
      }

      .navbar-dark .navbar-brand, .navbar-dark .navbar-nav .nav-link {
        color: #fff;
      }
      .navbar-dark .navbar-brand:hover, .navbar-dark .navbar-nav .nav-link:hover {
        color: #ccc;
      }

      @media (max-width: 768px) {
        .carousel-inner .carousel-item h1 {
          font-size: 2.5rem;
        }
        .carousel-inner .carousel-item p {
          font-size: 1.25rem;
        }
        .carousel-inner .carousel-item .btn {
          font-size: 1rem;
          padding: 0.5rem 1rem;
        }
        .custom-size.display-4 {
          font-size: 2.5rem;
        }
      }

      @media (max-width: 576px) {
        .carousel-inner .carousel-item h1 {
          font-size: 2rem;
        }
        .carousel-inner .carousel-item p {
          font-size: 1rem;
        }
        .carousel-inner .carousel-item .btn {
          font-size: 0.875rem;
          padding: 0.4rem 0.8rem;
        }
        .custom-size.display-4 {
          font-size: 2rem;
        }
      }

      .carousel-inner .carousel-item h1 {
        font-size: clamp(2rem, 5vw, 3rem);
      }
      .carousel-inner .carousel-item p {
        font-size: clamp(1rem, 2.5vw, 1.5rem);
      }
      .carousel-inner .carousel-item .btn {
        font-size: clamp(0.875rem, 1.5vw, 1.2rem);
      }
      .carousel-inner .carousel-item {
        align-items: center;
        justify-content: center;
        height: 100%;
      }

.navbar-nav .nav-item {
  margin-right: 15px;
}
</style>
</head>

<body class="bg-car">
<nav id="navbar" class="navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top w-100 px-3">
<a id="opp" class="navbar-brand" href="index.php">
<h1 class="custom-size 75rem">
        EASYPARK
    </h1>
  </a>
  <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
    <ul id="opp" class="navbar-nav">
      <li class="nav-item">
      <a class="nav-link" href="index.php">Home</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">Reserve</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">How It Works</a>
      </li>
      <li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle btn btn-primary d-flex align-items-center" href="#" id="accountDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" style="width:32px;height:32px;object-fit:cover;border-radius:50%;border:2px solid #fff;margin-right:8px;"> 
    <i class="fa-solid fa-circle-user d-none d-md-inline"></i> My Account (<?php echo $_SESSION['username'] ?>)
  </a>
  <div class="dropdown-menu" aria-labelledby="accountDropdown">
    <a class="dropdown-item" href="profile.php">My Profile</a>
    
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="logout.php">Logout</a>
  </div>
</li>
    </ul>
  </div>  
</nav>

<div class="container-fluid text-warning">

  <div id="myCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">

    
    <div class="carousel-inner">
          <div class="carousel-item active">
                    <div class="w-100 d-flex justify-content-center align-items-center container" style="height: 90vh">
                        <div class="container p-5" style="height: 90%;">
                        <br><br><h1 class="custom-size display-4" style="text-align: center;">Welcome to EASYPARK</h1>
                              <br>
                              <p class="lead text-light text-center">Reserve your spot anytime, anywhere</p>
                              <div class="d-flex justify-content-center">
                                  <a href="#" role="button" class="hov btn btn-primary btn-lg mt-3">Reserve Now</a>
                              </div>
                              
                            </div>
                    
                    </div>
          </div>
          <div class="carousel-item">
          <div class="w-100 d-flex justify-content-center align-items-center container" style="height: 90vh">
                        <div class="container p-5" style="height: 90%;">
                        <br><br><h1 class="custom-size display-4" style="text-align: center;">Park with Confidence</h1>
                              <br>
                              <p class="lead text-light text-center">Real-time availability, no more guessing</p>
                              <div class="d-flex justify-content-center">
                                  <a href="#" role="button" class="hov btn btn-primary btn-lg mt-3">Learn More</a>
                              </div>
                              
                            </div>
                    
                    </div>
            </div>
          <div class="carousel-item">
          <div class="w-100 d-flex justify-content-center align-items-center container" style="height: 90vh">
                        <div class="container p-5" style="height: 90%;">
                        <br><br><h1 class="custom-size display-4" style="text-align: center;">Save Time & Fuel</h1>
                              <br>
                              <p class="lead text-light text-center">Stop circling blocks, start parking smarter</p>
                              <div class="d-flex justify-content-center">
                                  <a href="#" role="button" class="hov btn btn-primary btn-lg mt-3">Try our Service</a>
                              </div>
                              
                            </div>
                    
                    </div>
          </div>
          
    </div>

    <a class="carousel-control-prev custom-hover" href="#myCarousel" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next custom-hover" href="#myCarousel" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>


    <ul class="carousel-indicators">
      <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
      <li data-target="#myCarousel" data-slide-to="1" ></li>
      <li data-target="#myCarousel" data-slide-to="2" ></li>
    </ul>
    
    </div>
  </div>

</div>

<?php include 'footer.php'; ?>

<script src="js/jquery.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/ef9baa832e.js"></script>

<script>
  const navbar = document.getElementById('navbar');

window.addEventListener('scroll', function () {
  if (window.scrollY > 100) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});
</script>




</body>

</html>