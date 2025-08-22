<?php
require_once '../includes/db.php'; // <-- Add this line if not present

$pageTitle = "Book Request - ISUR-ORA Digital Library";
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $book_title = trim($_POST['book_title'] ?? '');
    $book_author = trim($_POST['book_author'] ?? '');
    $book_copy = trim($_POST['book_copy'] ?? '');
    $book_year = trim($_POST['book_year'] ?? '');

    // Basic validation
    if ($student_name && $id_number && $book_title) {
        // Insert into database
        try {
            $stmt = $pdo->prepare("INSERT INTO book_requests (student_name, id_number, contact, address, book_title, book_author, book_copy, book_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_name, $id_number, $contact, $address, $book_title, $book_author, $book_copy, $book_year]);
            $success = "Your request has been saved successfully!";
            $_POST = []; // <-- Add this line to clear the form fields
        } catch (PDOException $e) {
            $error = "Failed to save your request. Please try again later.";
        }

        // (Optional) Try to send email as well
        /*
        $to = "library@isur.edu.ph";
        $subject = "Book Request from $student_name";
        $message = "Book Request Details:\n\n";
        $message .= "Name: $student_name\n";
        $message .= "ID Number: $id_number\n";
        $message .= "Description: $description\n";
        $message .= "Contact: $contact\n";
        $message .= "Address: $address\n";
        $headers = "From: noreply@isur.edu.ph\r\n";
        if ($contact) {
            $headers .= "Reply-To: $contact";
        }
        mail($to, $subject, $message, $headers);
        */
    } else {
        $error = "Please fill in all required fields.";
    }
}

include "_layout.php";
?>

<div style="padding-top: 80px;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h2 class="mb-4 text-primary"><i class="bi bi-bookmark-plus me-2"></i>Book Request Form</h2>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="post" autocomplete="off">
                            <div class="mb-3">
                                <label for="student_name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold">Address (Optional)</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="id_number" class="form-label fw-semibold">ID Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="id_number" name="id_number" required value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="contact" class="form-label fw-semibold">Contact (Optional)</label>
                                    <input type="text" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Book Info</label>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="book_title" class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="book_title" name="book_title" required value="<?php echo htmlspecialchars($_POST['book_title'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="book_author" class="form-label">Author (Optional)</label>
                                        <input type="text" class="form-control" id="book_author" name="book_author" value="<?php echo htmlspecialchars($_POST['book_author'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="book_copy" class="form-label">Copy (Optional)</label>
                                        <input type="number" class="form-control" id="book_copy" name="book_copy" min="1" value="<?php echo htmlspecialchars($_POST['book_copy'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="book_year" class="form-label">Year (Optional)</label>
                                        <input type="text" class="form-control" id="book_year" name="book_year" value="<?php echo htmlspecialchars($_POST['book_year'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-1"></i>Submit Request
                            </button>
                        </form>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-link"><i class="bi bi-arrow-left"></i> Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>