<?php
session_start();

// --- 1. FUNCTIONAL AUTH & CONTACT LOGIC ---
$status_msg = "";
$status_type = "";

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Login/Signup Post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Auth Logic
    if (isset($_POST['auth_action'])) {
        $email = filter_var($_POST['auth_email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['auth_pass'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password)) {
            $_SESSION['user'] = $email;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $status_msg = "Authentication failed. Check your inputs.";
            $status_type = "error";
        }
    }
    // Contact Form Logic (JavaScript handles the validation, PHP handles the "submission")
    elseif (isset($_POST['contact_form'])) {
        $status_msg = "Thank you! Your message has been sent to NexGen Support.";
        $status_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexGen | Premium IT Services Group 2</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --bg: #0b0f1a;
            --card-bg: rgba(22, 30, 46, 0.7);
            --primary: #38bdf8;
            --accent: #6366f1;
            --text: #f1f5f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        body { background-color: var(--bg); color: var(--text); line-height: 1.6; }

        /* Auth Screen */
        .auth-wrapper {
            height: 100vh; display: flex; justify-content: center; align-items: center;
            background: radial-gradient(circle at top right, #1e293b, #0b0f1a);
        }
        .auth-card {
            background: var(--card-bg); padding: 40px; border-radius: 30px;
            width: 100%; max-width: 400px; border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px); text-align: center;
        }

        /* Navigation */
        nav {
            position: fixed; width: 100%; top: 0; z-index: 1000;
            padding: 1.2rem 8%; display: flex; justify-content: space-between; align-items: center;
            background: rgba(11, 15, 26, 0.9); backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .logo { font-size: 1.4rem; font-weight: 800; text-decoration: none; color: white; }
        .logo span { color: var(--primary); }
        .nav-links { display: flex; list-style: none; gap: 20px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-size: 0.85rem; opacity: 0.7; transition: 0.3s; }
        .nav-links a:hover { opacity: 1; color: var(--primary); }

        /* General Sections */
        section { padding: 100px 8%; min-height: auto; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 2.5rem; font-weight: 800; }
        .section-header p { opacity: 0.6; max-width: 600px; margin: 10px auto; }

        /* Hero Section */
        .hero {
            height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center;
            background: radial-gradient(circle at center, rgba(56, 189, 248, 0.05), transparent);
        }
        .hero h1 { font-size: clamp(2.5rem, 8vw, 4rem); font-weight: 800; margin-bottom: 20px; }
        
        /* Service Cards */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
        .card { 
            background: var(--card-bg); padding: 40px; border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.05); transition: 0.4s ease;
        }
        .card:hover { transform: translateY(-10px); border-color: var(--primary); background: rgba(56, 189, 248, 0.05); }
        .card i { font-size: 2.5rem; color: var(--primary); margin-bottom: 20px; display: block; }

        /* Team & Testimonials */
        .team-member { text-align: center; }
        .team-img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid var(--primary); }
        
        .testimonial-card { font-style: italic; background: rgba(255,255,255,0.03); padding: 30px; border-radius: 20px; }

        /* Contact & Map */
        .contact-container { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .map-box { border-radius: 24px; overflow: hidden; height: 100%; min-height: 300px; border: 1px solid rgba(255,255,255,0.1); }

        /* UI Elements */
        input, textarea {
            width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
            padding: 15px; border-radius: 12px; color: white; margin-bottom: 15px; outline: none;
        }
        .cta-btn {
            display: inline-block; padding: 16px 32px; background: var(--primary); color: #0b0f1a;
            border: none; border-radius: 50px; font-weight: 800; cursor: pointer; text-decoration: none; transition: 0.3s;
        }
        .cta-btn:hover { transform: scale(1.05); box-shadow: 0 10px 20px rgba(56,189,248,0.3); }

        .msg { padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
        .error { background: #451a1a; color: #f87171; border: 1px solid #991b1b; }
        .success { background: #064e3b; color: #34d399; border: 1px solid #059669; }

        footer { padding: 60px 8%; background: #070a13; border-top: 1px solid rgba(255,255,255,0.05); }
        .social-links { display: flex; gap: 15px; margin-top: 20px; font-size: 1.2rem; }
        .social-links a { color: var(--primary); transition: 0.3s; }
        .social-links a:hover { color: white; }

        @media (max-width: 900px) { .contact-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php if (!isset($_SESSION['user'])): ?>
        <!-- LOGIN / CREATE ACCOUNT -->
        <div class="auth-wrapper">
            <div class="auth-card">
                <a href="#" class="logo">NEXT<span>GEN</span></a>
                <div style="display:flex; gap:20px; justify-content:center; margin: 25px 0;" id="auth-tabs">
                    <span style="cursor:pointer; color:var(--primary); font-weight:800;">Portal Access</span>
                </div>
                <?php if($status_msg): ?> <div class="msg error"><?php echo $status_msg; ?></div> <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="auth_action" value="login">
                    <input type="email" name="auth_email" placeholder="Student Email" required>
                    <input type="password" name="auth_pass" placeholder="Password" required>
                    <button type="submit" class="cta-btn" style="width:100%">Enter Business Site</button>
                </form>
                <p style="margin-top:20px; font-size:0.7rem; opacity:0.5;">GROUP 2 PROJECT | BSIT</p>
            </div>
        </div>

    <?php else: ?>
        <!-- NAV -->
        <nav>
            <a href="#" class="logo">NEXT<span>GEN</span></a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#team">Team</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="?logout=true" style="color:#f87171"><i class="fas fa-sign-out-alt"></i></a></li>
            </ul>
        </nav>

        <!-- HOME (HERO) -->
        <section class="hero" id="home">
            <div>
                <h1>Innovating Your <br><span>Digital Future</span></h1>
                <p>Enterprise-grade IT solutions for modern businesses. Group 2 excellence.</p>
                <a href="#services" class="cta-btn">View Our Services</a>
            </div>
        </section>

        <!-- ABOUT (MISSION/VISION) -->
        <section id="about" style="background: rgba(255,255,255,0.01);">
            <div class="section-header">
                <h2>About NexGen</h2>
                <p>Leading the path in technical innovation since 2026.</p>
            </div>
            <div class="grid">
                <div class="card">
                    <h3><i class="fas fa-eye"></i> Our Vision</h3>
                    <p>To be the primary catalyst for digital transformation in Himamaylan City and beyond.</p>
                </div>
                <div class="card">
                    <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
                    <p>Providing robust, scalable, and secure IT infrastructure to empower local businesses.</p>
                </div>
            </div>
        </section>

        <!-- SERVICES -->
        <section id="services">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>Tailored solutions for your technical needs.</p>
            </div>
            <div class="grid">
                <div class="card">
                    <i class="fas fa-laptop-code"></i>
                    <h4>Web Development</h4>
                    <p>High-performance web systems and automated platforms.</p>
                </div>
                <div class="card">
                    <i class="fas fa-network-wired"></i>
                    <h4>Network Solutions</h4>
                    <p>Hardware optimization and secure connectivity setups.</p>
                </div>
                <div class="card">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Cyber Security</h4>
                    <p>Information assurance and digital asset protection.</p>
                </div>
            </div>
        </section>

        <!-- TEAM -->
        <section id="team" style="background: rgba(255,255,255,0.01);">
            <div class="section-header">
                <h2>Our Team</h2>
                <p>The experts behind the code.</p>
            </div>
            <div class="grid">
                <div class="team-member">
                    <img src="https://i.pravatar.cc/150?u=joseph" class="team-img" alt="Paul">
                    <h4>Paul Angelo Eto</h4>
                    <p style="color:var(--primary); font-size:0.8rem;">Lead Developer</p>
                </div>
                <div class="team-member">
                    <img src="https://i.pravatar.cc/150?u=group2" class="team-img" alt="Member">
                    <h4>Member Two</h4>
                    <p style="color:var(--primary); font-size:0.8rem;">System Architect</p>
                </div>
                <div class="team-member">
                    <img src="https://i.pravatar.cc/150?u=member3" class="team-img" alt="Member">
                    <h4>Member Three</h4>
                    <p style="color:var(--primary); font-size:0.8rem;">UI/UX Designer</p>
                </div>
            </div>
        </section>

        <!-- TESTIMONIALS -->
        <section id="testimonials">
            <div class="section-header">
                <h2>Client Feedback</h2>
            </div>
            <div class="grid">
                <div class="testimonial-card">
                    <p>"NexGen transformed our office network. Speed increased by 200%!"</p>
                    <small style="color:var(--primary);">- Local Enterprise</small>
                </div>
                <div class="testimonial-card">
                    <p>"The best IT services in Himamaylan City. Very professional team."</p>
                    <small style="color:var(--primary);">- Tech Solutions Inc.</small>
                </div>
            </div>
        </section>

        <!-- CONTACT -->
        <section id="contact">
            <div class="section-header">
                <h2>Get In Touch</h2>
            </div>
            <div class="contact-container">
                <div>
                    <?php if($status_msg && $status_type == 'success'): ?> <div class="msg success"><?php echo $status_msg; ?></div> <?php endif; ?>
                    <form id="contactForm" method="POST">
                        <input type="hidden" name="contact_form" value="1">
                        <input type="text" id="name" placeholder="Full Name" required>
                        <input type="email" id="email" placeholder="Email Address" required>
                        <textarea id="message" rows="5" placeholder="Your Message" required></textarea>
                        <button type="submit" class="cta-btn">Send Message</button>
                    </form>
                </div>
                <div class="map-box">
                    <!-- Embedded Google Map -->
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.3!2d122.8!3d10.1!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDA2JzAwLjAiTiAxMjLCsDQ4JzAwLjAiRQ!5e0!3m2!1sen!2sph!4v1652450000000" width="100%" height="100%" style="border:0; filter: grayscale(1) invert(90%);" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </section>

        <footer>
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:20px;">
                <div>
                    <a href="#" class="logo">NEXT<span>GEN</span></a>
                    <p style="font-size:0.8rem; opacity:0.5; max-width:300px; margin-top:10px;">Providing excellence in IT services for Group 2 Business Website Project.</p>
                </div>
                <div>
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <p style="text-align:center; margin-top:40px; opacity:0.3; font-size:0.7rem;">&copy; 2026 NEXGEN | JOSEPH BUENAVISTA | HIMAMAYLAN CITY</p>
        </footer>
    <?php endif; ?>

    <script>
        // JavaScript Form Validation
        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const name = document.getElementById('name').value;
            
            if(name.length < 3) {
                alert("Please enter a valid name.");
                e.preventDefault();
            }
            if(!email.includes('@')) {
                alert("Please enter a valid email address.");
                e.preventDefault();
            }
        });
    </script>
</body>
</html>