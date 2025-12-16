<style>
  .glass-footer {
    background: #0f0f0f;
    position: relative;
    overflow: hidden;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    font-family: 'Outfit', sans-serif;
  }

  /* Subtle glow at the top */
  .glass-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(240, 165, 0, 0.5), transparent);
  }

  .footer-title {
    color: #f0a500;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 20px;
  }

  .footer-desc {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
    line-height: 1.6;
  }

  .footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    display: block;
    margin-bottom: 10px;
    transition: all 0.3s ease;
  }

  .footer-links a:hover {
    color: #f0a500;
    transform: translateX(5px);
  }

  .social-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    margin: 0 5px;
    transition: all 0.3s ease;
    text-decoration: none !important;
  }

  .social-btn:hover {
    background: #f0a500;
    color: #000;
    transform: translateY(-3px);
  }

  .copyright {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    margin-top: 40px;
    padding-top: 20px;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.4);
  }
</style>

<footer class="glass-footer pt-5 pb-4">
  <div class="container text-center text-md-left">
    <div class="row text-center text-md-left">
      <!-- Company Info -->
      <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mt-3">
        <h5 class="footer-title">EASYPARK</h5>
        <p class="footer-desc">
          Simplifying your parking experience. <br>
          Reserve spots, pay securely, and park without the hassle.
        </p>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mt-3">
        <h5 class="footer-title">DISCOVER</h5>
        <div class="footer-links">
          <a href="about.php">About Us</a>
          <a href="how-it-works.php">How It Works</a>
          <a href="faq.php">Frequently Asked Questions</a>
          <a href="contact.php">Contact Leadership</a>
          <a href="terms.php">Terms of Service</a>
          <a href="privacy.php">Privacy Policy</a>
        </div>
      </div>

      <!-- Social Links -->
      <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mt-3">
        <h5 class="footer-title">CONNECT</h5>
        <p class="footer-desc mb-3">Follow us for updates and exclusive offers.</p>

        <div class="d-flex justify-content-center justify-content-md-start">
          <a href="https://www.facebook.com/randythegreat000" class="social-btn" target="_blank"><i
              class="fab fa-facebook-f"></i></a>
          <a href="https://x.com/AiruxPH" class="social-btn" target="_blank"><i class="fab fa-twitter"></i></a>
          <a href="https://www.instagram.com/itsmerandythegreat" class="social-btn" target="_blank"><i
              class="fab fa-instagram"></i></a>
          <a href="https://www.linkedin.com/in/anecito-randy-calunod-jr-326680210" class="social-btn" target="_blank"><i
              class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
    </div>

    <!-- Copyright -->
    <div class="copyright text-center">
      <p>&copy; 2025 EasyPark. All Rights Reserved. | Crafted with <i class="fas fa-heart text-danger"></i> for Drivers.
      </p>
    </div>
  </div>
</footer>

<!-- Toast Script Global -->
<script>
  // Initialize standard toasts if present
  $(document).ready(function () {
    $('.toast').toast('show');
  });
</script>