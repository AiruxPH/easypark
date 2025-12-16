<?php
// wallet.php - My Wallet / Top-Up page
session_start();
require_once 'includes/db.php';
require_once 'includes/constants.php'; // ensure SLOT_RATES/etc available if needed, mostly for consistency

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- 1. HANDLE FORM SUBMISSION (Top-Up) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'topup') {
    $amountCoins = floatval($_POST['amount_coins']);
    $costPhp = floatval($_POST['cost_php']);
    $paymentMethod = $_POST['payment_method'] ?? 'online';

    // Server-side validation
    // Custom limit logic: 5 to 3000
    // But packages might be fixed amounts.
    if ($amountCoins < 5 || $amountCoins > 3000) {
        $error = "Top-up amount must be between 5 and 3000 Coins.";
    } else {
        // Proceed with updates
        try {
            $pdo->beginTransaction();

            // 1. Update User Balance
            $stmt = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE user_id = ?");
            $stmt->execute([$amountCoins, $user_id]);

            // 2. Log to coin_transactions
            $stmt = $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, transaction_type, description, transaction_date) VALUES (?, ?, 'topup', ?, NOW())");
            // Description: e.g. "Top-up: 50 Coins (Paid 48.00)"
            $desc = "Top-up: " . number_format($amountCoins, 2) . " Coins";
            $stmt->execute([$user_id, $amountCoins, $desc]);

            // 3. Log to payments (Financial Record)
            // reference_number: generate a fake one or auto-increment if DB handles it? 
            // DB schema: reference_number is an INT NOT NULL. Auto-increment usually? 
            // Let's check schema. Usually it's AUTO_INCREMENT. If not, I need to generate random.
            // Assuming AUTO_INCREMENT for now based on typical usage.
            // If not auto_inc, we'd need to fetch max + 1.
            // Wait, payments definition: `reference_number` int(11) NOT NULL. Does it say AUTO_INCREMENT?
            // I should assume it does or user random.
            // I'll insert without ID if auto-inc, or generate random if needed.
            // Safest: insert other fields.

            $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method, status, payment_date) VALUES (?, ?, ?, 'successful', NOW())");
            // Wait, payments schema has `reservation_id` (nullable). But does it have `user_id`?
            // Let's check my knowledge of `payments` schema.
            // `payments` table JOINs `reservations` to get `user_id`. It does NOT have `user_id` directly in typical relational design unless added.
            // But wait, if reservation_id is NULL, how do we know WHO paid?
            // PROBLEM: If `payments` table doesn't have `user_id` and `reservation_id` is NULL, the payment is orphaned.
            // I must check the schema of `payments` quickly.
            // If `payments` table relies on `reservation_id` to link to user, I cannot record a "General Top-up" in `payments` table effectively unless I add `user_id` to `payments` or ...
            // Ah, `coin_transactions` HAS `user_id`. 
            // Maybe I don't need to insert into `payments` if it's purely internal?
            // BUT: Admin "Payments" section reads from `payments` table.
            // If I want it to show up there, I need a way to link it to the user.
            // Let me pause and check schema in next step. For now, I will write the code assuming I might need to fix this integration.
            // actually, let's look at previous `viewed_file` output in history.
            // `payments` table: `reference_number` (int, PK?), `reservation_id` (int, NULL), `amount`, `method`, ...
            // It does NOT show `user_id` column.
            // This is a schema gap for general payments.
            // SOLUTION for now: I will rely on `coin_transactions` for user history. 
            // If I want it in the `payments` table (financial ledger), I might have to leave `user_id` unknown there, OR (better) add `user_id` to `payments` table.
            // The user didn't ask me to change schema for `payments` specifically, but logically I should.
            // However, `coin_transactions` IS the ledger for coins.
            // Maybe `payments` is strictly for Reservation-related money flow?
            // User said: "payments for these top-ups... fake gcash...".
            // I will Insert into `coin_transactions` (PRIMARY).
            // I will also Insert into `payments` but since I can't link to `reservation`, checking if I can link to `user`?
            // If not, I'll skip `payments` table insert for now OR (better) I'll assume standard practice and maybe I missed a column.
            // I'll check schema in next step.
            // For this write, I'll comment out the `payments` insert to be safe and uncomment after verification.

            // $stmt = $pdo->prepare("INSERT INTO payments ... "); // TODO: confirm schema

            $pdo->commit();
            $success = "Successfully topped up " . number_format($amountCoins, 2) . " Coins!";

            // Re-fetch user balance
            $stmt = $pdo->prepare("SELECT coins FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $newBal = $stmt->fetchColumn();
            $_SESSION['coins'] = $newBal; // if stored in session

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

// --- 2. FETCH DATA ---
// Get current balance
$stmt = $pdo->prepare("SELECT coins FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn() ?: 0;

// Get transaction history
$stmt = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 20");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - EasyPark</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        .coin-card {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            color: white;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .coin-card:hover {
            transform: translateY(-5px);
        }

        .bg-gradient-primary-to-secondary {
            background: linear-gradient(45deg, #4e73df 0%, #224abe 100%);
        }
    </style>
</head>

<body id="page-top" class="bg-gradient-light">
    <div id="wrapper">

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'includes/client_navbar.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">My Wallet</h1>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success shadow-sm"><?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger shadow-sm"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Balance Card -->
                        <div class="col-xl-4 col-md-5 mb-4">
                            <div class="card glass-card h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Current Balance</div>
                                            <div class="h1 mb-0 font-weight-bold text-gray-800">
                                                <i class="fas fa-coins text-warning"></i>
                                                <?= number_format($balance, 2) ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-wallet fa-4x text-gray-300"></i>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-center">
                                        <small class="text-muted">Use coins to pay for parking reservations.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Up Options -->
                        <div class="col-xl-8 col-md-7 mb-4">
                            <div class="card glass-card mb-4">
                                <div
                                    class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-transparent border-bottom-0">
                                    <h6 class="m-0 font-weight-bold text-primary">Top-Up Packages</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Package 1 -->
                                        <div class="col-md-4 mb-3">
                                            <div class="card coin-card border-0 shadow-sm"
                                                onclick="openPaymentModal(10, 10, 'Starter')">
                                                <div class="card-body text-center">
                                                    <h5 class="font-weight-bold">Starter</h5>
                                                    <h2 class="display-4"><i class="fas fa-coins fa-xs"></i> 10</h2>
                                                    <p class="mb-0">Price: ₱10.00</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Package 2 -->
                                        <div class="col-md-4 mb-3">
                                            <div class="card coin-card border-0 shadow-sm"
                                                style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);"
                                                onclick="openPaymentModal(50, 48, 'Value')">
                                                <div class="card-body text-center">
                                                    <div class="badge badge-danger badge-counter position-absolute"
                                                        style="top:10px; right:10px;">SAVE 4%</div>
                                                    <h5 class="font-weight-bold">Value</h5>
                                                    <h2 class="display-4"><i class="fas fa-coins fa-xs"></i> 50</h2>
                                                    <p class="mb-0">Price: ₱48.00</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Package 3 -->
                                        <div class="col-md-4 mb-3">
                                            <div class="card coin-card border-0 shadow-sm"
                                                style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);"
                                                onclick="openPaymentModal(100, 95, 'Pro')">
                                                <div class="card-body text-center">
                                                    <div class="badge badge-danger badge-counter position-absolute"
                                                        style="top:10px; right:10px;">SAVE 5%</div>
                                                    <h5 class="font-weight-bold">Pro</h5>
                                                    <h2 class="display-4"><i class="fas fa-coins fa-xs"></i> 100</h2>
                                                    <p class="mb-0">Price: ₱95.00</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <h6 class="m-0 font-weight-bold text-primary mb-3">Custom Amount</h6>
                                    <form id="customForm" onsubmit="event.preventDefault(); submitCustom();">
                                        <div class="input-group">
                                            <input type="number" id="customAmount" class="form-control"
                                                placeholder="Enter amount (5 - 3000)" min="5" max="3000">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">Buy Coins</button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Rate: 1 Coin = ₱1.00</small>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="card glass-card shadow mb-4">
                        <div class="card-header py-3 bg-transparent border-bottom-0">
                            <h6 class="m-0 font-weight-bold text-primary">Transaction History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered text-dark" width="100%" cellspacing="0">
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
                                                <td><span
                                                        class="badge badge-secondary"><?= ucfirst($t['transaction_type']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($t['description']) ?></td>
                                                <td
                                                    class="<?= $t['amount'] >= 0 ? 'text-success' : 'text-danger' ?> font-weight-bold">
                                                    <?= $t['amount'] >= 0 ? '+' : '' ?>
                                                    <?= number_format($t['amount'], 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($transactions)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No transactions found.</td>
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
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="paymentTitle">Confirm Purchase</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You are about to purchase <strong id="modalCoins">0</strong> Coins.</p>
                    <p class="h4 text-primary font-weight-bold">Total: ₱<span id="modalCost">0.00</span></p>

                    <hr>

                    <form id="paymentForm" method="POST">
                        <input type="hidden" name="action" value="topup">
                        <input type="hidden" name="amount_coins" id="inputCoins">
                        <input type="hidden" name="cost_php" id="inputCost">

                        <div class="form-group">
                            <label>Select Payment Method</label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-light border active">
                                    <input type="radio" name="payment_method" value="gcash" checked> GCash
                                </label>
                                <label class="btn btn-light border">
                                    <input type="radio" name="payment_method" value="bank"> Bank Transfer
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Account / Phone Number (Mock)</label>
                            <input type="text" class="form-control" placeholder="09XX-XXX-XXXX" required>
                        </div>

                        <button type="submit" class="btn btn-success btn-block py-3 font-weight-bold shadow">
                            <i class="fas fa-lock"></i> PAY NOW
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script>
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
            openPaymentModal(val, val, 'Custom Top-Up');
        }
    </script>
</body>

</html>