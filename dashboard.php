<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
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

    }

    .hov:hover {
      transform: scale(1.1);
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
        transition: opacity: 1s ease-in-out;
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
    font-size: 2rem;
  }
  .carousel-inner .carousel-item p {
    font-size: 1rem;
  }
  .carousel-inner .carousel-item .btn {
    font-size: 1rem;
    padding: 0.5rem 1rem;
  }
}
  .carousel-inner .carousel-item h1 {
    font-size: 3rem; /* Use rem for scalable font size */
  }
  .carousel-inner .carousel-item p {
    font-size: 1.5rem; /* Use rem for scalable font size */
  }
  .carousel-inner .carousel-item .btn {
    font-size: 1.2rem; /* Use rem for scalable font size */
    padding: 0.75rem 1.5rem; /* Adjust button padding */
  }

  .carousel-inner .carousel-item {
  align-items: center;
  justify-content: center;
  height: 100%; /* Keep for content centering */
}

.navbar-nav .nav-item {
  margin-right: 15px;
}
</style>
</head>

<body class="bg-car">
<nav id="navbar" class="w-100 navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top">
<a id="opp" class="navbar-brand" href="index.php">
<h1 class="custom-size 75rem">
        EASYPARK
    </h1>
  </a>
  <button class="navbar-toggler ml-auto" type="button" data-toggle="collapse" data-target="#collapsibleNavbar ">
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
      <li class="nav-item">
        <a class="nav-link btn btn-primary" href="#"> <i class="fa-solid fa-circle-user"></i> My Account</a>
      </li>

      <li class="nav-item">
        <a class="nav-link btn btn-primary" href="logout.php"> Logout</a>
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
<footer class="bg-dark text-light pt-5 pb-4">
  <div class="container text-center text-md-left">
    <div class="row">
      <!-- Company Info -->
      <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 font-weight-bold text-warning">EASYPARK</h5>
        <p>We help drivers find and reserve parking spots faster, smarter, and stress-free.</p>
      </div>

      <!-- Quick Links -->
      <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
        <h5 class="text-uppercase mb-4 font-weight-bold text-warning">Quick Links</h5>
        <p><a href="#about" class="text-light" style="text-decoration: none;">About Us</a></p>
        <p><a href="#terms" class="text-light" style="text-decoration: none;">Terms of Service</a></p>
        <p><a href="#privacy" class="text-light" style="text-decoration: none;">Privacy Policy</a></p>
        <p><a href="#contact" class="text-light" style="text-decoration: none;">Contact</a></p>
      </div>

      <!-- Social Links -->
      <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3 text-center">
        <h5 class="text-uppercase mb-4 font-weight-bold text-warning">Follow Us</h5>
        <a href="https://www.facebook.com/randythegreat000" class="text-light mr-4" target="_blank" rel="noopener"><i class="fa fa-facebook fa-lg"></i></a>
        <a href="https://x.com/AiruxPH" class="text-light mr-4" target="_blank" rel="noopener"><i class="fa fa-twitter fa-lg"></i></a>
        <a href="https://www.instagram.com/itsmerandythegreat" class="text-light mr-4" target="_blank" rel="noopener"><i class="fa fa-instagram fa-lg"></i></a>
        <a href="https://www.linkedin.com/in/anecito-randy-calunod-jr-326680210" class="text-light" target="_blank" rel="noopener"><i class="fa fa-linkedin fa-lg"></i></a>

      </div>
    </div>

    <!-- Copyright -->
    <div class="row mt-4">
      <div class="col-md-12 text-center">
        <p class="text-muted mb-0">&copy; 2025 EASYPARK. All Rights Reserved.</p>
      </div>
    </div>
  </div>
</footer>

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