<?php
class FinancialStatement
{
    private $pdo;
    private $userId;

    public function __construct($pdo, $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public function deposit($amount)
    {
        if ($amount <= 0) {
            throw new Exception("Invalid deposit amount.");
        }

        $this->pdo->beginTransaction();
        try {
            // Update balance
            $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $this->userId]);

            // Record transaction
            $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, credit, balance) VALUES (?, ?, ?, (SELECT balance FROM users WHERE id = ?))");
            $stmt->execute([$this->userId, 'Deposit', $amount, $this->userId]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function withdraw($amount)
    {
        if ($amount <= 0) {
            throw new Exception("Invalid withdrawal amount.");
        }

        $this->pdo->beginTransaction();
        try {
            // Check balance
            $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$this->userId]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                throw new Exception("Your balance is insufficient.");
            }

            // Update balance
            $stmt = $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $this->userId]);

            // Record transaction
            $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, debit, balance) VALUES (?, ?, ?, (SELECT balance FROM users WHERE id = ?))");
            $stmt->execute([$this->userId, 'Withdraw', $amount, $this->userId]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function transfer($recipientUsername, $amount)
    {
        if ($amount <= 0) {
            throw new Exception("Invalid transfer amount.");
        }

        $this->pdo->beginTransaction();
        try {
            // Check balance
            $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$this->userId]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                throw new Exception("Your balance is insufficient.");
            }

            // Get recipient ID
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$recipientUsername]);
            $recipientId = $stmt->fetchColumn();

            if (!$recipientId) {
                throw new Exception("Recipient not found.");
            }

            // Update balances
            $stmt = $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $this->userId]);

            $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $recipientId]);

            // Record transactions
            $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, debit, balance, description) VALUES (?, ?, ?, (SELECT balance FROM users WHERE id = ?), ?)");
            $stmt->execute([$this->userId, 'Transfer', $amount, $this->userId, "Transfer to $recipientUsername"]);

            $stmt = $this->pdo->prepare("INSERT INTO transactions (user_id, type, credit, balance, description) VALUES (?, ?, ?, (SELECT balance FROM users WHERE id = ?), ?)");
            $stmt->execute([$recipientId, 'Transfer', $amount, $recipientId, "Transfer from " . $this->getUsername()]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function checkBalance()
    {
        $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }

    public function getHistory()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY time ASC");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsername()
    {
        $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
}
