<?php
session_start();
require 'db.php';
require 'FinancialStatement.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$financialStatement = new FinancialStatement($pdo, $userId);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['deposit'])) {
            $amount = (float)$_POST['amount'];
            $financialStatement->deposit($amount);
            $message = "Deposit of $$amount successful.";
        }

        if (isset($_POST['withdraw'])) {
            $amount = (float)$_POST['amount'];
            $financialStatement->withdraw($amount);
            $message = "Withdraw of $$amount successful.";
        }

        if (isset($_POST['transfer'])) {
            $recipientUsername = $_POST['recipient'];
            $amount = (float)$_POST['amount'];
            $financialStatement->transfer($recipientUsername, $amount);
            $message = "Transferred $$amount to $recipientUsername.";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

$balance = $financialStatement->checkBalance();
$history = $financialStatement->getHistory();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['username']); ?></h1>
        <h2>Balance: <?= number_format($balance, 0); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <h3>Deposit</h3>
            <input type="number" name="amount" placeholder="Amount" required>
            <button type="submit" name="deposit" class="btn btn-success">Deposit</button>
        </form>

        <form method="POST">
            <h3>Withdraw</h3>
            <input type="number" name="amount" placeholder="Amount" required>
            <button type="submit" name="withdraw" class="btn btn-danger">Withdraw</button>
        </form>

        <form method="POST">
            <h3>Transfer</h3>
            <input type="text" name="recipient" placeholder="Recipient Username" required>
            <input type="number" name="amount" placeholder="Amount" required>
            <button type="submit" name="transfer" class="btn btn-primary">Transfer</button>
        </form>

        <h3>Transaction History</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Balance</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['time'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['type'] ?? ''); ?></td>
                        <td><?= number_format($transaction['debit'] ?? 0, 0); ?></td>
                        <td><?= number_format($transaction['credit'] ?? 0, 0); ?></td>
                        <td><?= number_format($transaction['balance'] ?? 0, 0); ?></td>
                        <td><?= htmlspecialchars($transaction['description'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" action="logout.php">
            <button type="submit" class="btn btn-secondary">Logout</button>
        </form>
    </div>

</body>

</html>