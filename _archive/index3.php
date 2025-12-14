<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EASYPARK - Under Construction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .construction-bg {
            background-image: url('https://images8.alphacoders.com/366/thumb-1920-366762.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .overlay {
            background: rgba(0, 0, 0, 0.7);
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-pulse-slow {
            animation: pulse 3s infinite;
        }
    </style>
</head>
<body class="font-sans">
    <!-- Construction Section -->
    <section class="construction-bg min-h-screen flex items-center justify-center text-white">
        <div class="overlay fixed inset-0"></div>
        <div class="container mx-auto px-4 z-10 text-center mt-4 mb-4">
            <div class="animate-pulse-slow flex items-center justify-center">
    <i class="fas fa-car text-yellow-500 text-8xl"></i>
    <h1 class="text-5xl md:text-7xl font-bold mx-4 mb-4">EASYPARK</h1>
</div>
<h2 class="text-3xl md:text-5xl font-bold mb-8 text-yellow-500 text-center">UNDER CONSTRUCTION</h2>

            
            <p class="text-xl md:text-2xl mb-12 max-w-2xl mx-auto">
                We're building a revolutionary parking solution. Stay tuned for our launch!
            </p>

            <!-- Countdown Timer -->
            <div class="flex flex-wrap justify-center space-x-4 mb-12">
    <div class="bg-white bg-opacity-20 rounded-lg p-4 w-24">
        <div id="days" class="text-3xl font-bold">00</div>
        <div class="text-sm">Days</div>
    </div>
    <div class="bg-white bg-opacity-20 rounded-lg p-4 w-24">
        <div id="hours" class="text-3xl font-bold">00</div>
        <div class="text-sm">Hours</div>
    </div>
    <div class="bg-white bg-opacity-20 rounded-lg p-4 w-24">
        <div id="minutes" class="text-3xl font-bold">00</div>
        <div class="text-sm">Minutes</div>
    </div>
    <div class="bg-white bg-opacity-20 rounded-lg p-4 w-24">
        <div id="seconds" class="text-3xl font-bold">00</div>
        <div class="text-sm">Seconds</div>
    </div>
</div>


            <!-- Contact Info -->
            <div class="max-w-md mx-auto bg-white bg-opacity-10 p-6 rounded-lg">
                <h3 class="text-xl font-semibold mb-4">CONTACT US</h3>
               <div class="text-center">
    <p class="mb-2">
        <a href="mailto:randythegreat000@gmail.com" class="text-lg text-blue-500 hover:text-blue-700">
            <i class="fas fa-envelope mr-2"></i> randythegreat000@gmail.com
        </a>
    </p>
    <p class="mb-2">
        <a href="tel:+639168811468" class="text-lg text-green-500 hover:text-green-700">
            <i class="fas fa-phone mr-2"></i> (+63) 916 881 1468
        </a>
    </p>
</div>

                <div class="flex justify-center space-x-4 mt-4">
                    <a href="http://facebook.com/randythegreat000" class="text-2xl hover:text-orange-500 transition">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="http://x.com/AiruxPH" class="text-2xl hover:text-orange-500 transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="http://instagram.com/itsmerandythegreat" class="text-2xl hover:text-orange-500 transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Countdown Script -->
    <script>
        // Set the launch date (December 1, 2024)
        const launchDate = new Date("May 5, 2025 00:00:00").getTime();

        // Update countdown every second
        const countdown = setInterval(function() {
            const now = new Date().getTime();
            const distance = launchDate - now;

            // Time calculations
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Display results
            document.getElementById("days").innerHTML = days.toString().padStart(2, "0");
            document.getElementById("hours").innerHTML = hours.toString().padStart(2, "0");
            document.getElementById("minutes").innerHTML = minutes.toString().padStart(2, "0");
            document.getElementById("seconds").innerHTML = seconds.toString().padStart(2, "0");

            // If countdown is finished
            if (distance < 0) {
                clearInterval(countdown);
                document.getElementById("days").innerHTML = "00";
                document.getElementById("hours").innerHTML = "00";
                document.getElementById("minutes").innerHTML = "00";
                document.getElementById("seconds").innerHTML = "00";
                document.querySelector("h2").textContent = "WE'RE LIVE!";
                document.querySelector("h2").classList.add("text-green-500");
                document.querySelector("h2").classList.remove("text-orange-500");
            }
        }, 1000);
    </script>
</body>
</html>