<?php
// wallet.php - My Wallet / Top-Up page
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/constants.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 1: Retrieve Flash Messages
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// --- 1. HANDLE FORM SUBMISSION (Top-Up) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'topup') {
    $amountCoins = floatval($_POST['amount_coins']);
    $paymentMethod = $_POST['payment_method'] ?? 'online';

    if ($amountCoins < 5 || $amountCoins > 3000) {
        $_SESSION['flash_error'] = "Top-up amount must be between 5 and 3000 Coins.";
        header("Location: wallet.php");
        exit;
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Update User Balance
            $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE user_id = ?");
            $stmt->execute([$amountCoins, $user_id]);

            // 2. Log to coin_transactions
            $transaction_desc = "Top-up: " . number_format($amountCoins, 2) . " Coins via " . ucfirst($paymentMethod);
            $stmt = $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description, transaction_date) VALUES (?, ?, 'topup', ?, NOW())");
            $stmt->execute([$user_id, $amountCoins, $transaction_desc]);

            // 3. Log to payments (Financial Record)
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method, status, payment_date) VALUES (?, ?, ?, 'successful', NOW())");
            $costPhp = floatval($_POST['cost_php']);
            $stmt->execute([$user_id, $costPhp, 'online']);

            $pdo->commit();

            // Notification
            require_once 'includes/notifications.php';
            sendNotification($pdo, $user_id, 'Wallet Top-Up', 'You have successfully added ' . number_format($amountCoins, 2) . ' coins.', 'success', 'wallet.php');

            logActivity($pdo, $user_id, 'client', 'wallet_topup', "User topped up " . number_format($amountCoins, 2) . " coins via " . ucfirst($paymentMethod));

            // PRG Pattern: Redirect to prevent re-submission
            $_SESSION['flash_success'] = "Successfully topped up " . number_format($amountCoins, 2) . " Coins!";
            header("Location: wallet.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Transaction failed: " . $e->getMessage();
            header("Location: wallet.php");
            exit;
        }
    }
}

// --- 2. FETCH DATA ---
$stmt = $pdo->prepare("SELECT coins FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 10");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Wallet - EasyPark</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/favicon.png" type="image/png">
    <style>
        .bg-car {
            background-image: url('images/bg-car.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .glass-panel {
            background: rgba(43, 45, 66, 0.85);
            /* Darker glass for contrast */
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .coin-card {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border-radius: 12px;
        }

        .coin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .table-glass {
            color: #fff;
        }

        .table-glass thead th {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }

        .table-glass td {
            border-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="bg-car">
    <?php include 'includes/client_navbar.php'; ?>

    <div class="container py-5">
        <h2 class="text-warning mb-4"><i class="fas fa-wallet"></i> My Wallet</h2>

        <?php if ($success): ?>
            <div class="alert alert-success shadow"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger shadow"><?= $error ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Balance Panel -->
            <div class="col-lg-4 mb-4">
                <div class="glass-panel p-4 text-center h-100 text-white">
                    <h5 class="text-uppercase text-white-50 font-weight-bold mb-3">Current Balance</h5>
                    <div class="display-4 font-weight-bold text-warning mb-3">
                        <i class="fas fa-coins"></i> <?= number_format($balance, 2) ?>
                    </div>
                    <p class="small text-light">Use coins to reserve parking slots instantly.</p>
                </div>
            </div>

            <!-- Top Up Panel -->
            <div class="col-lg-8 mb-4">
                <div class="glass-panel p-4 h-100">
                    <h5 class="text-white font-weight-bold mb-4">Top-Up Packages</h5>
                    <div class="row">
                        <!-- Package 1 -->
                        <div class="col-md-4 mb-3">
                            <div class="card coin-card h-100" onclick="openPaymentModal(10, 10, 'Starter')">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <h5 class="font-weight-bold">Starter</h5>
                                    <h3><i class="fas fa-coins fa-xs"></i> 10</h3>
                                    <p class="mb-0 small">₱10.00</p>
                                </div>
                            </div>
                        </div>
                        <!-- Package 2 -->
                        <div class="col-md-4 mb-3">
                            <div class="card coin-card h-100"
                                style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);"
                                onclick="openPaymentModal(50, 48, 'Value')">
                                <div
                                    class="card-body text-center d-flex flex-column justify-content-center position-relative">
                                    <div class="badge badge-danger position-absolute" style="top:5px; right:5px;">-4%
                                    </div>
                                    <h5 class="font-weight-bold">Value</h5>
                                    <h3><i class="fas fa-coins fa-xs"></i> 50</h3>
                                    <p class="mb-0 small">₱48.00</p>
                                </div>
                            </div>
                        </div>
                        <!-- Package 3 -->
                        <div class="col-md-4 mb-3">
                            <div class="card coin-card h-100"
                                style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);"
                                onclick="openPaymentModal(100, 95, 'Pro')">
                                <div
                                    class="card-body text-center d-flex flex-column justify-content-center position-relative">
                                    <div class="badge badge-danger position-absolute" style="top:5px; right:5px;">-5%
                                    </div>
                                    <h5 class="font-weight-bold">Pro</h5>
                                    <h3><i class="fas fa-coins fa-xs"></i> 100</h3>
                                    <p class="mb-0 small">₱95.00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-4">

                    <h6 class="text-white font-weight-bold mb-3">Custom Amount</h6>
                    <form onsubmit="event.preventDefault(); submitCustom();" class="form-inline">
                        <div class="input-group mb-2 mr-sm-2 w-100">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-dark text-white border-secondary">🪙</div>
                            </div>
                            <input type="number" id="customAmount"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Enter amount (5 - 3000)" min="5" max="3000">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">Buy</button>
                            </div>
                        </div>
                        <small class="text-white-50">Rate: 1 Coin = ₱1.00</small>
                    </form>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="row">
            <div class="col-12">
                <div class="glass-panel p-4">
                    <h5 class="text-white font-weight-bold mb-3">Transaction History</h5>
                    <div class="table-responsive">
                        <table class="table table-glass table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                                        <td><span class="badge badge-light"><?= ucfirst($t['transaction_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($t['description']) ?></td>
                                        <td
                                            class="<?= $t['amount'] >= 0 ? 'text-success' : 'text-danger' ?> font-weight-bold">
                                            <?= $t['amount'] >= 0 ? '+' : '' ?>     <?= number_format($t['amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-white-50">No transactions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Confirm Purchase</h5>
                    <button class="close text-white" type="button" data-dismiss="modal">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You are about to purchase <strong class="text-warning"><span id="modalCoins">0</span>
                            Coins</strong>.</p>
                    <p class="h4 mb-4">Total: ₱<span id="modalCost">0.00</span></p>

                    <form id="paymentForm" method="POST">
                        <input type="hidden" name="action" value="topup">
                        <input type="hidden" name="amount_coins" id="inputCoins">
                        <input type="hidden" name="cost_php" id="inputCost">

                        <div class="form-group">
                            <label>Select Payment Method</label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-light active">
                                    <input type="radio" name="payment_method" value="gcash" checked> GCash
                                </label>
                                <label class="btn btn-outline-light">
                                    <input type="radio" name="payment_method" value="bank"> Bank Transfer
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Account Number (Mock)</label>
                            <input type="text" class="form-control bg-secondary text-white border-0"
                                placeholder="09XX-XXX-XXXX" required>
                        </div>

                        <button type="submit" class="btn btn-success btn-block font-weight-bold mt-4">
                            PAY NOW <i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', function () {
                if (window.scrollY > 50) navbar.classList.add('scrolled');
                else navbar.classList.remove('scrolled');
            });
        }

        function openPaymentModal(coins, cost, title) {
            $('#modalCoins').text(coins);
            $('#modalCost').text(cost.toFixed(2));
            $('#inputCoins').val(coins);
            $('#inputCost').val(cost);
            $('#paymentModal').modal('show');
        }

        function submitCustom() {
            var val = parseFloat($('#customAmount').val());
            if (isNaN(val) || val < 5 || val > 3000) {
                alert('Please enter a valid amount between 5 and 3000.');
                return;
            }
            // 1:1 rate
            openPaymentModal(val, val, 'Custom Top-Up');
        }
    </script>
</body>

</html>