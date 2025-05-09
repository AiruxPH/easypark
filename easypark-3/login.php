<?php

?>
<!DOCTYPE>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/bootstrap.min.css" >


<style>

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

    @media (max-width: 768px)
      {
        .bg-image-dark {
          background-size: cover;
        }
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
        opacity: 0;
      }

      .custom-hover:hover {
        opacity 1;
      }

      #navbar {
        transition: background: 1s ease-in-out;
      }
      #navbar::before {
        
      }
      .scrolled {
        background: rgba(0, 0, 0, 0.3);
        transition: background: 1s ease-in-out;
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
</style>
</head>

<body>
<nav id="navbar" class="w-100 navbar navbar-expand-lg bg-image-dark navbar-dark sticky-top" >
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
        <a class="nav-link btn btn-primary" href="login.php">Login/Sign Up</a>
      </li>
    </ul>
  </div>  
</nav>

<div class="container-fluid bg-car text-warning"">

<div class="w-100 d-flex justify-content-center align-items-center container" style="height: 90vh">

<div class="container py-5">
  <div id="login" class="d-flex justify-content-center align-items-center flex-column position-relative">
    <div class="w-100" style="max-width: 400px;">
      <h3 class="mb-4 text-center">Log in</h3>

      <!-- Social Login Buttons -->
      <div class="d-flex justify-content-center align-items-center mb-4">
        <a href="uc.php"
           title="Log in with Google"
           class="btn btn-outline-secondary d-flex align-items-center justify-content-center py-2 px-3 rounded mr-2"
           data-qa="google-login-button">
          <img src="https://auth.hostinger.com/assets/images/oauth/google.svg" alt="Google">
        </a>
        <a href="uc.php"
           title="Log in with Facebook"
           class="btn btn-outline-secondary d-flex align-items-center justify-content-center py-2 px-3 rounded mx-2"
           data-qa="facebook-login-button">
          <img src="https://auth.hostinger.com/assets/images/oauth/facebook.svg" alt="Facebook">
        </a>
        <a href="uc.php"
           title="Log in with Github"
           class="btn btn-outline-secondary d-flex align-items-center justify-content-center py-2 px-3 rounded ml-2"
           data-qa="github-login-button">
          <img src="https://auth.hostinger.com/assets/images/oauth/github.svg" alt="Github">
        </a>
      </div>

      <!-- OR Divider -->
      <div class="position-relative mb-4 text-center">
        <hr>
        <p class="position-absolute" style="top: -14px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 10px; font-size: 14px;">or</p>
      </div>

      <!-- Login Form -->
      <form method="post" autocomplete="on" class="w-100">
        <input type="hidden" name="_token" value="z5r1qyxckAvENXL1YDEpMarc1Dy2jSmy3l7NYwpW" autocomplete="off">

        <!-- Email Field -->
        <div class="form-group">
          <label for="email-input">Email address</label>
          <input id="email-input" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" name="email" type="email" class="form-control" required data-qa="email-input">
        </div>

        <!-- Password Field -->
        <div class="form-group">
  <label for="password-input">Password</label>

  <div class="position-relative">
    <input id="password-input" name="password" type="password" class="form-control pr-5" required data-qa="password-input">

    <span id="show-password-button"
          style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;">
      <svg id="eye-icon" width="20" height="20" style="fill: #727586;" xmlns="http://www.w3.org/2000/svg">
        <path clip-rule="evenodd" d="m12 4.5c-5 0-9.27 3.11-11 7.5 1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
      </svg>
    </span>
  </div>
</div>



        <!-- Forgot Password -->
        <div class="form-group mb-4 text-left">
          <a href="/forgot-password" class="small text-primary text-decoration-none" data-qa="forgot-password-link">Forgot password?</a>
        </div>

        <!-- Login Button -->
        <button type="submit" class="btn btn-primary btn-block mb-3" data-qa="login-button">Log in</button>

        <!-- Account Recovery -->
        <div class="text-center mb-3">
          <a href="/account-recovery" class="small text-primary text-decoration-none" data-qa="account-recovery-link">Can't Access Your Account?</a>
        </div>
      </form>

      <!-- Sign Up -->
      <div class="text-center">
        <span>Don't have an account? </span>
        <a href="register" class="small text-primary text-decoration-none" data-qa="signup-button">Sign Up</a>
      </div>
    </div>
  </div>
</div>

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
<script src="js/ef9baa832e.js" crossorigin="anonymous"></script>
<script src="js/jquery.slim.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

<script>
  const navbar = document.getElementById('navbar');
  const opp = document.getElementById('opp');

  window.addEventListener('scroll', function () {
    if (window.scrollY > 100) {

      navbar.classList.add('scrolled');
      opp.classList.add('op1');
    } else {

      navbar.classList.remove('scrolled');
      opp.classList.remove('op1');
    }
  });
</script>


<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("show-password-button");
    const passwordInput = document.getElementById("password-input");
    const eyeIcon = document.getElementById("eye-icon");

    let isPasswordVisible = false;

    toggleBtn.addEventListener("click", function () {
      isPasswordVisible = !isPasswordVisible;

      passwordInput.type = isPasswordVisible ? "text" : "password";

      // Optional: Change icon color when active (can be styled differently too)
      eyeIcon.style.fill = isPasswordVisible ? "#343a40" : "#727586";
    });
  });
</script>


</body>

</html>