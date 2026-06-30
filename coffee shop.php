<?php
// ==========================================
// 1. DATABASE CONFIGURATION & INITIALIZATION
// ==========================================
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "coffee_shop";

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Automatically create database and tables if they don't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$db_name`;");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contacts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `message` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `total_price` DECIMAL(10,2) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `item_name` VARCHAR(100) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    );");

} catch (PDOException $e) {
    die("Database Connection Failure: " . $e->getMessage());
}

// ==========================================
// 2. BACKEND POST REQUEST PROCESSING
// ==========================================
$form_feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Handle Shopping Cart Orders
    if ($action === 'place_order') {
        $cart_json = isset($_POST['cart_data']) ? $_POST['cart_data'] : '';
        $cart_items = json_decode($cart_json, true);

        if (!empty($cart_items)) {
            try {
                $pdo->beginTransaction();

                // 1. Calculate grand total
                $grand_total = 0;
                foreach ($cart_items as $item) {
                    $grand_total += $item['price'] * $item['quantity'];
                }

                // 2. Insert main order record
                $stmtOrder = $pdo->prepare("INSERT INTO `orders` (`total_price`) VALUES (:total_price)");
                $stmtOrder->execute([':total_price' => $grand_total]);
                $order_id = $pdo->lastInsertId();

                // 3. Insert individual items linked to that order ID
                $stmtItem = $pdo->prepare("INSERT INTO `order_items` (`order_id`, `item_name`, `price`, `quantity`) VALUES (:order_id, :item_name, :price, :quantity)");
                
                $breakdown = "";
                foreach ($cart_items as $item) {
                    $subtotal = $item['price'] * $item['quantity'];
                    $stmtItem->execute([
                        ':order_id'  => $order_id,
                        ':item_name' => $item['name'],
                        ':price'     => $item['price'],
                        ':quantity'  => $item['quantity']
                    ]);
                    $breakdown .= "<li><strong>{$item['name']}</strong> (x{$item['quantity']}) - \$" . number_format($subtotal, 2) . "</li>";
                }

                $pdo->commit();
                
                $form_feedback = "
                    <div class='alert success'>
                        <h3>☕ Order Saved to Database! (ID: #$order_id)</h3>
                        <p>Your order is being meticulously prepared by our baristas.</p>
                        <ul style='text-align: left; margin: 1rem auto; max-width: 300px; padding-left: 20px;'>
                            $breakdown
                        </ul>
                        <strong>Grand Total: \$" . number_format($grand_total, 2) . "</strong>
                    </div>";

            } catch (Exception $e) {
                $pdo->rollBack();
                $form_feedback = "<div class='alert error'><h3>❌ Database Error</h3><p>" . $e->getMessage() . "</p></div>";
            }
        }
    } 
    
    // Handle Contact Form Entries
    elseif ($action === 'send_message') {
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $message = htmlspecialchars(trim($_POST['message']));

        if (!empty($name) && !empty($email) && !empty($message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO `contacts` (`name`, `email`, `message`) VALUES (:name, :email, :message)");
                $stmt->execute([
                    ':name'    => $name,
                    ':email'   => $email,
                    ':message' => $message
                ]);

                $form_feedback = "
                    <div class='alert success'>
                        <h3>✨ Contact Record Saved!</h3>
                        <p>Thank you, <strong>$name</strong>! We have stored your inquiry and will respond to <strong>$email</strong> shortly.</p>
                    </div>";
            } catch (PDOException $e) {
                $form_feedback = "<div class='alert error'><h3>❌ Database Error</h3><p>" . $e->getMessage() . "</p></div>";
            }
        }
    }
}

// ==========================================
// 3. CATALOG DATA (Coffee & New Desserts)
// ==========================================
$menu_items = [
    // Beverages
    ['id' => 1, 'name' => 'Caramel Macchiato', 'price' => 4.50, 'category' => 'Coffee', 'image' => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?w=500&auto=format&fit=crop&q=60'],
    ['id' => 2, 'name' => 'Vanilla Latte', 'price' => 4.25, 'category' => 'Coffee', 'image' => 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=500&auto=format&fit=crop&q=60'],
    ['id' => 3, 'name' => 'Iced Matcha Latte', 'price' => 4.75, 'category' => 'Tea', 'image' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?w=500&auto=format&fit=crop&q=60'],
    
    // Exquisite Desserts
    ['id' => 4, 'name' => 'Velvet Espresso Tiramisu', 'price' => 6.50, 'category' => 'Dessert', 'image' => 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=500&auto=format&fit=crop&q=60'],
    ['id' => 5, 'name' => 'Dark Chocolate Lava Cake', 'price' => 7.00, 'category' => 'Dessert', 'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=500&auto=format&fit=crop&q=60'],
    ['id' => 6, 'name' => 'New York Blueberry Cheesecake', 'price' => 5.75, 'category' => 'Dessert', 'image' => 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=500&auto=format&fit=crop&q=60']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Aura – Elegant Coffee & Patisserie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c1b11;
            --accent: #c5a880;
            --light-bg: #fdfbf7;
            --text-dark: #333333;
            --text-light: #777777;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Navigation */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(44, 27, 17, 0.95);
            padding: 1.2rem 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            color: var(--accent);
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            letter-spacing: 2px;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            margin-left: 2rem;
            transition: color 0.3s;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-links a:hover {
            color: var(--accent);
        }

        /* Hero Section */
        .hero {
            height: 70vh;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=1600&auto=format&fit=crop&q=80') center/cover;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: var(--white);
            padding: 0 1rem;
            margin-top: 60px;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 1rem;
            letter-spacing: 2px;
        }

        .hero p {
            font-size: 1.2rem;
            font-weight: 300;
            margin-bottom: 2rem;
            max-width: 600px;
            color: #e0e0e0;
        }

        .btn {
            background-color: var(--accent);
            color: var(--primary);
            padding: 0.8rem 2.5rem;
            text-decoration: none;
            font-weight: 600;
            border-radius: 4px;
            transition: all 0.3s ease;
            border: 1px solid var(--accent);
        }

        .btn:hover {
            background-color: transparent;
            color: var(--accent);
        }

        /* Feedback Alerts */
        .alert-container {
            max-width: 1200px;
            margin: 4rem auto -2rem auto;
            padding: 0 2rem;
        }

        .alert {
            background: var(--white);
            border-left: 4px solid var(--accent);
            padding: 1.5rem;
            border-radius: 4px;
            text-align: center;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
        }
        
        .alert.error {
            border-left-color: #cc0000;
        }

        /* Main Container layout for Menu & Cart */
        .main-layout {
            max-width: 1200px;
            margin: 5rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }

        @media (max-width: 900px) {
            .main-layout { grid-template-columns: 1fr; }
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: var(--primary);
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 2rem;
        }

        .menu-item {
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: transform 0.3s;
        }

        .menu-item:hover {
            transform: translateY(-5px);
        }

        .menu-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .menu-info {
            padding: 1.5rem;
        }

        .menu-category {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .menu-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .menu-price {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .add-to-cart-btn {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.6rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .add-to-cart-btn:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* Elegant Cart Sidebar */
        .cart-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .cart-items-list {
            list-style: none;
            margin-bottom: 1.5rem;
            max-height: 240px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eeeeee;
            font-size: 0.95rem;
        }

        .cart-item-remove {
            background: none;
            border: none;
            color: #cc0000;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            border-top: 2px solid var(--primary);
            padding-top: 1rem;
        }

        /* Contact Section */
        .contact-section {
            background-color: rgba(44, 27, 17, 0.02);
            padding: 5rem 2rem;
        }

        .contact-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: var(--white);
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 1rem;
            outline: none;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--accent);
        }

        .submit-btn {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: var(--accent);
            color: var(--primary);
        }

        .submit-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            color: #777777;
        }

        footer {
            background: var(--primary);
            color: #b0a096;
            text-align: center;
            padding: 2rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <nav>
        <a href="#" class="logo">L'AURA</a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#menu">Menu & Desserts</a>
            <a href="#contact">Contact</a>
        </div>
    </nav>

    <section id="home" class="hero">
        <h1>Crafted Passion, Elegantly Served</h1>
        <p>Indulge in artisanal coffees perfectly paired with our newly launched premium sweet delicacies.</p>
        <a href="#menu" class="btn">Explore Menu</a>
    </section>

    <?php if(!empty($form_feedback)): ?>
        <div class="alert-container">
            <?= $form_feedback; ?>
        </div>
    <?php endif; ?>

    <main class="main-layout" id="menu">
        <div>
            <h2 class="section-title">Menu & Masterpiece Desserts</h2>
            <div class="menu-grid">
                <?php foreach($menu_items as $item): ?>
                    <div class="menu-item">
                        <img src="<?= $item['image']; ?>" alt="<?= $item['name']; ?>" class="menu-img">
                        <div class="menu-info">
                            <div class="menu-category"><?= $item['category']; ?></div>
                            <h3><?= $item['name']; ?></h3>
                            <div class="menu-price">$<?= number_format($item['price'], 2); ?></div>
                            <button class="add-to-cart-btn" onclick="addToCart(<?= $item['id']; ?>, '<?= addslashes($item['name']); ?>', <?= $item['price']; ?>)">
                                Add to Order
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cart-container">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Your Order</h3>
            <ul class="cart-items-list" id="cart-items">
                <li style="color: var(--text-light); font-size: 0.9rem; text-align: center;">Your cart is empty.</li>
            </ul>
            <div class="cart-total">
                <span>Total:</span>
                <span id="cart-total-amount">$0.00</span>
            </div>
            <form action="" method="POST" onsubmit="return passCartData(this)">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <input type="hidden" name="action" value="place_order">
                <button type="submit" class="submit-btn" id="checkout-btn" disabled>Place Order</button>
            </form>
        </div>
    </main>

    <section class="contact-section" id="contact">
        <div class="contact-wrapper">
            <h2 class="section-title" style="text-align: center;">Get in Touch</h2>
            <form action="" method="POST">
                <input type="hidden" name="action" value="send_message">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required placeholder="Your name">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label for="message">Message / Special Requests</label>
                    <textarea id="message" name="message" rows="5" required placeholder="Tell us how we can curate your experience..."></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
    </section>

    <footer>
        <p>&copy; 2026 L'Aura Espresso Lounge. All rights reserved.</p>
    </footer>

    <script>
        let cart = [];

        function addToCart(id, name, price) {
            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            updateCartUI();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCartUI();
        }

        function updateCartUI() {
            const cartList = document.getElementById('cart-items');
            const totalSpan = document.getElementById('cart-total-amount');
            const checkoutBtn = document.getElementById('checkout-btn');

            if (cart.length === 0) {
                cartList.innerHTML = '<li style="color: var(--text-light); font-size: 0.9rem; text-align: center;">Your cart is empty.</li>';
                totalSpan.innerText = '$0.00';
                checkoutBtn.disabled = true;
                return;
            }

            cartList.innerHTML = '';
            let grandTotal = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                grandTotal += itemTotal;

                const li = document.createElement('li');
                li.className = 'cart-item';
                li.innerHTML = `
                    <div>
                        <strong>${item.name}</strong> <span style="color:var(--text-light)">x${item.quantity}</span>
                    </div>
                    <div>
                        <span style="margin-right: 10px;">$${itemTotal.toFixed(2)}</span>
                        <button class="cart-item-remove" onclick="removeFromCart(${item.id})">&times;</button>
                    </div>
                `;
                cartList.appendChild(li);
            });

            totalSpan.innerText = `$${grandTotal.toFixed(2)}`;
            checkoutBtn.disabled = false;
        }

        function passCartData(form) {
            if(cart.length === 0) return false;
            document.getElementById('cart-data-input').value = JSON.stringify(cart);
            return true;
        }
    </script>
</body>
</html>