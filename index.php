<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://public-frontend-cos.metadl.com/mgx/img/favicon_atoms.ico" type="image/x-icon">
    <title>Faculty Management Evaluation System</title>
    <link rel="stylesheet" href="./css/common.css">
    <link rel="stylesheet" href="./css/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="nav-brand">
                <img src="./media/nbsc_logo.png" alt="NBSC Logo" class="nav-logo">
                <img src="./media/LOGO.jpg" alt="Logo" class="nav-logo">
                <span class="nav-title">Institute For Business Management</span>
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="./Door/login.php" class="nav-login-btn">Login</a></li>
            </ul>
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <section class="hero" id="hero">
        <div class="hero-bg">
            <img src="https://mgx-backend-cdn.metadl.com/generate/images/983975/2026-02-22/6f368e27-8807-44ed-8ad2-dd187dd13629.png" alt="Campus">
        </div>
        <div class="hero-overlay"></div>

        <div class="particles" id="particles"></div>

        <div class="hero-content">
            <span class="hero-badge">Welcome IBM</span>
            <h1 class="hero-title">
                Institute For Business Management<br>
                <span class="gold-highlight">Student Evaluation System</span>
            </h1>
            <p class="hero-subtitle">
                Empowering excellence in education through comprehensive student performance tracking, evaluation, and assessment reporting.
            </p>
           
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <img src="./media/LOGO.jpg" alt="Logo" class="footer-logo">
                <span>Student Evaluation System</span>
            </div>
            <p>&copy; 2026 CJCM. All Rights Reserved.</p>
            <ul class="footer-links">
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
    </footer>

    <script>    function addRefreshButton() {
            var navLinks = document.getElementById('navLinks');
            if (navLinks) {
                var refreshLi = document.createElement('li');
                var refreshLink = document.createElement('a');
                refreshLink.href = 'javascript:void(0)';
                refreshLink.className = 'nav-refresh-btn';
                refreshLink.title = 'Refresh';
                refreshLink.onclick = function() { location.reload(); };
                refreshLink.innerHTML = '<i class="fas fa-sync-alt"></i>';
                refreshLi.appendChild(refreshLink);
                navLinks.insertBefore(refreshLi, navLinks.firstChild);
            }
        }
           if (!sessionStorage.getItem('siteVisited')) {
            sessionStorage.setItem('siteVisited', 'true');   setTimeout(function() {
                window.location.reload();
            }, 500);
        }
        
        document.addEventListener('DOMContentLoaded', addRefreshButton);
    </script>
    <script type="module" src="./js/landing.js"></script>
</body>

</html>
